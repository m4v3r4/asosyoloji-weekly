<?php
/**
 * Original Asosyoloji event metadata.
 *
 * @package AsosyolojiWeekly
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function asosyoloji_weekly_register_event_meta() {
	$fields = array(
		'em_id'              => 'integer',
		'em_event_type'      => 'string',
		'em_venue'           => 'string',
		'em_organizer'       => 'string',
		'em_performer'       => 'string',
		'em_start_date'      => 'integer',
		'em_start_time'      => 'string',
		'em_end_date'        => 'integer',
		'em_end_time'        => 'string',
		'em_all_day'         => 'boolean',
		'em_start_date_time' => 'integer',
		'em_end_date_time'   => 'integer',
		'em_fixed_event_price' => 'string',
	);

	foreach ( $fields as $key => $type ) {
		register_post_meta(
			'em_event',
			$key,
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
}
add_action( 'init', 'asosyoloji_weekly_register_event_meta', 20 );

function asosyoloji_weekly_meta_box() {
	add_meta_box(
		'asosyoloji-event-details',
		__( 'Etkinlik Bilgileri', 'asosyoloji-weekly' ),
		'asosyoloji_weekly_meta_box_render',
		'em_event',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'asosyoloji_weekly_meta_box' );

function asosyoloji_weekly_meta_box_render( $post ) {
	wp_nonce_field( 'asosyoloji_weekly_save_event', 'asosyoloji_weekly_nonce' );

	$event = asosyoloji_weekly_event_data( $post->ID );
	?>
	<div class="aso-weekly-admin-grid">
		<p>
			<label><strong><?php esc_html_e( 'Başlangıç tarihi', 'asosyoloji-weekly' ); ?></strong></label><br>
			<input type="date" name="aso_event_start_date" value="<?php echo esc_attr( $event['start_date'] ); ?>" required>
		</p>
		<p>
			<label><strong><?php esc_html_e( 'Başlangıç saati', 'asosyoloji-weekly' ); ?></strong></label><br>
			<input type="time" name="aso_event_start_time" value="<?php echo esc_attr( $event['start_time'] ); ?>">
		</p>
		<p>
			<label><strong><?php esc_html_e( 'Bitiş tarihi', 'asosyoloji-weekly' ); ?></strong></label><br>
			<input type="date" name="aso_event_end_date" value="<?php echo esc_attr( $event['end_date'] ); ?>">
		</p>
		<p>
			<label><strong><?php esc_html_e( 'Bitiş saati', 'asosyoloji-weekly' ); ?></strong></label><br>
			<input type="time" name="aso_event_end_time" value="<?php echo esc_attr( $event['end_time'] ); ?>">
		</p>
		<p>
			<label><strong><?php esc_html_e( 'Tüm gün', 'asosyoloji-weekly' ); ?></strong></label><br>
			<label><input type="checkbox" name="aso_event_all_day" value="1" <?php checked( ! empty( $event['all_day'] ) ); ?>> <?php esc_html_e( 'Saat göstermeden tüm gün etkinliği olarak işaretle', 'asosyoloji-weekly' ); ?></label>
		</p>
		<p>
			<label><strong><?php esc_html_e( 'Fiyat / bilet bilgisi', 'asosyoloji-weekly' ); ?></strong></label><br>
			<input class="widefat" type="text" name="aso_event_price" value="<?php echo esc_attr( $event['price'] ); ?>">
		</p>
	</div>
	<p>
		<?php esc_html_e( 'Etkinlik türü ve mekan için sağ taraftaki Etkinlik Türleri ve Mekanlar alanlarını kullanın.', 'asosyoloji-weekly' ); ?>
	</p>
	<?php
}

function asosyoloji_weekly_time_24h( $value ) {
	if ( ! $value ) {
		return '';
	}
	$timestamp = strtotime( $value );
	return $timestamp ? gmdate( 'H:i', $timestamp ) : sanitize_text_field( $value );
}

function asosyoloji_weekly_timestamp_from_fields( $date, $time = '' ) {
	if ( ! $date ) {
		return 0;
	}

	$parts = array_map( 'intval', explode( '-', $date ) );
	if ( 3 !== count( $parts ) ) {
		return 0;
	}

	$hour = 0;
	$minute = 0;
	if ( $time ) {
		$time_parts = explode( ':', $time );
		$hour       = isset( $time_parts[0] ) ? (int) $time_parts[0] : 0;
		$minute     = isset( $time_parts[1] ) ? (int) $time_parts[1] : 0;
	}

	return gmmktime( $hour, $minute, 0, $parts[1], $parts[2], $parts[0] );
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

	$start_date = isset( $_POST['aso_event_start_date'] ) ? sanitize_text_field( wp_unslash( $_POST['aso_event_start_date'] ) ) : '';
	$start_time = isset( $_POST['aso_event_start_time'] ) ? sanitize_text_field( wp_unslash( $_POST['aso_event_start_time'] ) ) : '';
	$end_date   = isset( $_POST['aso_event_end_date'] ) ? sanitize_text_field( wp_unslash( $_POST['aso_event_end_date'] ) ) : $start_date;
	$end_time   = isset( $_POST['aso_event_end_time'] ) ? sanitize_text_field( wp_unslash( $_POST['aso_event_end_time'] ) ) : $start_time;
	$all_day    = isset( $_POST['aso_event_all_day'] ) ? 1 : 0;
	$price      = isset( $_POST['aso_event_price'] ) ? sanitize_text_field( wp_unslash( $_POST['aso_event_price'] ) ) : '';

	if ( ! $end_date ) {
		$end_date = $start_date;
	}

	$start_ts = asosyoloji_weekly_timestamp_from_fields( $start_date, $start_time );
	$end_ts   = asosyoloji_weekly_timestamp_from_fields( $end_date, $end_time );

	update_post_meta( $post_id, 'em_start_date_time', $start_ts );
	update_post_meta( $post_id, 'em_end_date_time', $end_ts );
	update_post_meta( $post_id, 'em_start_date', asosyoloji_weekly_timestamp_from_fields( $start_date ) );
	update_post_meta( $post_id, 'em_end_date', asosyoloji_weekly_timestamp_from_fields( $end_date ) );
	update_post_meta( $post_id, 'em_start_time', $start_time ? wp_date( 'h:i A', strtotime( $start_time ) ) : '' );
	update_post_meta( $post_id, 'em_end_time', $end_time ? wp_date( 'h:i A', strtotime( $end_time ) ) : '' );
	update_post_meta( $post_id, 'em_all_day', $all_day );
	update_post_meta( $post_id, 'em_fixed_event_price', $price );
}
add_action( 'save_post_em_event', 'asosyoloji_weekly_save_meta' );
