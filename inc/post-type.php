<?php
/**
 * Original Asosyoloji event model.
 *
 * @package AsosyolojiWeekly
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function asosyoloji_weekly_register_post_type() {
	if ( ! post_type_exists( 'em_event' ) ) {
		register_post_type(
			'em_event',
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
				'has_archive'        => 'events',
				'rewrite'            => array( 'slug' => 'event', 'with_front' => false ),
				'supports'           => array( 'title', 'editor', 'excerpt', 'thumbnail', 'author', 'comments' ),
				'publicly_queryable' => true,
				'show_ui'            => true,
			)
		);
	}

	if ( ! taxonomy_exists( 'em_event_type' ) ) {
		register_taxonomy(
			'em_event_type',
			'em_event',
			array(
				'labels' => array(
					'name'          => __( 'Etkinlik Türleri', 'asosyoloji-weekly' ),
					'singular_name' => __( 'Etkinlik Türü', 'asosyoloji-weekly' ),
				),
				'public'       => true,
				'show_in_rest' => true,
				'hierarchical' => true,
				'rewrite'      => array( 'slug' => 'event-type', 'with_front' => false ),
			)
		);
	} else {
		register_taxonomy_for_object_type( 'em_event_type', 'em_event' );
	}

	if ( ! taxonomy_exists( 'em_venue' ) ) {
		register_taxonomy(
			'em_venue',
			'em_event',
			array(
				'labels' => array(
					'name'          => __( 'Mekanlar', 'asosyoloji-weekly' ),
					'singular_name' => __( 'Mekan', 'asosyoloji-weekly' ),
				),
				'public'       => true,
				'show_in_rest' => true,
				'hierarchical' => false,
				'rewrite'      => array( 'slug' => 'venue', 'with_front' => false ),
			)
		);
	} else {
		register_taxonomy_for_object_type( 'em_venue', 'em_event' );
	}
}
add_action( 'init', 'asosyoloji_weekly_register_post_type', 5 );

/**
 * Move records created by early plugin versions to the original site's em_event model.
 */
function asosyoloji_weekly_migrate_plugin_events() {
	if ( get_option( 'asosyoloji_weekly_em_model_migrated' ) ) {
		return;
	}

	$legacy_types = array( 'event', 'asosyoloji_event' );

	foreach ( $legacy_types as $legacy_type ) {
		if ( ! post_type_exists( $legacy_type ) ) {
			continue;
		}

		$ids = get_posts(
			array(
				'post_type'      => $legacy_type,
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);

		foreach ( $ids as $post_id ) {
			$type_names = array();
			foreach ( array( 'event-categories', 'event_category', 'asosyoloji_event_type' ) as $taxonomy ) {
				if ( taxonomy_exists( $taxonomy ) ) {
					$names = wp_get_post_terms( $post_id, $taxonomy, array( 'fields' => 'names' ) );
					if ( ! is_wp_error( $names ) ) {
						$type_names = array_merge( $type_names, $names );
					}
				}
			}

			wp_update_post(
				array(
					'ID'        => $post_id,
					'post_type' => 'em_event',
				)
			);

			if ( $type_names ) {
				wp_set_object_terms( $post_id, array_values( array_unique( $type_names ) ), 'em_event_type', false );
			}
		}
	}

	update_option( 'asosyoloji_weekly_em_model_migrated', ASOSYOLOJI_WEEKLY_VERSION );
	flush_rewrite_rules( false );
}
add_action( 'admin_init', 'asosyoloji_weekly_migrate_plugin_events', 5 );

function asosyoloji_weekly_event_columns( $columns ) {
	$columns['aso_event_date']  = __( 'Etkinlik Tarihi', 'asosyoloji-weekly' );
	$columns['aso_event_time']  = __( 'Saat', 'asosyoloji-weekly' );
	$columns['aso_event_place'] = __( 'Yer', 'asosyoloji-weekly' );
	return $columns;
}
add_filter( 'manage_em_event_posts_columns', 'asosyoloji_weekly_event_columns' );

function asosyoloji_weekly_event_column_content( $column, $post_id ) {
	$event = asosyoloji_weekly_event_data( $post_id );

	if ( 'aso_event_date' === $column ) {
		echo $event['start_date'] ? esc_html( wp_date( 'd.m.Y', strtotime( $event['start_date'] ) ) ) : '<strong style="color:#b32d2e">' . esc_html__( 'Tarih girilmemiş', 'asosyoloji-weekly' ) . '</strong>';
	}

	if ( 'aso_event_time' === $column ) {
		echo $event['start_time'] ? esc_html( $event['start_time'] ) : '—';
	}

	if ( 'aso_event_place' === $column ) {
		echo $event['venue'] ? esc_html( $event['venue'] ) : '—';
	}
}
add_action( 'manage_em_event_posts_custom_column', 'asosyoloji_weekly_event_column_content', 10, 2 );
