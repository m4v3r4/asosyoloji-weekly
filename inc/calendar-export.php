<?php
/**
 * Calendar export helpers.
 *
 * @package AsosyolojiWeekly
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function asosyoloji_weekly_event_datetime( $date, $time = '', $utc = false ) {
	if ( ! $date ) {
		return '';
	}

	$value = $date . ' ' . ( $time ? $time : '00:00' );
	$zone  = wp_timezone();
	$dt    = date_create_immutable( $value, $zone );

	if ( ! $dt ) {
		return '';
	}

	if ( $utc ) {
		$dt = $dt->setTimezone( new DateTimeZone( 'UTC' ) );
	}

	return $dt->format( 'Ymd\THis' ) . ( $utc ? 'Z' : '' );
}

function asosyoloji_weekly_ics_url( $post_id ) {
	return add_query_arg(
		array(
			'event_ics' => absint( $post_id ),
		),
		home_url( '/' )
	);
}

function asosyoloji_weekly_google_calendar_url( $post_id ) {
	$event = asosyoloji_weekly_event_data( $post_id );

	$start = asosyoloji_weekly_event_datetime( $event['start_date'], $event['start_time'], true );
	$end   = asosyoloji_weekly_event_datetime(
		$event['end_date'] ? $event['end_date'] : $event['start_date'],
		$event['end_time'] ? $event['end_time'] : $event['start_time'],
		true
	);

	$location = trim( implode( ', ', array_filter( array( $event['venue'], $event['city'] ) ) ) );

	return add_query_arg(
		array(
			'action'   => 'TEMPLATE',
			'text'     => $event['title'],
			'dates'    => $start . '/' . $end,
			'details'  => $event['url'],
			'location' => $location,
		),
		'https://calendar.google.com/calendar/render'
	);
}

function asosyoloji_weekly_ics_endpoint() {
	$post_id = isset( $_GET['event_ics'] ) ? absint( $_GET['event_ics'] ) : 0;

	if ( ! $post_id || 'event' !== get_post_type( $post_id ) || 'publish' !== get_post_status( $post_id ) ) {
		return;
	}

	$event = asosyoloji_weekly_event_data( $post_id );
	$start = asosyoloji_weekly_event_datetime( $event['start_date'], $event['start_time'], true );
	$end   = asosyoloji_weekly_event_datetime(
		$event['end_date'] ? $event['end_date'] : $event['start_date'],
		$event['end_time'] ? $event['end_time'] : $event['start_time'],
		true
	);

	$escape = static function ( $value ) {
		return str_replace(
			array( "\\", ";", ",", "
", "", "
" ),
			array( "\\\\", "\;", "\,", "\n", "\n", "\n" ),
			(string) $value
		);
	};

	$location = trim( implode( ', ', array_filter( array( $event['venue'], $event['city'] ) ) ) );
	$lines    = array(
		'BEGIN:VCALENDAR',
		'VERSION:2.0',
		'PRODID:-//Asosyoloji//Haftalik//TR',
		'CALSCALE:GREGORIAN',
		'BEGIN:VEVENT',
		'UID:asosyoloji-event-' . $post_id . '@' . wp_parse_url( home_url( '/' ), PHP_URL_HOST ),
		'DTSTAMP:' . gmdate( 'Ymd\THis\Z' ),
		'DTSTART:' . $start,
		'DTEND:' . $end,
		'SUMMARY:' . $escape( $event['title'] ),
		'DESCRIPTION:' . $escape( wp_strip_all_tags( $event['excerpt'] ) ),
		'LOCATION:' . $escape( $location ),
		'URL:' . esc_url_raw( $event['url'] ),
		'END:VEVENT',
		'END:VCALENDAR',
	);

	status_header( 200 );
	nocache_headers();
	header( 'Content-Type: text/calendar; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="asosyoloji-etkinlik-' . $post_id . '.ics"' );

	echo implode( "
", $lines ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	exit;
}
add_action( 'template_redirect', 'asosyoloji_weekly_ics_endpoint', 1 );
