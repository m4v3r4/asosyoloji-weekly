<?php
/**
 * Single event content enhancements.
 *
 * @package AsosyolojiWeekly
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function asosyoloji_weekly_event_details_markup( $post_id ) {
	$event = asosyoloji_weekly_event_data( $post_id );

	if ( ! $event['start_date'] ) {
		return '';
	}

	$date_label = wp_date( 'd F Y', strtotime( $event['start_date'] ) );
	if ( $event['end_date'] && $event['end_date'] !== $event['start_date'] ) {
		$date_label .= ' – ' . wp_date( 'd F Y', strtotime( $event['end_date'] ) );
	}

	$time_label = $event['start_time'];
	if ( $event['end_time'] ) {
		$time_label .= ( $time_label ? ' – ' : '' ) . $event['end_time'];
	}

	ob_start();
	?>
	<aside class="aso-event-details" aria-label="<?php esc_attr_e( 'Etkinlik bilgileri', 'asosyoloji-weekly' ); ?>">
		<div class="aso-event-details__grid">
			<div>
				<span class="aso-event-details__label"><?php esc_html_e( 'Tarih', 'asosyoloji-weekly' ); ?></span>
				<strong><?php echo esc_html( $date_label ); ?></strong>
			</div>

			<?php if ( $time_label ) : ?>
				<div>
					<span class="aso-event-details__label"><?php esc_html_e( 'Saat', 'asosyoloji-weekly' ); ?></span>
					<strong><?php echo esc_html( $time_label ); ?></strong>
				</div>
			<?php endif; ?>

			<?php if ( $event['venue'] || $event['city'] ) : ?>
				<div>
					<span class="aso-event-details__label"><?php esc_html_e( 'Yer', 'asosyoloji-weekly' ); ?></span>
					<strong><?php echo esc_html( trim( implode( ', ', array_filter( array( $event['venue'], $event['city'] ) ) ) ) ); ?></strong>
				</div>
			<?php endif; ?>

			<?php if ( $event['organizer'] ) : ?>
				<div>
					<span class="aso-event-details__label"><?php esc_html_e( 'Organizatör', 'asosyoloji-weekly' ); ?></span>
					<strong><?php echo esc_html( $event['organizer'] ); ?></strong>
				</div>
			<?php endif; ?>

			<?php if ( $event['free'] || $event['price'] ) : ?>
				<div>
					<span class="aso-event-details__label"><?php esc_html_e( 'Katılım', 'asosyoloji-weekly' ); ?></span>
					<strong>
						<?php echo $event['free'] ? esc_html__( 'Ücretsiz', 'asosyoloji-weekly' ) : esc_html( $event['price'] ); ?>
					</strong>
				</div>
			<?php endif; ?>
		</div>

		<div class="aso-event-details__actions">
			<a href="<?php echo esc_url( asosyoloji_weekly_ics_url( $post_id ) ); ?>">
				<?php esc_html_e( 'Takvime Ekle (.ics)', 'asosyoloji-weekly' ); ?>
			</a>
			<a href="<?php echo esc_url( asosyoloji_weekly_google_calendar_url( $post_id ) ); ?>" target="_blank" rel="noopener noreferrer">
				<?php esc_html_e( 'Google Calendar', 'asosyoloji-weekly' ); ?>
			</a>
			<?php if ( $event['event_url'] ) : ?>
				<a href="<?php echo esc_url( $event['event_url'] ); ?>" target="_blank" rel="noopener noreferrer">
					<?php esc_html_e( 'Etkinlik Sayfası', 'asosyoloji-weekly' ); ?>
				</a>
			<?php endif; ?>
		</div>
	</aside>
	<?php
	return (string) ob_get_clean();
}

function asosyoloji_weekly_prepend_event_details( $content ) {
	if ( ! is_singular( 'event' ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	return asosyoloji_weekly_event_details_markup( get_the_ID() ) . $content;
}
add_filter( 'the_content', 'asosyoloji_weekly_prepend_event_details', 8 );
