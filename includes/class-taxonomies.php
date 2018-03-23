<?php
/**
 * An object that defines and manipulates our custom taxonomies and their terms.
 *
 *
 * @since      1.3.1
 * @package    Inventory_Presser
 * @subpackage Inventory_Presser/includes
 * @author     Corey Salzano <corey@fridaynet.com>, John Norton <norton@fridaynet.com>
 */
class Inventory_Presser_Taxonomies {

	var $post_type;
	var $days = array('Mon','Tue','Wed','Thu','Fri','Sat','Sun');

	function __construct( $post_type='inventory_vehicle' ) {
		$this->post_type = $post_type;
	}

	function hooks() {

		//create custom taxonomies for vehicles
		add_action( 'init', array( $this, 'create_custom_taxonomies' ) );

		add_action( 'invp_delete_all_data', array( $this, 'delete_term_data' ));

		// location taxonomy admin actions
		add_action( 'location_add_form_fields', array( $this, 'add_location_fields'), 10, 2 );
		add_action( 'location_add_form_fields', array( $this, 'add_location_fields_javascript'), 11, 1 );
		add_action( 'created_location', array( $this, 'save_location_meta'), 10, 2 );
		add_action( 'location_edit_form_fields', array( $this, 'edit_location_field'), 10, 2 );
		add_action( 'edited_location', array( $this, 'save_location_meta'), 10, 2 );

		//Sort some taxonomy terms as numbers
		add_filter( 'get_terms_orderby', array( $this, 'sort_terms_as_numbers' ), 10,  3 );

		//Save custom taxonomy terms when posts are saved
		add_action( 'save_post_' . $this->post_type, array( $this, 'save_vehicle_taxonomy_terms' ), 10, 2 );

		//Load our scripts
		add_action( 'admin_enqueue_scripts', array( $this, 'load_scripts' ) );
	}

	/* location taxonomy */
	function add_location_fields( $taxonomy ) {
	    ?>

	    <div class="form-field term-group location-tax">
		    <div class="form-wrap form-field">
		        <label>Phone Numbers</label>
		        <div class="repeat-group">
		        	<div class="repeat-container"></div>
		        	<div class="repeat-this">
		        		<div class="repeat-form">
					        <input type="text" name="phone_description[]" placeholder="Label" />
					        <input type="text" name="phone_number[]" placeholder="Number" required />
				        </div>
				        <div class="repeat-buttons">
				        	<span class="dashicons dashicons-menu repeat-move"></span>
				        	<span class="dashicons dashicons-trash repeat-delete"></span>
				        </div>
			        </div>
			        <button type="button" class="repeat-add">Add Phone Block</button>
		        </div>
		    </div>
		    <div class="form-wrap form-field">
		        <label>Hours</label>
		        <div class="repeat-group">
		        	<div class="repeat-container"></div>
		        	<div class="repeat-this">
		        		<div class="repeat-form">

				        	<input type="text" name="hours_title[]" placeholder="Title" />

				        	<table>
				        		<thead>
				        			<th></th>
				        			<th>Open</th>
				        			<th></th>
				        			<th>Close</th>
				        			<th>Appt Only</th>
				        		</thead>
				        		<tbody>
				        			<?php foreach ($this->days as $index => $day) { ?>
					        		<tr>
					        			<th><?php echo $day ?></th>
					        			<td><input name="hours[<?php echo $index ?>][open][]" class="timepick" type="text"></td>
					        			<td>to</td>
					        			<td><input name="hours[<?php echo $index ?>][close][]" class="timepick" type="text"></td>
					        			<td>
											<select name="hours[<?php echo $index ?>][appt][]">
												<option value="0">No</option>
												<option value="1">Yes</option>
											</select>
					        			</td>
					        		</tr>
					        		<?php } ?>
					        	</tbody>
				        	</table>

				        </div>
				        <div class="repeat-buttons">
				        	<span class="dashicons dashicons-menu repeat-move"></span>
				        	<span class="dashicons dashicons-trash repeat-delete"></span>
				        </div>
			        </div>
			        <p class="description"><?php _e( 'When saving multiple sets of hours for a single location, position the primary showroom hours first.', 'inventory_presser' ); ?></p>
			        <p><button type="button" class="repeat-add">Add Hours Block</button></p>
		        </div>
	        </div>
	    </div>

	    <?php
	}

	/**
	 * When the user is manually adding a location term, populate the slug
	 * while they type the name.
	 */
	function add_location_fields_javascript() {
		?><script type="text/javascript"><!--
		jQuery('#tag-name').on('input', function(){
			jQuery('#tag-slug').val( jQuery(this).val().replace(' ', '-').replace(/[^a-z\-0-9]/gi,'').toLowerCase());
		});
		//-->
		</script><?php
	}

	function convert_hyphens_to_underscores( $str ) {
		return str_replace( '-', '_', $str );
	}

	function create_custom_taxonomies() {
		//loop over this data, register the taxonomies, and populate the terms if needed
		$taxonomy_data = $this->taxonomy_data();
		for( $i=0; $i<sizeof( $taxonomy_data ); $i++ ) {
			//create the taxonomy
			$taxonomy_name = $this->convert_hyphens_to_underscores( $taxonomy_data[$i]['args']['query_var'] );
			register_taxonomy( $taxonomy_name, $this->post_type(), $taxonomy_data[$i]['args'] );
		}
	}

	function delete_term_data() {
		//remove the terms in taxonomies
		$taxonomy_data = $this->taxonomy_data();
		for( $i=0; $i<sizeof( $taxonomy_data ); $i++ ) {
			$tax = $taxonomy_data[$i]['args']['label'];
			$terms = get_terms( $tax, array( 'fields' => 'ids', 'hide_empty' => false ) );
			foreach ( $terms as $value ) {
				wp_delete_term( $value, $tax );
			}
		}
	}

	function edit_location_field( $term, $taxonomy ){

	    // get current term meta
	    $location_meta = get_term_meta( $term->term_id, 'location-phone-hours', true );
	    // make sure the current term meta has unique id's
	    if ($location_meta) {
    		$location_meta = Inventory_Presser_Location_Helper::getInstance()->check_location_term_meta_ids($term->term_id, $location_meta);
    	}

	    ?>
	    <tr class="form-field term-group-wrap">
	        <th scope="row"><label>Phone Numbers</label></th>
	        <td>
		        <div class="repeat-group">
		        	<div class="repeat-container">
					<?php if (isset($location_meta['phones'])) { ?>
					<?php foreach ($location_meta['phones'] as $index => $phone) { ?>

			        	<div class="repeated">
			        		<div class="repeat-form">
							<?php
							echo sprintf('<input type="hidden" name="phone_uid[]" value="%s" />', $phone['uid']);
							echo sprintf('<input type="text" name="phone_description[]" value="%s" placeholder="Label" />', $phone['phone_description']);
							echo sprintf('<input type="text" name="phone_number[]" value="%s" placeholder="Number" />', $phone['phone_number']);
							?>
					        </div>
					        <div class="repeat-buttons">
					        	<span class="dashicons dashicons-menu repeat-move"></span>
					        	<span class="dashicons dashicons-trash repeat-delete"></span>
					        </div>
				        </div>
					<?php } ?>
					<?php } ?>
					</div>
					<div class="repeat-this">
		        		<div class="repeat-form">
					        <input type="text" name="phone_description[]" placeholder="Label" />
					        <input type="text" name="phone_number[]" placeholder="Number" />
				        </div>
				        <div class="repeat-buttons">
				        	<span class="dashicons dashicons-menu repeat-move"></span>
				        	<span class="dashicons dashicons-trash repeat-delete"></span>
				        </div>
			        </div>
			        <button type="button" class="repeat-add">Add Phone Block</button>
		        </div>
			</td>
	    </tr>
	    <tr class="form-field term-group-wrap">
	        <th scope="row"><label>Hours</label></th>
	        <td>
		        <div class="repeat-group">
		        	<div class="repeat-container">
					<?php if (isset($location_meta['hours'])) { ?>
					<?php foreach ($location_meta['hours'] as $index => $hours) { ?>
						<div class="repeated">
			        		<div class="repeat-form">

			       				<input type="text" name="hours_title[]" placeholder="Title" value="<?php echo $hours['title'] ?>" />
			       				<input type="hidden" name="hours_uid[]" placeholder="Title" value="<?php echo $hours['uid'] ?>" />

					        	<table class="repeater-table">
					        		<thead>
					        			<td></td>
					        			<td>Open</td>
					        			<td></td>
					        			<td>Close</td>
					        			<td>Appt Only</td>
					        		</thead>
					        		<tbody>
					        			<?php foreach ($this->days as $index => $day) { ?>
						        		<tr>
						        			<td><?php echo $day ?></td>
						        			<td><input name="hours[<?php echo $index ?>][open][]" class="timepick" type="text" value="<?php echo $hours[$index]['open'] ?>"></td>
						        			<td>to</td>
						        			<td><input name="hours[<?php echo $index ?>][close][]" class="timepick" type="text" value="<?php echo $hours[$index]['close'] ?>"></td>
						        			<td>
												<select name="hours[<?php echo $index ?>][appt][]" autocomplete="off">
													<option value="0"<?php echo ($hours[$index]['appt'] == '0') ? ' selected' : ''; ?>>No</option>
													<option value="1"<?php echo ($hours[$index]['appt'] == '1') ? ' selected' : ''; ?>>Yes</option>
												</select>
						        			</td>
						        		</tr>
						        		<?php } ?>
						        	</tbody>
					        	</table>

					        </div>
					        <div class="repeat-buttons">
					        	<span class="dashicons dashicons-menu repeat-move"></span>
					        	<span class="dashicons dashicons-trash repeat-delete"></span>
					        </div>
				        </div>
					<?php } ?>
					<?php } ?>
		        	</div>
		        	<div class="repeat-this">
		        		<div class="repeat-form">

		       				<input type="text" name="hours_title[]" placeholder="Title" />

				        	<table class="repeater-table">
				        		<thead>
				        			<td></td>
				        			<td>Open</td>
				        			<td></td>
				        			<td>Close</td>
				        			<td>Appt Only</td>
				        		</thead>
				        		<tbody>
					        		<?php foreach ($this->days as $index => $day) { ?>
					        		<tr>
					        			<td><?php echo $day ?></td>
					        			<td><input name="hours[<?php echo $index ?>][open][]" class="timepick" type="text"></td>
					        			<td>to</td>
					        			<td><input name="hours[<?php echo $index ?>][close][]" class="timepick" type="text"></td>
					        			<td>
											<select name="hours[<?php echo $index ?>][appt][]">
												<option value="0">No</option>
												<option value="1">Yes</option>
											</select>
					        			</td>
					        		</tr>
					        		<?php } ?>
					        	</tbody>
				        	</table>

				        </div>
				        <div class="repeat-buttons">
				        	<span class="dashicons dashicons-menu repeat-move"></span>
				        	<span class="dashicons dashicons-trash repeat-delete"></span>
				        </div>
			        </div>
			        <p class="description"><?php _e( 'When saving multiple sets of hours for a single location, position the primary showroom hours first.', 'inventory_presser' ); ?></p>
			        <p><button type="button" class="repeat-add">Add Hours Block</button></p>
		        </div>
	        </td>
	    </tr><?php
	}

	function get_term_slug( $taxonomy_name, $post_id ) {
		$terms = wp_get_object_terms( $post_id, $taxonomy_name, array( 'orderby' => 'term_id', 'order' => 'ASC' ) );
		if ( ! is_wp_error( $terms ) ) {
			if ( isset( $terms[0] ) && isset( $terms[0]->name ) ) {
				return $terms[0]->slug;
			}
		}
		return '';
	}

	function load_scripts($hook) {
		global $current_screen;
		if (($hook == 'edit-tags.php' || $hook == 'term.php') && $current_screen->post_type == $this->post_type() && $current_screen->taxonomy == 'location') {
			wp_enqueue_style('inventory-presser-timepicker-css',  plugins_url( '/css/jquery.timepicker.css', dirname( __FILE__ ) ));
			wp_enqueue_script('inventory-presser-timepicker', plugins_url( '/js/jquery.timepicker.min.js', dirname( __FILE__ ) ), array('jquery'), '1.8.10');
			wp_enqueue_script('jquery-ui-sortable');
			wp_enqueue_script('inventory-presser-location', plugins_url( '/js/tax-location.js', dirname( __FILE__ ) ), array('inventory-presser-timepicker','jquery-ui-sortable'));
		}

	}

	function meta_box_html_condition( $post ) {
		echo $this->taxonomy_meta_box_html( 'condition', 'inventory_presser_condition', $post );
	}

	function meta_box_html_cylinders( $post ) {
		echo $this->taxonomy_meta_box_html( 'cylinders', 'inventory_presser_cylinders', $post );
	}

	function meta_box_html_availability( $post ) {
		echo $this->taxonomy_meta_box_html( 'availability', 'inventory_presser_availability', $post );
	}

	function meta_box_html_drive_type( $post ) {
		echo $this->taxonomy_meta_box_html( 'drive_type', 'inventory_presser_drive_type', $post );
	}

	function meta_box_html_fuel( $post ) {
		echo $this->taxonomy_meta_box_html( 'fuel', 'inventory_presser_fuel', $post );
	}

	function meta_box_html_propulsion_type( $post ) {
		echo $this->taxonomy_meta_box_html( 'propulsion_type', 'inventory_presser_propulsion_type', $post );
	}

	function meta_box_html_transmission( $post ) {
		echo $this->taxonomy_meta_box_html( 'transmission', apply_filters( 'invp_prefix_meta_key', 'transmission' ), $post );
	}

	function meta_box_html_type( $post ) {
		$html = $this->taxonomy_meta_box_html( 'type', apply_filters( 'invp_prefix_meta_key', 'type' ), $post );
		//add an onchange attribute to the select
		$html = str_replace( '<select', '<select onchange="invp_vehicle_type_changed( this.value );" ', $html );
		echo $html;
	}

	function meta_box_html_locations( $post ) {
		echo $this->taxonomy_meta_box_html( 'location', 'inventory_presser_location', $post ) .
			'<p><a href="edit-tags.php?taxonomy=location&post_type=' . $this->post_type() . '">Manage locations</a></p>';
	}

	function post_type() {
		return $this->post_type;
	}

	function save_location_meta( $term_id, $tt_id ) {

		if (isset($_POST['hours_title']) && isset($_POST['phone_number'])) {

			$meta_final = array('phones' => array(), 'hours' => array());

			// HOURS
			$count = count( $_POST['hours_title'] ) - 2;

			for ($i = 0; $i <= $count; $i++) {

				$has_data = false;

				$this_hours = array();

				// if this is an update, carry the id through
				if (isset($_POST['hours_uid'][$i])) {
					$this_hours['uid'] = $_POST['hours_uid'][$i];
				}
				// title of hours set
				$this_hours['title'] = sanitize_text_field($_POST['hours_title'][$i]);

				// add daily hours info to the final array, check to make sure there's data
				foreach ($_POST['hours'] as $day => $harray) {

					$open = sanitize_text_field($harray['open'][$i]);
					$close = sanitize_text_field($harray['close'][$i]);
					$appt = sanitize_text_field($harray['appt'][$i]);
					if (!$has_data && ($open || $close || $appt == '1')) {
						$has_data = true;
					}
					$this_hours[$day] = array('open' => $open, 'close' => $close, 'appt'=> $appt);
				}

				if ($has_data) {
					$meta_final['hours'][] = $this_hours;
				}

			}

			// PHONE NUMBERS
			foreach ($_POST['phone_number'] as $i => $phone_number) {

				$phone_number = sanitize_text_field($phone_number);

				if ($phone_number) {
					$this_phone = array(
						'phone_number' => $phone_number,
						'phone_description' => sanitize_text_field($_POST['phone_description'][$i])
					);
					// if this is an update, carry the id through
					if (isset($_POST['phone_uid'][$i])) {
						$this_phone['uid'] = $_POST['phone_uid'][$i];
					}
					// add this phone number to meta array
					$meta_final['phones'][] = $this_phone;
				}

	    	}

	    	// add uid's if we don't have them
	    	$meta_final = Inventory_Presser_Location_Helper::getInstance()->check_location_term_meta_ids($term_id, $meta_final, false);

	    	update_term_meta( $term_id, 'location-phone-hours', $meta_final);

		}
	}

	//save custom taxonomy terms when vehicles are saved
	function save_vehicle_taxonomy_terms( $post_id, $is_update ) {
		foreach( $this->slugs_array() as $slug ) {
			$taxonomy_name = $slug;
			switch( $slug ) {
				case 'style':
					$slug = 'body_style';
					break;
				case 'model_year':
					$slug = 'year';
					break;
			}
			$this->save_taxonomy_term( $post_id, $taxonomy_name, 'inventory_presser_' . $slug );
		}
	}

	function save_taxonomy_term( $post_id, $taxonomy_name, $element_name ) {
		if ( isset( $_POST[$element_name] ) ) {
			$term_slug = sanitize_text_field( $_POST[$element_name] );
			if ( '' == $term_slug ) {
				// the user is setting the vehicle type to empty string
				wp_remove_object_terms( $post_id, $this->get_term_slug( $taxonomy_name, $post_id ), $taxonomy_name );
				return;
			}
			$term = get_term_by( 'slug', $term_slug, $taxonomy_name );
			if ( empty( $term ) || is_wp_error( $term ) ) {
				//the term does not exist. create it
				$term_arr = array(
					'slug'        => sanitize_title( $term_slug ),
					'description' => $term_slug,
					'name'        => $term_slug,
				);
				$id_arr = wp_insert_term( $term_slug, $taxonomy_name, $term_arr );
				if( ! is_wp_error( $id_arr ) ) {
					$term->term_id = $id_arr['term_id'];
				}
			}
			$set = wp_set_object_terms( $post_id, $term->term_id, $taxonomy_name, false );
			if ( is_wp_error( $set ) ) {
				//There was an error setting the term
			}
		}
	}

	//the slug is the way the database identifies taxonomies, all lower-case and
	//underscores instead of spaces
	function slug( $label ) {
		return str_replace( ' ', '_', strtolower( $label ) );
	}

	//returns an array of all our taxonomy query vars
	function query_vars_array() {
		$arr = array();
		foreach( $this->taxonomy_data() as $taxonomy_array ) {
			if( ! isset( $taxonomy_array['args'] ) || ! isset( $taxonomy_array['args']['query_var'] ) ) {
				continue;
			}
			array_push( $arr, $this->slug( $taxonomy_array['args']['query_var'] ) );
		}
		return $arr;
	}

	//returns an array of all our taxonomy slugs
	function slugs_array() {
		$arr = array();
		foreach( $this->query_vars_array() as $query_var ) {
			array_push( $arr, str_replace( '-', '_', $query_var ) );
		}
		return $arr;
	}

	function sort_terms_as_numbers( $order_by, $args, $taxonomies ) {

		if( '' == $order_by ) { return ''; }

		$taxonomies_to_sort = array(
			'cylinders',
			'model_year',
		);
		foreach( $taxonomies_to_sort as $taxonomy_to_sort ) {
			if( in_array( $taxonomy_to_sort, $taxonomies ) ) {
				$order_by .=  '+0';
				break;
			}
		}
		return $order_by;
	}

	//this is an array of taxonomy names and the corresponding arrays of term data
	function taxonomy_data( ) {
		return apply_filters(
			'invp_taxonomy_data',
			array (
				array (
					'args' => array (
						'hierarchical'   => true,
						'label'          => 'Model years',
						'labels'         => array (
							'name'          => 'Model years',
							'singular_name' => 'Model year',
							'search_items'  => 'Search years',
							'popular_items' => 'Popular years',
							'all_items'     => 'All years',
						),
						'meta_box_cb'    => null,
						'query_var'      => 'model-year',
						'singular_label' => 'Model year',
						'show_in_menu'   => false,
						'show_in_rest'   => true,
						'show_ui'        => false,
					),
				),
				array (
					'args' => array (
						'hierarchical'   => true,
						'label'          => 'Makes',
						'labels'         => array (
							'name'          => 'Makes',
							'singular_name' => 'Make',
							'search_items'  => 'Search makes',
							'popular_items' => 'Popular makes',
							'all_items'     => 'All makes',
						),
						'meta_box_cb'    => null,
						'query_var'      => 'make',
						'singular_label' => 'Make',
						'show_in_menu'   => false,
						'show_in_rest'   => true,
						'show_ui'        => false,
					),
				),
				array (
					'args' => array (
						'hierarchical'   => true,
						'label'          => 'Models',
						'labels'         => array (
							'name'          => 'Models',
							'singular_name' => 'Model',
							'search_items'  => 'Search models',
							'popular_items' => 'Popular models',
							'all_items'     => 'All models',
						),
						'meta_box_cb'    => null,
						'query_var'      => 'model',
						'singular_label' => 'Model',
						'show_in_menu'   => false,
						'show_in_rest'   => true,
						'show_ui'        => false,
					),
				),
				array (
					'args' => array (
						'hierarchical'   => true,
						'label'          => 'Conditions',
						'labels'         => array (
							'name'          => 'Conditions',
							'singular_name' => 'Condition',
							'search_items'  => 'Search new and used',
							'popular_items' => 'Popular conditions',
							'all_items'     => 'All new and used',
						),
						'meta_box_cb'    => array( $this, 'meta_box_html_condition' ),
						'query_var'      => 'condition',
						'singular_label' => 'Condition',
						'show_in_menu'   => false,
						'show_in_rest'   => true,
					),
					'term_data' =>	array (
						'New'  => 'New',
						'Used' => 'Used',
					),
				),
				array (
					'args' => array (
						'hierarchical'   => true,
						'label'          => 'Types',
						'labels'         => array (
							'name'          => 'Types',
							'singular_name' => 'Type',
							'search_items'  => 'Search types',
							'popular_items' => 'Popular types',
							'all_items'     => 'All types',
						),
						'meta_box_cb'    => array( $this, 'meta_box_html_type' ),
						'query_var'      => 'type',
						'rest_base'      => 'inventory_type',
						'singular_label' => 'Type',
						'show_in_menu'   => false,
						'show_in_rest'   => true,
					),
					'term_data' =>	array (
						'ATV' => 'All Terrain Vehicle',
						'BOAT'=> 'Boat',
						'BUS' => 'Bus',
						'CAR' => 'Passenger Car',
						'MOT' => 'Motorcycle',
						'MOW' => 'Mower',
						'OTH' => 'Other',
						'RV'  => 'Recreational Vehicle',
						'SUV' => 'Sport Utility Vehicle',
						'TRLR'=> 'Trailer',
						'TRU' => 'Truck',
						'VAN' => 'Van',
					),
				),
				array (
					'args' => array (
						'hierarchical'   => true,
						'label'          => 'Availabilities',
						'labels'         => array (
							'name'          => 'Availabilities',
							'singular_name' => 'Availability',
							'search_items'  => 'Search availabilities',
							'popular_items' => 'Popular availabilities',
							'all_items'     => 'All sold and for sale',
						),
						'meta_box_cb'    => array( $this, 'meta_box_html_availability' ),
						'query_var'      => 'availability',
						'singular_label' => 'Availability',
						'show_in_menu'   => false,
						'show_in_rest'   => true,
					),
					'term_data' =>	array (
						'For sale' => 'For sale',
						'Sold'     => 'Sold',
					),
				),
				array (
					'args' => array (
						'hierarchical'   => true,
						'label'          => 'Drive types',
						'labels'         => array (
							'name'          => 'Drive types',
							'singular_name' => 'Drive type',
							'search_items'  => 'Search drive types',
							'popular_items' => 'Popular drive types',
							'all_items'     => 'All drive types',
						),
						'meta_box_cb'    => array( $this, 'meta_box_html_drive_type' ),
						'query_var'      => 'drive-type',
						'singular_label' => 'Drive type',
						'show_in_menu'   => false,
						'show_in_rest'   => true,
					),
					'term_data' =>	array (
						'4FD' => 'Front Wheel Drive w/4x4',
						'4RD' => 'Rear Wheel Drive w/4x4',
						'2WD' => 'Two Wheel Drive',
						'4WD' => 'Four Wheel Drive',
						'AWD' => 'All Wheel Drive',
						'FWD' => 'Front Wheel Drive',
						'RWD' => 'Rear Wheel Drive',
					),
				),

				/**
				 * Propulsion type is essentially drive type for boats
				 */

				array (
					'args' => array (
						'hierarchical'   => true,
						'label'          => 'Propulsion types',
						'labels'         => array (
							'name'          => 'Propulsion types',
							'singular_name' => 'Propulsion type',
							'search_items'  => 'Search propulsion types',
							'popular_items' => 'Popular propulsion types',
							'all_items'     => 'All propulsion types',
						),
						'meta_box_cb'    => array( $this, 'meta_box_html_propulsion_type' ),
						'query_var'      => 'propulsion-type',
						'singular_label' => 'Propulsion type',
						'show_in_menu'   => false,
						'show_in_rest'   => true,
					),
					'term_data' =>	array (
						'IN'  => 'Inboard',
						'OUT' => 'Outboard',
						'IO'  => 'Inboard/Outboard',
						'JET' => 'Jet',
					),
				),

				array (
					'args' => array (
						'hierarchical'   => true,
						'label'          => 'Fuels',
						'labels'         => array (
							'name'          => 'Fuel types',
							'singular_name' => 'Fuel type',
							'search_items'  => 'Search fuel types',
							'popular_items' => 'Popular fuel types',
							'all_items'     => 'All fuel types',
						),
						'meta_box_cb'    => array( $this, 'meta_box_html_fuel' ),
						'query_var'      => 'fuel',
						'singular_label' => 'Fuel',
						'show_in_menu'   => false,
						'show_in_rest'   => true,
					),
					'term_data' =>	array (
						'B' => 'Electric and Gas Hybrid',
						'C' => 'Convertible',
						'D' => 'Diesel',
						'E' => 'Electric',
						'F' => 'Flexible',
						'G' => 'Gas',
						'N' => 'Compressed Natural Gas',
						'P' => 'Propane',
						'R' => 'Hydrogen Fuel Cell',
						'U' => 'Unknown',
						'Y' => 'Electric and Diesel Hybrid',
					),
				),
				array (
					'args' => array (
						'hierarchical'   => true,
						'label'          => 'Transmissions',
						'labels'         => array (
							'name'          => 'Transmissions',
							'singular_name' => 'Transmission',
							'search_items'  => 'Search transmissions',
							'popular_items' => 'Popular transmissions',
							'all_items'     => 'All transmissions',
						),
						'meta_box_cb'    => array( $this, 'meta_box_html_transmission' ),
						'query_var'      => 'transmission',
						'singular_label' => 'Transmission',
						'show_in_menu'   => false,
						'show_in_rest'   => true,
					),
					'term_data' =>	array (
						'A' => 'Automatic',
						'E' => 'ECVT',
						'M' => 'Manual',
						'U' => 'Unknown',
					),
				),
				array (
					'args' => array (
						'hierarchical'   => true,
						'label'          => 'Cylinders',
						'labels'         => array (
							'name'          => 'Cylinders',
							'singular_name' => 'Cylinder count',
							'search_items'  => 'Search cylinder counts',
							'popular_items' => 'Popular cylinder counts',
							'all_items'     => 'All cylinder counts',
						),
						'meta_box_cb'    => array( $this, 'meta_box_html_cylinders' ),
						'query_var'      => 'cylinders',
						'singular_label' => 'Cylinders',
						'show_in_menu'   => false,
						'show_in_rest'   => true,
					),
					'term_data' => array (
						'1'  => '1',
						'2'  => '2',
						'3'  => '3',
						'4'  => '4',
						'5'  => '5',
						'6'  => '6',
						'8'  => '8',
						'10' => '10',
						'12' => '12',
					),
				),
				array (
					'args' => array (
						'hierarchical'   => true,
						'label'          => 'Body styles',
						'labels'         => array (
							'name'          => 'Body styles',
							'singular_name' => 'Body style',
							'search_items'  => 'Search body styles',
							'popular_items' => 'Popular body styles',
							'all_items'     => 'All body styles',
						),
						'meta_box_cb'    => null,
						'query_var'      => 'style',
						'singular_label' => 'Body style',
						'show_in_menu'   => false,
						'show_in_rest'   => true,
						'show_ui'        => false,
					),
				),
				array (
					'args' => array (
						'hierarchical'   => false,
						'label'          => 'Locations',
						'labels'         => array (
							'name'          => 'Location',
							'singular_name' => 'Location',
							'search_items'  => 'Search locations',
							'popular_items' => 'Popular locations',
							'all_items'     => 'All locations',
							'edit_item'     => __( 'Edit Location', 'inventory-presser' ),
							'update_item'   => __( 'Update Location', 'inventory-presser' ),
							'add_new_item'  => __( 'Add New Location', 'inventory-presser' ),
							'new_item_name' => __( 'New Location Name', 'inventory-presser' ),
							'menu_name'     => __( 'Locations', 'inventory-presser' ),
						),
						'meta_box_cb'    => array( $this, 'meta_box_html_locations' ),
						'query_var'      => 'location',
						'show_ui'			=> true,
						'singular_label' => 'Location',
						'show_in_menu'   => true,
						'show_in_rest'   => true,
					),
				),
			)
		);
	}

	function taxonomy_meta_box_html( $taxonomy_name, $element_name, $post ) {
		/**
		 * Creates HTML output for a meta box that turns a taxonomy into
		 * a select drop-down list instead of the typical checkboxes
		 */
		$HTML  = '<select name="' . $element_name . '" id="' . $element_name . '">'
			. '<option></option>'; //offering a blank value is the only way a user can remove the value

		//get all the term names and slugs for $taxonomy_name
		$terms = get_terms( $taxonomy_name,  array( 'hide_empty' => false ) );

		if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {

			//get the saved term for this taxonomy
			$saved_term_slug = $this->get_term_slug( $taxonomy_name, $post->ID );

			foreach( $terms as $term ) {
				$HTML .= '<option value="' . $term->slug . '"'
					. selected( strtolower( $term->slug ), strtolower( $saved_term_slug ), false )
					. '>' . $term->name . '</option>';
			}
		}
		return $HTML . '</select>';
	}
}
