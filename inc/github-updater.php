<?php
/**
 * GitHub release updater.
 *
 * @package AsosyolojiWeekly
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ASOSYOLOJI_WEEKLY_GITHUB_REPOSITORY', 'm4v3r4/asosyoloji-weekly' );
define( 'ASOSYOLOJI_WEEKLY_RELEASE_ASSET', 'asosyoloji-weekly.zip' );

function asosyoloji_weekly_latest_release( $force = false ) {
	$cache_key = 'asosyoloji_weekly_github_release';

	if ( ! $force ) {
		$cached = get_site_transient( $cache_key );
		if ( is_array( $cached ) ) {
			return $cached;
		}
	}

	$response = wp_remote_get(
		'https://api.github.com/repos/' . ASOSYOLOJI_WEEKLY_GITHUB_REPOSITORY . '/releases/latest',
		array(
			'headers' => array(
				'Accept'               => 'application/vnd.github+json',
				'X-GitHub-Api-Version' => '2022-11-28',
				'User-Agent'           => 'Asosyoloji-Weekly/' . ASOSYOLOJI_WEEKLY_VERSION,
			),
			'timeout' => 8,
		)
	);

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
		return new WP_Error( 'asosyoloji_weekly_release_http', __( 'GitHub sürüm bilgisi alınamadı.', 'asosyoloji-weekly' ) );
	}

	$data = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( ! is_array( $data ) || empty( $data['tag_name'] ) ) {
		return new WP_Error( 'asosyoloji_weekly_release_invalid', __( 'GitHub sürüm yanıtı geçersiz.', 'asosyoloji-weekly' ) );
	}

	$download_url = '';
	foreach ( $data['assets'] ?? array() as $asset ) {
		if ( ASOSYOLOJI_WEEKLY_RELEASE_ASSET === ( $asset['name'] ?? '' ) ) {
			$download_url = esc_url_raw( $asset['browser_download_url'] ?? '' );
			break;
		}
	}

	$release = array(
		'version'      => ltrim( (string) $data['tag_name'], 'vV' ),
		'html_url'     => esc_url_raw( $data['html_url'] ?? '' ),
		'download_url' => $download_url,
		'body'         => wp_kses_post( $data['body'] ?? '' ),
	);

	set_site_transient( $cache_key, $release, HOUR_IN_SECONDS );

	return $release;
}

function asosyoloji_weekly_plugin_update( $transient ) {
	if ( ! is_object( $transient ) ) {
		$transient = new stdClass();
	}

	$release = asosyoloji_weekly_latest_release();

	if (
		is_wp_error( $release ) ||
		empty( $release['version'] ) ||
		empty( $release['download_url'] ) ||
		version_compare( $release['version'], ASOSYOLOJI_WEEKLY_VERSION, '<=' )
	) {
		return $transient;
	}

	if ( ! isset( $transient->response ) || ! is_array( $transient->response ) ) {
		$transient->response = array();
	}

	$plugin_file = plugin_basename( ASOSYOLOJI_WEEKLY_DIR . 'asosyoloji-weekly.php' );

	$transient->response[ $plugin_file ] = (object) array(
		'id'            => 'github.com/' . ASOSYOLOJI_WEEKLY_GITHUB_REPOSITORY,
		'slug'          => 'asosyoloji-weekly',
		'plugin'        => $plugin_file,
		'new_version'   => $release['version'],
		'url'           => $release['html_url'],
		'package'       => $release['download_url'],
		'requires'      => '6.0',
		'requires_php'  => '8.0',
	);

	return $transient;
}
add_filter( 'pre_set_site_transient_update_plugins', 'asosyoloji_weekly_plugin_update' );

function asosyoloji_weekly_plugin_information( $result, $action, $args ) {
	if ( 'plugin_information' !== $action || empty( $args->slug ) || 'asosyoloji-weekly' !== $args->slug ) {
		return $result;
	}

	$release = asosyoloji_weekly_latest_release();
	if ( is_wp_error( $release ) ) {
		return $result;
	}

	return (object) array(
		'name'          => 'Asosyoloji Haftalık',
		'slug'          => 'asosyoloji-weekly',
		'version'       => $release['version'],
		'author'        => 'Asosyoloji',
		'homepage'      => $release['html_url'],
		'requires'      => '6.0',
		'requires_php'  => '8.0',
		'download_link' => $release['download_url'],
		'sections'      => array(
			'description' => __( 'Asosyoloji için etkinlik, haftalık liste ve takvim altyapısı.', 'asosyoloji-weekly' ),
			'changelog'   => wpautop( wp_kses_post( $release['body'] ) ),
		),
	);
}
add_filter( 'plugins_api', 'asosyoloji_weekly_plugin_information', 20, 3 );

function asosyoloji_weekly_clear_release_cache() {
	delete_site_transient( 'asosyoloji_weekly_github_release' );
}
add_action( 'wp_update_plugins', 'asosyoloji_weekly_clear_release_cache' );
