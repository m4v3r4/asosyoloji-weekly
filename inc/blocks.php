<?php
/**
 * Gutenberg blocks.
 *
 * @package AsosyolojiWeekly
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function asosyoloji_weekly_register_blocks() {
	wp_register_script(
		'asosyoloji-weekly-blocks',
		ASOSYOLOJI_WEEKLY_URL . 'assets/blocks.js',
		array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-i18n' ),
		ASOSYOLOJI_WEEKLY_VERSION,
		true
	);

	register_block_type(
		'asosyoloji-weekly/list',
		array(
			'api_version'     => 3,
			'editor_script'   => 'asosyoloji-weekly-blocks',
			'render_callback' => 'asosyoloji_weekly_render_list_block',
			'attributes'      => array(
				'title' => array(
					'type'    => 'string',
					'default' => 'Haftalık',
				),
				'mode' => array(
					'type'    => 'string',
					'default' => 'week',
				),
				'count' => array(
					'type'    => 'number',
					'default' => 10,
				),
				'city' => array(
					'type'    => 'string',
					'default' => '',
				),
				'compact' => array(
					'type'    => 'boolean',
					'default' => false,
				),
			),
		)
	);

	register_block_type(
		'asosyoloji-weekly/calendar',
		array(
			'api_version'     => 3,
			'editor_script'   => 'asosyoloji-weekly-blocks',
			'render_callback' => 'asosyoloji_weekly_render_calendar_block',
			'attributes'      => array(
				'title' => array(
					'type'    => 'string',
					'default' => 'Etkinlik Takvimi',
				),
				'year' => array(
					'type'    => 'number',
					'default' => 0,
				),
				'month' => array(
					'type'    => 'number',
					'default' => 0,
				),
			),
		)
	);
}
add_action( 'init', 'asosyoloji_weekly_register_blocks' );

function asosyoloji_weekly_render_list_block( $attributes ) {
	if ( 'upcoming' === ( $attributes['mode'] ?? '' ) ) {
		return asosyoloji_weekly_render_list(
			array(
				'title'   => sanitize_text_field( $attributes['title'] ?? __( 'Haftalık', 'asosyoloji-weekly' ) ),
				'mode'    => 'upcoming',
				'count'   => absint( $attributes['count'] ?? 10 ),
				'city'    => sanitize_text_field( $attributes['city'] ?? '' ),
				'compact' => ! empty( $attributes['compact'] ),
			)
		);
	}

	return asosyoloji_weekly_render_switcher(
		array(
			'title'   => sanitize_text_field( $attributes['title'] ?? __( 'Etkinlik Takvimi', 'asosyoloji-weekly' ) ),
			'count'   => absint( $attributes['count'] ?? 10 ),
			'city'    => sanitize_text_field( $attributes['city'] ?? '' ),
			'compact' => ! empty( $attributes['compact'] ),
			'active'  => 'month' === ( $attributes['mode'] ?? '' ) ? 'month' : 'week',
		)
	);
}

function asosyoloji_weekly_render_calendar_block( $attributes ) {
	return asosyoloji_weekly_render_calendar(
		array(
			'title' => sanitize_text_field( $attributes['title'] ?? __( 'Etkinlik Takvimi', 'asosyoloji-weekly' ) ),
			'year'  => absint( $attributes['year'] ?? 0 ) ?: current_time( 'Y' ),
			'month' => absint( $attributes['month'] ?? 0 ) ?: current_time( 'm' ),
		)
	);
}
