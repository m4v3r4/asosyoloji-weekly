<?php
/**
 * Event structured data.
 *
 * @package AsosyolojiWeekly
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function asosyoloji_weekly_event_schema() {
	if ( ! is_singular( 'event' ) ) {
		return;
	}

	$event = asosyoloji_weekly_event_data( get_queried_object_id() );
	if ( empty( $event['start_date'] ) ) {
		return;
	}

	$start = $event['start_date'] . ( $event['start_time'] ? 'T' . $event['start_time'] . ':00' : '' );
	$end   = '';

	if ( $event['end_date'] ) {
		$end = $event['end_date'] . ( $event['end_time'] ? 'T' . $event['end_time'] . ':00' : '' );
	}

	$schema = array(
		'@context'  => 'https://schema.org',
		'@type'     => 'Event',
		'name'      => $event['title'],
		'url'       => $event['url'],
		'startDate' => $start,
		'description' => $event['excerpt'] ? wp_strip_all_tags( $event['excerpt'] ) : wp_trim_words( wp_strip_all_tags( get_post_field( 'post_content', $event['id'] ) ), 35, '…' ),
		'inLanguage'  => 'tr-TR',
		'eventStatus' => 'https://schema.org/EventScheduled',
		'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
	);

	if ( $end ) {
		$schema['endDate'] = $end;
	}

	if ( $event['image'] ) {
		$schema['image'] = array( $event['image'] );
	}

	if ( $event['venue'] || $event['city'] ) {
		$schema['location'] = array(
			'@type'   => 'Place',
			'name'    => $event['venue'] ? $event['venue'] : $event['city'],
			'address' => array(
				'@type'           => 'PostalAddress',
				'addressLocality' => $event['city'],
				'addressCountry'  => 'TR',
			),
		);
	}

	if ( $event['organizer'] ) {
		$schema['organizer'] = array(
			'@type' => 'Organization',
			'name'  => $event['organizer'],
		);
	}

	if ( $event['event_url'] ) {
		$schema['offers'] = array(
			'@type'         => 'Offer',
			'url'           => $event['event_url'],
			'availability'  => 'https://schema.org/InStock',
			'priceCurrency' => 'TRY',
		);

		if ( $event['free'] ) {
			$schema['isAccessibleForFree'] = true;
			$schema['offers']['price']     = '0';
		} elseif ( $event['price'] && is_numeric( str_replace( ',', '.', $event['price'] ) ) ) {
			$schema['offers']['price'] = str_replace( ',', '.', $event['price'] );
		}
	}

	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>';
}
add_action( 'wp_head', 'asosyoloji_weekly_event_schema', 35 );
