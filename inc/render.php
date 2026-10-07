<?php
/**
 * Frontend renderers and shortcodes.
 *
 * @package AsosyolojiWeekly
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function asosyoloji_weekly_format_day( $date ) {
	$timestamp = strtotime( $date );
	return $timestamp ? wp_date( 'd', $timestamp ) : '';
}

function asosyoloji_weekly_format_weekday( $date ) {
	$timestamp = strtotime( $date );
	return $timestamp ? wp_date( 'l', $timestamp ) : '';
}

function asosyoloji_weekly_render_list( $args = array() ) {
	$defaults = array(
		'title'      => __( 'Haftalık', 'asosyoloji-weekly' ),
		'mode'       => 'week',
		'count'      => 10,
		'event_type' => 0,
		'city'       => '',
		'compact'    => false,
	);

	$args = wp_parse_args( $args, $defaults );

	if ( 'week' === $args['mode'] ) {
		$range = asosyoloji_weekly_week_range();
		$query = asosyoloji_weekly_get_events(
			array(
				'start'      => $range['start'],
				'end'        => $range['end'],
				'count'      => $args['count'],
				'event_type' => $args['event_type'],
				'city'       => $args['city'],
			)
		);
	} else {
		$query = asosyoloji_weekly_get_events(
			array(
				'start'      => asosyoloji_weekly_today(),
				'count'      => $args['count'],
				'event_type' => $args['event_type'],
				'city'       => $args['city'],
			)
		);
	}

	ob_start();
	?>
	<section class="aso-weekly<?php echo $args['compact'] ? ' aso-weekly--compact' : ''; ?>">
		<div class="aso-weekly__header">
			<div>
				<div class="aso-weekly__kicker"><?php esc_html_e( 'Etkinlik Takvimi', 'asosyoloji-weekly' ); ?></div>
				<h2 class="aso-weekly__title"><?php echo esc_html( $args['title'] ); ?></h2>
			</div>
			<?php if ( 'week' === $args['mode'] ) : ?>
				<div class="aso-weekly__range">
					<?php echo esc_html( wp_date( 'd M', strtotime( $range['start'] ) ) ); ?>
					–
					<?php echo esc_html( wp_date( 'd M', strtotime( $range['end'] ) ) ); ?>
				</div>
			<?php endif; ?>
		</div>

		<div class="aso-weekly__events">
			<?php if ( $query->have_posts() ) : ?>
				<?php
				while ( $query->have_posts() ) :
					$query->the_post();
					$event = asosyoloji_weekly_event_data( get_the_ID() );
					?>
					<article class="aso-weekly-event">
						<time class="aso-weekly-event__date" datetime="<?php echo esc_attr( $event['start_date'] ); ?>">
							<span class="aso-weekly-event__weekday"><?php echo esc_html( asosyoloji_weekly_format_weekday( $event['start_date'] ) ); ?></span>
							<span class="aso-weekly-event__day"><?php echo esc_html( asosyoloji_weekly_format_day( $event['start_date'] ) ); ?></span>
						</time>

						<div class="aso-weekly-event__body">
							<?php if ( $event['types'] ) : ?>
								<div class="aso-weekly-event__type"><?php echo esc_html( $event['types'][0] ); ?></div>
							<?php endif; ?>

							<h3 class="aso-weekly-event__title">
								<a href="<?php echo esc_url( $event['url'] ); ?>"><?php echo esc_html( $event['title'] ); ?></a>
							</h3>

							<div class="aso-weekly-event__meta">
								<?php if ( $event['start_time'] ) : ?>
									<span><?php echo esc_html( $event['start_time'] ); ?></span>
								<?php endif; ?>
								<?php if ( $event['venue'] ) : ?>
									<span><?php echo esc_html( $event['venue'] ); ?></span>
								<?php endif; ?>
								<?php if ( $event['city'] ) : ?>
									<span><?php echo esc_html( $event['city'] ); ?></span>
								<?php endif; ?>
							</div>

							<?php if ( ! $args['compact'] && $event['excerpt'] ) : ?>
								<p class="aso-weekly-event__excerpt"><?php echo esc_html( wp_trim_words( $event['excerpt'], 22 ) ); ?></p>
							<?php endif; ?>
						</div>

						<a class="aso-weekly-event__more" href="<?php echo esc_url( $event['url'] ); ?>">
							<?php esc_html_e( 'Detay', 'asosyoloji-weekly' ); ?>
						</a>
					</article>
				<?php endwhile; ?>
			<?php else : ?>
				<p class="aso-weekly__empty"><?php esc_html_e( 'Bu dönem için etkinlik bulunmuyor.', 'asosyoloji-weekly' ); ?></p>
			<?php endif; ?>
		</div>
	</section>
	<?php
	wp_reset_postdata();

	return (string) ob_get_clean();
}

function asosyoloji_weekly_render_calendar( $args = array() ) {
	$defaults = array(
		'title' => __( 'Etkinlik Takvimi', 'asosyoloji-weekly' ),
		'year'  => (int) current_time( 'Y' ),
		'month' => (int) current_time( 'm' ),
	);

	$args  = wp_parse_args( $args, $defaults );
	$year  = max( 2000, absint( $args['year'] ) );
	$month = min( 12, max( 1, absint( $args['month'] ) ) );

	$first_day = sprintf( '%04d-%02d-01', $year, $month );
	$last_day  = wp_date( 'Y-m-t', strtotime( $first_day ) );
	$query     = asosyoloji_weekly_get_events(
		array(
			'start' => $first_day,
			'end'   => $last_day,
			'count' => -1,
		)
	);

	$events_by_day = array();
	while ( $query->have_posts() ) {
		$query->the_post();
		$event = asosyoloji_weekly_event_data( get_the_ID() );
		$day   = (int) wp_date( 'j', strtotime( $event['start_date'] ) );
		$events_by_day[ $day ][] = $event;
	}
	wp_reset_postdata();

	$days_in_month = (int) wp_date( 't', strtotime( $first_day ) );
	$start_weekday = (int) wp_date( 'N', strtotime( $first_day ) );

	ob_start();
	?>
	<section class="aso-calendar">
		<div class="aso-calendar__header">
			<div>
				<div class="aso-weekly__kicker"><?php esc_html_e( 'Aylık Görünüm', 'asosyoloji-weekly' ); ?></div>
				<h2 class="aso-calendar__title"><?php echo esc_html( $args['title'] ); ?></h2>
			</div>
			<div class="aso-calendar__month"><?php echo esc_html( wp_date( 'F Y', strtotime( $first_day ) ) ); ?></div>
		</div>

		<div class="aso-calendar__weekdays" aria-hidden="true">
			<?php foreach ( array( 'Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt', 'Paz' ) as $day_name ) : ?>
				<span><?php echo esc_html( $day_name ); ?></span>
			<?php endforeach; ?>
		</div>

		<div class="aso-calendar__grid">
			<?php for ( $empty = 1; $empty < $start_weekday; $empty++ ) : ?>
				<div class="aso-calendar__day is-empty" aria-hidden="true"></div>
			<?php endfor; ?>

			<?php for ( $day = 1; $day <= $days_in_month; $day++ ) : ?>
				<?php $day_events = $events_by_day[ $day ] ?? array(); ?>
				<div class="aso-calendar__day<?php echo $day_events ? ' has-events' : ''; ?>">
					<div class="aso-calendar__day-number"><?php echo esc_html( $day ); ?></div>
					<?php foreach ( array_slice( $day_events, 0, 3 ) as $event ) : ?>
						<a class="aso-calendar__event" href="<?php echo esc_url( $event['url'] ); ?>">
							<?php if ( $event['start_time'] ) : ?>
								<span><?php echo esc_html( $event['start_time'] ); ?></span>
							<?php endif; ?>
							<?php echo esc_html( $event['title'] ); ?>
						</a>
					<?php endforeach; ?>
					<?php if ( count( $day_events ) > 3 ) : ?>
						<span class="aso-calendar__more">+<?php echo esc_html( count( $day_events ) - 3 ); ?></span>
					<?php endif; ?>
				</div>
			<?php endfor; ?>
		</div>
	</section>
	<?php

	return (string) ob_get_clean();
}

function asosyoloji_weekly_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'title'   => __( 'Haftalık', 'asosyoloji-weekly' ),
			'mode'    => 'week',
			'count'   => 10,
			'city'    => '',
			'compact' => '0',
		),
		$atts,
		'asosyoloji_haftalik'
	);

	return asosyoloji_weekly_render_list(
		array(
			'title'   => sanitize_text_field( $atts['title'] ),
			'mode'    => 'upcoming' === $atts['mode'] ? 'upcoming' : 'week',
			'count'   => absint( $atts['count'] ),
			'city'    => sanitize_text_field( $atts['city'] ),
			'compact' => '1' === (string) $atts['compact'],
		)
	);
}
add_shortcode( 'asosyoloji_haftalik', 'asosyoloji_weekly_shortcode' );

function asosyoloji_calendar_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'title' => __( 'Etkinlik Takvimi', 'asosyoloji-weekly' ),
			'year'  => current_time( 'Y' ),
			'month' => current_time( 'm' ),
		),
		$atts,
		'asosyoloji_takvim'
	);

	return asosyoloji_weekly_render_calendar(
		array(
			'title' => sanitize_text_field( $atts['title'] ),
			'year'  => absint( $atts['year'] ),
			'month' => absint( $atts['month'] ),
		)
	);
}
add_shortcode( 'asosyoloji_takvim', 'asosyoloji_calendar_shortcode' );
