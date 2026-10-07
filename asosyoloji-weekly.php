<?php
/**
 * Plugin Name: Asosyoloji Haftalık
 * Description: Asosyoloji için etkinlik, haftalık liste ve takvim altyapısı.
 * Version: 0.1.0
 * Author: Asosyoloji
 * License: GPL-3.0-or-later
 * Text Domain: asosyoloji-weekly
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ASOSYOLOJI_WEEKLY_VERSION', '0.1.0' );
define( 'ASOSYOLOJI_WEEKLY_DIR', plugin_dir_path( __FILE__ ) );
define( 'ASOSYOLOJI_WEEKLY_URL', plugin_dir_url( __FILE__ ) );

require_once ASOSYOLOJI_WEEKLY_DIR . 'inc/post-type.php';
require_once ASOSYOLOJI_WEEKLY_DIR . 'inc/meta.php';
require_once ASOSYOLOJI_WEEKLY_DIR . 'inc/query.php';
require_once ASOSYOLOJI_WEEKLY_DIR . 'inc/render.php';
require_once ASOSYOLOJI_WEEKLY_DIR . 'inc/schema.php';
require_once ASOSYOLOJI_WEEKLY_DIR . 'inc/blocks.php';
require_once ASOSYOLOJI_WEEKLY_DIR . 'inc/widget.php';
require_once ASOSYOLOJI_WEEKLY_DIR . 'inc/calendar-export.php';
require_once ASOSYOLOJI_WEEKLY_DIR . 'inc/single-event.php';

function asosyoloji_weekly_assets() {
	wp_enqueue_style(
		'asosyoloji-weekly',
		ASOSYOLOJI_WEEKLY_URL . 'assets/weekly.css',
		array(),
		ASOSYOLOJI_WEEKLY_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'asosyoloji_weekly_assets' );

function asosyoloji_weekly_activate() {
	asosyoloji_weekly_register_post_type();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'asosyoloji_weekly_activate' );

function asosyoloji_weekly_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'asosyoloji_weekly_deactivate' );
