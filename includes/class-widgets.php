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

	var $days = array('Mon','Tue','Wed','Thu','Fri','Sat','Sun');
	const ID_BASE = '_invp_hours';

	function __construct() {
		parent::__construct(
			self::ID_BASE,
			'Hours',
			array( 'description' => 'Display hours of operation.', )
		);

		add_action( 'invp_delete_all_data', array( $this, 'delete_option' ) );
	}

	public function delete_option() {
		delete_option( 'widget_' . self::ID_BASE );
	}

	// widget front-end
	public function widget( $args, $instance ) {

		if (is_array($instance['cb_display']) && count($instance['cb_display']) > 0) {

			$title = apply_filters( 'widget_title', $instance['title'] );
			$cb_showclosed = (isset($instance['cb_showclosed']) && $instance['cb_showclosed'] == 'true');

			// before and after widget arguments are defined by themes TODO??
			echo $args['before_widget'];

			if ( ! empty( $title ) ) {
				echo $args['before_title'] . $title . $args['after_title'];
			}
			echo '<div class="invp-hours">';

			// get all locations
			$location_info = get_terms('location', array('fields'=>'id=>name', 'hide_empty'=>false));

			// loop through each location
			foreach ($location_info as $term_id => $name) {

				// get term meta for location
				$location_meta = get_term_meta( $term_id, 'location-phone-hours', true );

				// if any hour sets have been selected for this location
				if (isset($instance['cb_display'][$term_id]) && is_array($instance['cb_display'][$term_id]) && count($instance['cb_display'][$term_id]) > 0 && isset( $location_meta['hours'] ) && count($location_meta['hours']) > 0) {

					// loop through each hour set from term meta
					foreach ($location_meta['hours'] as $index => $hourset) {

						if (in_array($hourset['uid'], $instance['cb_display'][$term_id])) {

							$hours_title = '';
							if (isset($instance['cb_title'][$term_id]) && is_array($instance['cb_title'][$term_id]) && in_array($hourset['uid'], $instance['cb_title'][$term_id])) {
								$hours_title = sprintf( '<strong>%s</strong>', $hourset['title'] );
							}
							echo apply_filters( 'invp_hours_title', $hours_title, $hourset['uid'] );

							// get current day number, starting on a monday
							$current_weekday = date('w') - 1;
							$current_weekday = ($current_weekday == -1) ? 6 : $current_weekday;

							$start_of_week = get_option('start_of_week') -1;

							echo '<table>';

							// output a row for each day
							for ($z = $start_of_week; $z < ($start_of_week + 7); $z++) {

								$i = ($z > 6) ? $z - 7 : $z;

								// do a check to make sure we want to output this row
								$echo_row = false;
								if (($hourset[$i]['appt'] == 1) || (!empty($hourset[$i]['open']) && !empty($hourset[$i]['close'])) || $cb_showclosed) {
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
									echo sprintf('<th>%s</th>',$this->days[$i]);

									if ($hourset[$i]['appt'] == 1 && !empty($hourset[$i]['open']) && !empty($hourset[$i]['close'])) {
										echo sprintf('<td colspan="2">%s - %s & Appointment</td>',$hourset[$i]['open'],$hourset[$i]['close']);
									} elseif ($hourset[$i]['appt'] == 1) {
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
		$cb_showclosed = (isset($instance[ 'cb_showclosed' ]) && $instance[ 'cb_showclosed' ] == 'true') ? ' checked' : '';

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

		<p>
		<label for="<?php echo $this->get_field_id('cb_showclosed'); ?>">Show All Closed Days</label>
		<input type="checkbox" id="<?php echo $this->get_field_id('cb_showclosed'); ?>" name="<?php echo $this->get_field_name('cb_showclosed'); ?>" value="true"<?php echo $cb_showclosed; ?>>
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
		$instance['cb_showclosed'] = ( !empty( $new_instance['cb_showclosed'] ) ) ? $new_instance['cb_showclosed'] : '';

		return $instance;
	}

} // Class Inventory_Presser_Location_Hours



// Address Widget
class Inventory_Presser_Location_Address extends WP_Widget {

	const ID_BASE = '_invp_address';

	function __construct() {
		parent::__construct(
			self::ID_BASE,
			'Address',
			array( 'description' => 'Display one or more mailing addresses.', )
		);

		add_action( 'invp_delete_all_data', array( $this, 'delete_option' ) );
	}

	public function delete_option() {
		delete_option( 'widget_' . self::ID_BASE );
	}

	// front-end
	public function widget( $args, $instance ) {

		$title = apply_filters( 'widget_title', $instance['title'] );
		// before and after widget arguments are defined by themes
		echo $args['before_widget'];
		if (!empty( $title )) {
			echo $args['before_title'] . $title . $args['after_title'];
		}

		if (isset($instance['cb_single_line']) && $instance['cb_single_line'] == 'true') {
			foreach ($instance['cb_display'] as $i => $term_id) {
				$location = get_term( $term_id, 'location' );
				if( ! is_wp_error( $location ) && null != $location ) {
					echo '<span>' . preg_replace( '/\r|\n/', ', ', trim( $location->description ) ) . '</span>';
				}
			}
		} else {
			foreach ($instance['cb_display'] as $i => $term_id) {
				$location = get_term( $term_id, 'location' );
				if( ! is_wp_error( $location ) && null != $location ) {
					echo '<div>' . nl2br( $location->description ). '</div>';
				}
			}
		}


		echo $args['after_widget'];
	}

	// Widget Backend
	public function form( $instance ) {

		$title = isset($instance[ 'title' ]) ? $instance[ 'title' ] : '';
		$cb_single_line = (isset($instance['cb_single_line']) && $instance['cb_single_line'] == 'true') ? ' checked' : '';

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
		<p>
		<input type="checkbox" id="<?php echo $this->get_field_id('cb_single_line'); ?>" name="<?php echo $this->get_field_name('cb_single_line'); ?>" value="true"<?php echo $cb_single_line; ?>>
		<label for="<?php echo $this->get_field_id('cb_single_line'); ?>">Single Line Display</label>
		</p>
		<p><?php echo $address_table; ?></p>
		<?php
	}

	// Updating widget replacing old instances with new
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['cb_display'] = ( !empty( $new_instance['cb_display'] ) ) ? $new_instance['cb_display'] : array();
		$instance['cb_single_line'] = ( !empty( $new_instance['cb_single_line'] ) ) ? $new_instance['cb_single_line'] : '';
		return $instance;
	}

} // Class Inventory_Presser_Location_Address


// Phone Widget
class Inventory_Presser_Location_Phones extends WP_Widget {

	// formats for widget display.  To add more, just follow the pattern
	var $formats = array(
		'small_left_label' => array(
			'selector' => 'Small, left label',
			'uses_labels' => true,
			'before' => '<table>',
			'repeater' => '<tr><th>%1$s</th><td class="phone-link"><a href="tel:%2$s">%2$s</a></td><tr>',
			'after' => '</table>',
			),
		'large_no_label' => array(
			'selector' => 'Large, no label',
			'uses_labels' => false,
			'before' => '',
			'repeater' => '<h2><a href="tel:%1$s">%1$s</a></h2>',
			'after' => '',
			),
		'large_table_left' => array(
			'selector' => 'Large tabled, left label',
			'uses_labels' => true,
			'before' => '<table>',
			'repeater' => '<tr><th>%1$s</th><td class="phone-link"><a href="tel:%2$s">%2$s</a></td><tr>',
			'after' => '</table>',
			),
		'large_left_label' => array(
			'selector' => 'Large, small left label',
			'uses_labels' => true,
			'before' => '<table>',
			'repeater' => '<tr><th>%1$s</th><td><h2><a href="tel:%2$s">%2$s</a></h2></td><tr>',
			'after' => '</table>',
			),
		'large_right_label' => array(
			'selector' => 'Large, small right label',
			'uses_labels' => true,
			'before' => '<table>',
			'repeater' => '<tr><td><h2><a href="tel:%2$s">%2$s</a></h2></td><th>%1$s</th><tr>',
			'after' => '</table>',
			),
		'single_line_labels' => array(
			'selector' => 'Single line with labels',
			'uses_labels' => true,
			'before' => '',
			'repeater' => '<span>%1$s:</span> <a href="tel:%2$s">%2$s</a>',
			'after' => '',
			),
		'single_line_no_labels' => array(
			'selector' => 'Single line no labels',
			'uses_labels' => false,
			'before' => '',
			'repeater' => '<span><a href="tel:%1$s">%1$s</a></span>',
			'after' => '',
			),
		);

	const ID_BASE = '_invp_phone';

	function __construct() {
		parent::__construct(
			self::ID_BASE,
			'Phone Number',
			array( 'description' => 'Display one or more phone numbers.', )
		);

		add_action( 'invp_delete_all_data', array( $this, 'delete_option' ) );
	}

	public function delete_option() {
		delete_option( 'widget_' . self::ID_BASE );
	}

	// widget front-end
	public function widget( $args, $instance ) {

		if (is_array($instance['cb_display']) && count($instance['cb_display']) > 0) {

			$title = apply_filters( 'widget_title', $instance['title'] );

			$format_slugs = array_keys($this->formats);
			$format = in_array($instance['format'], $format_slugs) ? $instance['format'] : $format_slugs[0];

			// before and after widget arguments are defined by themes
			echo $args['before_widget'];
			if (!empty( $title ))
			echo $args['before_title'] . $title . $args['after_title'];
			echo sprintf('<div class="invp-%s">', $format);

			// get all locations
			$location_info = get_terms('location', array('fields'=>'id=>name', 'hide_empty'=>false));

			echo $this->formats[$format]['before'];

			// loop through each location
			foreach ($location_info as $term_id => $name) {

				// get term meta for location
				$location_meta = get_term_meta( $term_id, 'location-phone-hours', true );

				// if any hour sets have been selected for this location
				if (isset($instance['cb_display'][$term_id]) && is_array($instance['cb_display'][$term_id]) && count($instance['cb_display'][$term_id]) > 0 && isset($location_meta['phones']) && count($location_meta['phones']) > 0) {

					// loop through each hour set from term meta
					foreach ($location_meta['phones'] as $index => $phoneset) {

						// if the phone number has been selected, output it
						if (in_array($phoneset['uid'], $instance['cb_display'][$term_id])) {
							echo ($this->formats[$format]['uses_labels']) ? sprintf($this->formats[$format]['repeater'], $phoneset['phone_description'], $phoneset['phone_number']) : sprintf($this->formats[$format]['repeater'], $phoneset['phone_number']);
						}

					}

				}

			}

			echo $this->formats[$format]['after'];

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


// Carfax Widget
class Carfax_Widget extends WP_Widget {

	var $images = array(
		'default' => array('text'=>'Simple Show Me Logo', 'img'=>'show-me-carfax.svg'),
		'advantage' => array('text'=>'Advantage Dealer Badge', 'img'=>'carfax-advantage-dealer.png'),
		'dealership' => array('text'=>'Car Fox Dealership', 'img'=>'carfax-portrait-blue.jpg'),
		'foxleft' => array('text'=>'Car Fox Left', 'img'=>'carfax-show-me-blue.png'),
		'foxoval' => array('text'=>'Car Fox Oval', 'img'=>'carfax-show-me-blue-oval.png'),
		'landscape' => array('text'=>'Landscape Blue', 'img'=>'carfax-show-me-landscape.jpg'),
	);

	const ID_BASE = '_invp_carfax';

	function __construct() {
		parent::__construct(
			self::ID_BASE,
			'Carfax Badge',
			array( 'description' => 'Choose a CARFAX badge that links to your inventory.', )
		);

		add_action( 'invp_delete_all_data', array( $this, 'delete_option' ) );
	}

	public function delete_option() {
		delete_option( 'widget_' . self::ID_BASE );
	}

	// front-end
	public function widget( $args, $instance ) {

		$image_keys = array_keys($this->images);
		$image = (in_array($instance['image'], $image_keys)) ? $instance['image'] : $image_keys[0];

		$title = apply_filters( 'widget_title', $instance['title'] );
		// before and after widget arguments are defined by themes
		echo $args['before_widget'];
		if (!empty( $title ))
		echo $args['before_title'] . $title . $args['after_title'];

		echo wpautop( $instance['before_image'] );
		if( 'svg' == strtolower( pathinfo( $this->images[$image]['img'], PATHINFO_EXTENSION ) ) ) {
			//Include the SVG inline instead of using an <img> element
			$svg = file_get_contents( dirname( dirname( __FILE__ ) ) . '/assets/' . $this->images[$image]['img'] );
			echo sprintf( '<a href="%s">' . $svg . '</a>', get_post_type_archive_link( 'inventory_vehicle' ) );
		} else {
			echo sprintf( '<a href="%s"><img src="%s"></a>', get_post_type_archive_link( 'inventory_vehicle' ), plugins_url( '/assets/'.$this->images[$image]['img'], dirname(__FILE__) ) );
		}
		echo wpautop( $instance['after_image'] );
		echo $args['after_widget'];
	}

	// Widget Backend
	public function form( $instance ) {

		$image_keys = array_keys($this->images);

		$title = isset($instance[ 'title' ]) ? $instance[ 'title' ] : '';
		$before_image = isset($instance[ 'before_image' ]) ? $instance[ 'before_image' ] : '';
		$image = isset($instance[ 'image' ]) ? $instance[ 'image' ] : $image_keys[0];
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
		<label for="<?php echo $this->get_field_id( 'image' ); ?>"><?php _e( 'Image:' ); ?></label>

		<select class="widefat" id="<?php echo $this->get_field_id('image'); ?>" name="<?php echo $this->get_field_name('image'); ?>">
		<?php foreach ($this->images as $key => $imginfo) {
			$select_text = ($key == $image) ? ' selected' : '';
			echo sprintf('<option value="%s"%s>%s</option>',$key,$select_text,$imginfo['text']);
		} ?>
		</select>

		</p>

		<p>
		<label for="<?php echo $this->get_field_id( 'after_image' ); ?>"><?php _e( 'Text after image:' ); ?></label>
		<textarea class="widefat" id="<?php echo $this->get_field_id('after_image'); ?>" name="<?php echo $this->get_field_name('after_image'); ?>"><?php echo esc_attr( $after_image ); ?></textarea>
		</p>
		<?php
	}

	// Updating widget replacing old instances with new
	public function update( $new_instance, $old_instance ) {
		$image_keys = array_keys($this->images);
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['before_image'] = ( ! empty( $new_instance['before_image'] ) ) ? strip_tags( $new_instance['before_image'] ) : '';
		$instance['image'] = ( ! empty( $new_instance['image'] ) ) ? strip_tags( $new_instance['image'] ) : $image_keys[0];
		$instance['after_image'] = ( ! empty( $new_instance['after_image'] ) ) ? strip_tags( $new_instance['after_image'] ) : '';
		return $instance;
	}

} // Class Carfax_Widget

// Kelley Blue Book Widget
class KBB_Widget extends WP_Widget {

	var $images = array(
		'default' => array('text'=>'Bordered Rectangle', 'img'=>'kelley-blue-book.jpg'),
	);

	const ID_BASE = '_invp_kbb';

	function __construct() {
		parent::__construct(
			self::ID_BASE,
			'Kelley Blue Book Logo',
			array( 'description' => 'KBB logo image linked to kbb.com', )
		);

		add_action( 'invp_delete_all_data', array( $this, 'delete_option' ) );
	}

	public function delete_option() {
		delete_option( 'widget_' . self::ID_BASE );
	}

	// front-end
	public function widget( $args, $instance ) {

		$image_keys = array_keys($this->images);
		$image = (in_array($instance['image'], $image_keys)) ? $instance['image'] : $image_keys[0];

		$title = apply_filters( 'widget_title', $instance['title'] );
		// before and after widget arguments are defined by themes
		echo $args['before_widget'];
		if (!empty( $title ))
		echo $args['before_title'] . $title . $args['after_title'];

		echo wpautop($instance['before_image']);
		echo sprintf('<a href="%s" target="_blank"><img src="%s"></a>','http://kbb.com',plugins_url( '/assets/'.$this->images[$image]['img'], dirname(__FILE__)));
		echo wpautop($instance['after_image']);

		echo $args['after_widget'];
	}

	// Widget Backend
	public function form( $instance ) {

		$image_keys = array_keys($this->images);

		$title = isset($instance[ 'title' ]) ? $instance[ 'title' ] : '';
		$before_image = isset($instance[ 'before_image' ]) ? $instance[ 'before_image' ] : '';
		$image = isset($instance[ 'image' ]) ? $instance[ 'image' ] : $image_keys[0];
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
		<label for="<?php echo $this->get_field_id( 'image' ); ?>"><?php _e( 'Image:' ); ?></label>

		<select class="widefat" id="<?php echo $this->get_field_id('image'); ?>" name="<?php echo $this->get_field_name('image'); ?>">
		<?php foreach ($this->images as $key => $imginfo) {
			$select_text = ($key == $image) ? ' selected' : '';
			echo sprintf('<option value="%s"%s>%s</option>',$key,$select_text,$imginfo['text']);
		} ?>
		</select>

		</p>

		<p>
		<label for="<?php echo $this->get_field_id( 'after_image' ); ?>"><?php _e( 'Text after image:' ); ?></label>
		<textarea class="widefat" id="<?php echo $this->get_field_id('after_image'); ?>" name="<?php echo $this->get_field_name('after_image'); ?>"><?php echo esc_attr( $after_image ); ?></textarea>
		</p>
		<?php
	}

	// Updating widget replacing old instances with new
	public function update( $new_instance, $old_instance ) {
		$image_keys = array_keys($this->images);
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['before_image'] = ( ! empty( $new_instance['before_image'] ) ) ? strip_tags( $new_instance['before_image'] ) : '';
		$instance['image'] = ( ! empty( $new_instance['image'] ) ) ? strip_tags( $new_instance['image'] ) : $image_keys[0];
		$instance['after_image'] = ( ! empty( $new_instance['after_image'] ) ) ? strip_tags( $new_instance['after_image'] ) : '';
		return $instance;
	}

} // Class KBB_Widget

// Stock Photo Slider
class Stock_Photo_Slider extends WP_Widget {

	// the image sets, follow the pattern to add more
	private $image_sets = array(
		'acura' => array(
			'label' => 'Acura',
			'photos' => array(
				'acura-01.jpg',
				'acura-02.jpg',
				'acura-03.jpg',
				'acura-04.jpg',
				),
			),
		'audi' => array(
			'label' => 'Audi',
			'photos' => array(
				'audi-01.jpg',
				'audi-02.jpg',
				),
			),
		'bmw' => array(
			'label' => 'BMW',
			'photos' => array(
				'bmw-01.jpg',
				'bmw-02.jpg',
				'bmw-03.jpg',
				),
			),
		'ford' => array(
			'label' => 'Ford',
			'photos' => array(
				'ford-2013-taurus.jpg',
				'ford-2015-explorer.jpg',
				'ford-2015-taurus.jpg',
				'ford-2016-focus.jpg',
				'ford-2017-escape.jpg',
				),
			),
		'hyundai' => array(
			'label' => 'Hyundai',
			'photos' => array(
				'hyundai-2016-elantra.jpg',
				'hyundai-2016-genesis.jpg',
				'hyundai-2016-santa-fe.jpg',
				),
			),
		'mercedes' => array(
			'label' => 'Mercedes-Benz',
			'photos' => array(
				'mercedes-01.jpg',
				'mercedes-02.jpg',
				'mercedes-03.jpg',
				),
			),
		'nissan' => array(
			'label' => 'Nissan',
			'photos' => array(
				'nissan-2013-altima.jpg',
				'nissan-2016-altima.jpg',
				'nissan-2016-pulsar.jpg',
				'nissan-2016-sentra.jpg',
				),
			),
		'boats' => array(
			'label' => 'Boats',
			'photos' => array(
				'boat-1.jpg',
				'boat-2.jpg',
				'boat-3.jpg',
				'boat-4.jpg',
				'boat-5.jpg',
				),
			),
		'trucks' => array(
			'label' => 'Trucks',
			'photos' => array(
				'truck-1.jpg',
				'truck-2.jpg',
				'truck-3.jpg',
				'truck-4.jpg',
				),
			),
		'suvs' => array(
			'label' => 'SUVs',
			'photos' => array(
				'suv-01.jpg',
				'suv-02.jpg',
				'suv-03.jpg',
				'suv-04.jpg',
				'suv-05.jpg',
				'suv-06.jpg',
				'suv-07.jpg',
				),
			),
		'italian' => array(
			'label' => 'Italian',
			'photos' => array(
				'italian-01.jpg',
				'italian-02.jpg',
				'italian-03.jpg',
				'italian-04.jpg',
				'italian-05.jpg',
				'italian-06.jpg',
				),
			),
		);

	const ID_BASE = '_invp_sps';

	function __construct() {
		parent::__construct(
			self::ID_BASE,
			'Stock Photo Slider',
			array( 'description' => 'Full width slideshow with multiple vehicle photo sets.', )
		);

		add_action( 'invp_delete_all_data', array( $this, 'delete_option' ) );
	}

	public function delete_option() {
		delete_option( 'widget_' . self::ID_BASE );
	}

	// front-end
	public function widget( $args, $instance ) {

		$link_slides = (isset($instance['link_slides']) && $instance['link_slides'] == 'true');

		$image_pool = array();
		// merge each photo set into one array
		foreach ($instance['image_sets'] as $set) {
			$image_pool = array_merge($image_pool, $this->image_sets[$set]['photos']);
		}
		// mix em up for random display
		shuffle($image_pool);
		// take the first 5
		$display_images = array_slice($image_pool, 0, 5);
		// base url of photos
		$base_url = plugins_url( '/assets/stock-slider/', dirname(__FILE__));

		$title = apply_filters( 'widget_title', $instance['title'] );
		// before and after widget arguments are defined by themes
		echo $args['before_widget'];

		if (!empty( $title ))
		echo $args['before_title'] . $title . $args['after_title'];

		?>

		<div class="flexslider flex-native">
		<ul class="slides">
		<?php
		$inventory_link = get_post_type_archive_link('inventory_vehicle');
		foreach ($display_images as $filename) {
			if ($link_slides) {
				echo sprintf('<li><a href="%s"><img src="%s"></a></li>',$inventory_link,$base_url.$filename);
			} else {
				echo sprintf('<li><img src="%s"></li>',$base_url.$filename);
			}
		}
		?>

		</ul>
		</div>

		<?php

		echo $args['after_widget'];
	}

	// Widget Backend
	public function form( $instance ) {

		$image_keys = array_keys($this->image_sets);

		$title = isset($instance[ 'title' ]) ? $instance[ 'title' ] : '';
		$selected_sets = isset($instance[ 'image_sets' ]) ? $instance[ 'image_sets' ] : $image_keys;
		$link_slides = (isset($instance['link_slides']) && $instance['link_slides'] == 'true') ? ' checked' : '';

		// Widget admin form
		?>
		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<p>
		<label for="<?php echo $this->get_field_id('link_slides'); ?>"><input type="checkbox" id="<?php echo $this->get_field_id('link_slides'); ?>" name="<?php echo $this->get_field_name('link_slides'); ?>" value="true"<?php echo $link_slides; ?>> Link slides to Inventory</label>
		</p>
		<p>
		<label for="<?php echo $this->get_field_id( 'image_sets[]' ); ?>"><?php _e( 'Image Sets:' ); ?></label>

		<table>
		<?php

		foreach ($this->image_sets as $slug => $info) {
			$checked = in_array($slug, $selected_sets) ? ' checked' : '';
			echo sprintf('<tr><td><input type="checkbox" id="%s" name="%s" value="%s"%s></td><td>%s</td></tr>',
				$this->get_field_id('image_sets'),
				$this->get_field_name('image_sets[]'),
				$slug,
				$checked,
				$info['label'].' ('.count($info['photos']).')'
				);
		}

		?>
		</table>

		</p>

		<?php
	}

	// Updating widget replacing old instances with new
	public function update( $new_instance, $old_instance ) {
		$image_keys = array_keys($this->image_sets);
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['image_sets'] = ( ! empty( $new_instance['image_sets'] ) ) ? $new_instance['image_sets'] : $image_keys;
		$instance['link_slides'] = ( !empty( $new_instance['link_slides'] ) ) ? $new_instance['link_slides'] : '';
		return $instance;
	}

} // Class Stock_Photo_Slider

// Inventory Slider
class Inventory_Slider extends WP_Widget {

	const ID_BASE = '_invp_slick';

	var $featured_select = array('featured_priority'=>'Priority for Featured Vehicles', 'featured_only'=>'Featured Vehicles Only', 'random'=>'Random' );
	var $text_displays = array('none' => 'None','top' => 'Top', 'bottom'=>'Bottom');

	function __construct() {
		parent::__construct(
			self::ID_BASE,
			'Vehicle Slider',
			array( 'description' => 'A slideshow for all vehicles with at least one photo.', )
		);

		add_action( 'invp_delete_all_data', array( $this, 'delete_option' ) );
	}

	public function delete_option() {
		delete_option( 'widget_' . self::ID_BASE );
	}

	// front-end
	public function widget( $args, $instance ) {

		$title = apply_filters( 'widget_title', $instance['title'] );
		$showcount = $instance['showcount'];
		$showtext = $instance['showtext'];
		$featured_select_slugs = array_keys($this->featured_select);
		$featured_select = isset($instance['featured_select']) ? $instance[ 'featured_select' ] : $featured_select_slugs[0];
		$showtitle = (isset($instance['cb_showtitle']) && $instance['cb_showtitle'] == 'true');
		$showprice = (isset($instance['cb_showprice']) && $instance['cb_showprice'] == 'true');

		$inventory_ids = array();

		if ($featured_select == 'random') {

			$gpargs = array(
				'numberposts'=> $showcount * 5,
				'post_type'=>'inventory_vehicle',
				'meta_key'=>'_thumbnail_id',
				'fields' => 'ids',
				'orderby'=>'rand',
				'order' => 'ASC'
			);

			$inventory_ids = get_posts($gpargs);

		} else {

			$gpargs = array(
				'numberposts' => $showcount * 5,
				'post_type' => 'inventory_vehicle',
				'meta_query' => array(
					'relation' => 'AND',
					array(
						'key'     => 'inventory_presser_featured',
						'value'   => 1,
						'compare' => '=',
					),
					array(
						'key'	  => '_thumbnail_id',
						'compare' => 'EXISTS',
					)
				),
				'fields' => 'ids',
				'orderby'=>'rand',
				'order' => 'ASC'
			);

			$inventory_ids = get_posts($gpargs);


			if (count($inventory_ids) < ($showcount * 5) && $featured_select == 'featured_priority') {

				$gpargs = array(
					'numberposts'=> ($showcount * 5) - (count($inventory_ids)),
					'post_type'=>'inventory_vehicle',
					'meta_query' => array(
						'relation' => 'AND',
						array(
							'key'     => 'inventory_presser_featured',
							'value'   => 0,
							'compare' => '='
						),
						array(
							'key'	  => '_thumbnail_id',
							'compare' => 'EXISTS'
						)
					),
					'fields' => 'ids',
					'orderby'=>'rand',
					'order' => 'ASC'
				);

				$inventory_ids += get_posts($gpargs);

			}

		}

		if ($inventory_ids) {

			shuffle($inventory_ids);

			// before and after widget arguments are defined by themes
			echo $args['before_widget'];
			if (!empty( $title ))
				echo $args['before_title'] . $title . $args['after_title'];

			echo sprintf('<div class="slick-slider-element" data-slick=\'{"slidesToShow": %1$d, "slidesToScroll": %1$d, "easing": "ease", "autoplaySpeed": %2$d, "speed": 4000}\'>', $showcount, ($showcount * 1000) +1000);

			foreach ($inventory_ids as $inventory_id) {

				$vehicle = new Inventory_Presser_Vehicle($inventory_id);
				echo sprintf('<div class="widget-inventory-slide-wrap"><a href="%s"><div class="slick-background-image" style="background-image: url(%s);">',$vehicle->url,wp_get_attachment_image_url(get_post_thumbnail_id($inventory_id), 'large'));
				if ($showtext != 'none') {
					echo sprintf('<div class="slick-text slick-text-%s">', $showtext);
					if ($showtitle) {
						echo sprintf('<h3>%s %s %s</h3>', $vehicle->year, $vehicle->make, $vehicle->model);
					}
					if ($showprice) {
						echo sprintf('<h2>%s</h2>',$vehicle->price('Call For Price'));
					}
					echo '</div>';
				}

				echo '</div></a></div>';


			}

			echo '</div>';

			echo $args['after_widget'];

		}

	}

	// Widget Backend
	public function form( $instance ) {

		$title = isset($instance[ 'title' ]) ? $instance[ 'title' ] : '';
		$showcount = isset($instance[ 'showcount' ]) ? $instance[ 'showcount' ] : 3;

		$featured_select_slugs = array_keys($this->featured_select);
		$featured_select = isset($instance['featured_select']) ? $instance[ 'featured_select' ] : $featured_select_slugs[0];

		$text_displays_slugs = array_keys($this->text_displays);
		$showtext = isset($instance['showtext']) ? $instance[ 'showtext' ] : $text_displays_slugs[0];

		$cb_showtitle = (isset($instance['cb_showtitle']) && $instance['cb_showtitle'] == 'true') ? ' checked' : '';
		$cb_showprice = (isset($instance['cb_showprice']) && $instance['cb_showprice'] == 'true') ? ' checked' : '';

		// Widget admin form
		?>
		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<p>
		<label for="<?php echo $this->get_field_id( 'showcount' ); ?>"><?php _e( 'Vehicles to show:' ); ?></label>
		<select class="widefat" id="<?php echo $this->get_field_id('showcount'); ?>" name="<?php echo $this->get_field_name('showcount'); ?>">
		<?php

			for ($i=1; $i < 8; $i++) {
				$select_text = ($i == $showcount) ? ' selected' : '';
				echo sprintf('<option value="%1$d"%2$s>%1$d</option>',$i,$select_text);
			}

		?>
		</select>
		</p>

		<p>
		<label for="<?php echo $this->get_field_id( 'featured_select' ); ?>"><?php _e( 'Vehicle Selection:' ); ?></label>
		<select class="widefat" id="<?php echo $this->get_field_id('featured_select'); ?>" name="<?php echo $this->get_field_name('featured_select'); ?>">
		<?php
			foreach ($this->featured_select as $slug => $label) {
				$select_text = ($slug == $featured_select) ? ' selected' : '';
				echo sprintf('<option value="%s"%s>%s</option>',$slug,$select_text,$label);
			}
		?>
		</select>
		</p>

		<p>
		<label for="<?php echo $this->get_field_id( 'showtext' ); ?>"><?php _e( 'Text Overlay:' ); ?></label>
		<select class="widefat" id="<?php echo $this->get_field_id('showtext'); ?>" name="<?php echo $this->get_field_name('showtext'); ?>">
		<?php
			foreach ($this->text_displays as $slug => $label) {
				$select_text = ($slug == $showtext) ? ' selected' : '';
				echo sprintf('<option value="%s"%s>%s</option>',$slug,$select_text,$label);
			}
		?>
		</select>
		</p>
		<p>
		<label for="<?php echo $this->get_field_id('cb_showtitle'); ?>"><input type="checkbox" id="<?php echo $this->get_field_id('cb_showtitle'); ?>" name="<?php echo $this->get_field_name('cb_showtitle'); ?>" value="true"<?php echo $cb_showtitle; ?>> Show Vehicle Title</label>
		</p>
		<p>
		<label for="<?php echo $this->get_field_id('cb_showprice'); ?>"><input type="checkbox" id="<?php echo $this->get_field_id('cb_showprice'); ?>" name="<?php echo $this->get_field_name('cb_showprice'); ?>" value="true"<?php echo $cb_showprice; ?>> Show Vehicle Price</label>
		</p>

		<?php
	}

	// Updating widget replacing old instances with new
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['showcount'] = ( ! empty( $new_instance['showcount'] ) ) ? strip_tags( $new_instance['showcount'] ) : 3;
		$instance['featured_select'] = ( ! empty( $new_instance['featured_select'] ) ) ? strip_tags( $new_instance['featured_select'] ) : '';
		$instance['showtext'] = ( ! empty( $new_instance['showtext'] ) ) ? strip_tags( $new_instance['showtext'] ) : '';
		$instance['cb_showtitle'] = ( !empty( $new_instance['cb_showtitle'] ) ) ? $new_instance['cb_showtitle'] : '';
		$instance['cb_showprice'] = ( !empty( $new_instance['cb_showprice'] ) ) ? $new_instance['cb_showprice'] : '';
		return $instance;
	}

} // Class Inventory_Slider




// Inventory Grid
class Inventory_Grid extends WP_Widget {

	private $column_options = array(3,4,5);

	const ID_BASE = '_invp_inventory_grid';

	function __construct() {
		parent::__construct(
			self::ID_BASE,
			'Grid',
			array( 'description' => 'Display a grid of vehicles.', )
		);

		add_action( 'invp_delete_all_data', array( $this, 'delete_option' ) );
	}

	public function delete_option() {
		delete_option( 'widget_' . self::ID_BASE );
	}

	private function get_column_options_html($selected_term) {
 		$html = '';
 		foreach ($this->column_options as $index => $columns) {
 			$selected = ($selected_term == $columns) ? ' selected' : '';
 			$html .= sprintf('<option value="%1$d"%2$s>%1$d columns</option>', $columns, $selected);
 		}
 		return $html;
 	}

	// front-end
	public function widget( $args, $instance ) {

		$title = apply_filters( 'widget_title', $instance['title'] );
		$columns = (isset($instance['columns'])) ? $instance['columns'] : 5;
		$limit = (isset($instance['limit'])) ? $instance['limit'] : $columns * 3;
		$show_captions = (isset($instance['cb_showcaptions']) && $instance['cb_showcaptions'] == 'true');
		$show_button = (isset($instance['cb_showbutton']) && $instance['cb_showbutton'] == 'true');

		switch ($columns) {
		    case 3:
		        $col_class = 'one-third';
		        break;
		    case 4:
		        $col_class = 'one-fourth';
		        break;
		    case 5:
		        $col_class = 'one-fifth';
		        break;
		}

		$limit = ($limit != 0) ? $limit : -1;

		// before and after widget arguments are defined by themes
		echo $args['before_widget'];
		if (!empty( $title ))
		echo $args['before_title'] . $title . $args['after_title'];

		$gp_args=array(
			'posts_per_page'=>$limit,
			'post_type'=>'inventory_vehicle',
			'meta_key'=>'_thumbnail_id', //has thumbnail
			'fields' => 'ids',
			'orderby'=>'rand',
			'order' => 'ASC'
		);

		//Does the user want featured vehicles only?
		if( isset($instance['cb_featured_only']) && 'true' == $instance['cb_featured_only'] ) {
			$gp_args['meta_query'] = array(
				array(
					'key'     => 'inventory_presser_featured',
					'value'   => '1',
				),
			);
		}

		$inventory_ids = get_posts( $gp_args );

		$grid_html = '';

		if ($inventory_ids) {

			$grid_html .= '<div class="invp-grid pad cf">';
			$grid_html .= '<ul class="grid-slides">';

			foreach ($inventory_ids as $inventory_id) {

				$vehicle = new Inventory_Presser_Vehicle($inventory_id);


				$grid_html .= '<li class="grid '.$col_class.'"><a class="grid-link" href="'.$vehicle->url.'">';

				$grid_html .= '<div class="grid-image" style="background-image: url('.wp_get_attachment_image_url(get_post_thumbnail_id($inventory_id), 'large').');">';
				$grid_html .= "</div>";

				if ($show_captions) {
					$grid_html .= "<p class=\"grid-caption\">";
					$grid_html .= $vehicle->post_title.'&nbsp;&nbsp;';
					$grid_html .= $vehicle->price(' ');
					$grid_html .= "</p>";
				}

				$grid_html .= "</a></li>\n";

			}

			$grid_html .= '</ul>';
			$grid_html .= "</div>";
			if ($show_button) {
				$grid_html .= '<div class="invp-grid-button"><a href="'.get_post_type_archive_link( 'inventory_vehicle' ).'" class="_button _button-med">Full Inventory</a></div>';
			}

		}

		echo $grid_html;

		echo $args['after_widget'];
	}

	// Widget Backend
	public function form( $instance ) {

		$title = isset($instance[ 'title' ]) ? $instance[ 'title' ] : '';
		$columns = (isset($instance['columns'])) ? $instance['columns'] : 5;
		$limit = (isset($instance['limit'])) ? $instance['limit'] : $columns * 3;

		// Widget admin form
		?>
		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>">Title:</label>
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>

		<p>
		<label for="<?php echo $this->get_field_id('columns'); ?>">Column count:</label>
		<select class="widefat" id="<?php echo $this->get_field_id('columns'); ?>" name="<?php echo $this->get_field_name('columns'); ?>">
		<?php echo $this->get_column_options_html($columns); ?>
		</select>
		</p>
		<p>
		<label for="<?php echo $this->get_field_id( 'limit' ); ?>">Maximum:</label>
		<input class="widefat" id="<?php echo $this->get_field_id('limit'); ?>" name="<?php echo $this->get_field_name('limit'); ?>" type="number" value="<?php echo esc_attr( $limit ); ?>" />
		</p>
		<p>
			<input type="checkbox" id="<?php echo $this->get_field_id('cb_showcaptions'); ?>" name="<?php echo $this->get_field_name('cb_showcaptions'); ?>" value="true"<?php checked( 'true', isset( $instance['cb_showcaptions'] ) ? $instance['cb_showcaptions'] : '', true ); ?>>
			<label for="<?php echo $this->get_field_id('cb_showcaptions'); ?>">Show captions</label>
			<br />
			<input type="checkbox" id="<?php echo $this->get_field_id('cb_showbutton'); ?>" name="<?php echo $this->get_field_name('cb_showbutton'); ?>" value="true"<?php checked( 'true', isset( $instance['cb_showbutton'] ) ? $instance['cb_showbutton'] : '', true ); ?>>
			<label for="<?php echo $this->get_field_id('cb_showbutton'); ?>">Show inventory button</label>
			<br />
			<input type="checkbox" id="<?php echo $this->get_field_id('cb_featured_only'); ?>" name="<?php echo $this->get_field_name('cb_featured_only'); ?>" value="true"<?php checked( 'true', isset( $instance['cb_featured_only'] ) ? $instance['cb_featured_only'] : '', true ); ?>>
			<label for="<?php echo $this->get_field_id('cb_featured_only'); ?>">Featured vehicles only</label>
		</p>

		<?php
	}

	// Updating widget replacing old instances with new
	public function update( $new_instance, $old_instance ) {

		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['columns'] = ( ! empty( $new_instance['columns'] ) ) ? strip_tags( $new_instance['columns'] ) : 5;
		$instance['limit'] = ( ! empty( $new_instance['limit'] ) ) ? strip_tags( $new_instance['limit'] ) : 15;
		$instance['cb_showcaptions'] = ( !empty( $new_instance['cb_showcaptions'] ) ) ? $new_instance['cb_showcaptions'] : '';
		$instance['cb_showbutton'] = ( !empty( $new_instance['cb_showbutton'] ) ) ? $new_instance['cb_showbutton'] : '';
		$instance['cb_featured_only'] = ( !empty( $new_instance['cb_featured_only'] ) ) ? $new_instance['cb_featured_only'] : '';

		return $instance;
	}

} // Class Inventory_Grid


// Price Filters
class Price_Filters extends WP_Widget {

	const ID_BASE = '_invp_price_filters';
	const CUSTOM_POST_TYPE = 'inventory_vehicle';

	var $price_defaults = array(5000,10000,15000,20000);
	var $display_types = array(
			'buttons'=>'Buttons',
			'text'=>'Text',
		);
	var $orientations = array(
			'horizontal'=>'Horizontal',
			'vertical'=>'Vertical',
		);

	function __construct() {
		parent::__construct(
			self::ID_BASE,
			'Maximum Price Filter',
			array( 'description' => 'Filter vehicles by a maximum price.', )
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

		echo sprintf('<div class="price-filter price-filter-%s">',$instance['orientation']);
		if (!empty( $title )) {
			echo '<div class="price-title">'.$args['before_title'] . $title . $args['after_title'].'</div>';
		}

		if (!$reset_link_only) {

			$price_points = (isset($instance['prices']) && is_array($instance['prices'])) ? $instance['prices'] : $this->price_defaults;

			$base_link = add_query_arg( array(
			    'orderby' => 'inventory_presser_price',
			    'order' => 'DESC',
			), get_post_type_archive_link(self::CUSTOM_POST_TYPE));

			$class_string = ($instance['display_type'] == 'buttons') ? ' class="_button _button-med"' : ' class="price-filter-text"';

			foreach ($price_points as $price_point) {
				$this_link = add_query_arg( 'max_price', $price_point, $base_link);
				echo sprintf('<div><a href="%s"%s><i class="fa fa-arrow-circle-down"></i>&nbsp;&nbsp;%s</a></div>',$this_link,$class_string,'$' . number_format($price_point, 0, '.', ',' ));
			}

		}

		if (isset($_GET['max_price'])) {
			echo sprintf('<div><a href="%s">Remove %s Price Filter</a></div>', remove_query_arg('max_price'),'$' . number_format((int)$_GET['max_price'], 0, '.', ',' ));
		}

		echo '</div>';

		echo $args['after_widget'];

	}

	// Widget Backend
	public function form( $instance ) {

		$title = isset($instance['title']) ? $instance[ 'title' ] : 'Price Filter';
		$prices = (isset($instance['prices']) && is_array($instance['prices'])) ? implode(',', $instance['prices']) : implode(',', $this->price_defaults);
		$display_type_slugs = array_keys($this->display_types);
		$display_type = isset($instance['display_type']) ? $instance[ 'display_type' ] : $display_type_slugs[0];
		$orientation_slugs = array_keys($this->orientations);
		$orientation = isset($instance['orientation']) ? $instance[ 'orientation' ] : $orientation_slugs[0];
		$cb_reset_link_only = (isset($instance[ 'cb_reset_link_only' ]) && $instance[ 'cb_reset_link_only' ] == 'true') ? ' checked' : '';

		// Widget admin form
		?>
		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<p>
		<label for="<?php echo $this->get_field_id('prices'); ?>">Price Points (separated by commas)</label>
		<textarea class="widefat" id="<?php echo $this->get_field_id('prices'); ?>" name="<?php echo $this->get_field_name('prices'); ?>"><?php echo esc_attr( $prices ); ?></textarea>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('display_type'); ?>">Display Format:</label>
			<select class="widefat" id="<?php echo $this->get_field_id('display_type'); ?>" name="<?php echo $this->get_field_name('display_type'); ?>">
			<?php
			foreach ($this->display_types as $key => $label) {
				$selected = ($display_type == $key) ? ' selected' : '';
				echo sprintf('<option value="%s"%s>%s</option>', $key, $selected, $label);
			}
			?>
			</select>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('orientation'); ?>">Orientation:</label>
			<select class="widefat" id="<?php echo $this->get_field_id('orientation'); ?>" name="<?php echo $this->get_field_name('orientation'); ?>">
			<?php
			foreach ($this->orientations as $key => $label) {
				$selected = ($orientation == $key) ? ' selected' : '';
				echo sprintf('<option value="%s"%s>%s</option>', $key, $selected, $label);
			}
			?>
			</select>
		</p>
		<p>
		<label for="<?php echo $this->get_field_id('cb_reset_link_only'); ?>">Show Reset Link Only</label>
		<input type="checkbox" id="<?php echo $this->get_field_id('cb_reset_link_only'); ?>" name="<?php echo $this->get_field_name('cb_reset_link_only'); ?>" value="true"<?php echo $cb_reset_link_only; ?>>
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

} // Class Price_Filters

// bootstrap class for these widgets
class Inventory_Presser_Location_Widgets {

	const CUSTOM_POST_TYPE = 'inventory_vehicle';

	function __construct( ) {
		add_action( 'widgets_init', array( $this, 'widgets_init' ) );
		add_action( 'current_screen', array( $this, 'check_ids' ) );
		if ( ! is_admin() && (
			isset( $_GET['min_price'] )
			|| isset( $_GET['max_price'] )
			|| isset( $_GET['min_odometer'] )
			|| isset( $_GET['max_odometer'] )
			|| isset( $_GET['favorites'] )
		) ) {
			add_action( 'pre_get_posts', array( $this, 'pre_get_posts'),99);
		}
	}

	public function pre_get_posts( $query ) {

		//Do not mess with the query if it's not the main one and our CPT
		if ( !$query->is_main_query() || $query->query_vars['post_type'] != self::CUSTOM_POST_TYPE ) {
			return;
		}

		if ( isset( $_GET['favorites'] ) && isset( $_COOKIE['vehicle_favorites'] ) ) {
			$query->set( 'post__in', json_decode( $_COOKIE['vehicle_favorites'] ) );
		}

		//Get original meta query
		$meta_query = $query->get('meta_query');

		if ( isset( $_GET['max_price'] )
			|| isset( $_GET['min_price'] )
			|| isset( $_GET['max_odometer'] )
			|| isset( $_GET['min_odometer'] )
		) {
			$meta_query['relation'] = 'AND';
		}

		if ( isset( $_GET['max_price'] ) ) {
			$meta_query[] = array(
	            'key'     => 'inventory_presser_price',
	            'value'   => (int) $_GET['max_price'],
	            'compare' => '<=',
	            'type'    => 'numeric'
	        );
		}

		if ( isset( $_GET['min_price'] ) ) {
			$meta_query[] = array(
	            'key'     => 'inventory_presser_price',
	            'value'   => (int) $_GET['min_price'],
	            'compare' => '>=',
	            'type'    => 'numeric'
	        );
		}

		if ( isset( $_GET['min_odometer'] ) ) {
			$meta_query[] = array(
	            'key'     => 'inventory_presser_odometer',
	            'value'   => (int) $_GET['min_odometer'],
	            'compare' => '>=',
	            'type'    => 'numeric'
	        );
		}

		if ( isset( $_GET['max_odometer'] ) ) {
			$meta_query[] = array(
	            'key'     => 'inventory_presser_odometer',
	            'value'   => (int) $_GET['max_odometer'],
	            'compare' => '<=',
	            'type'    => 'numeric'
	        );
		}

		$query->set( 'meta_query', $meta_query );
		return $query;
	}

	function widgets_init() {
		register_widget('Inventory_Presser_Location_Hours');
		register_widget('Inventory_Presser_Location_Address');
		register_widget('Inventory_Presser_Location_Phones');
		register_widget('Carfax_Widget');
		register_widget('KBB_Widget');
		register_widget('Stock_Photo_Slider');
		register_widget('Inventory_Slider');
		register_widget('Inventory_Grid');
		register_widget('Price_Filters');
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