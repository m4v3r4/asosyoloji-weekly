<?php
/**
 * Weekly events widget.
 *
 * @package AsosyolojiWeekly
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Asosyoloji_Weekly_Widget extends WP_Widget {

	public function __construct() {
		parent::__construct(
			'asosyoloji_weekly_events',
			__( 'Asosyoloji: Haftalık / Aylık Takvim', 'asosyoloji-weekly' ),
			array(
				'description' => __( 'Etkinlikleri haftalık liste, aylık takvim veya iki görünüm arasında sekmeli olarak gösterir.', 'asosyoloji-weekly' ),
			)
		);
	}

	public function widget( $args, $instance ) {
		$title   = $instance['title'] ?? __( 'Haftalık', 'asosyoloji-weekly' );
		$view    = $instance['view'] ?? 'tabs';
		$count   = min( 30, max( 1, absint( $instance['count'] ?? 10 ) ) );
		$city    = sanitize_text_field( $instance['city'] ?? '' );
		$widget_id = wp_unique_id( 'aso-weekly-widget-' );

		echo wp_kses_post( $args['before_widget'] );

		if ( 'week' === $view ) {
			echo wp_kses_post(
				asosyoloji_weekly_render_list(
					array(
						'title'   => $title,
						'mode'    => 'week',
						'count'   => $count,
						'city'    => $city,
						'compact' => true,
					)
				)
			);
		} elseif ( 'month' === $view ) {
			echo wp_kses_post(
				asosyoloji_weekly_render_calendar(
					array(
						'title' => $title,
					)
				)
			);
		} else {
			?>
			<section class="aso-weekly-switcher" id="<?php echo esc_attr( $widget_id ); ?>" data-aso-calendar-switcher>
				<div class="aso-weekly-switcher__bar" role="tablist" aria-label="<?php esc_attr_e( 'Takvim görünümü', 'asosyoloji-weekly' ); ?>">
					<button
						type="button"
						class="aso-weekly-switcher__tab is-active"
						role="tab"
						aria-selected="true"
						aria-controls="<?php echo esc_attr( $widget_id ); ?>-week"
						data-aso-calendar-tab="week"
					>
						<?php esc_html_e( 'Haftalık', 'asosyoloji-weekly' ); ?>
					</button>
					<button
						type="button"
						class="aso-weekly-switcher__tab"
						role="tab"
						aria-selected="false"
						aria-controls="<?php echo esc_attr( $widget_id ); ?>-month"
						data-aso-calendar-tab="month"
					>
						<?php esc_html_e( 'Aylık', 'asosyoloji-weekly' ); ?>
					</button>
				</div>

				<div
					id="<?php echo esc_attr( $widget_id ); ?>-week"
					class="aso-weekly-switcher__panel is-active"
					role="tabpanel"
					data-aso-calendar-panel="week"
				>
					<?php
					echo wp_kses_post(
						asosyoloji_weekly_render_list(
							array(
								'title'   => $title,
								'mode'    => 'week',
								'count'   => $count,
								'city'    => $city,
								'compact' => true,
							)
						)
					);
					?>
				</div>

				<div
					id="<?php echo esc_attr( $widget_id ); ?>-month"
					class="aso-weekly-switcher__panel"
					role="tabpanel"
					hidden
					data-aso-calendar-panel="month"
				>
					<?php
					echo wp_kses_post(
						asosyoloji_weekly_render_calendar(
							array(
								'title' => $title,
							)
						)
					);
					?>
				</div>
			</section>
			<?php
		}

		echo wp_kses_post( $args['after_widget'] );
	}

	public function update( $new_instance, $old_instance ) {
		unset( $old_instance );

		$view = sanitize_key( $new_instance['view'] ?? 'tabs' );
		if ( ! in_array( $view, array( 'tabs', 'week', 'month' ), true ) ) {
			$view = 'tabs';
		}

		return array(
			'title' => sanitize_text_field( $new_instance['title'] ?? '' ),
			'view'  => $view,
			'count' => min( 30, max( 1, absint( $new_instance['count'] ?? 10 ) ) ),
			'city'  => sanitize_text_field( $new_instance['city'] ?? '' ),
		);
	}

	public function form( $instance ) {
		$title = $instance['title'] ?? __( 'Haftalık', 'asosyoloji-weekly' );
		$view  = $instance['view'] ?? 'tabs';
		$count = absint( $instance['count'] ?? 10 );
		$city  = $instance['city'] ?? '';
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Başlık', 'asosyoloji-weekly' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'view' ) ); ?>"><?php esc_html_e( 'Takvim görünümü', 'asosyoloji-weekly' ); ?></label>
			<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'view' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'view' ) ); ?>">
				<option value="tabs" <?php selected( $view, 'tabs' ); ?>><?php esc_html_e( 'Haftalık + Aylık (sekmeli)', 'asosyoloji-weekly' ); ?></option>
				<option value="week" <?php selected( $view, 'week' ); ?>><?php esc_html_e( 'Yalnız haftalık', 'asosyoloji-weekly' ); ?></option>
				<option value="month" <?php selected( $view, 'month' ); ?>><?php esc_html_e( 'Yalnız aylık', 'asosyoloji-weekly' ); ?></option>
			</select>
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'count' ) ); ?>"><?php esc_html_e( 'Haftalık görünümde azami etkinlik', 'asosyoloji-weekly' ); ?></label>
			<input class="tiny-text" id="<?php echo esc_attr( $this->get_field_id( 'count' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'count' ) ); ?>" type="number" min="1" max="30" value="<?php echo esc_attr( $count ); ?>">
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'city' ) ); ?>"><?php esc_html_e( 'Şehir filtresi', 'asosyoloji-weekly' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'city' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'city' ) ); ?>" type="text" value="<?php echo esc_attr( $city ); ?>">
			<small><?php esc_html_e( 'Boş bırakırsanız tüm şehirler gösterilir.', 'asosyoloji-weekly' ); ?></small>
		</p>
		<?php
	}
}

function asosyoloji_weekly_register_widget() {
	register_widget( 'Asosyoloji_Weekly_Widget' );
}
add_action( 'widgets_init', 'asosyoloji_weekly_register_widget' );
