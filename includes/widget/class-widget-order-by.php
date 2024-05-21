<?php
defined( 'ABSPATH' ) || exit;

/**
 * Inventory_Presser_Order_By_Widget
 *
 * Creates a widget that allows users to sort vehicles by a vehicle attribute.
 *
 * If a menu item of type "Custom Link" exists with "Email a Friend" set as the
 * "Navigation Label", this class will change the URL to a mailto: link
 * containing vehicle information so the vehicle can be sent to a friend via
 * email.
 *
 * @since      3.8.0
 * @package inventory-presser
 * @subpackage inventory-presser/includes
 * @author     Corey Salzano <corey@friday.systems>
 */
class Inventory_Presser_Order_By_Widget extends WP_Widget {


	const ID_BASE = '_invp_order_by';

	/**
	 * __construct
	 *
	 * Calls the parent class' contructor and adds a hook that will delete the
	 * option that stores this widget's data when the plugin's delete all data
	 * method is run.
	 *
	 * @return void
	 */
	public function __construct() {
		parent::__construct(
			self::ID_BASE, // Base ID
			__( 'Sort by Vehicle Attributes', 'inventory-presser' ), // Name
			array(
				'description'           => __( 'A list of vehicle attributes by which users can sort listings.', 'inventory-presser' ),
				'show_instance_in_rest' => true,
			) // Args
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
	 * form
	 *
	 * Outputs the widget settings form that is shown in the dashboard.
	 *
	 * @param  array $instance The widget options
	 * @return void
	 */
	public function form( $instance ) {
		$title = ( isset( $instance['title'] ) ? $instance['title'] : '' );
		?>
		 <p>
		  <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'inventory-presser' ); ?></label>
		  <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" />
		</p>
		<?php
		$args                   = array(
			'public' => true,
		);
		$already_turned_on_keys = array();
		if ( isset( $instance['post-meta-keys'] ) ) {
			$already_turned_on_keys = explode( '|', $instance['post-meta-keys'] );
		}

		echo '<p>Which fields should users be allowed to use as sort fields?</p>'
		. '<dl>';

		foreach ( $this->get_post_meta_keys_and_labels( $instance ) as $key => $label ) {
			// output checkbox for each one
			echo '<dt>';
			$title = 'Allow users to order by ' . $key; // title attribute for checkbox and label
			echo '<input type="checkbox" id="' . $this->get_field_id( 'obpm-key-' . $key )
			. '" name="obpm-key-' . $key . '"';
			if ( in_array( $key, $already_turned_on_keys ) ) {
				echo ' checked="checked"';
			}
			echo ' title="' . $title . '"/>'
			. '<label for="' . $this->get_field_id( 'obpm-key-' . $key ) . '" title="' . $title . '">' . $this->prettify_meta_key( $key ) . '</label>'
			. '</dt>' // and a text box for a label
			. '<dd>'
			. '<label for="' . $this->get_field_id( 'obpm-label-' . $key ) . '">Label</label> '
			. '<input type="text" id="' . $this->get_field_id( 'obpm-label-' . $key ) . '"'
			. ' name="obpm-label-' . $key . '" '
			. 'value="' . $label . '" title="Label for ' . $key . '" />'
			. '</dd>';
		}
		echo '</dl>';
	}

	/**
	 * get_post_meta_keys_and_labels
	 *
	 * Produces an associative array where the post meta keys are the keys
	 * and the values are human-readable labels.
	 *
	 * @param  array $instance The widget options
	 * @return array
	 */
	function get_post_meta_keys_and_labels( $instance ) {
		$arr = array();
		foreach ( INVP::keys() as $key ) {
			$key = apply_filters( 'invp_prefix_meta_key', $key );
			// if we have a saved label, use that. otherwise, create a label
			$arr[ $key ] = ( isset( $instance[ 'label-' . $key ] ) ? $instance[ 'label-' . $key ] : $this->prettify_meta_key( $key ) );
		}
		/**
		 * Some fields do not make sense to order by, such as interior color & VIN
		 */
		$ignored_keys = array(
			apply_filters( 'invp_prefix_meta_key', 'body_style' ),
			apply_filters( 'invp_prefix_meta_key', 'car_id' ),
			apply_filters( 'invp_prefix_meta_key', 'color' ),
			apply_filters( 'invp_prefix_meta_key', 'dealer_id' ),
			apply_filters( 'invp_prefix_meta_key', 'edmunds_style_id' ),
			apply_filters( 'invp_prefix_meta_key', 'engine' ),
			apply_filters( 'invp_prefix_meta_key', 'featured' ),
			apply_filters( 'invp_prefix_meta_key', 'interior_color' ),
			apply_filters( 'invp_prefix_meta_key', 'leads_id' ),
			apply_filters( 'invp_prefix_meta_key', 'options_array' ),
			apply_filters( 'invp_prefix_meta_key', 'prices' ),
			apply_filters( 'invp_prefix_meta_key', 'trim' ),
			apply_filters( 'invp_prefix_meta_key', 'vin' ),
		);
		foreach ( apply_filters( 'invp_sort_by_widget_ignored_fields', $ignored_keys ) as $ignored_key ) {
			unset( $arr[ $ignored_key ] );
		}
		return $arr;
	}

	/**
	 * Crudely takes a post meta key, removes underscores, and converts the
	 * string to Title Case.
	 *
	 * @param  string $key
	 * @return string
	 */
	function prettify_meta_key( $key ) {
		return str_replace( '_', ' ', ucfirst( apply_filters( 'invp_unprefix_meta_key', $key ) ?? '' ) );
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
		$instance          = array();
		$instance['title'] = wp_strip_all_tags( $new_instance['title'] );
		$keys              = array();
		foreach ( INVP::keys() as $key ) {
			$key = apply_filters( 'invp_prefix_meta_key', $key );
			if ( isset( $_REQUEST[ 'obpm-key-' . $key ] ) ) {
				array_push( $keys, $key );
				if ( isset( $_REQUEST[ 'obpm-label-' . $key ] ) ) {
					$instance[ 'label-' . $key ] = wp_strip_all_tags( $_REQUEST[ 'obpm-label-' . $key ] );
				}
			} else {
				unset( $instance[ 'label-' . $key ] );
			}
		}
		$instance['post-meta-keys'] = implode( '|', $keys );
		return $instance;
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

		wp_enqueue_script( 'order-by-widget-javascript' );
		extract( $args );

		$title = apply_filters( 'widget_title', ( isset( $instance['title'] ) ? $instance['title'] : '' ) );

		$keys_to_list = explode( '|', $instance['post-meta-keys'] );
		if ( 0 < sizeof( $keys_to_list ) ) {
			echo $before_widget;
			if ( $title ) {
				echo $before_title . $title . $after_title;
			}
			echo '<ul class="order-by-list list-nostyle">';
			foreach ( $keys_to_list as $key ) {
				echo '<li><a href="javascript:order_by_post_meta(\'' . $key . '\');">'
				. ( isset( $instance[ 'label-' . $key ] ) ? $instance[ 'label-' . $key ] : $key )
				. '</a></li>';
			}
			echo '</ul>' . $after_widget;
		}
	}
}
