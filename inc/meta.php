<?php
/**
 * Event and location metadata.
 *
 * @package AsosyolojiWeekly
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function asosyoloji_weekly_register_event_meta() {
	$event_meta = array(
		'_event_start_date' => 'string',
		'_event_start_time' => 'string',
		'_event_end_date'   => 'string',
		'_event_end_time'   => 'string',
		'_location_id'      => 'integer',
		'_event_organizer'  => 'string',
		'_event_url'        => 'string',
		'_event_price'      => 'string',
		'_event_free'       => 'boolean',
	);

	foreach ( $event_meta as $meta_key => $type ) {
		register_post_meta(
			'event',
			$meta_key,
			array(
				'type'              => $type,
				'single'            => true,
				'show_in_rest'      => true,
				'auth_callback'     => static function () {
					return current_user_can( 'edit_posts' );
				},
				'sanitize_callback' => 'boolean' === $type ? 'rest_sanitize_boolean' : ( 'integer' === $type ? 'absint' : 'sanitize_text_field' ),
			)
		);
	}

	$location_meta = array(
		'_location_address',
		'_location_town',
		'_location_state',
		'_location_postcode',
		'_location_region',
		'_location_country',
	);

	foreach ( $location_meta as $meta_key ) {
		register_post_meta(
			'location',
			$meta_key,
			array(
				'type'          => 'string',
				'single'        => true,
				'show_in_rest'  => true,
				'auth_callback' => static function () {
					return current_user_can( 'edit_posts' );
				},
				'sanitize_callback' => 'sanitize_text_field',
			)
		);
	}
}
add_action( 'init', 'asosyoloji_weekly_register_event_meta', 20 );

function asosyoloji_weekly_migrate_meta_keys() {
	if ( get_option( 'asosyoloji_weekly_meta_model_migrated' ) ) {
		return;
	}

	$events = get_posts(
		array(
			'post_type'      => 'event',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		)
	);

	$map = array(
		'_aso_event_start_date' => '_event_start_date',
		'_aso_event_start_time' => '_event_start_time',
		'_aso_event_end_date'   => '_event_end_date',
		'_aso_event_end_time'   => '_event_end_time',
		'_aso_event_organizer'  => '_event_organizer',
		'_aso_event_url'        => '_event_url',
		'_aso_event_price'      => '_event_price',
		'_aso_event_free'       => '_event_free',
	);

	foreach ( $events as $post_id ) {
		foreach ( $map as $old_key => $new_key ) {
			if ( '' === (string) get_post_meta( $post_id, $new_key, true ) ) {
				$old_value = get_post_meta( $post_id, $old_key, true );
				if ( '' !== (string) $old_value ) {
					update_post_meta( $post_id, $new_key, $old_value );
				}
			}
		}

		$legacy_venue = get_post_meta( $post_id, '_aso_event_venue', true );
		$legacy_city  = get_post_meta( $post_id, '_aso_event_city', true );

		if ( ! get_post_meta( $post_id, '_location_id', true ) && ( $legacy_venue || $legacy_city ) ) {
			$location_id = wp_insert_post(
				array(
					'post_type'   => 'location',
					'post_status' => 'publish',
					'post_title'  => $legacy_venue ? $legacy_venue : $legacy_city,
				)
			);

			if ( ! is_wp_error( $location_id ) && $location_id ) {
				update_post_meta( $location_id, '_location_town', $legacy_city );
				update_post_meta( $post_id, '_location_id', $location_id );
			}
		}

		$start_date = get_post_meta( $post_id, '_event_start_date', true );
		$start_time = get_post_meta( $post_id, '_event_start_time', true );
		$end_date   = get_post_meta( $post_id, '_event_end_date', true );
		$end_time   = get_post_meta( $post_id, '_event_end_time', true );

		if ( $start_date ) {
			update_post_meta( $post_id, '_start_ts', strtotime( $start_date . ' ' . ( $start_time ? $start_time : '00:00' ) ) );
		}
		if ( $end_date ) {
			update_post_meta( $post_id, '_end_ts', strtotime( $end_date . ' ' . ( $end_time ? $end_time : '23:59' ) ) );
		}
	}

	update_option( 'asosyoloji_weekly_meta_model_migrated', ASOSYOLOJI_WEEKLY_VERSION );
}
add_action( 'admin_init', 'asosyoloji_weekly_migrate_meta_keys', 20 );

function asosyoloji_weekly_meta_box() {
	add_meta_box(
		'asosyoloji-event-details',
		__( 'Etkinlik Bilgileri', 'asosyoloji-weekly' ),
		'asosyoloji_weekly_meta_box_render',
		'event',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'asosyoloji_weekly_meta_box' );

function asosyoloji_weekly_meta_box_render( $post ) {
	wp_nonce_field( 'asosyoloji_weekly_save_event', 'asosyoloji_weekly_nonce' );

	$fields = array(
		'start_date' => get_post_meta( $post->ID, '_event_start_date', true ),
		'start_time' => get_post_meta( $post->ID, '_event_start_time', true ),
		'end_date'   => get_post_meta( $post->ID, '_event_end_date', true ),
		'end_time'   => get_post_meta( $post->ID, '_event_end_time', true ),
		'location'   => absint( get_post_meta( $post->ID, '_location_id', true ) ),
		'organizer'  => get_post_meta( $post->ID, '_event_organizer', true ),
		'event_url'  => get_post_meta( $post->ID, '_event_url', true ),
		'price'      => get_post_meta( $post->ID, '_event_price', true ),
		'free'       => get_post_meta( $post->ID, '_event_free', true ),
	);

	$locations = get_posts(
		array(
			'post_type'      => 'location',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		)
	);
	?>
	<div class="aso-weekly-admin-grid">
		<p><label><strong><?php esc_html_e( 'Başlangıç tarihi', 'asosyoloji-weekly' ); ?></strong></label><br><input type="date" name="aso_event_start_date" value="<?php echo esc_attr( $fields['start_date'] ); ?>" required></p>
		<p><label><strong><?php esc_html_e( 'Başlangıç saati', 'asosyoloji-weekly' ); ?></strong></label><br><input type="time" name="aso_event_start_time" value="<?php echo esc_attr( $fields['start_time'] ); ?>"></p>
		<p><label><strong><?php esc_html_e( 'Bitiş tarihi', 'asosyoloji-weekly' ); ?></strong></label><br><input type="date" name="aso_event_end_date" value="<?php echo esc_attr( $fields['end_date'] ); ?>"></p>
		<p><label><strong><?php esc_html_e( 'Bitiş saati', 'asosyoloji-weekly' ); ?></strong></label><br><input type="time" name="aso_event_end_time" value="<?php echo esc_attr( $fields['end_time'] ); ?>"></p>
		<p>
			<label><strong><?php esc_html_e( 'Mekan', 'asosyoloji-weekly' ); ?></strong></label><br>
			<select class="widefat" name="aso_event_location_id">
				<option value="0"><?php esc_html_e( 'Mekan seçin', 'asosyoloji-weekly' ); ?></option>
				<?php foreach ( $locations as $location ) : ?>
					<option value="<?php echo esc_attr( $location->ID ); ?>" <?php selected( $fields['location'], $location->ID ); ?>><?php echo esc_html( $location->post_title ); ?></option>
				<?php endforeach; ?>
			</select>
			<small><a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=location' ) ); ?>"><?php esc_html_e( 'Yeni mekan ekle', 'asosyoloji-weekly' ); ?></a></small>
		</p>
		<p><label><strong><?php esc_html_e( 'Organizatör', 'asosyoloji-weekly' ); ?></strong></label><br><input class="widefat" type="text" name="aso_event_organizer" value="<?php echo esc_attr( $fields['organizer'] ); ?>"></p>
		<p><label><strong><?php esc_html_e( 'Etkinlik bağlantısı', 'asosyoloji-weekly' ); ?></strong></label><br><input class="widefat" type="url" name="aso_event_url" value="<?php echo esc_url( $fields['event_url'] ); ?>"></p>
		<p><label><strong><?php esc_html_e( 'Fiyat / bilet bilgisi', 'asosyoloji-weekly' ); ?></strong></label><br><input class="widefat" type="text" name="aso_event_price" value="<?php echo esc_attr( $fields['price'] ); ?>"></p>
		<p><label><input type="checkbox" name="aso_event_free" value="1" <?php checked( $fields['free'], '1' ); ?>> <?php esc_html_e( 'Ücretsiz etkinlik', 'asosyoloji-weekly' ); ?></label></p>
	</div>
	<?php
}

function asosyoloji_weekly_save_meta( $post_id ) {
	if (
		! isset( $_POST['asosyoloji_weekly_nonce'] ) ||
		! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['asosyoloji_weekly_nonce'] ) ), 'asosyoloji_weekly_save_event' ) ||
		! current_user_can( 'edit_post', $post_id ) ||
		wp_is_post_revision( $post_id )
	) {
		return;
	}

	$text_fields = array(
		'aso_event_start_date' => '_event_start_date',
		'aso_event_start_time' => '_event_start_time',
		'aso_event_end_date'   => '_event_end_date',
		'aso_event_end_time'   => '_event_end_time',
		'aso_event_organizer'  => '_event_organizer',
		'aso_event_price'      => '_event_price',
	);

	foreach ( $text_fields as $input => $meta_key ) {
		$value = isset( $_POST[ $input ] ) ? sanitize_text_field( wp_unslash( $_POST[ $input ] ) ) : '';
		if ( '' === $value ) {
			delete_post_meta( $post_id, $meta_key );
		} else {
			update_post_meta( $post_id, $meta_key, $value );
		}
	}

	update_post_meta( $post_id, '_location_id', isset( $_POST['aso_event_location_id'] ) ? absint( $_POST['aso_event_location_id'] ) : 0 );

	$url = isset( $_POST['aso_event_url'] ) ? esc_url_raw( wp_unslash( $_POST['aso_event_url'] ) ) : '';
	if ( $url ) {
		update_post_meta( $post_id, '_event_url', $url );
	} else {
		delete_post_meta( $post_id, '_event_url' );
	}

	update_post_meta( $post_id, '_event_free', isset( $_POST['aso_event_free'] ) ? '1' : '0' );

	$start_date = get_post_meta( $post_id, '_event_start_date', true );
	$start_time = get_post_meta( $post_id, '_event_start_time', true );
	$end_date   = get_post_meta( $post_id, '_event_end_date', true );
	$end_time   = get_post_meta( $post_id, '_event_end_time', true );

	if ( $start_date ) {
		update_post_meta( $post_id, '_start_ts', strtotime( $start_date . ' ' . ( $start_time ? $start_time : '00:00' ) ) );
	}
	if ( $end_date ) {
		update_post_meta( $post_id, '_end_ts', strtotime( $end_date . ' ' . ( $end_time ? $end_time : '23:59' ) ) );
	}
}
add_action( 'save_post_event', 'asosyoloji_weekly_save_meta' );
