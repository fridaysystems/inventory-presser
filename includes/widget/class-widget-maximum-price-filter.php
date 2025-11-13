<?php
defined( 'ABSPATH' ) || exit;

/**
 * Inventory_Presser_Maximum_Price_Filter
 *
 * This class creates the Maximum Price Filter widget.
 *
 * @since      1.3.1
 * @package inventory-presser
 * @subpackage inventory-presser/includes
 * @author     Corey Salzano <corey@friday.systems>, John Norton <norton@fridaynet.com>
 */
class Inventory_Presser_Maximum_Price_Filter extends WP_Widget {


	const ID_BASE = '_invp_price_filters';

	/**
	 * Default price tier values
	 *
	 * @var array
	 */
	protected $price_defaults = array( 40000, 30000, 20000, 15000, 10000 );

	/**
	 * Calls the parent class' contructor and adds a hook that will delete the
	 * option that stores this widget's data when the plugin's delete all data
	 * method is run.
	 *
	 * @return void
	 */
	public function __construct() {
		parent::__construct(
			self::ID_BASE,
			__( 'Maximum Price Filter', 'inventory-presser' ),
			array(
				'description'           => __( 'Filter vehicles by a maximum price.', 'inventory-presser' ),
				'show_instance_in_rest' => true,
			)
		);

		add_action( 'invp_delete_all_data', array( $this, 'delete_option' ) );
	}

	/**
	 * Deletes the option that stores this widget's data.
	 *
	 * @return void
	 */
	public function delete_option() {
		delete_option( 'widget_' . self::ID_BASE );
	}

	/**
	 * display_types
	 *
	 * @return array An associative array of display type choices, including
	 * buttons or text.
	 */
	protected function display_types() {
		return array(
			'buttons' => __( 'Buttons', 'inventory-presser' ),
			'text'    => __( 'Text', 'inventory-presser' ),
		);
	}

	/**
	 * orientations
	 *
	 * @return array An associative array of display orientations, including
	 * horizontal or vertical.
	 */
	protected function orientations() {
		return array(
			'horizontal' => __( 'Horizontal', 'inventory-presser' ),
			'vertical'   => __( 'Vertical', 'inventory-presser' ),
		);
	}

	/**
	 * Outputs the widget front-end HTML
	 *
	 * @param  array $args
	 * @param  array $instance
	 * @return void
	 */
	public function widget( $args, $instance ) {

		// Need the stylesheet for this content.
		wp_enqueue_style( 'invp-maximum-price-filters' );

		$reset_link_only = ( isset( $instance['cb_reset_link_only'] ) && 'true' === $instance['cb_reset_link_only'] );

		if ( $reset_link_only && ! isset( $_GET['max_price'] ) ) {
			return;
		}

		echo wp_kses_post( $args['before_widget'] ?? '' );

		$title = apply_filters( 'widget_title', $instance['title'] ?? '' );

		printf( '<div class="price-filter price-filter-%s">', esc_attr( $instance['orientation'] ?? '' ) );
		if ( ! empty( $title ) ) {
			printf(
				'<div class="price-title">%s%s%s</div>',
				wp_kses_post( $args['before_title'] ?? '' ),
				esc_html( $title ),
				wp_kses_post( $args['after_title'] ?? '' )
			);
		}

		if ( ! $reset_link_only ) {

			$price_points = ( isset( $instance['prices'] ) && is_array( $instance['prices'] ) ) ? $instance['prices'] : $this->price_defaults;

			$base_link = add_query_arg(
				array(
					'orderby' => apply_filters( 'invp_prefix_meta_key', 'price' ),
					'order'   => 'DESC',
				),
				get_post_type_archive_link( INVP::POST_TYPE )
			);

			$class_string = ( 'buttons' === ( $instance['display_type'] ?? '' ) ) ? '_button _button-med btn' : 'price-filter-text';

			foreach ( $price_points as $price_point ) {
				$this_link = add_query_arg( 'max_price', $price_point, $base_link );
				printf(
					'<div><a href="%s" class="%s"><span class="dashicons dashicons-arrow-down-alt"></span>&nbsp;$%s</a></div>',
					esc_url( $this_link ),
					esc_attr( $class_string ),
					esc_html( number_format( $price_point, 0, '.', ',' ) )
				);
			}
		}

		if ( isset( $_GET['max_price'] ) ) {
			printf(
				'<div><a href="%s">%s $%s %s</a></div>',
				esc_url( remove_query_arg( 'max_price' ) ),
				esc_html__( 'Remove', 'inventory-presser' ),
				number_format( (int) $_GET['max_price'], 0, '.', ',' ),
				esc_html__( 'Shop by Price', 'inventory-presser' )
			);
		}

		echo '</div>' . wp_kses_post( $args['after_widget'] ?? '' );
	}

	/**
	 * Outputs the widget settings form that is shown in the dashboard.
	 *
	 * @param  array $instance
	 * @return void
	 */
	public function form( $instance ) {

		$title              = isset( $instance['title'] ) ? $instance['title'] : __( 'Shop by Price', 'inventory-presser' );
		$prices             = ( isset( $instance['prices'] ) && is_array( $instance['prices'] ) ) ? implode( ',', $instance['prices'] ) : implode( ',', $this->price_defaults );
		$display_type_slugs = array_keys( $this->display_types() );
		$display_type       = isset( $instance['display_type'] ) ? $instance['display_type'] : $display_type_slugs[0];
		$orientation_slugs  = array_keys( $this->orientations() );
		$orientation        = isset( $instance['orientation'] ) ? $instance['orientation'] : $orientation_slugs[0];

		// Widget admin form.
		?>
		<p>
		<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'inventory-presser' ); ?></label>
		<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<p>
		<label for="<?php echo esc_attr( $this->get_field_id( 'prices' ) ); ?>"><?php esc_html_e( 'Price Points (separated by commas)', 'inventory-presser' ); ?></label>
		<textarea class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'prices' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'prices' ) ); ?>"><?php echo esc_html( $prices ); ?></textarea>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'display_type' ) ); ?>"><?php esc_html_e( 'Display Format:', 'inventory-presser' ); ?></label>
			<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'display_type' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'display_type' ) ); ?>">
		<?php
		foreach ( $this->display_types() as $key => $label ) {
			printf(
				'<option value="%s"%s>%s</option>',
				esc_attr( $key ),
				selected( $display_type === $key, true, false ),
				esc_html( $label )
			);
		}
		?>
			</select>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'orientation' ) ); ?>"><?php esc_html_e( 'Orientation:', 'inventory-presser' ); ?></label>
			<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'orientation' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'orientation' ) ); ?>">
		<?php
		foreach ( $this->orientations() as $key => $label ) {
			printf(
				'<option value="%s"%s>%s</option>',
				esc_attr( $key ),
				selected( $orientation == $key, true, false ),
				esc_html( $label )
			);
		}
		?>
			</select>
		</p>
		<p>
		<label for="<?php echo esc_attr( $this->get_field_id( 'cb_reset_link_only' ) ); ?>"><?php esc_html_e( 'Show Reset Link Only', 'inventory-presser' ); ?></label>
		<input type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'cb_reset_link_only' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'cb_reset_link_only' ) ); ?>" value="true"<?php checked( 'true', ( isset( $instance['cb_reset_link_only'] ) ? $instance['cb_reset_link_only'] : '' ) ); ?>>
		</p>
		<?php
	}

	/**
	 * Saves the widget settings when a dashboard user clicks the Save button.
	 *
	 * @param  array $new_instance
	 * @param  array $old_instance
	 * @return array The updated array full of settings
	 */
	public function update( $new_instance, $old_instance ) {
		$instance          = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? wp_strip_all_tags( $new_instance['title'] ) : '';
		if ( is_string( $new_instance['prices'] ) ) {
			$new_instance['prices'] = explode( ',', $new_instance['prices'] );
		}
		$instance['prices']             = ( ! empty( $new_instance['prices'] ) ) ? array_map( 'intval', $new_instance['prices'] ) : $this->price_defaults;
		$instance['display_type']       = ( ! empty( $new_instance['display_type'] ) ) ? wp_strip_all_tags( $new_instance['display_type'] ) : '';
		$instance['orientation']        = ( ! empty( $new_instance['orientation'] ) ) ? wp_strip_all_tags( $new_instance['orientation'] ) : '';
		$instance['cb_reset_link_only'] = ( ! empty( $new_instance['cb_reset_link_only'] ) ) ? $new_instance['cb_reset_link_only'] : '';
		return $instance;
	}
}
