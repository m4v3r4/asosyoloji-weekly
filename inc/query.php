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
	$start_time = $all_day ? '' : asosyoloji_weekly_time_24h( $start_raw ?: ( $start_ts ? gmdate( 'H:i', $start_ts ) : '' ) );
	$end_time   = $all_day ? '' : asosyoloji_weekly_time_24h( $end_raw ?: ( $end_ts ? gmdate( 'H:i', $end_ts ) : '' ) );

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
	);
}

function asosyoloji_weekly_get_events( $args = array() ) {
	$defaults = array(
		'start'      => asosyoloji_weekly_today(),
		'end'        => '',
		'count'      => -1,
		'event_type' => 0,
		'city'       => '',
	);
	$args = wp_parse_args( $args, $defaults );

	$start_ts = asosyoloji_weekly_timestamp_from_fields( $args['start'], '00:00' );
	$end_ts   = $args['end'] ? asosyoloji_weekly_timestamp_from_fields( $args['end'], '23:59' ) : PHP_INT_MAX;

	$meta_query = array(
		'relation' => 'AND',
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
		array(
			'key'     => 'em_end_date_time',
			'value'   => $start_ts,
			'compare' => '>=',
			'type'    => 'NUMERIC',
		),
	);

	$query_args = array(
		'post_type'      => 'em_event',
		'post_status'    => 'publish',
		'posts_per_page' => intval( $args['count'] ),
		'meta_key'       => 'em_start_date_time',
		'orderby'        => 'meta_value_num',
		'order'          => 'ASC',
		'meta_query'     => $meta_query,
	);

	if ( $args['event_type'] ) {
		$query_args['tax_query'] = array(
			array(
				'taxonomy' => 'em_event_type',
				'field'    => 'term_id',
				'terms'    => absint( $args['event_type'] ),
			),
		);
	}

	return new WP_Query( $query_args );
}

function asosyoloji_weekly_collect_events( $args = array() ) {
	$query = asosyoloji_weekly_get_events( $args );
	$events = array();

	while ( $query->have_posts() ) {
		$query->the_post();
		$events[] = asosyoloji_weekly_event_data( get_the_ID() );
	}
	wp_reset_postdata();

	if ( ! empty( $args['city'] ) ) {
		$city = sanitize_text_field( $args['city'] );
		$events = array_values( array_filter( $events, static function( $event ) use ( $city ) {
			return ! $city || 0 === strcasecmp( (string) $event['city'], $city );
		} ) );
	}

	return $events;
}
