<?php
/**
 * Various classes to deal with location taxonomy widgets and checks
 *
 *
 * @since      1.3.1
 * @package    Inventory_Presser
 * @subpackage Inventory_Presser/includes
 * @author     Corey Salzano <corey@fridaynet.com>, John Norton <norton@fridaynet.com>
 */
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
    				$random = $this->get_random_string($existing_ids[$key]);
    				$meta_array[$key][$index]['uid'] = $random;
    				$existing_ids[$key][] = $random;
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
			array( 'description' => 'Select and display hours of operation.', ) 
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

		$title = isset($instance[ 'title' ]) ? $instance[ 'title' ] : 'Hours';
		$cb_display = isset($instance['cb_display']) ? $instance['cb_display'] : array();
		$cb_title = isset($instance['cb_title']) ? $instance['cb_title'] : array();

		// get all locations
		$location_info = get_terms('location', array('fields'=>'id=>name', 'hide_empty'=>false));

	    $hours_table = '<table><tbody>';

	    // loop through each location, set up form
	    foreach ($location_info as $term_id => $name) {
	    	$location_meta = get_term_meta( $term_id, 'location-phone-hours', true );
	    	if (count($location_meta['hours']) > 0) {
	    		$hours_table .= sprintf('<tr><td>%s</td><td>Display</td><td>Title</td></tr>', $name);
	    		foreach ($location_meta['hours'] as $index => $hourset) {

	    			$uid = $hourset['uid'];

	    			$hourset_title = ($hourset['title']) ? $hourset['title'] : 'No title entered';

	    			$cb_display_checked = (isset($cb_display[$term_id]) && is_array($cb_display[$term_id]) && in_array($uid, $cb_display[$term_id])) ? ' checked' : '';
	    			$cb_display_text = sprintf('<input type="checkbox" id="%s" name="%s" value="%s"%s />', $this->get_field_id('cb_display'), $this->get_field_name('cb_display['.$term_id.'][]'), $uid, $cb_display_checked);
	    			
	    			$cb_title_checked = (isset($cb_title[$term_id]) && is_array($cb_title[$term_id]) && in_array($uid, $cb_title[$term_id])) ? ' checked' : '';
	    			$cb_title_text = sprintf('<input type="checkbox" id="%s" name="%s" value="%s"%s />', $this->get_field_id('cb_title'), $this->get_field_name('cb_title['.$term_id.'][]'), $uid, $cb_title_checked);
	    			
	    			$hours_table .= sprintf('<tr><td><strong>%s</strong></td><td>%s</td><td>%s</td></tr>',
	    									$hourset_title,
	    									$cb_display_text,
	    									$cb_title_text);
	    		}
	    	}

	    }

	    $hours_table .= '</tbody></table>';

		// Widget admin form
		?>
		<p>
		<label for="<?php echo $this->get_field_id('title'); ?>">Main Title</label>
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
		</p>
		<?php
		echo $hours_table;
	}
		
	// Updating widget replacing old instances with new
	public function update( $new_instance, $old_instance ) {

		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['cb_display'] = ( !empty( $new_instance['cb_display'] ) ) ? $new_instance['cb_display'] : array();
		$instance['cb_title'] = ( !empty( $new_instance['cb_title'] ) ) ? $new_instance['cb_title'] : array();

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
		add_action( 'current_screen', array( &$this, 'thisScreen' ) );
	}

	function widgets_init() {
		register_widget('Inventory_Presser_Location_Hours');
	}

	function thisScreen() {

	    $currentScreen = get_current_screen();
	    // if on the widget admin page
	    if( $currentScreen->id === "widgets" ) {

			// loop through all locations and make sure the location term meta has unique id's
		    $term_ids = get_terms('location', array('fields'=>'ids', 'hide_empty'=>false));
		    foreach ($term_ids as $i => $term_id) {
		    	$location_meta = get_term_meta($term_id, 'location-phone-hours', true);
		    	if ($location_meta) {
		    		$location_meta = Inventory_Presser_Location_Helper::getInstance()->check_location_term_meta_ids($term_id, $location_meta);
		    	}
		    }

	    }
	    
	}

}

new Inventory_Presser_Location_Widgets();