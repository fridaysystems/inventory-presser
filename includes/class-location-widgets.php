<?php

class Inventory_Presser_Location_Helper {

	private static $instance;

    private function __construct() {}
    private function __clone() {}

    public static function getInstance() {
        if (!Inventory_Presser_Location_Helper::$instance instanceof self) {
             Inventory_Presser_Location_Helper::$instance = new self();
        }
        return Inventory_Presser_Location_Helper::$instance;
    }

    public function get_random_string($existing_ids = array(), $length = 12) {

    	// get a random string
    	$id = substr(str_shuffle(MD5(microtime())), 0, $length);

    	// prevent duplicates
    	if (in_array($id, $existing_ids)) {
    		$id = $this->get_random_string($existing_ids, $length);
    	}

        return $id;

    }

    public function check_location_term_meta_ids($term_id, $meta_array, $update = true) {

    	// build an array of existing ids per meta group to prevent duplicates
    	$existing_ids = array();
    	foreach ($meta_array as $key => $meta_groups) {
    		$existing_ids[$key] = array();
    		foreach ($meta_groups as $index => $value_array) {
    			if (array_key_exists('uid', $value_array)) {
    				$existing_ids[$key][] = $value_array['uid'];
    			}
    		}
    	}

    	// add an id to any meta group that doesn't have one
    	$meta_updated = false;
    	foreach ($meta_array as $key => $meta_groups) {
    		foreach ($meta_groups as $index => $value_array) {
    			if (!array_key_exists('uid', $value_array)) {
    				$meta_array[$key][$index]['uid'] = $this->get_random_string($existing_ids[$key]);
    				if (!$meta_updated ) {
    					$meta_updated  = true;
    				}
    			}
    		}
    	}

    	// update the db if any id's were added
    	if ($meta_updated && $update) {
    		update_term_meta($term_id, 'location-phone-hours', $meta_array);
    	}
    	
    	return $meta_array;

    }

}

// Hours Widget
class Inventory_Presser_Location_Hours extends WP_Widget {

	function __construct() {
		parent::__construct(
			'_invp_hours', 
			'Dealer Hours', 
			array( 'description' => 'Sample widget based on WPBeginner Tutorial', ) 
		);
	}

	// Creating widget front-end
	// This is where the action happens
	public function widget( $args, $instance ) {

		$title = apply_filters( 'widget_title', $instance['title'] );
		// before and after widget arguments are defined by themes
		echo $args['before_widget'];
		if ( ! empty( $title ) )
		echo $args['before_title'] . $title . $args['after_title'];

		// This is where you run the code and display the output
		echo 'hi';
		echo $args['after_widget'];
	}
			
	// Widget Backend 
	public function form( $instance ) {

		// get current term meta
	    //$location_meta = get_term_meta( $term->term_id, 'location-phone-hours', true );
	    // make sure the current term meta has unique id's
	    //$location_meta = Inventory_Presser_Location_Helper::getInstance()->check_location_term_meta_ids($term->term_id, $location_meta);

		if ( isset( $instance[ 'title' ] ) ) {
			$title = $instance[ 'title' ];
		} else {
			$title = 'Hours';
		}
		// Widget admin form
		?>
		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<?php 
	}
		
	// Updating widget replacing old instances with new
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		return $instance;
	}

} // Class Inventory_Presser_Location_Hours



// Hours Widget
class Inventory_Presser_Location_Address extends WP_Widget {

	function __construct() {
		parent::__construct(
			'_invp_hours',
			'Dealer Hours', 
			array( 'description' => 'Sample widget based on WPBeginner Tutorial', ) 
		);
	}

	// Creating widget front-end
	// This is where the action happens
	public function widget( $args, $instance ) {

		$title = apply_filters( 'widget_title', $instance['title'] );
		// before and after widget arguments are defined by themes
		echo $args['before_widget'];
		if (!empty( $title ))
		echo $args['before_title'] . $title . $args['after_title'];

		// This is where you run the code and display the output
		echo __( 'Hello, World!', '_dealer' );
		echo $args['after_widget'];
	}
			
	// Widget Backend 
	public function form( $instance ) {
		if ( isset( $instance[ 'title' ] ) ) {
			$title = $instance[ 'title' ];
		} else {
			$title = 'Hours';
		}
		// Widget admin form
		?>
		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<?php 
	}
		
	// Updating widget replacing old instances with new
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		return $instance;
	}

} // Class Inventory_Presser_Location_Address




// bootstrap class for these widgets
class Inventory_Presser_Location_Widgets {

	function __construct( ) {
		add_action( 'widgets_init', array( &$this, 'widgets_init' ) );
	}

	function widgets_init() {
		register_widget('Inventory_Presser_Location_Hours');
	}

}

new Inventory_Presser_Location_Widgets();