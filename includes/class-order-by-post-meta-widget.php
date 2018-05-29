<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Creates a widget that allows users to sort vehicles by a vehicle attribute.
 *
 * If a menu item of type "Custom Link" exists with "Email a Friend" set as the
 * "Navigation Label", this class will change the URL to a mailto: link
 * containing vehicle information so the vehicle can be sent to a friend via
 * email.
 *
 *
 * @since      3.8.0
 * @package    Inventory_Presser
 * @subpackage Inventory_Presser/includes
 * @author     Corey Salzano <corey@friday.systems>
 */
class Order_By_Widget extends WP_Widget {

 	const ID_BASE = '_invp_order_by';

	/**
	 * Sets up the widgets name etc
	 */
 	public function __construct() {
 		parent::__construct(
 			self::ID_BASE, // Base ID
 			__( 'Sort by Vehicle Attributes', 'inventory-presser' ), // Name
 			array( 'description' => __( 'A list of vehicle attributes by which users can sort listings.', 'inventory-presser' ), ) // Args
 		);

		//include scripts if widget is used
		if( is_active_widget( false, false, self::ID_BASE ) ) {
 			add_action( 'wp_enqueue_scripts', array( $this, 'load_javascript' ) );
 		}

		add_action( 'invp_delete_all_data', array( $this, 'delete_option' ) );
	}

	public function delete_option() {
		delete_option( 'widget_' . self::ID_BASE );
	}

	/**
	 * Outputs the options form on admin
	 *
	 * @param array $instance The widget options
	 */
	public function form( $instance ) {
		$title = ( isset( $instance[ 'title' ] ) ? $instance[ 'title' ] : '' );
        ?>
         <p>
          <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
          <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
        </p>
        <?php
		$args = array(
			'public' => true,
		);
		$already_turned_on_keys = array();
		if( isset( $instance['post-meta-keys'] ) ) {
			$already_turned_on_keys = explode( '|', $instance['post-meta-keys'] );
		}

		echo '<p>Which fields should users be allowed to use as sort fields?</p>'
			. '<dl>';

		foreach( $this->get_post_meta_keys_and_labels( $instance ) as $key => $label ) {
			//output checkbox for each one
			echo '<dt>';
			$title = 'Allow users to order by ' . $key; //title attribute for checkbox and label
			echo '<input type="checkbox" id="' . $this->get_field_id('obpm-key-' . $key)
				. '" name="obpm-key-' . $key . '"';
			if( in_array( $key, $already_turned_on_keys ) ) {
				echo ' checked="checked"';
			}
			echo ' title="' . $title . '"/>'
				. '<label for="' . $this->get_field_id('obpm-key-' . $key) . '" title="' . $title . '">' . $this->prettify_meta_key( $key ) . '</label>'
				. '</dt>' //and a text box for a label
				. '<dd>'
				. '<label for="' . $this->get_field_id('obpm-label-' . $key) . '">Label</label> '
				. '<input type="text" id="' . $this->get_field_id('obpm-label-' . $key) . '"'
				. ' name="obpm-label-' . $key . '" '
				. 'value="' . $label . '" title="Label for ' . $key . '" />'
				. '</dd>';
		}
		echo '</dl>';
	}

	/**
	 * Produces an associative array where the post meta keys are the keys
	 * and the values are human-readable labels.
	 *
	 * @param array $instance The widget options
	 */
	function get_post_meta_keys_and_labels( $instance ) {
		/**
		 * Example output
		 *
		 *	array(
		 *		'{meta-prefix}odometer' => 'Odometer',
		 *		'{meta-prefix}price'    => 'Price',
		 *	)
		 *
		 */
		$arr = array();
		$v = new Inventory_Presser_Vehicle();
		foreach( $v->keys() as $key ) {
			$key = apply_filters( 'invp_prefix_meta_key', $key );
			//if we have a saved label, use that. otherwise, create a label
			$arr[$key] = ( isset( $instance['label-' . $key] ) ? $instance['label-' . $key] : $this->prettify_meta_key( $key ) );
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
			apply_filters( 'invp_prefix_meta_key', 'option_array' ),
			apply_filters( 'invp_prefix_meta_key', 'prices' ),
			apply_filters( 'invp_prefix_meta_key', 'trim' ),
			apply_filters( 'invp_prefix_meta_key', 'vin' ),
		);
		foreach( apply_filters( 'invp_sort_by_widget_ignored_fields', $ignored_keys ) as $ignored_key ) {
			unset( $arr[$ignored_key] );
		}
		return $arr;
	}

	function load_javascript( ) {
		wp_register_script( 'order-by-widget-javascript', plugins_url( 'js/order-by-post-meta-widget.js', dirname( __FILE__ ) ) );
		wp_enqueue_script( 'order-by-widget-javascript' );
	}

	function prettify_meta_key( $key ) {
		return str_replace( '_', ' ', ucfirst( apply_filters( 'invp_unprefix_meta_key', $key ) ) );
	}

	/**
	 * Processing widget options on save
	 *
	 * @param array $new_instance The new options
	 * @param array $old_instance The previous options
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = strip_tags( $new_instance['title'] );
		$tax_args = array(
			'public'   => true,
			'_builtin' => false,
		);
		$keys = array();
		$v = new Inventory_Presser_Vehicle();
		foreach( $v->keys() as $key ) {
			$key = apply_filters( 'invp_prefix_meta_key', $key );
			if( isset( $_REQUEST['obpm-key-' . $key] ) ) {
				array_push( $keys, $key );
				if( isset( $_REQUEST['obpm-label-' . $key] ) ) {
					$instance['label-' . $key] = strip_tags( $_REQUEST['obpm-label-' . $key] );
				}
			} else {
				unset( $instance['label-' . $key] );
			}
		}
		$instance['post-meta-keys'] = implode( '|', $keys );
		return $instance;
	}

 	/**
 	 * Outputs the content of the widget
 	 *
 	 * @param array $args
 	 * @param array $instance
	 */
 	public function widget( $args, $instance ) {

 		extract( $args );

 		$title = apply_filters('widget_title', ( isset( $instance['title'] ) ? $instance['title'] : '' ));

 		$keys_to_list = explode( '|', $instance['post-meta-keys'] );
 		if( 0 < sizeof( $keys_to_list ) ) {
 		 	echo $before_widget;
	 		if ( $title ) {
	        	echo $before_title . $title . $after_title;
			}
			echo '<ul class="order-by-list list-nostyle">';
			foreach( $keys_to_list as $key ) {
				echo '<li><a href="javascript:order_by_post_meta(\'' . $key . '\');">'
					. ( isset( $instance['label-' . $key] ) ? $instance['label-' . $key] : $key )
					. '</a></li>';
			}
			echo '</ul>' . $after_widget;
 		}
 	}
}
