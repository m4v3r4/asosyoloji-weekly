<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function asosyoloji_weekly_today() {
	return current_time( 'Y-m-d' );
}

function asosyoloji_weekly_week_range( $timestamp = null ) {
	$timestamp = $timestamp ?: current_time( 'timestamp' );
	$start = strtotime( 'today', $timestamp );
	return array(
		'start' => wp_date( 'Y-m-d', $start ),
		'end'   => wp_date( 'Y-m-d', strtotime( '+6 days', $start ) ),
	);
}

function asosyoloji_weekly_event_data( $post_id ) {
	$start_ts  = absint( get_post_meta( $post_id, 'em_start_date_time', true ) );
	$end_ts    = absint( get_post_meta( $post_id, 'em_end_date_time', true ) );
	$start_raw = get_post_meta( $post_id, 'em_start_time', true );
	$end_raw   = get_post_meta( $post_id, 'em_end_time', true );
	$all_day   = (bool) get_post_meta( $post_id, 'em_all_day', true );

	$start_date = $start_ts ? gmdate( 'Y-m-d', $start_ts ) : '';
	$end_date   = $end_ts ? gmdate( 'Y-m-d', $end_ts ) : $start_date;
	$start_time = $all_day ? '' : asosyoloji_weekly_time_24h( $start_raw );
	$end_time   = $all_day ? '' : asosyoloji_weekly_time_24h( $end_raw );

	$venues = wp_get_post_terms( $post_id, 'em_venue', array( 'fields' => 'names' ) );
	$types  = wp_get_post_terms( $post_id, 'em_event_type', array( 'fields' => 'names' ) );
	$raw_excerpt = get_post_field( 'post_excerpt', $post_id );
	if ( ! $raw_excerpt ) {
		$raw_excerpt = wp_trim_words(
			wp_strip_all_tags( strip_shortcodes( (string) get_post_field( 'post_content', $post_id ) ) ),
			40
		);
	}

	$venue = ( ! is_wp_error( $venues ) && $venues ) ? $venues[0] : get_post_meta( $post_id, 'em_venue', true );
	$event_url = esc_url_raw( get_post_meta( $post_id, 'em_event_url', true ) );

	return array(
		'id'         => absint( $post_id ),
		'post_type'  => 'em_event',
		'title'      => get_the_title( $post_id ),
		'url'        => get_permalink( $post_id ),
		'excerpt'    => $raw_excerpt,
		'image'      => get_the_post_thumbnail_url( $post_id, 'large' ),
		'start_date' => $start_date,
		'start_time' => $start_time,
		'end_date'   => $end_date,
		'end_time'   => $end_time,
		'venue'      => sanitize_text_field( $venue ),
		'city'       => sanitize_text_field( get_post_meta( $post_id, 'em_city', true ) ),
		'organizer'  => sanitize_text_field( get_post_meta( $post_id, 'em_organizer', true ) ),
		'event_url'  => $event_url,
		'price'      => get_post_meta( $post_id, 'em_fixed_event_price', true ),
		'free'       => (bool) get_post_meta( $post_id, 'em_free', true ),
		'all_day'    => $all_day,
		'types'      => ( ! is_wp_error( $types ) ) ? $types : array(),
		'recurrence' => sanitize_key( get_post_meta( $post_id, 'em_recurrence', true ) ) ?: 'none',
		'recurrence_interval' => max( 1, absint( get_post_meta( $post_id, 'em_recurrence_interval', true ) ) ),
		'recurrence_until' => sanitize_text_field( get_post_meta( $post_id, 'em_recurrence_until', true ) ),
	);
}

/**
 * Expand an event into occurrences that overlap a requested date range.
 *
 * @param array  $event Event data.
 * @param string $range_start Range start date.
 * @param string $range_end Range end date.
 * @return array
 */
function asosyoloji_weekly_expand_occurrences( $event, $range_start, $range_end ) {
	$frequency = in_array( $event['recurrence'], array( 'daily', 'weekly', 'monthly' ), true ) ? $event['recurrence'] : 'none';
	$base_start = strtotime( $event['start_date'] );
	$base_end   = strtotime( $event['end_date'] ?: $event['start_date'] );
	$window_start = strtotime( $range_start );
	$window_end   = strtotime( $range_end );

	if ( ! $base_start || ! $base_end || ! $window_start || ! $window_end ) {
		return array();
	}

	$duration_days = max( 0, (int) round( ( $base_end - $base_start ) / DAY_IN_SECONDS ) );
	$until = 'none' === $frequency ? $base_start : strtotime( $event['recurrence_until'] ?: $event['start_date'] );
	if ( ! $until ) {
		$until = $base_start;
	}

	$interval = max( 1, absint( $event['recurrence_interval'] ) );
	$step = array(
		'daily'   => '+' . $interval . ' day',
		'weekly'  => '+' . $interval . ' week',
		'monthly' => '+' . $interval . ' month',
	);
	$cursor = $base_start;
	$occurrences = array();
	$guard = 0;

	while ( $cursor <= $until && $cursor <= $window_end && $guard < 1200 ) {
		$occurrence_end = strtotime( '+' . $duration_days . ' day', $cursor );
		if ( $cursor <= $window_end && $occurrence_end >= $window_start ) {
			$occurrence = $event;
			$occurrence['start_date'] = gmdate( 'Y-m-d', $cursor );
			$occurrence['end_date'] = gmdate( 'Y-m-d', $occurrence_end );
			$occurrence['occurrence_key'] = $event['id'] . '-' . $occurrence['start_date'];
			$occurrences[] = $occurrence;
		}

		if ( 'none' === $frequency ) {
			break;
		}
		$next = strtotime( $step[ $frequency ], $cursor );
		if ( ! $next || $next <= $cursor ) {
			break;
		}
		$cursor = $next;
		++$guard;
	}

	return $occurrences;
}

function asosyoloji_weekly_get_events( $args = array() ) {
	$defaults = array(
		'start'      => asosyoloji_weekly_today(),
		'end'        => '',
		'count'      => -1,
		'event_type' => 0,
		'city'       => '',
		'venue'      => 0,
		'order'      => 'ASC',
	);
	$args = wp_parse_args( $args, $defaults );

	$end_ts   = $args['end'] ? asosyoloji_weekly_timestamp_from_fields( $args['end'], '23:59' ) : PHP_INT_MAX;

	$meta_query = array(
		array(
			'key'     => 'em_start_date_time',
			'compare' => 'EXISTS',
		),
		array(
			'key'     => 'em_start_date_time',
			'value'   => $end_ts,
			'compare' => '<=',
			'type'    => 'NUMERIC',
		),
	);

	$query_args = array(
		'post_type'      => 'em_event',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'meta_key'       => 'em_start_date_time',
		'orderby'        => 'meta_value_num',
		'order'          => 'ASC',
		'meta_query'     => $meta_query,
	);

	$tax_query = array();
	if ( $args['event_type'] ) {
		$tax_query[] = array(
				'taxonomy' => 'em_event_type',
				'field'    => 'term_id',
				'terms'    => absint( $args['event_type'] ),
		);
	}
	if ( $args['venue'] ) {
		$tax_query[] = array(
			'taxonomy' => 'em_venue',
			'field'    => 'term_id',
			'terms'    => absint( $args['venue'] ),
		);
	}
	if ( $tax_query ) {
		$query_args['tax_query'] = $tax_query;
	}

	return new WP_Query( $query_args );
}

function asosyoloji_weekly_collect_events( $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'start'      => asosyoloji_weekly_today(),
			'end'        => '',
			'count'      => -1,
			'event_type' => 0,
			'city'       => '',
			'venue'      => 0,
			'order'      => 'ASC',
		)
	);
	$query = asosyoloji_weekly_get_events( $args );
	$events = array();

	while ( $query->have_posts() ) {
		$query->the_post();
		$event = asosyoloji_weekly_event_data( get_the_ID() );
		$events = array_merge( $events, asosyoloji_weekly_expand_occurrences( $event, $args['start'], $args['end'] ?: '2099-12-31' ) );
	}
	wp_reset_postdata();

	if ( ! empty( $args['city'] ) ) {
		$city = sanitize_text_field( $args['city'] );
		$events = array_values( array_filter( $events, static function( $event ) use ( $city ) {
			return ! $city || 0 === strcasecmp( (string) $event['city'], $city );
		} ) );
	}

	usort(
		$events,
		static function( $left, $right ) use ( $args ) {
			$left_key  = $left['start_date'] . ' ' . ( $left['start_time'] ?: '00:00' );
			$right_key = $right['start_date'] . ' ' . ( $right['start_time'] ?: '00:00' );
			$comparison = strcmp( $left_key, $right_key );
			return 'DESC' === strtoupper( $args['order'] ) ? -$comparison : $comparison;
		}
	);

	$count = intval( $args['count'] );
	if ( $count > 0 ) {
		$events = array_slice( $events, 0, $count );
	}

	return $events;
}
