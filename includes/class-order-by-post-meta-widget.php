<?php
class Order_By_Widget extends WP_Widget {

 	const ID_BASE = 'order_by_widget';

	/**
	 * Sets up the widgets name etc
	 */
 	public function __construct() {
 		parent::__construct(
 			self::ID_BASE, // Base ID
 			__( 'Order By Post Meta', 'inventory_presser' ), // Name
 			array( 'description' => __( 'A widget that allows users to sort posts by post meta values', 'inventory_presser' ), ) // Args
 		);

 		//Load our JavaScript
 		add_action( 'wp_enqueue_scripts', array( &$this, 'load_javascript' ) );
 	}

	/**
	 * Outputs the options form on admin
	 *
	 * @param array $instance The widget options
	 */
	public function form( $instance ) {
		if ( isset( $instance[ 'title' ] ) ) {
			$title = $instance[ 'title' ];
		} else {
			$title = '';
		}
        ?>
         <p>
          <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
          <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
        </p>
        <?php
		$args = array(
			'public'   => true,
		);
		$already_turned_on_keys = array();
		if( isset( $instance['post-meta-keys'] ) ) {
			$already_turned_on_keys = explode( '|', $instance['post-meta-keys'] );
		}
		//Which post meta keys should the widget allow users to choose?
		echo '<p>Which post meta keys should users be allowed to use as sort fields?</p>';
		//get all post meta keys
		echo '<dl>';
		foreach( $this->get_post_meta_keys_and_labels( $instance ) as $key => $label ) {
			//output checkbox for each one
			echo '<dt>';
			$title = 'Allow users to order by ' . $key; //title attribute for checkbox and label
			echo '<input type="checkbox" id="obpm-key-' . $key . '" name="obpm-key-' . $key . '"';
			if( in_array( $key, $already_turned_on_keys ) ) {
				echo ' checked="checked"';
			}
			echo ' title="' . $title . '"/>';
			echo '<label for="obpm-key-' . $key . '" title="' . $title . '">' . $key . '</label>';
			echo '</dt>';
			//and a text box for a label
			echo '<dd>';
			echo '<label for="obpm-label-' . $key . '">Label</label> ';
			echo '<input type="text" id="obpm-label-' . $key . '" name="obpm-label-' . $key . '" ';
			echo 'value="' . $label . '" title="Label for ' . $key . '" />';
			echo '</dd>';
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
		 *		'inventory_presser_odometer' => 'Odometer',
		 *		'inventory_presser_price'    => 'Price',
		 *	)
		 *
		 */
		$arr = array();
		$vehicle = new Inventory_Presser_Vehicle();
		foreach( $this->get_post_meta_keys_from_database() as $key ) {
			//if we have a saved label, use that. otherwise, create a label
			$arr[$key] = ( isset( $instance['label-' . $key] ) ? $instance['label-' . $key] : $vehicle->make_post_meta_key_readable( $key ) );
		}
		/**
		 * Some fields do not make sense to order by, such as interior color & VIN
	 	 */
		$ignored_keys = array(
			'inventory_presser_engine',
			'inventory_presser_interior_color',
			'inventory_presser_option_array',
			'inventory_presser_trim',
			'inventory_presser_vin',
		);
		foreach( apply_filters( 'order_by_post_meta_widget_ignored_fields', $ignored_keys ) as $ignored_key ) {
			unset( $arr[$ignored_key] );
		}
		return $arr;
	}

	/**
	 * Get all post meta keys except when they start with an underscore,
	 * contain a pipe, or are empty string. Returns a single dimensional array.
	 */
	function get_post_meta_keys_from_database() {
		global $wpdb;
		$query = "
			SELECT DISTINCT($wpdb->postmeta.meta_key)
			FROM $wpdb->posts
			LEFT JOIN $wpdb->postmeta
			ON $wpdb->posts.ID = $wpdb->postmeta.post_id
			WHERE $wpdb->postmeta.meta_key LIKE 'inventory_presser_%'
			ORDER BY $wpdb->postmeta.meta_key
		";
		return $wpdb->get_col( $query );
	}

	function load_javascript( ) {
		if( is_active_widget( false, false, self::ID_BASE ) ) {
			wp_register_script( 'order-by-widget-javascript', plugins_url( 'js/order-by-post-meta-widget.js', dirname( __FILE__ ) ) );
			wp_enqueue_script( 'order-by-widget-javascript' );
		}
	}

	/**
	 * Processing widget options on save
	 *
	 * @param array $new_instance The new options
	 * @param array $old_instance The previous options
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = strip_tags($new_instance['title']);
		$tax_args = array(
			'public'   => true,
			'_builtin' => false,
		);
		$keys = array();
		foreach( $this->get_post_meta_keys_from_database() as $key ) {
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
	 		if ( $title )
	        	echo $before_title . $title . $after_title;
 		 	echo '<ul class="order-by-list">';
			foreach( $keys_to_list as $key ) {
				echo '<li><a href="javascript:order_by_post_meta(\'' . $key . '\');">';
				echo isset( $instance['label-' . $key] ) ? $instance['label-' . $key] : $key;
				echo '</a></li>';
			}
			echo '</ul>' . $after_widget;
 		}
 	}
}
