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


function asosyoloji_weekly_event_columns( $columns ) {
	$columns['aso_event_date'] = __( 'Etkinlik Tarihi', 'asosyoloji-weekly' );
	$columns['aso_event_time'] = __( 'Saat', 'asosyoloji-weekly' );
	$columns['aso_event_place'] = __( 'Yer', 'asosyoloji-weekly' );
	return $columns;
}
add_filter( 'manage_asosyoloji_event_posts_columns', 'asosyoloji_weekly_event_columns' );

function asosyoloji_weekly_event_column_content( $column, $post_id ) {
	$event = asosyoloji_weekly_event_data( $post_id );

	if ( 'aso_event_date' === $column ) {
		if ( $event['start_date'] ) {
			echo esc_html( wp_date( 'd.m.Y', strtotime( $event['start_date'] ) ) );
			if ( $event['end_date'] && $event['end_date'] !== $event['start_date'] ) {
				echo ' – ' . esc_html( wp_date( 'd.m.Y', strtotime( $event['end_date'] ) ) );
			}
		} else {
			echo '<strong style="color:#b32d2e">' . esc_html__( 'Tarih girilmemiş', 'asosyoloji-weekly' ) . '</strong>';
		}
	}

	if ( 'aso_event_time' === $column ) {
		echo $event['start_time'] ? esc_html( $event['start_time'] ) : '—';
	}

	if ( 'aso_event_place' === $column ) {
		$place = trim( implode( ', ', array_filter( array( $event['venue'], $event['city'] ) ) ) );
		echo $place ? esc_html( $place ) : '—';
	}
}
add_action( 'manage_asosyoloji_event_posts_custom_column', 'asosyoloji_weekly_event_column_content', 10, 2 );
