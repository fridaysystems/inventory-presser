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

// Price Filters
class Inventory_Presser_Maximum_Price_Filter extends WP_Widget {

	const ID_BASE = '_invp_price_filters';

	var $price_defaults = array( 5000,10000,15000,20000 );
	var $display_types = array(
		'buttons' => 'Buttons',
		'text'    => 'Text',
	);
	var $orientations = array(
		'horizontal' => 'Horizontal',
		'vertical'   => 'Vertical',
	);

	function __construct() {
		parent::__construct(
			self::ID_BASE,
			__( 'Maximum Price Filter', 'inventory-presser' ),
			array( 'description' => __( 'Filter vehicles by a maximum price.', 'inventory-presser' ), )
		);

		add_action( 'invp_delete_all_data', array( $this, 'delete_option' ) );
	}

	public function delete_option() {
		delete_option( 'widget_' . self::ID_BASE );
	}

	// front-end
	public function widget( $args, $instance ) {

		$reset_link_only = (isset($instance['cb_reset_link_only']) && $instance['cb_reset_link_only'] == 'true');

		if ($reset_link_only && !isset($_GET['max_price']))
			return;

		echo $args['before_widget'];

		$title = apply_filters( 'widget_title', $instance['title'] );

		printf( '<div class="price-filter price-filter-%s">', $instance['orientation'] );
		if (!empty( $title )) {
			echo '<div class="price-title">'.$args['before_title'] . $title . $args['after_title'].'</div>';
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
					'<div><a href="%s"%s><span class="dashicons dashicons-arrow-down-alt"></span>&nbsp;&nbsp;$%s</a></div>',
					$this_link,
					$class_string,
					number_format( $price_point, 0, '.', ',' )
				);
			}
		}

		if ( isset( $_GET['max_price'] ) ) {
			printf(
				'<div><a href="%s">Remove $%s Price Filter</a></div>',
				remove_query_arg('max_price'),
				number_format( (int) $_GET['max_price'], 0, '.', ',' )
			);
		}

		echo '</div>' . $args['after_widget'];
	}

	// Widget Backend
	public function form( $instance ) {

		$title = isset($instance['title']) ? $instance[ 'title' ] : __( 'Price Filter', 'inventory-presser' );
		$prices = (isset($instance['prices']) && is_array($instance['prices'])) ? implode(',', $instance['prices']) : implode(',', $this->price_defaults);
		$display_type_slugs = array_keys($this->display_types);
		$display_type = isset($instance['display_type']) ? $instance[ 'display_type' ] : $display_type_slugs[0];
		$orientation_slugs = array_keys($this->orientations);
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
			foreach ($this->display_types as $key => $label) {
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
			foreach ($this->orientations as $key => $label) {
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

	// Updating widget replacing old instances with new
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
