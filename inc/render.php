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

function asosyoloji_weekly_format_range( $start_date, $end_date ) {
	if ( ! $start_date ) {
		return '';
	}

	if ( ! $end_date || $end_date === $start_date ) {
		return wp_date( 'd F', strtotime( $start_date ) );
	}

	$start_ts = strtotime( $start_date );
	$end_ts   = strtotime( $end_date );

	if ( wp_date( 'm Y', $start_ts ) === wp_date( 'm Y', $end_ts ) ) {
		return sprintf(
			'%1$s–%2$s %3$s',
			wp_date( 'd', $start_ts ),
			wp_date( 'd', $end_ts ),
			wp_date( 'F', $end_ts )
		);
	}

	return sprintf(
		'%1$s – %2$s',
		wp_date( 'd F', $start_ts ),
		wp_date( 'd F', $end_ts )
	);
}

function asosyoloji_weekly_time_label( $event ) {
	if ( ! empty( $event['all_day'] ) ) {
		return __( 'Tüm gün', 'asosyoloji-weekly' );
	}

	return ! empty( $event['start_time'] ) ? $event['start_time'] : __( 'Saat belirtilmedi', 'asosyoloji-weekly' );
}

function asosyoloji_weekly_recurrence_label( $event ) {
	$interval = max( 1, absint( $event['recurrence_interval'] ?? 1 ) );
	$frequency = $event['recurrence'] ?? 'none';
	$labels = array(
		'daily' => 1 === $interval ? __( 'Her gün', 'asosyoloji-weekly' ) : sprintf( __( 'Her %d günde bir', 'asosyoloji-weekly' ), $interval ),
		'weekly' => 1 === $interval ? __( 'Her hafta', 'asosyoloji-weekly' ) : sprintf( __( 'Her %d haftada bir', 'asosyoloji-weekly' ), $interval ),
		'monthly' => 1 === $interval ? __( 'Her ay', 'asosyoloji-weekly' ) : sprintf( __( 'Her %d ayda bir', 'asosyoloji-weekly' ), $interval ),
	);
	if ( empty( $labels[ $frequency ] ) ) {
		return '';
	}

	$until = ! empty( $event['recurrence_until'] ) ? wp_date( 'd F Y', strtotime( $event['recurrence_until'] ) ) : '';
	return $until ? sprintf( __( '%1$s · %2$s tarihine kadar', 'asosyoloji-weekly' ), $labels[ $frequency ], $until ) : $labels[ $frequency ];
}

function asosyoloji_weekly_render_list( $args = array() ) {
	$defaults = array(
		'title'      => __( 'Haftalık', 'asosyoloji-weekly' ),
		'mode'       => 'week',
		'count'      => 10,
		'event_type' => 0,
		'city'       => '',
		'compact'    => false,
		'venue'      => 0,
		'start'      => '',
		'end'        => '',
		'order'      => 'ASC',
		'kicker'     => __( 'Etkinlik Takvimi', 'asosyoloji-weekly' ),
		'empty_text' => __( 'Bu dönem için etkinlik bulunmuyor.', 'asosyoloji-weekly' ),
	);

	$args = wp_parse_args( $args, $defaults );

	if ( $args['start'] ) {
		$events = asosyoloji_weekly_collect_events(
			array(
				'start'      => $args['start'],
				'end'        => $args['end'],
				'count'      => $args['count'],
				'event_type' => $args['event_type'],
				'venue'      => $args['venue'],
				'city'       => $args['city'],
				'order'      => $args['order'],
			)
		);
	} elseif ( 'week' === $args['mode'] ) {
		$range  = asosyoloji_weekly_week_range();
		$events = asosyoloji_weekly_collect_events(
			array(
				'start'      => $range['start'],
				'end'        => $range['end'],
				'count'      => $args['count'],
				'event_type' => $args['event_type'],
				'venue'      => $args['venue'],
				'city'       => $args['city'],
			)
		);
	} else {
		$events = asosyoloji_weekly_collect_events(
			array(
				'start'      => asosyoloji_weekly_today(),
				'count'      => $args['count'],
				'event_type' => $args['event_type'],
				'venue'      => $args['venue'],
				'city'       => $args['city'],
			)
		);
	}

	ob_start();
	?>
	<section class="aso-weekly<?php echo $args['compact'] ? ' aso-weekly--compact' : ''; ?>">
		<div class="aso-weekly__header">
			<div>
				<div class="aso-weekly__kicker"><?php echo esc_html( $args['kicker'] ); ?></div>
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
			<?php if ( $events ) : ?>
				<?php foreach ( $events as $event ) : ?>
					<?php $is_multiday = $event['end_date'] && $event['end_date'] !== $event['start_date']; ?>
					<article class="aso-weekly-event<?php echo $is_multiday ? ' is-multiday' : ''; ?>">
						<time class="aso-weekly-event__date" datetime="<?php echo esc_attr( $event['start_date'] ); ?>">
							<?php if ( $is_multiday ) : ?>
								<span class="aso-weekly-event__weekday"><?php esc_html_e( 'Tarih Aralığı', 'asosyoloji-weekly' ); ?></span>
								<span class="aso-weekly-event__range-label"><?php echo esc_html( asosyoloji_weekly_format_range( $event['start_date'], $event['end_date'] ) ); ?></span>
							<?php else : ?>
								<span class="aso-weekly-event__weekday"><?php echo esc_html( asosyoloji_weekly_format_weekday( $event['start_date'] ) ); ?></span>
								<span class="aso-weekly-event__day"><?php echo esc_html( asosyoloji_weekly_format_day( $event['start_date'] ) ); ?></span>
							<?php endif; ?>
						</time>

						<div class="aso-weekly-event__body">
							<?php if ( $event['types'] ) : ?>
								<div class="aso-weekly-event__type"><?php echo esc_html( $event['types'][0] ); ?></div>
							<?php endif; ?>

							<h3 class="aso-weekly-event__title">
								<a href="<?php echo esc_url( $event['url'] ); ?>"><?php echo esc_html( $event['title'] ); ?></a>
							</h3>

							<div class="aso-weekly-event__meta">
								<span><?php echo esc_html( asosyoloji_weekly_time_label( $event ) ); ?></span>
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
				<?php endforeach; ?>
			<?php else : ?>
				<p class="aso-weekly__empty"><?php echo esc_html( $args['empty_text'] ); ?></p>
			<?php endif; ?>
		</div>
	</section>
	<?php
	return (string) ob_get_clean();
}

function asosyoloji_weekly_render_calendar( $args = array() ) {
	$defaults = array(
		'title' => __( 'Etkinlik Takvimi', 'asosyoloji-weekly' ),
		'year'  => (int) current_time( 'Y' ),
		'month' => (int) current_time( 'm' ),
		'event_type' => 0,
		'venue' => 0,
	);

	$args  = wp_parse_args( $args, $defaults );

	$requested_month = isset( $_GET['aso_calendar_month'] ) ? sanitize_text_field( wp_unslash( $_GET['aso_calendar_month'] ) ) : '';
	if ( preg_match( '/^(\d{4})-(\d{2})$/', $requested_month, $matches ) ) {
		$args['year']  = absint( $matches[1] );
		$args['month'] = absint( $matches[2] );
	}

	$year  = max( 2000, absint( $args['year'] ) );
	$month = min( 12, max( 1, absint( $args['month'] ) ) );

	$first_day = sprintf( '%04d-%02d-01', $year, $month );
	$last_day  = wp_date( 'Y-m-t', strtotime( $first_day ) );
	$events = asosyoloji_weekly_collect_events(
		array(
			'start' => $first_day,
			'end'   => $last_day,
			'count' => -1,
			'event_type' => absint( $args['event_type'] ),
			'venue' => absint( $args['venue'] ),
		)
	);

	$events_by_day = array();
	foreach ( $events as $event ) {
		$event_start = max( $event['start_date'], $first_day );
		$event_end   = min( $event['end_date'] ? $event['end_date'] : $event['start_date'], $last_day );
		$cursor      = strtotime( $event_start );
		$end_cursor  = strtotime( $event_end );

		while ( $cursor && $cursor <= $end_cursor ) {
			$day = (int) wp_date( 'j', $cursor );
			$events_by_day[ $day ][] = $event;
			$cursor = strtotime( '+1 day', $cursor );
		}
	}

	$today         = asosyoloji_weekly_today();
	$days_in_month = (int) wp_date( 't', strtotime( $first_day ) );
	$start_weekday = (int) wp_date( 'N', strtotime( $first_day ) );
	$previous_month = wp_date( 'Y-m', strtotime( '-1 month', strtotime( $first_day ) ) );
	$next_month     = wp_date( 'Y-m', strtotime( '+1 month', strtotime( $first_day ) ) );
	$previous_url   = add_query_arg( 'aso_calendar_month', $previous_month );
	$next_url       = add_query_arg( 'aso_calendar_month', $next_month );

	ob_start();
	?>
	<section class="aso-calendar">
		<div class="aso-calendar__header">
			<div>
				<div class="aso-weekly__kicker"><?php esc_html_e( 'Aylık Görünüm', 'asosyoloji-weekly' ); ?></div>
				<h2 class="aso-calendar__title"><?php echo esc_html( $args['title'] ); ?></h2>
			</div>
			<div class="aso-calendar__nav">
				<a href="<?php echo esc_url( $previous_url ); ?>" aria-label="<?php esc_attr_e( 'Önceki ay', 'asosyoloji-weekly' ); ?>">←</a>
				<div class="aso-calendar__month"><?php echo esc_html( wp_date( 'F Y', strtotime( $first_day ) ) ); ?></div>
				<a href="<?php echo esc_url( $next_url ); ?>" aria-label="<?php esc_attr_e( 'Sonraki ay', 'asosyoloji-weekly' ); ?>">→</a>
			</div>
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
				<?php
				$day_events = $events_by_day[ $day ] ?? array();
				$day_date   = sprintf( '%04d-%02d-%02d', $year, $month, $day );
				$day_class  = 'aso-calendar__day';
				if ( $day_events ) {
					$day_class .= ' has-events';
				}
				if ( $day_date === $today ) {
					$day_class .= ' is-today';
				}
				?>
				<div class="<?php echo esc_attr( $day_class ); ?>">
					<div class="aso-calendar__day-number"><?php echo esc_html( $day ); ?></div>
					<?php foreach ( array_slice( $day_events, 0, 3 ) as $event ) : ?>
						<?php
						$is_multiday  = $event['end_date'] && $event['end_date'] !== $event['start_date'];
						$is_start     = $day_date === $event['start_date'];
						$is_end       = $day_date === $event['end_date'];
						$event_class  = 'aso-calendar__event';
						if ( $is_multiday ) {
							$event_class .= ' is-multiday';
							if ( $is_start ) {
								$event_class .= ' is-range-start';
							} elseif ( $is_end ) {
								$event_class .= ' is-range-end';
							} else {
								$event_class .= ' is-range-middle';
							}
						}
						?>
						<a class="<?php echo esc_attr( $event_class ); ?>" href="<?php echo esc_url( $event['url'] ); ?>" data-event-id="<?php echo esc_attr( $event['id'] ); ?>">
							<?php if ( $event['start_time'] && ( ! $is_multiday || $is_start ) ) : ?>
								<span><?php echo esc_html( $event['start_time'] ); ?></span>
							<?php endif; ?>
							<span class="aso-calendar__event-title<?php echo $is_multiday && ! $is_start ? ' is-continuation' : ''; ?>">
								<?php echo esc_html( $event['title'] ); ?>
							</span>
						</a>
					<?php endforeach; ?>
					<?php if ( count( $day_events ) > 3 ) : ?>
						<details class="aso-calendar__more">
							<summary>+<?php echo esc_html( count( $day_events ) - 3 ); ?> <?php esc_html_e( 'etkinlik', 'asosyoloji-weekly' ); ?></summary>
							<div class="aso-calendar__more-list">
								<?php foreach ( array_slice( $day_events, 3 ) as $event ) : ?>
									<a href="<?php echo esc_url( $event['url'] ); ?>"><?php echo esc_html( $event['title'] ); ?></a>
								<?php endforeach; ?>
							</div>
						</details>
					<?php endif; ?>
				</div>
			<?php endfor; ?>
		</div>

		<div class="aso-calendar__mobile-list" aria-label="<?php esc_attr_e( 'Bu ayın etkinlikleri', 'asosyoloji-weekly' ); ?>">
			<?php if ( $events ) : ?>
				<?php foreach ( $events as $event ) : ?>
					<?php $is_multiday = $event['end_date'] && $event['end_date'] !== $event['start_date']; ?>
					<article class="aso-calendar-mobile-event">
						<time class="aso-calendar-mobile-event__date" datetime="<?php echo esc_attr( $event['start_date'] ); ?>">
							<?php echo esc_html( $is_multiday ? asosyoloji_weekly_format_range( $event['start_date'], $event['end_date'] ) : wp_date( 'd M', strtotime( $event['start_date'] ) ) ); ?>
						</time>
						<div>
							<h3><a href="<?php echo esc_url( $event['url'] ); ?>"><?php echo esc_html( $event['title'] ); ?></a></h3>
							<div class="aso-calendar-mobile-event__meta">
								<?php echo esc_html( asosyoloji_weekly_time_label( $event ) ); ?>
								<?php if ( $event['venue'] || $event['city'] ) : ?>
									<span aria-hidden="true"> · </span><?php echo esc_html( implode( ', ', array_filter( array( $event['venue'], $event['city'] ) ) ) ); ?>
								<?php endif; ?>
							</div>
						</div>
					</article>
				<?php endforeach; ?>
			<?php else : ?>
				<p class="aso-weekly__empty"><?php esc_html_e( 'Bu ay için etkinlik bulunmuyor.', 'asosyoloji-weekly' ); ?></p>
			<?php endif; ?>
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

	if ( 'upcoming' === $atts['mode'] ) {
		return asosyoloji_weekly_render_list(
			array(
				'title'   => sanitize_text_field( $atts['title'] ),
				'mode'    => 'upcoming',
				'count'   => absint( $atts['count'] ),
				'city'    => sanitize_text_field( $atts['city'] ),
				'compact' => '1' === (string) $atts['compact'],
			)
		);
	}

	return asosyoloji_weekly_render_switcher(
		array(
			'title'   => sanitize_text_field( $atts['title'] ),
			'count'   => absint( $atts['count'] ),
			'city'    => sanitize_text_field( $atts['city'] ),
			'compact' => '1' === (string) $atts['compact'],
			'active'  => 'month' === $atts['mode'] ? 'month' : 'week',
		)
	);
}
add_shortcode( 'asosyoloji_haftalik', 'asosyoloji_weekly_shortcode' );

/**
 * Render the public calendar browser with date-state and taxonomy filters.
 *
 * @param array $args Browser settings.
 * @return string
 */
function asosyoloji_weekly_render_browser( $args = array() ) {
	$defaults = array(
		'title' => __( 'Etkinlik Takvimi', 'asosyoloji-weekly' ),
		'year'  => (int) current_time( 'Y' ),
		'month' => (int) current_time( 'm' ),
	);
	$args = wp_parse_args( $args, $defaults );

	$scope = isset( $_GET['aso_event_scope'] ) ? sanitize_key( wp_unslash( $_GET['aso_event_scope'] ) ) : 'calendar';
	$scope = in_array( $scope, array( 'calendar', 'today', 'upcoming', 'past' ), true ) ? $scope : 'calendar';
	$event_type = isset( $_GET['aso_event_type'] ) ? absint( $_GET['aso_event_type'] ) : 0;
	$venue = isset( $_GET['aso_event_venue'] ) ? absint( $_GET['aso_event_venue'] ) : 0;
	$types = get_terms( array( 'taxonomy' => 'em_event_type', 'hide_empty' => true ) );
	$venues = get_terms( array( 'taxonomy' => 'em_venue', 'hide_empty' => true ) );
	$types = is_wp_error( $types ) ? array() : $types;
	$venues = is_wp_error( $venues ) ? array() : $venues;
	$base_url = remove_query_arg( array( 'aso_event_scope', 'aso_event_type', 'aso_event_venue', 'aso_calendar_month' ) );
	$today = asosyoloji_weekly_today();

	$tabs = array(
		'calendar' => __( 'Takvim', 'asosyoloji-weekly' ),
		'today' => __( 'Bugün', 'asosyoloji-weekly' ),
		'upcoming' => __( 'Yaklaşan', 'asosyoloji-weekly' ),
		'past' => __( 'Geçmiş', 'asosyoloji-weekly' ),
	);

	ob_start();
	?>
	<section class="aso-event-browser" data-aso-event-browser>
		<nav class="aso-event-browser__tabs" aria-label="<?php esc_attr_e( 'Etkinlik dönemi', 'asosyoloji-weekly' ); ?>">
			<?php foreach ( $tabs as $tab_scope => $tab_label ) : ?>
				<?php
				$tab_url = add_query_arg(
					array_filter(
						array(
							'aso_event_scope' => $tab_scope,
							'aso_event_type'  => $event_type ?: null,
							'aso_event_venue' => $venue ?: null,
						)
					),
					$base_url
				);
				?>
				<a href="<?php echo esc_url( $tab_url ); ?>"<?php echo $scope === $tab_scope ? ' class="is-active" aria-current="page"' : ''; ?>><?php echo esc_html( $tab_label ); ?></a>
			<?php endforeach; ?>
		</nav>

		<form class="aso-event-browser__filters" method="get" action="<?php echo esc_url( $base_url ); ?>">
			<input type="hidden" name="aso_event_scope" value="<?php echo esc_attr( $scope ); ?>">
			<label>
				<span><?php esc_html_e( 'Etkinlik türü', 'asosyoloji-weekly' ); ?></span>
				<select name="aso_event_type">
					<option value="0"><?php esc_html_e( 'Tüm türler', 'asosyoloji-weekly' ); ?></option>
					<?php foreach ( $types as $term ) : ?>
						<option value="<?php echo esc_attr( $term->term_id ); ?>" <?php selected( $event_type, $term->term_id ); ?>><?php echo esc_html( $term->name ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<label>
				<span><?php esc_html_e( 'Mekân', 'asosyoloji-weekly' ); ?></span>
				<select name="aso_event_venue">
					<option value="0"><?php esc_html_e( 'Tüm mekânlar', 'asosyoloji-weekly' ); ?></option>
					<?php foreach ( $venues as $term ) : ?>
						<option value="<?php echo esc_attr( $term->term_id ); ?>" <?php selected( $venue, $term->term_id ); ?>><?php echo esc_html( $term->name ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<button type="submit"><?php esc_html_e( 'Filtrele', 'asosyoloji-weekly' ); ?></button>
			<?php if ( $event_type || $venue ) : ?>
				<a class="aso-event-browser__clear" href="<?php echo esc_url( add_query_arg( 'aso_event_scope', $scope, $base_url ) ); ?>"><?php esc_html_e( 'Temizle', 'asosyoloji-weekly' ); ?></a>
			<?php endif; ?>
		</form>

		<div class="aso-event-browser__results">
			<?php if ( 'calendar' === $scope ) : ?>
				<?php echo wp_kses_post( asosyoloji_weekly_render_calendar( array( 'title' => $args['title'], 'year' => $args['year'], 'month' => $args['month'], 'event_type' => $event_type, 'venue' => $venue ) ) ); ?>
			<?php elseif ( 'today' === $scope ) : ?>
				<?php echo wp_kses_post( asosyoloji_weekly_render_list( array( 'title' => __( 'Bugünün etkinlikleri', 'asosyoloji-weekly' ), 'mode' => 'range', 'start' => $today, 'end' => $today, 'count' => -1, 'event_type' => $event_type, 'venue' => $venue, 'kicker' => __( 'Bugün', 'asosyoloji-weekly' ), 'empty_text' => __( 'Bugün için etkinlik bulunmuyor.', 'asosyoloji-weekly' ) ) ) ); ?>
			<?php elseif ( 'upcoming' === $scope ) : ?>
				<?php echo wp_kses_post( asosyoloji_weekly_render_list( array( 'title' => __( 'Yaklaşan etkinlikler', 'asosyoloji-weekly' ), 'mode' => 'range', 'start' => $today, 'end' => wp_date( 'Y-m-d', strtotime( '+2 years', strtotime( $today ) ) ), 'count' => 40, 'event_type' => $event_type, 'venue' => $venue, 'kicker' => __( 'Yaklaşan', 'asosyoloji-weekly' ), 'empty_text' => __( 'Yaklaşan etkinlik bulunmuyor.', 'asosyoloji-weekly' ) ) ) ); ?>
			<?php else : ?>
				<?php echo wp_kses_post( asosyoloji_weekly_render_list( array( 'title' => __( 'Geçmiş etkinlikler', 'asosyoloji-weekly' ), 'mode' => 'range', 'start' => '2000-01-01', 'end' => wp_date( 'Y-m-d', strtotime( '-1 day', strtotime( $today ) ) ), 'count' => 40, 'event_type' => $event_type, 'venue' => $venue, 'order' => 'DESC', 'kicker' => __( 'Arşiv', 'asosyoloji-weekly' ), 'empty_text' => __( 'Geçmiş etkinlik bulunmuyor.', 'asosyoloji-weekly' ) ) ) ); ?>
			<?php endif; ?>
		</div>
	</section>
	<?php
	return (string) ob_get_clean();
}

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

	return asosyoloji_weekly_render_browser(
		array(
			'title' => sanitize_text_field( $atts['title'] ),
			'year'  => absint( $atts['year'] ),
			'month' => absint( $atts['month'] ),
		)
	);
}
add_shortcode( 'asosyoloji_takvim', 'asosyoloji_calendar_shortcode' );


function asosyoloji_weekly_render_switcher( $args = array() ) {
	$defaults = array(
		'title'   => __( 'Etkinlik Takvimi', 'asosyoloji-weekly' ),
		'count'   => 10,
		'city'    => '',
		'compact' => false,
		'active'  => 'month',
	);

	$args      = wp_parse_args( $args, $defaults );
	$active    = 'month' === $args['active'] ? 'month' : 'week';
	$widget_id = wp_unique_id( 'aso-weekly-switcher-' );

	ob_start();
	?>
	<section class="aso-weekly-switcher" id="<?php echo esc_attr( $widget_id ); ?>" data-aso-calendar-switcher>
		<div class="aso-weekly-switcher__bar" role="tablist" aria-label="<?php esc_attr_e( 'Takvim görünümü', 'asosyoloji-weekly' ); ?>">
			<button type="button" class="aso-weekly-switcher__tab<?php echo 'week' === $active ? ' is-active' : ''; ?>" role="tab" aria-selected="<?php echo 'week' === $active ? 'true' : 'false'; ?>" aria-controls="<?php echo esc_attr( $widget_id ); ?>-week" data-aso-calendar-tab="week">
				<?php esc_html_e( 'Haftalık', 'asosyoloji-weekly' ); ?>
			</button>
			<button type="button" class="aso-weekly-switcher__tab<?php echo 'month' === $active ? ' is-active' : ''; ?>" role="tab" aria-selected="<?php echo 'month' === $active ? 'true' : 'false'; ?>" aria-controls="<?php echo esc_attr( $widget_id ); ?>-month" data-aso-calendar-tab="month">
				<?php esc_html_e( 'Aylık', 'asosyoloji-weekly' ); ?>
			</button>
		</div>

		<div id="<?php echo esc_attr( $widget_id ); ?>-week" class="aso-weekly-switcher__panel<?php echo 'week' === $active ? ' is-active' : ''; ?>" role="tabpanel" <?php echo 'week' === $active ? '' : 'hidden'; ?> data-aso-calendar-panel="week">
			<?php echo wp_kses_post( asosyoloji_weekly_render_list( array(
				'title'   => $args['title'],
				'mode'    => 'week',
				'count'   => $args['count'],
				'city'    => $args['city'],
				'compact' => $args['compact'],
			) ) ); ?>
		</div>

		<div id="<?php echo esc_attr( $widget_id ); ?>-month" class="aso-weekly-switcher__panel<?php echo 'month' === $active ? ' is-active' : ''; ?>" role="tabpanel" <?php echo 'month' === $active ? '' : 'hidden'; ?> data-aso-calendar-panel="month">
			<?php echo wp_kses_post( asosyoloji_weekly_render_calendar( array( 'title' => $args['title'] ) ) ); ?>
		</div>
	</section>
	<?php
	return (string) ob_get_clean();
}
