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
			__( 'Asosyoloji: Haftalık Etkinlikler', 'asosyoloji-weekly' ),
			array(
				'description' => __( 'Bu haftanın veya yaklaşan etkinliklerin kompakt listesini gösterir.', 'asosyoloji-weekly' ),
			)
		);
	}

	public function widget( $args, $instance ) {
		echo wp_kses_post( $args['before_widget'] );
		echo wp_kses_post(
			asosyoloji_weekly_render_list(
				array(
					'title'   => $instance['title'] ?? __( 'Haftalık', 'asosyoloji-weekly' ),
					'mode'    => ( $instance['mode'] ?? 'week' ) === 'upcoming' ? 'upcoming' : 'week',
					'count'   => absint( $instance['count'] ?? 7 ),
					'city'    => sanitize_text_field( $instance['city'] ?? '' ),
					'compact' => true,
				)
			)
		);
		echo wp_kses_post( $args['after_widget'] );
	}

	public function update( $new_instance, $old_instance ) {
		unset( $old_instance );

		return array(
			'title' => sanitize_text_field( $new_instance['title'] ?? '' ),
			'mode'  => ( $new_instance['mode'] ?? 'week' ) === 'upcoming' ? 'upcoming' : 'week',
			'count' => min( 20, max( 1, absint( $new_instance['count'] ?? 7 ) ) ),
			'city'  => sanitize_text_field( $new_instance['city'] ?? '' ),
		);
	}

	public function form( $instance ) {
		$title = $instance['title'] ?? __( 'Haftalık', 'asosyoloji-weekly' );
		$mode  = $instance['mode'] ?? 'week';
		$count = absint( $instance['count'] ?? 7 );
		$city  = $instance['city'] ?? '';
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Başlık', 'asosyoloji-weekly' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'mode' ) ); ?>"><?php esc_html_e( 'Gösterim', 'asosyoloji-weekly' ); ?></label>
			<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'mode' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'mode' ) ); ?>">
				<option value="week" <?php selected( $mode, 'week' ); ?>><?php esc_html_e( 'Bu hafta', 'asosyoloji-weekly' ); ?></option>
				<option value="upcoming" <?php selected( $mode, 'upcoming' ); ?>><?php esc_html_e( 'Yaklaşan etkinlikler', 'asosyoloji-weekly' ); ?></option>
			</select>
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'count' ) ); ?>"><?php esc_html_e( 'Etkinlik sayısı', 'asosyoloji-weekly' ); ?></label>
			<input class="tiny-text" id="<?php echo esc_attr( $this->get_field_id( 'count' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'count' ) ); ?>" type="number" min="1" max="20" value="<?php echo esc_attr( $count ); ?>">
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'city' ) ); ?>"><?php esc_html_e( 'Şehir filtresi', 'asosyoloji-weekly' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'city' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'city' ) ); ?>" type="text" value="<?php echo esc_attr( $city ); ?>">
		</p>
		<?php
	}
}

function asosyoloji_weekly_register_widget() {
	register_widget( 'Asosyoloji_Weekly_Widget' );
}
add_action( 'widgets_init', 'asosyoloji_weekly_register_widget' );
