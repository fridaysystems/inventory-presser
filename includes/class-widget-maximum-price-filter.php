<?php
defined( 'ABSPATH' ) or exit;

/**
 * Various classes to deal with location taxonomy widgets and checks
 *
 *
 * @since      1.3.1
 * @package    Inventory_Presser
 * @subpackage Inventory_Presser/includes
 * @author     Corey Salzano <corey@friday.systems>, John Norton <norton@fridaynet.com>
 */

/**
 * Inventory_Presser_Maximum_Price_Filter
 * 
 * This class creates the Maximum Price Filter widget.
 */
class Inventory_Presser_Maximum_Price_Filter extends WP_Widget {

	const ID_BASE = '_invp_price_filters';

	var $price_defaults = array( 5000,10000,15000,20000 );

	/**
	 * __construct
	 *
	 * Calls the parent class' contructor and adds a hook that will delete the
	 * option that stores this widget's data when the plugin's delete all data
	 * method is run.
	 * 
	 * @return void
	 */
	function __construct() {
		parent::__construct(
			self::ID_BASE,
			__( 'Maximum Price Filter', 'inventory-presser' ),
			array( 'description' => __( 'Filter vehicles by a maximum price.', 'inventory-presser' ), )
		);

		add_action( 'invp_delete_all_data', array( $this, 'delete_option' ) );
	}

	/**
	 * delete_option
	 * 
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
	function display_types() {
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
	function orientations() {
		return array(
			'horizontal' => __( 'Horizontal', 'inventory-presser' ),
			'vertical'   => __( 'Vertical', 'inventory-presser' ),
		);
	}

	/**
	 * widget
	 * 
	 * Outputs the widget front-end HTML
	 *
	 * @param  array $args
	 * @param  array $instance
	 * @return void
	 */
	public function widget( $args, $instance ) {

		//Need the stylesheet for this content
		wp_enqueue_style( 'invp-maximum-price-filters' );

		$reset_link_only = (isset($instance['cb_reset_link_only']) && $instance['cb_reset_link_only'] == 'true');

		if ($reset_link_only && !isset($_GET['max_price']))
			return;

		echo $args['before_widget'];

		$title = apply_filters( 'widget_title', $instance['title'] );

		printf( '<div class="price-filter price-filter-%s">', $instance['orientation'] );
		if( ! empty( $title ) ) {
			printf(
				'<div class="price-title">%s%s%s</div>',
				$args['before_title'],
				$title,
				$args['after_title']
			);
		}

		if (!$reset_link_only) {

			$price_points = (isset($instance['prices']) && is_array($instance['prices'])) ? $instance['prices'] : $this->price_defaults;

			$base_link = add_query_arg( array(
			    'orderby' => apply_filters( 'invp_prefix_meta_key', 'price' ),
			    'order'   => 'DESC',
			), get_post_type_archive_link( Inventory_Presser_Plugin::CUSTOM_POST_TYPE ) );

			$class_string = ($instance['display_type'] == 'buttons') ? ' class="_button _button-med"' : ' class="price-filter-text"';

			foreach ($price_points as $price_point) {
				$this_link = add_query_arg( 'max_price', $price_point, $base_link);
				printf(
					'<div><a href="%s"%s><span class="dashicons dashicons-arrow-down-alt"></span>&nbsp;$%s</a></div>',
					$this_link,
					$class_string,
					number_format( $price_point, 0, '.', ',' )
				);
			}
		}

		if ( isset( $_GET['max_price'] ) ) {
			printf(
				'<div><a href="%s">%s $%s %s</a></div>',
				remove_query_arg('max_price'),
				__( 'Remove', 'inventory-presser' ),
				number_format( (int) $_GET['max_price'], 0, '.', ',' ),
				__( 'Price Filter', 'inventory-presser' )
			);
		}

		echo '</div>' . $args['after_widget'];
	}

	/**
	 * form
	 * 
	 * Outputs the widget settings form that is shown in the dashboard.
	 *
	 * @param  array $instance
	 * @return void
	 */
	public function form( $instance ) {

		$title = isset($instance['title']) ? $instance[ 'title' ] : __( 'Price Filter', 'inventory-presser' );
		$prices = (isset($instance['prices']) && is_array($instance['prices'])) ? implode(',', $instance['prices']) : implode(',', $this->price_defaults);
		$display_type_slugs = array_keys($this->display_types());
		$display_type = isset($instance['display_type']) ? $instance[ 'display_type' ] : $display_type_slugs[0];
		$orientation_slugs = array_keys($this->orientations());
		$orientation = isset($instance['orientation']) ? $instance[ 'orientation' ] : $orientation_slugs[0];

		// Widget admin form
		?>
		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'inventory-presser' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<p>
		<label for="<?php echo $this->get_field_id('prices'); ?>"><?php _e( 'Price Points (separated by commas)', 'inventory-presser' ); ?></label>
		<textarea class="widefat" id="<?php echo $this->get_field_id('prices'); ?>" name="<?php echo $this->get_field_name('prices'); ?>"><?php echo esc_attr( $prices ); ?></textarea>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('display_type'); ?>"><?php _e( 'Display Format:', 'inventory-presser' ); ?></label>
			<select class="widefat" id="<?php echo $this->get_field_id('display_type'); ?>" name="<?php echo $this->get_field_name('display_type'); ?>">
			<?php
			foreach ($this->display_types() as $key => $label) {
				printf(
					'<option value="%s"%s>%s</option>',
					$key,
					selected( $display_type == $key, true, false ),
					$label
				);
			}
			?>
			</select>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('orientation'); ?>"><?php _e( 'Orientation:', 'inventory-presser' ); ?></label>
			<select class="widefat" id="<?php echo $this->get_field_id('orientation'); ?>" name="<?php echo $this->get_field_name('orientation'); ?>">
			<?php
			foreach ($this->orientations() as $key => $label) {
				printf(
					'<option value="%s"%s>%s</option>',
					$key,
					selected( $orientation == $key, true, false ),
					$label
				);
			}
			?>
			</select>
		</p>
		<p>
		<label for="<?php echo $this->get_field_id('cb_reset_link_only'); ?>"><?php _e( 'Show Reset Link Only', 'inventory-presser' ); ?></label>
		<input type="checkbox" id="<?php echo $this->get_field_id('cb_reset_link_only'); ?>" name="<?php echo $this->get_field_name('cb_reset_link_only'); ?>" value="true"<?php checked( 'true', ( isset( $instance[ 'cb_reset_link_only' ] ) ? $instance['cb_reset_link_only'] : '' ) ); ?>>
		</p>
		<?php
	}

	/**
	 * update
	 *
	 * Saves the widget settings when a dashboard user clicks the Save button.
	 * 
	 * @param  array $new_instance
	 * @param  array $old_instance
	 * @return array The updated array full of settings
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( !empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['prices'] = (!empty($new_instance['prices'])) ? array_map('intval', explode(',', $new_instance['prices'])) : $this->price_defaults;
		$instance['display_type'] = ( !empty( $new_instance['display_type'] ) ) ? strip_tags( $new_instance['display_type'] ) : '';
		$instance['orientation'] = ( !empty( $new_instance['orientation'] ) ) ? strip_tags( $new_instance['orientation'] ) : '';
		$instance['cb_reset_link_only'] = ( !empty( $new_instance['cb_reset_link_only'] ) ) ? $new_instance['cb_reset_link_only'] : '';
		return $instance;
	}

}
