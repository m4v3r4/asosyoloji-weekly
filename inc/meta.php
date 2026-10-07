<?php
/**
 * Event metadata.
 *
 * @package AsosyolojiWeekly
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function asosyoloji_weekly_meta_box() {
	add_meta_box(
		'asosyoloji-event-details',
		__( 'Etkinlik Bilgileri', 'asosyoloji-weekly' ),
		'asosyoloji_weekly_meta_box_render',
		'asosyoloji_event',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'asosyoloji_weekly_meta_box' );

function asosyoloji_weekly_meta_box_render( $post ) {
	wp_nonce_field( 'asosyoloji_weekly_save_event', 'asosyoloji_weekly_nonce' );

	$fields = array(
		'start_date' => get_post_meta( $post->ID, '_aso_event_start_date', true ),
		'start_time' => get_post_meta( $post->ID, '_aso_event_start_time', true ),
		'end_date'   => get_post_meta( $post->ID, '_aso_event_end_date', true ),
		'end_time'   => get_post_meta( $post->ID, '_aso_event_end_time', true ),
		'venue'      => get_post_meta( $post->ID, '_aso_event_venue', true ),
		'city'       => get_post_meta( $post->ID, '_aso_event_city', true ),
		'organizer'  => get_post_meta( $post->ID, '_aso_event_organizer', true ),
		'event_url'  => get_post_meta( $post->ID, '_aso_event_url', true ),
		'price'      => get_post_meta( $post->ID, '_aso_event_price', true ),
		'free'       => get_post_meta( $post->ID, '_aso_event_free', true ),
	);
	?>
	<div class="aso-weekly-admin-grid">
		<p>
			<label for="aso-event-start-date"><strong><?php esc_html_e( 'Başlangıç tarihi', 'asosyoloji-weekly' ); ?></strong></label><br>
			<input id="aso-event-start-date" type="date" name="aso_event_start_date" value="<?php echo esc_attr( $fields['start_date'] ); ?>" required>
		</p>
		<p>
			<label for="aso-event-start-time"><strong><?php esc_html_e( 'Başlangıç saati', 'asosyoloji-weekly' ); ?></strong></label><br>
			<input id="aso-event-start-time" type="time" name="aso_event_start_time" value="<?php echo esc_attr( $fields['start_time'] ); ?>">
		</p>
		<p>
			<label for="aso-event-end-date"><strong><?php esc_html_e( 'Bitiş tarihi', 'asosyoloji-weekly' ); ?></strong></label><br>
			<input id="aso-event-end-date" type="date" name="aso_event_end_date" value="<?php echo esc_attr( $fields['end_date'] ); ?>">
		</p>
		<p>
			<label for="aso-event-end-time"><strong><?php esc_html_e( 'Bitiş saati', 'asosyoloji-weekly' ); ?></strong></label><br>
			<input id="aso-event-end-time" type="time" name="aso_event_end_time" value="<?php echo esc_attr( $fields['end_time'] ); ?>">
		</p>
		<p>
			<label for="aso-event-venue"><strong><?php esc_html_e( 'Mekan', 'asosyoloji-weekly' ); ?></strong></label><br>
			<input class="widefat" id="aso-event-venue" type="text" name="aso_event_venue" value="<?php echo esc_attr( $fields['venue'] ); ?>">
		</p>
		<p>
			<label for="aso-event-city"><strong><?php esc_html_e( 'Şehir', 'asosyoloji-weekly' ); ?></strong></label><br>
			<input class="widefat" id="aso-event-city" type="text" name="aso_event_city" value="<?php echo esc_attr( $fields['city'] ); ?>">
		</p>
		<p>
			<label for="aso-event-organizer"><strong><?php esc_html_e( 'Organizatör', 'asosyoloji-weekly' ); ?></strong></label><br>
			<input class="widefat" id="aso-event-organizer" type="text" name="aso_event_organizer" value="<?php echo esc_attr( $fields['organizer'] ); ?>">
		</p>
		<p>
			<label for="aso-event-url"><strong><?php esc_html_e( 'Etkinlik bağlantısı', 'asosyoloji-weekly' ); ?></strong></label><br>
			<input class="widefat" id="aso-event-url" type="url" name="aso_event_url" value="<?php echo esc_url( $fields['event_url'] ); ?>">
		</p>
		<p>
			<label for="aso-event-price"><strong><?php esc_html_e( 'Fiyat / bilet bilgisi', 'asosyoloji-weekly' ); ?></strong></label><br>
			<input class="widefat" id="aso-event-price" type="text" name="aso_event_price" value="<?php echo esc_attr( $fields['price'] ); ?>">
		</p>
		<p>
			<label><input type="checkbox" name="aso_event_free" value="1" <?php checked( $fields['free'], '1' ); ?>> <?php esc_html_e( 'Ücretsiz etkinlik', 'asosyoloji-weekly' ); ?></label>
		</p>
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
		'aso_event_start_date' => '_aso_event_start_date',
		'aso_event_start_time' => '_aso_event_start_time',
		'aso_event_end_date'   => '_aso_event_end_date',
		'aso_event_end_time'   => '_aso_event_end_time',
		'aso_event_venue'      => '_aso_event_venue',
		'aso_event_city'       => '_aso_event_city',
		'aso_event_organizer'  => '_aso_event_organizer',
		'aso_event_price'      => '_aso_event_price',
	);

	foreach ( $text_fields as $input => $meta_key ) {
		$value = isset( $_POST[ $input ] ) ? sanitize_text_field( wp_unslash( $_POST[ $input ] ) ) : '';
		if ( '' === $value ) {
			delete_post_meta( $post_id, $meta_key );
		} else {
			update_post_meta( $post_id, $meta_key, $value );
		}
	}

	$url = isset( $_POST['aso_event_url'] ) ? esc_url_raw( wp_unslash( $_POST['aso_event_url'] ) ) : '';
	if ( $url ) {
		update_post_meta( $post_id, '_aso_event_url', $url );
	} else {
		delete_post_meta( $post_id, '_aso_event_url' );
	}

	update_post_meta( $post_id, '_aso_event_free', isset( $_POST['aso_event_free'] ) ? '1' : '0' );
}
add_action( 'save_post_asosyoloji_event', 'asosyoloji_weekly_save_meta' );
