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
	$monday    = strtotime( 'monday this week', $timestamp );
	$sunday    = strtotime( 'sunday this week', $timestamp );

	return array(
		'start' => wp_date( 'Y-m-d', $monday ),
		'end'   => wp_date( 'Y-m-d', $sunday ),
	);
}

function asosyoloji_weekly_get_events( $args = array() ) {
	$defaults = array(
		'start'          => asosyoloji_weekly_today(),
		'end'            => '',
		'count'          => -1,
		'event_type'     => 0,
		'city'           => '',
		'include_past'   => false,
	);

	$args = wp_parse_args( $args, $defaults );

	$meta_query = array(
		'relation' => 'AND',
		array(
			'key'     => '_aso_event_start_date',
			'compare' => 'EXISTS',
		),
	);

	if ( ! $args['include_past'] && $args['start'] ) {
		$meta_query[] = array(
			'key'     => '_aso_event_start_date',
			'value'   => sanitize_text_field( $args['start'] ),
			'compare' => '>=',
			'type'    => 'DATE',
		);
	}

	if ( $args['end'] ) {
		$meta_query[] = array(
			'key'     => '_aso_event_start_date',
			'value'   => sanitize_text_field( $args['end'] ),
			'compare' => '<=',
			'type'    => 'DATE',
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

function asosyoloji_weekly_event_data( $post_id ) {
	return array(
		'id'         => absint( $post_id ),
		'title'      => get_the_title( $post_id ),
		'url'        => get_permalink( $post_id ),
		'excerpt'    => get_the_excerpt( $post_id ),
		'image'      => get_the_post_thumbnail_url( $post_id, 'large' ),
		'start_date' => get_post_meta( $post_id, '_aso_event_start_date', true ),
		'start_time' => get_post_meta( $post_id, '_aso_event_start_time', true ),
		'end_date'   => get_post_meta( $post_id, '_aso_event_end_date', true ),
		'end_time'   => get_post_meta( $post_id, '_aso_event_end_time', true ),
		'venue'      => get_post_meta( $post_id, '_aso_event_venue', true ),
		'city'       => get_post_meta( $post_id, '_aso_event_city', true ),
		'organizer'  => get_post_meta( $post_id, '_aso_event_organizer', true ),
		'event_url'  => get_post_meta( $post_id, '_aso_event_url', true ),
		'price'      => get_post_meta( $post_id, '_aso_event_price', true ),
		'free'       => '1' === get_post_meta( $post_id, '_aso_event_free', true ),
		'types'      => wp_get_post_terms( $post_id, 'asosyoloji_event_type', array( 'fields' => 'names' ) ),
	);
}
