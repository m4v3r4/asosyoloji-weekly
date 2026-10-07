<?php
/**
 * Event queries.
 *
 * @package AsosyolojiWeekly
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function asosyoloji_weekly_today() {
	return current_time( 'Y-m-d' );
}

function asosyoloji_weekly_week_range( $timestamp = null ) {
	$timestamp = $timestamp ? $timestamp : current_time( 'timestamp' );
	$start     = strtotime( 'today', $timestamp );
	$end       = strtotime( '+6 days', $start );

	return array(
		'start' => wp_date( 'Y-m-d', $start ),
		'end'   => wp_date( 'Y-m-d', $end ),
	);
}

function asosyoloji_weekly_event_post_types() {
	$types = array( 'asosyoloji_event' );

	if ( post_type_exists( 'event' ) ) {
		$types[] = 'event';
	}

	return apply_filters( 'asosyoloji_weekly_event_post_types', array_values( array_unique( $types ) ) );
}

function asosyoloji_weekly_first_meta( $post_id, $keys ) {
	foreach ( $keys as $key ) {
		$value = get_post_meta( $post_id, $key, true );
		if ( '' !== $value && null !== $value ) {
			return $value;
		}
	}

	return '';
}

function asosyoloji_weekly_normalize_date( $value ) {
	if ( ! $value ) {
		return '';
	}

	if ( is_numeric( $value ) && strlen( (string) $value ) >= 9 ) {
		return wp_date( 'Y-m-d', (int) $value );
	}

	$timestamp = strtotime( (string) $value );
	return $timestamp ? wp_date( 'Y-m-d', $timestamp ) : sanitize_text_field( (string) $value );
}

function asosyoloji_weekly_normalize_time( $value ) {
	if ( ! $value ) {
		return '';
	}

	$timestamp = strtotime( (string) $value );
	return $timestamp ? wp_date( 'H:i', $timestamp ) : sanitize_text_field( (string) $value );
}

function asosyoloji_weekly_get_events( $args = array() ) {
	$defaults = array(
		'start'        => asosyoloji_weekly_today(),
		'end'          => '',
		'count'        => -1,
		'event_type'   => 0,
		'city'         => '',
		'include_past' => false,
	);

	$args = wp_parse_args( $args, $defaults );

	$meta_query = array(
		'relation' => 'AND',
		array(
			'key'     => '_aso_event_start_date',
			'compare' => 'EXISTS',
		),
	);

	if ( $args['start'] || $args['end'] ) {
		$range_start = $args['start'] ? sanitize_text_field( $args['start'] ) : '0000-01-01';
		$range_end   = $args['end'] ? sanitize_text_field( $args['end'] ) : '9999-12-31';

		$meta_query[] = array(
			'relation' => 'OR',
			array(
				'relation' => 'AND',
				array(
					'key'     => '_aso_event_start_date',
					'value'   => $range_start,
					'compare' => '>=',
					'type'    => 'DATE',
				),
				array(
					'key'     => '_aso_event_start_date',
					'value'   => $range_end,
					'compare' => '<=',
					'type'    => 'DATE',
				),
			),
			array(
				'relation' => 'AND',
				array(
					'key'     => '_aso_event_start_date',
					'value'   => $range_start,
					'compare' => '<=',
					'type'    => 'DATE',
				),
				array(
					'key'     => '_aso_event_end_date',
					'value'   => $range_start,
					'compare' => '>=',
					'type'    => 'DATE',
				),
			),
		);
	}

	if ( $args['city'] ) {
		$meta_query[] = array(
			'key'     => '_aso_event_city',
			'value'   => sanitize_text_field( $args['city'] ),
			'compare' => '=',
		);
	}

	$query_args = array(
		'post_type'      => 'asosyoloji_event',
		'post_status'    => 'publish',
		'posts_per_page' => intval( $args['count'] ),
		'meta_key'       => '_aso_event_start_date',
		'orderby'        => array(
			'meta_value' => 'ASC',
			'title'      => 'ASC',
		),
		'meta_type'      => 'DATE',
		'meta_query'     => $meta_query,
	);

	if ( $args['event_type'] ) {
		$query_args['tax_query'] = array(
			array(
				'taxonomy' => 'asosyoloji_event_type',
				'field'    => 'term_id',
				'terms'    => absint( $args['event_type'] ),
			),
		);
	}

	return new WP_Query( $query_args );
}

function asosyoloji_weekly_get_legacy_events( $args = array() ) {
	if ( ! post_type_exists( 'event' ) ) {
		return array();
	}

	$defaults = array(
		'start' => asosyoloji_weekly_today(),
		'end'   => '',
		'count' => -1,
	);

	$args = wp_parse_args( $args, $defaults );

	$posts = get_posts(
		array(
			'post_type'      => 'event',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	);

	$events = array();

	foreach ( $posts as $post ) {
		$event = asosyoloji_weekly_event_data( $post->ID );

		if ( ! $event['start_date'] ) {
			continue;
		}

		$event_end = $event['end_date'] ? $event['end_date'] : $event['start_date'];

		if ( $args['start'] && $event_end < $args['start'] ) {
			continue;
		}

		if ( $args['end'] && $event['start_date'] > $args['end'] ) {
			continue;
		}

		$events[] = $event;
	}

	usort(
		$events,
		static function ( $a, $b ) {
			return strcmp( $a['start_date'] . ' ' . $a['start_time'], $b['start_date'] . ' ' . $b['start_time'] );
		}
	);

	if ( $args['count'] > 0 ) {
		$events = array_slice( $events, 0, (int) $args['count'] );
	}

	return $events;
}

function asosyoloji_weekly_event_data( $post_id ) {
	$post_type = get_post_type( $post_id );

	$start_date = asosyoloji_weekly_first_meta(
		$post_id,
		array(
			'_aso_event_start_date',
			'event_start_date',
			'_event_start_date',
			'start_date',
			'_start_date',
			'event_date',
			'_event_date',
		)
	);
	$end_date = asosyoloji_weekly_first_meta(
		$post_id,
		array(
			'_aso_event_end_date',
			'event_end_date',
			'_event_end_date',
			'end_date',
			'_end_date',
		)
	);
	$start_time = asosyoloji_weekly_first_meta(
		$post_id,
		array(
			'_aso_event_start_time',
			'event_start_time',
			'_event_start_time',
			'start_time',
			'_start_time',
		)
	);
	$end_time = asosyoloji_weekly_first_meta(
		$post_id,
		array(
			'_aso_event_end_time',
			'event_end_time',
			'_event_end_time',
			'end_time',
			'_end_time',
		)
	);

	return array(
		'id'         => absint( $post_id ),
		'post_type'  => $post_type,
		'title'      => get_the_title( $post_id ),
		'url'        => get_permalink( $post_id ),
		'excerpt'    => get_the_excerpt( $post_id ),
		'image'      => get_the_post_thumbnail_url( $post_id, 'large' ),
		'start_date' => asosyoloji_weekly_normalize_date( $start_date ),
		'start_time' => asosyoloji_weekly_normalize_time( $start_time ),
		'end_date'   => asosyoloji_weekly_normalize_date( $end_date ),
		'end_time'   => asosyoloji_weekly_normalize_time( $end_time ),
		'venue'      => asosyoloji_weekly_first_meta( $post_id, array( '_aso_event_venue', 'event_venue', '_event_venue', 'venue', '_venue', 'location', '_location' ) ),
		'city'       => asosyoloji_weekly_first_meta( $post_id, array( '_aso_event_city', 'event_city', '_event_city', 'city', '_city' ) ),
		'organizer'  => asosyoloji_weekly_first_meta( $post_id, array( '_aso_event_organizer', 'event_organizer', '_event_organizer', 'organizer', '_organizer' ) ),
		'event_url'  => asosyoloji_weekly_first_meta( $post_id, array( '_aso_event_url', 'event_url', '_event_url', 'url' ) ),
		'price'      => asosyoloji_weekly_first_meta( $post_id, array( '_aso_event_price', 'event_price', '_event_price', 'price' ) ),
		'free'       => (bool) asosyoloji_weekly_first_meta( $post_id, array( '_aso_event_free', 'event_free', '_event_free' ) ),
		'types'      => 'asosyoloji_event' === $post_type ? wp_get_post_terms( $post_id, 'asosyoloji_event_type', array( 'fields' => 'names' ) ) : array(),
	);
}

function asosyoloji_weekly_collect_events( $args = array() ) {
	$query  = asosyoloji_weekly_get_events( $args );
	$events = array();

	while ( $query->have_posts() ) {
		$query->the_post();
		$events[] = asosyoloji_weekly_event_data( get_the_ID() );
	}
	wp_reset_postdata();

	$events = array_merge( $events, asosyoloji_weekly_get_legacy_events( $args ) );

	$unique = array();
	foreach ( $events as $event ) {
		$unique[ $event['post_type'] . ':' . $event['id'] ] = $event;
	}
	$events = array_values( $unique );

	usort(
		$events,
		static function ( $a, $b ) {
			return strcmp( $a['start_date'] . ' ' . $a['start_time'], $b['start_date'] . ' ' . $b['start_time'] );
		}
	);

	if ( ! empty( $args['count'] ) && (int) $args['count'] > 0 ) {
		$events = array_slice( $events, 0, (int) $args['count'] );
	}

	return $events;
}
