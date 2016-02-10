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

		/**
		 * Deliver our promise to front-end users, change the ORDER BY clause of
		 * the query that's fetching post objects.
		 */
		if( ! is_admin() && isset( $_GET['orderby'] ) ) {
			add_action( 'pre_get_posts', array( &$this, 'add_orderby_to_query' ) );
		}

		add_filter( 'order_by_post_meta_widget_meta_value_or_meta_value_num', array( &$this, 'indicate_post_meta_values_are_numbers' ), 10, 2 );
 	}

	function add_orderby_to_query( $query ) {
		if ( ! $query->is_main_query() ) {
			return;
		}

		add_filter( 'posts_clauses', array( &$this, 'modify_query_orderby' ) );
		
		$query->set( 'meta_key', $_GET['orderby'] );
		switch( $query->query_vars['meta_key'] ) {
		
			//MAKE
			case 'inventory_presser_make':
				$query->set( 'meta_query', array(
					'relation' => 'AND', 
						array( 'key' => 'inventory_presser_make', 'compare' => 'EXISTS' ), 
						array( 'key' => 'inventory_presser_model', 'compare' => 'EXISTS' ),
						array( 'key' => 'inventory_presser_trim', 'compare' => 'EXISTS' )
					) 
				);
				break;
				
			//MODEL		
			case 'inventory_presser_model':
				$query->set( 'meta_query', array(
					'relation' => 'AND', 
						array( 'key' => 'inventory_presser_model', 'compare' => 'EXISTS' ), 
						array( 'key' => 'inventory_presser_trim', 'compare' => 'EXISTS' ) 
					) 
				);
				break;
				
			//YEAR
			case 'inventory_presser_year':
				$query->set( 'meta_query', array(
					'relation' => 'AND', 
						array( 'key' => 'inventory_presser_year', 'compare' => 'EXISTS' ), 
						array( 'key' => 'inventory_presser_make', 'compare' => 'EXISTS' ),
						array( 'key' => 'inventory_presser_model', 'compare' => 'EXISTS' ),
						array( 'key' => 'inventory_presser_trim', 'compare' => 'EXISTS' )
					) 
				);
				break;
		}
		
		//Allow other developers to decide if the post meta values are numbers
		$meta_value_or_meta_value_num = apply_filters( 'order_by_post_meta_widget_meta_value_or_meta_value_num', 'meta_value', $_GET['orderby'] );
		$query->set( 'orderby', $meta_value_or_meta_value_num );
				 
		if( isset( $_GET['order'] ) ) {
			$query->set( 'order', $_GET['order'] );
		}
	}

 	/**
 	 * Turn a post meta key into a more readable name that is suggested as the
 	 * text a user clicks on to sort vehicles by a post meta key.
 	 * 
 	 * @param string $post_meta_key The key to make more friendly
 	 */
	function create_label( $post_meta_key ) {
		/**
		 * Remove 'inventory_presser_'
		 * Change underscores to spaces
		 * Capitalize the first character
		 */
		return ucfirst( str_replace( '_', ' ', str_replace( 'inventory_presser_', '', $post_meta_key ) ) );
	}
 	
	/**
	 * Outputs the options form on admin
	 *
	 * @param array $instance The widget options
	 */
	public function form( $instance ) {
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

	function get_last_word( $str ) {
		$pieces = explode( ' ', $str );
		return array_pop( $pieces );
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
		foreach( $this->get_post_meta_keys_from_database() as $key ) {
			//if we have a saved label, use that. otherwise, create a label
			$arr[$key] = ( isset( $instance['label-' . $key] ) ? $instance['label-' . $key] : $this->create_label( $key ) );
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

	/**
	 * Help WordPress understand which post meta values should be treated as
	 * numbers. By default, they are all strings, and strings sort differently 
	 * than numbers.
	 */
	function indicate_post_meta_values_are_numbers( $value, $meta_key ) {
		return ( in_array( $meta_key, array( 
			'_inventory_presser_car_ID',
			'_inventory_presser_dealer_ID',
			'inventory_presser_odometer',
			'inventory_presser_price',
			'inventory_presser_year',
		) ) ? 'meta_value_num' : $value );
	}

	function load_javascript( ) {
		if( is_active_widget( false, false, self::ID_BASE ) ) {
			wp_register_script( 'order-by-widget-javascript', plugins_url( 'js/order-by-post-meta-widget.js', dirname( __FILE__ ) ) );
			wp_enqueue_script( 'order-by-widget-javascript' );
		}
	}

	function modify_query_orderby( $pieces ) {
		/** 
		 * Count the number of meta fields we have added to the query by parsing
		 * the join piece of the query
		 */
		$meta_field_count = sizeof( explode( 'INNER JOIN wp_postmeta AS', $pieces['join'] ) )-1;
		
		//Parse out the ASC or DESC sort direction from the end of the ORDER BY clause
		$direction = $this->get_last_word( $pieces['orderby'] );
		$acceptable_directions = array( 'ASC', 'DESC' );
		$direction = ( in_array( $direction, $acceptable_directions ) ? ' ' . $direction : '' );
		
		/** 
		 * Build a string to replace the existing ORDER BY field name
		 * Essentially, we are going to turn 'wp_postmeta.meta_value' into
		 * 'mt1.meta_value ASC, mt2.meta_value ASC, mt3.meta_value ASC'
		 * where the number of meta values is what we calculated in $meta_field_count
		 */
		$replacement = '';
		for( $m=0; $m<$meta_field_count; $m++ ) {
			$replacement .= 'mt' . ( $m+1 ) . '.meta_value';
			if( $m < ( $meta_field_count-1 ) ) {
				$replacement .= $direction . ', ';
			}
		}
		
		if( '' != $replacement ) {
			global $wpdb;
			$pieces['orderby'] = str_replace( $wpdb->prefix . 'postmeta.meta_value', $replacement, $pieces['orderby'] );
		}
		return $pieces;
	}

	/**
	 * Processing widget options on save
	 *
	 * @param array $new_instance The new options
	 * @param array $old_instance The previous options
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
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
 		$keys_to_list = explode( '|', $instance['post-meta-keys'] );
 		if( 0 < sizeof( $keys_to_list ) ) {
 		 	echo $args['before_widget'];
 		 	echo '<span class="order-by-label">Order by</span><ul class="order-by-list">';
			foreach( $keys_to_list as $key ) {
				echo '<li><a href="javascript:order_by_post_meta(\'' . $key . '\');">';
				echo isset( $instance['label-' . $key] ) ? $instance['label-' . $key] : $key;
				echo '</a></li>';
			}
			echo '</ul>' . $args['after_widget'];
 		} 		
 	}
}