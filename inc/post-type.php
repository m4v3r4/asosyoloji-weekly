<?php
/**
 * Event post type.
 *
 * @package AsosyolojiWeekly
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function asosyoloji_weekly_register_post_type() {
	register_post_type(
		'asosyoloji_event',
		array(
			'labels' => array(
				'name'          => __( 'Etkinlikler', 'asosyoloji-weekly' ),
				'singular_name' => __( 'Etkinlik', 'asosyoloji-weekly' ),
				'add_new_item'  => __( 'Yeni Etkinlik Ekle', 'asosyoloji-weekly' ),
				'edit_item'     => __( 'Etkinliği Düzenle', 'asosyoloji-weekly' ),
				'view_item'     => __( 'Etkinliği Gör', 'asosyoloji-weekly' ),
				'search_items'  => __( 'Etkinliklerde Ara', 'asosyoloji-weekly' ),
			),
			'public'             => true,
			'show_in_rest'       => true,
			'menu_icon'          => 'dashicons-calendar-alt',
			'menu_position'      => 21,
			'has_archive'        => true,
			'rewrite'            => array( 'slug' => 'etkinlikler' ),
			'supports'           => array( 'title', 'editor', 'excerpt', 'thumbnail' ),
			'publicly_queryable' => true,
			'show_ui'            => true,
		)
	);

	register_taxonomy(
		'asosyoloji_event_type',
		'asosyoloji_event',
		array(
			'labels' => array(
				'name'          => __( 'Etkinlik Türleri', 'asosyoloji-weekly' ),
				'singular_name' => __( 'Etkinlik Türü', 'asosyoloji-weekly' ),
			),
			'public'       => true,
			'show_in_rest' => true,
			'hierarchical' => true,
			'rewrite'      => array( 'slug' => 'etkinlik-turu' ),
		)
	);
}
add_action( 'init', 'asosyoloji_weekly_register_post_type' );
