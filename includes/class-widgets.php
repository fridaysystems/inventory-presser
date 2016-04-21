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

	var $days = array('MON','TUE','WED','THU','FRI','SAT','SUN');

	function __construct() {
		parent::__construct(
			'_invp_hours', 
			'Dealer Hours', 
			array( 'description' => 'Select and display hours of operation.', ) 
		);
	}

	// widget front-end
	public function widget( $args, $instance ) {

		if (is_array($instance['cb_display']) && count($instance['cb_display']) > 0) {

			$title = apply_filters( 'widget_title', $instance['title'] );
			// before and after widget arguments are defined by themes TODO??
			echo $args['before_widget'];
			
			if ( ! empty( $title ) )
			echo $args['before_title'] . $title . $args['after_title'];
			echo '<div class="invp-hours">';

			// get all locations
			$location_info = get_terms('location', array('fields'=>'id=>name', 'hide_empty'=>false));

			// loop through each location
			foreach ($location_info as $term_id => $name) {

				// get term meta for location
				$location_meta = get_term_meta( $term_id, 'location-phone-hours', true );

				// if any hour sets have been selected for this location
				if (isset($instance['cb_display'][$term_id]) && is_array($instance['cb_display'][$term_id]) && count($instance['cb_display'][$term_id]) > 0 && count($location_meta['hours']) > 0) {

					// loop through each hour set from term meta
					foreach ($location_meta['hours'] as $index => $hourset) {

						if (in_array($hourset['uid'], $instance['cb_display'][$term_id])) {

							if (isset($instance['cb_title'][$term_id]) && is_array($instance['cb_title'][$term_id]) && in_array($hourset['uid'], $instance['cb_title'][$term_id])) {
								echo sprintf('<strong>%s</strong>',$hourset['title']);
							}

							// get current day number, starting on a monday
							$current_weekday = date('w') - 1;
							$current_weekday = ($current_weekday == -1) ? 6 : $current_weekday;

							echo '<table>';

							// output a row for each day
							for ($i = 0; $i < 7; $i++) {

								// do a check to make sure we want to output this row
								$echo_row = false;
								if (($hourset[$i]['appt'] == 1) || (!empty($hourset[$i]['open']) && !empty($hourset[$i]['close']))) {
									$echo_row = true;
								} elseif ($i < 6) {
									// check the remaining days, output current day if there are other displayed days following
									for ($r=($i+1); $r < 7; $r++) {
										if (($hourset[$r]['appt'] == 1) || (!empty($hourset[$r]['open']) && !empty($hourset[$r]['close']))) {
											$echo_row = true;
										}
									}
									// if there are no remaining days to display, break out of loop
									if (!$echo_row) {
										break;
									}
								}
								
								// output row
								if ($echo_row) {

									$current_row_class = ($current_weekday == $i) ? ' class="day-highlight"' : '';
									echo sprintf('<tr%s>',$current_row_class);
									echo sprintf('<td>%s</td>',$this->days[$i]);

									if ($hourset[$i]['appt'] == 1) {
										echo '<td colspan="2">Appointment Only</td>';
									} elseif (!empty($hourset[$i]['open']) && !empty($hourset[$i]['close'])) {
										echo sprintf('<td>%s</td>',$hourset[$i]['open']);
								    	echo sprintf('<td>%s</td>',$hourset[$i]['close']);
									} else {
										echo '<td colspan="2">Closed</td>';
									}
								    
								    echo '</tr>';

								}


							}

							echo '</table>';

						}

					}

				}

			}

			echo '</div>';

			echo $args['after_widget'];

		}

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
	    	if (isset($location_meta['hours']) && count($location_meta['hours']) > 0) {
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
		<p><?php echo $hours_table; ?></p>
		<?php
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



// Address Widget
class Inventory_Presser_Location_Address extends WP_Widget {

	function __construct() {
		parent::__construct(
			'_invp_adress',
			'Dealer Address', 
			array( 'description' => 'Select and display addresses.', ) 
		);
	}

	// front-end
	public function widget( $args, $instance ) {

		$title = apply_filters( 'widget_title', $instance['title'] );
		// before and after widget arguments are defined by themes
		echo $args['before_widget'];
		if (!empty( $title ))
		echo $args['before_title'] . $title . $args['after_title'];

		foreach ($instance['cb_display'] as $i => $term_id) {
			$location = get_term($term_id, 'location');
			echo '<div>'.nl2br($location->description).'</div>';
		}
		
		echo $args['after_widget'];
	}
			
	// Widget Backend 
	public function form( $instance ) {

		$title = isset($instance[ 'title' ]) ? $instance[ 'title' ] : '';

		// get all locations
		$location_terms = get_terms('location', array('hide_empty'=>false));

		// set 
		if (isset($instance['cb_display'])) {
			$cb_display = $instance['cb_display'];
		} else {
			// majority of dealers will have one address, let's precheck it for them
			if (count($location_terms) == 1) {
				$cb_display = array($location_terms[0]->term_id);
			} else {
				$cb_display = array();
			}
		}
		
	    $address_table = '<table><tbody>';
	    $address_table .= '<tr><td colspan="2">Select Addresses to Display</td></tr>';

	    // loop through each location, set up form
	   	foreach ($location_terms as $index => $term_object) {

	   		$check_text = (in_array($term_object->term_id, $cb_display)) ? ' checked' : '';
	   		$address_checkbox = sprintf('<input id="%s" name="%s" value="%s" type="checkbox"%s>',
	   			$this->get_field_id('cb_title'),
	   			$this->get_field_name('cb_display[]'),
	   			$term_object->term_id,
	   			$check_text);
	   		$address_table .= sprintf('<tr><td>%s</td><td>%s</td></tr>', $address_checkbox, nl2br($term_object->description));

	    }

	    $address_table .= '</tbody></table>';


		// Widget admin form
		?>
		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<p><?php echo $address_table; ?></p>
		<?php
	}
		
	// Updating widget replacing old instances with new
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['cb_display'] = ( !empty( $new_instance['cb_display'] ) ) ? $new_instance['cb_display'] : array();
		return $instance;
	}

} // Class Inventory_Presser_Location_Address


// Phone Widget
class Inventory_Presser_Location_Phones extends WP_Widget {

	// formats for widget display.  To add more, just follow the pattern
	var $formats = array(
		'small_top_label' => array(
			'selector' => 'Small, left label',
			'uses_labels' => true,
			'before' => '<table>',
			'repeater' => '<tr><td>%1$s</td><td class="phone-link"><a href="tel:%2$s">%2$s</a></td><tr>',
			'after' => '</table>',
			),
		'large_no_label' => array(
			'selector' => 'Large, no label',
			'uses_labels' => false,
			'before' => '',
			'repeater' => '<h2><a href="tel:%1$s">%1$s</a></h2>',
			'after' => '',
			),
		);

	function __construct() {
		parent::__construct(
			'_invp_phone',
			'Dealer Phone Number', 
			array( 'description' => 'Select and display phone numbers.', ) 
		);
	}

	// widget front-end
	public function widget( $args, $instance ) {

		if (is_array($instance['cb_display']) && count($instance['cb_display']) > 0) {

			$title = apply_filters( 'widget_title', $instance['title'] );
			$format = $instance['format'];

			// before and after widget arguments are defined by themes
			echo $args['before_widget'];
			if (!empty( $title ))
			echo $args['before_title'] . $title . $args['after_title'];
			echo sprintf('<div class="invp-%s">', $format);

			// get all locations
			$location_info = get_terms('location', array('fields'=>'id=>name', 'hide_empty'=>false));

			// loop through each location
			foreach ($location_info as $term_id => $name) {

				// get term meta for location
				$location_meta = get_term_meta( $term_id, 'location-phone-hours', true );

				// if any hour sets have been selected for this location
				if (isset($instance['cb_display'][$term_id]) && is_array($instance['cb_display'][$term_id]) && count($instance['cb_display'][$term_id]) > 0 && isset($location_meta['phones']) && count($location_meta['phones']) > 0) {
					
					echo $this->formats[$format]['before'];
					// loop through each hour set from term meta
					foreach ($location_meta['phones'] as $index => $phoneset) {

						// if the phone number has been selected, output it
						if (in_array($phoneset['uid'], $instance['cb_display'][$term_id])) {
							echo ($this->formats[$format]['uses_labels']) ? sprintf($this->formats[$format]['repeater'], $phoneset['phone_description'], $phoneset['phone_number']) : sprintf($this->formats[$format]['repeater'], $phoneset['phone_number']);
						}

					}

					echo $this->formats[$format]['after'];

				}

			}

			echo '</div>';
			echo $args['after_widget'];

		}

	}
			
	// Widget Backend 
	public function form( $instance ) {
		$title = isset($instance['title']) ? $instance['title'] : '';
		$format = isset($instance['format']) ? $instance['format'] : current(array_keys($this->formats));
		$cb_display = isset($instance['cb_display']) ? $instance['cb_display'] : array();

		// get all locations
		$location_info = get_terms('location', array('fields'=>'id=>name', 'hide_empty'=>false));

	    $phones_table = '<table><tbody>';

	    // loop through each location, set up form
	    foreach ($location_info as $term_id => $name) {
	    	$location_meta = get_term_meta( $term_id, 'location-phone-hours', true );
	    	if (isset($location_meta['phones']) && count($location_meta['phones']) > 0) {
	    		$phones_table .= sprintf('<tr><td colspan="3"><strong>%s</strong></td></tr>', $name);
	    		foreach ($location_meta['phones'] as $index => $phoneset) {

	    			$uid = $phoneset['uid'];

	    			$phoneset_number = ($phoneset['phone_number']) ? $phoneset['phone_number'] : 'No number entered';

	    			$cb_display_checked = (isset($cb_display[$term_id]) && is_array($cb_display[$term_id]) && in_array($uid, $cb_display[$term_id])) ? ' checked' : '';
	    			$cb_display_text = sprintf('<input type="checkbox" id="%s" name="%s" value="%s"%s />', $this->get_field_id('cb_display'), $this->get_field_name('cb_display['.$term_id.'][]'), $uid, $cb_display_checked);

	    			$phones_table .= sprintf('<tr><td>%s</td><td>%s</td><td>%s</td></tr>',
	    									$cb_display_text,
	    									$phoneset['phone_description'],
	    									$phoneset_number
	    									);
	    		}
	    	}

	    }

	    $phones_table .= '</tbody></table>';

		// Widget admin form
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>">Title (optional):</label> 
			<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('format'); ?>">Display Format:</label> 
			<select class="widefat" id="<?php echo $this->get_field_id('format'); ?>" name="<?php echo $this->get_field_name('format'); ?>">
			<?php
			foreach ($this->formats as $key => $format_array) {
				$selected = ($format == $key) ? ' selected' : '';
				echo sprintf('<option value="%s"%s>%s</option>', $key, $selected, $format_array['selector']);
			}
			?>
			</select>
		</p>
		<p><?php echo $phones_table; ?></p>
		<?php
	}
		
	// Updating widget replacing old instances with new
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( !empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['format'] = ( !empty( $new_instance['format'] ) ) ? $new_instance['format'] : current(array_keys($this->formats));
		$instance['cb_display'] = ( !empty( $new_instance['cb_display'] ) ) ? $new_instance['cb_display'] : array();
		return $instance;
	}

} // Class Inventory_Presser_Location_Phones


// Address Widget
class Carfax_Widget extends WP_Widget {

	function __construct() {
		parent::__construct(
			'_invp_carfax',
			'Carfax Reports', 
			array( 'description' => 'Advertise Carfax Report with Inventory Link', ) 
		);
	}

	// front-end
	public function widget( $args, $instance ) {

		$title = apply_filters( 'widget_title', $instance['title'] );
		// before and after widget arguments are defined by themes
		echo $args['before_widget'];
		if (!empty( $title ))
		echo $args['before_title'] . $title . $args['after_title'];

		echo wpautop($instance['before_image']);
		echo sprintf('<a href="%s"><img src="%s"></a>',get_post_type_archive_link( 'inventory_vehicle' ),plugins_url( '../assets/show_me_the_carfax.png', __FILE__ ));
		echo wpautop($instance['after_image']);
		
		echo $args['after_widget'];
	}
			
	// Widget Backend 
	public function form( $instance ) {

		$title = isset($instance[ 'title' ]) ? $instance[ 'title' ] : '';
		$before_image = isset($instance[ 'before_image' ]) ? $instance[ 'before_image' ] : '';
		$after_image = isset($instance[ 'after_image' ]) ? $instance[ 'after_image' ] : '';

		// Widget admin form
		?>
		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<p>
		<label for="<?php echo $this->get_field_id( 'before_image' ); ?>"><?php _e( 'Text before image:' ); ?></label> 
		<textarea class="widefat" id="<?php echo $this->get_field_id('before_image'); ?>" name="<?php echo $this->get_field_name('before_image'); ?>"><?php echo esc_attr( $before_image ); ?></textarea>
		</p>
		<p>
		<label for="<?php echo $this->get_field_id( 'after_image' ); ?>"><?php _e( 'Text after image:' ); ?></label> 
		<textarea class="widefat" id="<?php echo $this->get_field_id('after_image'); ?>" name="<?php echo $this->get_field_name('after_image'); ?>"><?php echo esc_attr( $after_image ); ?></textarea>
		</p>
		<?php
	}
		
	// Updating widget replacing old instances with new
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['before_image'] = ( ! empty( $new_instance['before_image'] ) ) ? strip_tags( $new_instance['before_image'] ) : '';
		$instance['after_image'] = ( ! empty( $new_instance['after_image'] ) ) ? strip_tags( $new_instance['after_image'] ) : '';
		return $instance;
	}

} // Class Carfax_Widget

// bootstrap class for these widgets
class Inventory_Presser_Location_Widgets {

	function __construct( ) {
		add_action( 'widgets_init', array( &$this, 'widgets_init' ) );
		add_action( 'current_screen', array( &$this, 'check_ids' ) );
	}

	function widgets_init() {
		register_widget('Inventory_Presser_Location_Hours');
		register_widget('Inventory_Presser_Location_Address');
		register_widget('Inventory_Presser_Location_Phones');
		register_widget('Carfax_Widget');
	}

	function check_ids() {

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