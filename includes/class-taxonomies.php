<?php
defined( 'ABSPATH' ) or exit;

/**
 * An object that defines and manipulates our custom taxonomies and their terms.
 *
 *
 * @since      1.3.1
 * @package    Inventory_Presser
 * @subpackage Inventory_Presser/includes
 * @author     Corey Salzano <corey@friday.systems>, John Norton <norton@fridaynet.com>
 */
class Inventory_Presser_Taxonomies
{
	const CRON_HOOK_DELETE_TERMS = 'inventory_presser_delete_unused_terms';
	const LOCATION_MAX_PHONES = 10; //the maximum number of phones a single address holds
	const LOCATION_MAX_HOURS = 5; //the maximum number of sets of hours a single address holds

	/* location taxonomy */
	function add_location_fields( $taxonomy )
	{
		?><div class="form-field term-group location-tax">
			<div class="form-wrap form-field">
				<label><?php _e( 'Phone Numbers', 'inventory-presser' ); ?></label>
				<div class="repeat-group">
					<div class="repeat-container"></div>
					<div class="repeat-this">
						<div class="repeat-form">
							<input type="text" name="phone_description[]" placeholder="<?php _e( 'Label', 'inventory-presser' ); ?>" />
							<input type="text" name="phone_number[]" placeholder="<?php _e( 'Number', 'inventory-presser' ); ?>" required />
						</div>
						<div class="repeat-buttons">
							<span class="dashicons dashicons-menu repeat-move" title="<?php _e( 'Drag to reposition', 'inventory-presser' ); ?>"></span>
							<span class="dashicons dashicons-trash repeat-delete" title="<?php _e( 'Delete this phone number', 'inventory-presser' ); ?>"></span>
						</div>
					</div>
					<button type="button" class="repeat-add button action"><?php _e( 'Add Phone Number', 'inventory-presser' ); ?></button>
				</div>
			</div>
			<div class="form-wrap form-field">
				<label><?php _e( 'Hours', 'inventory-presser' ); ?></label>
				<div class="repeat-group">
					<div class="repeat-container"></div>
					<div class="repeat-this">
						<div class="repeat-form">

						<input type="text" name="hours_title[]" placeholder="<?php _e( 'Title', 'inventory-presser' ); ?>" />

						<table class="wp-list-table widefat fixed striped hours">
							<thead>
								<th class="day-col"></th>
								<th><?php _e( 'Open', 'inventory-presser' ); ?></th>
								<th class="to-col"></th>
								<th><?php _e( 'Close', 'inventory-presser' ); ?></th>
								<th><?php _e( 'Appt Only', 'inventory-presser' ); ?></th>
							</thead>
							<tbody><?php

								foreach( array_keys( INVP::weekdays() ) as $index => $day)
								{
									?><tr>
									<th><?php echo $day ?></th>
									<td><input name="hours[<?php echo $index ?>][open][]" class="timepick" type="text"></td>
									<th>to</th>
									<td><input name="hours[<?php echo $index ?>][close][]" class="timepick" type="text"></td>
									<td>
										<select name="hours[<?php echo $index ?>][appt][]">
											<option value="0"><?php _e( 'No', 'inventory-presser' ); ?></option>
											<option value="1"><?php _e( 'Yes', 'inventory-presser' ); ?></option>
										</select>
									</td>
								</tr><?php

								}

							?></tbody>
							</table>

						</div>
						<div class="repeat-buttons">
							<span class="dashicons dashicons-menu repeat-move" title="<?php _e( 'Drag to reposition', 'inventory-presser' ); ?>"></span>
							<span class="dashicons dashicons-trash repeat-delete" title="<?php _e( 'Delete this set of hours', 'inventory-presser' ); ?>"></span>
						</div>
					</div>
					<p class="description"><?php _e( 'When saving multiple sets of hours for a single location, position the primary showroom hours first.', 'inventory-presser' ); ?></p>
					<p><button type="button" class="repeat-add button action"><?php _e( 'Add Hours Block', 'inventory-presser' ); ?></button></p>
				</div>
			</div>
		</div><?php
	}

	/**
	 * When the user is manually adding a location term, populate the slug
	 * while they type the name.
	 */
	function add_location_fields_javascript()
	{
		?><script type="text/javascript"><!--
		jQuery('#tag-name').on('input', function(){
			jQuery('#tag-slug').val( jQuery(this).val().replace(' ', '-').replace(/[^a-z\-0-9]/gi,'').toLowerCase());
		});
		//-->
		</script><?php
	}

	static function create_taxonomies()
	{
		//loop over this data, register the taxonomies, and populate the terms if needed
		$taxonomy_data = self::taxonomy_data();
		for( $i=0; $i<sizeof( $taxonomy_data ); $i++ )
		{
			//create the taxonomy, replace hyphens with underscores
			$taxonomy_name = str_replace( '-', '_', $taxonomy_data[$i]['args']['query_var'] );
			register_taxonomy( $taxonomy_name, Inventory_Presser_Plugin::CUSTOM_POST_TYPE, $taxonomy_data[$i]['args'] );
		}
	}

	function delete_term_data()
	{
		//remove the terms in taxonomies
		$taxonomy_data = self::taxonomy_data();
		for( $i=0; $i<sizeof( $taxonomy_data ); $i++ )
		{
			$tax = $taxonomy_data[$i]['args']['label'];
			$terms = get_terms( $tax, array(
				'fields'     => 'ids',
				'hide_empty' => false
			) );
			foreach ( $terms as $value )
			{
				wp_delete_term( $value, $tax );
			}
		}
	}

	/**
	 * The nature of inserting and deleting vehicles means terms in a few of our
	 * taxonomies will be left behind and unused. This method deletes some of
	 * them.
	 */
	function delete_unused_terms()
	{
		$terms = get_terms( array(
			'taxonomy'   => array( 'model_year', 'make', 'model', 'style' ),
			'childless'  => true,
			'count'      => true,
			'hide_empty' => false,
		) );

		foreach( $terms as $term )
		{
			if( 0 == $term->count )
			{
				wp_delete_term( $term->term_id, $term->taxonomy );
			}
		}
	}

	function edit_location_field( $term, $taxonomy )
	{
		?><tr class="form-field term-group-wrap">
			<th scope="row"><label><?php _e( 'Phone Numbers', 'inventory-presser' ); ?></label></th>
			<td>
				<div class="repeat-group">
					<div class="repeat-container"><?php

					$phones = $this->get_phones( $term->term_id );
					if( ! empty( $phones ) )
					{
						foreach( $phones as $phone )
						{
							?><div class="repeated">
							<div class="repeat-form"><?php

							printf(
								'<input type="hidden" name="phone_uid[]" value="%s" />'
								. '<input type="text" name="phone_description[]" value="%s" placeholder="%s" />'
								. '<input type="text" name="phone_number[]" value="%s" placeholder="%s" />',
								$phone['uid'],
								$phone['description'],
								__( 'Label', 'inventory-presser' ),
								$phone['number'],
								__( 'Number', 'inventory-presser' )
							);

							?></div>
							<div class="repeat-buttons">
								<span class="dashicons dashicons-menu repeat-move"></span>
								<span class="dashicons dashicons-trash repeat-delete"></span>
							</div>
						</div><?php

						}
					}

					?></div>
					<div class="repeat-this">
						<div class="repeat-form">
							<input type="text" name="phone_description[]" placeholder="<?php _e( 'Label', 'inventory-presser' ); ?>" />
							<input type="text" name="phone_number[]" placeholder="<?php _e( 'Number', 'inventory-presser' ); ?>" />
						</div>
						<div class="repeat-buttons">
							<span class="dashicons dashicons-menu repeat-move" title="<?php _e( 'Drag to reposition', 'inventory-presser' ); ?>"></span>
							<span class="dashicons dashicons-trash repeat-delete" title="<?php _e( 'Delete this set of hours', 'inventory-presser' ); ?>"></span>
						</div>
					</div>
					<button type="button" class="repeat-add button action"><?php _e( 'Add Phone Number', 'inventory-presser' ); ?></button>
				</div>
			</td>
		</tr>
		<tr class="form-field term-group-wrap">
			<th scope="row"><label><?php _e( 'Hours', 'inventory-presser' ); ?></label></th>
			<td>
				<div class="repeat-group">
					<div class="repeat-container"><?php

					$hours_sets = self::get_hours( $term->term_id );
					$days = array_keys( INVP::weekdays() );
					if( ! empty( $hours_sets ) )
					{
						foreach( $hours_sets as $hours )
						{

						?><div class="repeated">
							<div class="repeat-form">

								<input type="text" name="hours_title[]" placeholder="<?php _e( 'Title', 'inventory-presser' ); ?>" value="<?php echo $hours['title'] ?>" />
								<input type="hidden" name="hours_uid[]" placeholder="<?php _e( 'Title', 'inventory-presser' ); ?>" value="<?php echo $hours['uid'] ?>" />

								<table class="repeater-table hours">
									<thead>
										<th class="day-col"></th>
										<th><?php _e( 'Open', 'inventory-presser' ); ?></th>
										<th class="to-col"></th>
										<th><?php _e( 'Close', 'inventory-presser' ); ?></th>
										<th><?php _e( 'Appt Only', 'inventory-presser' ); ?></th>
									</thead>
									<tbody><?php

										for( $d=0; $d<7; $d++)
										{

										?><tr>
										<td><?php echo ucfirst( substr( $days[$d], 0, 3 ) ); ?></td>
										<td><input name="hours[<?php echo $d ?>][open][]" class="timepick" type="text" value="<?php echo $hours[$days[$d] . '_open'] ?>"></td>
										<td>to</td>
										<td><input name="hours[<?php echo $d ?>][close][]" class="timepick" type="text" value="<?php echo $hours[$days[$d] . '_close'] ?>"></td>
										<td>
											<select name="hours[<?php echo $d ?>][appt][]" autocomplete="off">
												<option value="0"<?php echo ($hours[$days[$d] . '_appt'] == '0') ? ' selected' : ''; ?>><?php _e( 'No', 'inventory-presser' ); ?></option>
												<option value="1"<?php echo ($hours[$days[$d] . '_appt'] == '1') ? ' selected' : ''; ?>><?php _e( 'Yes', 'inventory-presser' ); ?></option>
											</select>
										</td>
									</tr><?php

										}

								?></tbody>
							</table>

							</div>
							<div class="repeat-buttons">
								<span class="dashicons dashicons-menu repeat-move" title="<?php _e( 'Drag to reposition', 'inventory-presser' ); ?>"></span>
								<span class="dashicons dashicons-trash repeat-delete" title="<?php _e( 'Delete this set of hours', 'inventory-presser' ); ?>"></span>
							</div>
						</div><?php

						}
					}

					?></div>
					<div class="repeat-this">
						<div class="repeat-form">

							<input type="text" name="hours_title[]" placeholder="Title" />

							<table class="repeater-table hours">
								<thead>
									<th class="day-col"></th>
									<th><?php _e( 'Open', 'inventory-presser' ); ?></th>
									<th class="to-col"></th>
									<th><?php _e( 'Close', 'inventory-presser' ); ?></th>
									<th><?php _e( 'Appt Only', 'inventory-presser' ); ?></th>
								</thead>
								<tbody><?php

									foreach( array_keys( INVP::weekdays() ) as $d => $day )
									{

									?><tr>
										<td><?php echo ucfirst( substr( $days[$d], 0, 3 ) ); ?></td>
										<td><input name="hours[<?php echo $d ?>][open][]" class="timepick" type="text"></td>
										<td>to</td>
										<td><input name="hours[<?php echo $d ?>][close][]" class="timepick" type="text"></td>
										<td>
											<select name="hours[<?php echo $d ?>][appt][]">
												<option value="0"><?php _e( 'No', 'inventory-presser' ); ?></option>
												<option value="1"><?php _e( 'Yes', 'inventory-presser' ); ?></option>
											</select>
										</td>
									</tr><?php

									}

									?></tbody>
							</table>

						</div>
						<div class="repeat-buttons">
							<span class="dashicons dashicons-menu repeat-move"></span>
							<span class="dashicons dashicons-trash repeat-delete"></span>
						</div>
					</div>
					<p class="description"><?php _e( 'When saving multiple sets of hours for a single location, position the primary showroom hours first.', 'inventory-presser' ); ?></p>
					<p><button type="button" class="repeat-add button action"><?php _e( 'Add Hours Block', 'inventory-presser' ); ?></button></p>
				</div>
			</td>
		</tr><?php
	}

	/**
	 * Creates a unique ID for a phone number or set of hours.
	 *
	 * @param string $salt_string Any string to be combined with rand() as the value to pass to md5()
	 * @return string A string of 12 characters.
	 */
	function generate_location_uid( $salt_string = null )
	{
		return substr( md5( strval( rand() ) . $salt_string ), 0, 12 );
	}

	public static function get_hours( $term_id )
	{
		$hours = array();
		$term_meta = get_term_meta( $term_id );

		for( $h=1; $h<=self::LOCATION_MAX_HOURS; $h++ )
		{
			//Are there hours in this slot?
			if( empty( $term_meta['hours_' . $h . '_uid'][0] ) )
			{
				//No, we're done with this location
				break;
			}

			$set = array(
				'uid'   => $term_meta['hours_' . $h . '_uid'][0],
				'title' => self::meta_array_value_single( $term_meta, 'hours_' . $h . '_title' ),
			);

			$days = array_keys( INVP::weekdays() );
			for( $d=0; $d<7; $d++ )
			{
				$set[$days[$d] . '_appt'] = self::meta_array_value_single( $term_meta, 'hours_' . $h . '_' . $days[$d] . '_appt' );
				$set[$days[$d] . '_open'] = self::meta_array_value_single( $term_meta, 'hours_' . $h . '_' . $days[$d] . '_open' );
				$set[$days[$d] . '_close'] = self::meta_array_value_single( $term_meta, 'hours_' . $h . '_' . $days[$d] . '_close' );
			}

			$hours[] = $set;
		}
		return $hours;
	}

	public static function get_phones( $term_id )
	{
		$phones = array();
		$term_meta = get_term_meta( $term_id );

		for( $p=1; $p<=self::LOCATION_MAX_PHONES; $p++ )
		{
			//Is there a phone number in this slot?
			if( empty( $term_meta['phone_' . $p . '_uid'][0] ) )
			{
				//No, we're done with this location
				break;
			}

			$phones[] = array(
				'uid'         => $term_meta['phone_' . $p . '_uid'][0],
				'description' => self::meta_array_value_single( $term_meta, 'phone_' . $p . '_description' ),
				'number'      => self::meta_array_value_single( $term_meta, 'phone_' . $p . '_number' ),
			);
		}
		return $phones;
	}

	static function get_term_slug( $taxonomy_name, $post_id )
	{
		$terms = wp_get_object_terms( $post_id, $taxonomy_name, array( 'orderby' => 'term_id', 'order' => 'ASC' ) );
		if ( ! is_wp_error( $terms ) && isset( $terms[0] ) && isset( $terms[0]->name ) )
		{
			return $terms[0]->slug;
		}
		return '';
	}


	function hooks()
	{
		//create custom taxonomies for vehicles
		add_action( 'init', array( $this, 'create_taxonomies' ) );
		add_action( 'init', array( $this, 'register_meta' ) );

		add_action( 'invp_delete_all_data', array( $this, 'delete_term_data' ) );

		// location taxonomy admin actions
		add_action( 'location_add_form_fields', array( $this, 'add_location_fields'), 10, 2 );
		add_action( 'location_add_form_fields', array( $this, 'add_location_fields_javascript'), 11, 1 );
		add_action( 'created_location', array( $this, 'save_location_meta'), 10, 2 );
		add_action( 'location_edit_form_fields', array( $this, 'edit_location_field'), 10, 2 );
		add_action( 'edited_location', array( $this, 'save_location_meta'), 10, 2 );

		//Sort some taxonomy terms as numbers
		add_filter( 'get_terms_orderby', array( $this, 'sort_terms_as_numbers' ), 10,  3 );

		//Save custom taxonomy terms when posts are saved
		add_action( 'save_post_' . Inventory_Presser_Plugin::CUSTOM_POST_TYPE, array( $this, 'save_vehicle_taxonomy_terms' ), 10, 2 );

		//Load our scripts
		add_action( 'admin_enqueue_scripts', array( $this, 'load_scripts' ) );

		//Do not include sold vehicles in listings unless an option is checked
		add_action( 'pre_get_posts', array( $this, 'maybe_exclude_sold_vehicles' ) );

		/**
		 * Run a weekly cron job to delete empty terms and update term counts.
		 * The counts aren't always updated when deleting vehicles, and I'm not
		 * yet able to reproduce the bug in local copies of the sites.
		 */
		add_action( self::CRON_HOOK_DELETE_TERMS, array( $this, 'delete_unused_terms' ) );
		add_action( self::CRON_HOOK_DELETE_TERMS, array( $this, 'update_term_counts' ) );

		//Put terms into our taxonomies when the plugin is activated
		register_activation_hook( dirname( dirname( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'inventory-presser.php', array( 'Inventory_Presser_Taxonomies', 'populate_default_terms' ) );
		//Schedule a weekly wp-cron job to delete empty terms in our taxonomies
		register_activation_hook( dirname( dirname( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'inventory-presser.php', array( 'Inventory_Presser_Taxonomies', 'schedule_terms_cron_job' ) );
		//Remove the wp-cron job during deactivation
		register_deactivation_hook( dirname( dirname( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'inventory-presser.php', array( 'Inventory_Presser_Taxonomies', 'remove_terms_cron_job' ) );
	}

	function load_scripts( $hook )
	{
		global $current_screen;
		if ( ( $hook == 'edit-tags.php' || $hook == 'term.php')
			&& $current_screen->post_type == Inventory_Presser_Plugin::CUSTOM_POST_TYPE
			&& $current_screen->taxonomy == 'location' )
		{
			wp_enqueue_style('inventory-presser-timepicker-css',  plugins_url( '/css/jquery.timepicker.css', dirname( __FILE__ ) ));
			wp_enqueue_script('inventory-presser-timepicker', plugins_url( '/js/jquery.timepicker.min.js', dirname( __FILE__ ) ), array('jquery'), '1.8.10');
			wp_enqueue_script('jquery-ui-sortable');
			wp_enqueue_script('inventory-presser-location', plugins_url( '/js/tax-location.js', dirname( __FILE__ ) ), array('inventory-presser-timepicker','jquery-ui-sortable'));
		}

	}

	function maybe_exclude_sold_vehicles( $query )
	{
		if( is_admin() || ! $query->is_main_query()
			|| ! ( is_search() || is_post_type_archive( Inventory_Presser_Plugin::CUSTOM_POST_TYPE ) ) )
		{
			return;
		}

		//if the checkbox to include sold vehicles is checked, abort
		$plugin_settings = INVP::settings();
		if( isset( $plugin_settings['include_sold_vehicles'] ) && $plugin_settings['include_sold_vehicles'] )
		{
			return;
		}

		$taxonomy = 'availability';

		//if there is already a tax_query for taxonomy availability, abort
		if( $query->is_tax( $taxonomy ) )
		{
			return;
		}

		//do this
		$tax_query = array(
			array(
				'taxonomy' => $taxonomy,
				'field'    => 'slug',
				'terms'    => 'sold',
				'operator' => 'NOT IN',
			)
		);

		$query->set( 'tax_query', $tax_query );
	}

	static function meta_box_html_condition( $post )
	{
		echo self::taxonomy_meta_box_html( 'condition', apply_filters( 'invp_prefix_meta_key', 'condition' ), $post );
	}

	static function meta_box_html_cylinders( $post )
	{
		echo self::taxonomy_meta_box_html( 'cylinders', apply_filters( 'invp_prefix_meta_key', 'cylinders' ), $post );
	}

	static function meta_box_html_availability( $post )
	{
		echo self::taxonomy_meta_box_html( 'availability', apply_filters( 'invp_prefix_meta_key', 'availability' ), $post );
	}

	static function meta_box_html_drive_type( $post )
	{
		echo self::taxonomy_meta_box_html( 'drive_type', apply_filters( 'invp_prefix_meta_key', 'drive_type' ), $post );
	}

	static function meta_box_html_fuel( $post )
	{
		echo self::taxonomy_meta_box_html( 'fuel', apply_filters( 'invp_prefix_meta_key', 'fuel' ), $post );
	}

	static function meta_box_html_propulsion_type( $post )
	{
		echo self::taxonomy_meta_box_html( 'propulsion_type', apply_filters( 'invp_prefix_meta_key', 'propulsion_type' ), $post );
	}

	static function meta_box_html_transmission( $post )
	{
		echo self::taxonomy_meta_box_html( 'transmission', apply_filters( 'invp_prefix_meta_key', 'transmission' ), $post );
	}

	static function meta_box_html_type( $post )
	{
		$html = self::taxonomy_meta_box_html( 'type', apply_filters( 'invp_prefix_meta_key', 'type' ), $post );
		//add an onchange attribute to the select
		$html = str_replace( '<select', '<select onchange="invp_vehicle_type_changed( this.value );" ', $html );
		echo $html;
	}

	static function meta_box_html_locations( $post )
	{
		printf(
			'%s<p><a href="edit-tags.php?taxonomy=location&post_type=%s">Manage locations</a></p>',
			self::taxonomy_meta_box_html( 'location', apply_filters( 'invp_prefix_meta_key', 'location' ), $post ),
			Inventory_Presser_Plugin::CUSTOM_POST_TYPE
		);
	}

	private static function meta_array_value_single( $meta, $key )
	{
		return isset( $meta[$key][0] ) ? $meta[$key][0] : false;
	}

	//Populate our taxonomies with terms if they do not already exist
	static function populate_default_terms()
	{
		//create the taxonomies or else our wp_insert_term calls will fail
		self::create_taxonomies();

		$taxonomy_data = self::taxonomy_data();
		for( $i=0; $i<sizeof( $taxonomy_data ); $i++ )
		{
			if( ! isset( $taxonomy_data[$i]['term_data'] ) ) { continue; }

			foreach( $taxonomy_data[$i]['term_data'] as $abbr => $desc )
			{
				$taxonomy_name = str_replace( '-', '_', $taxonomy_data[$i]['args']['query_var'] );
				if ( ! is_array( term_exists( $desc, $taxonomy_name ) ) )
				{
					$term_exists = wp_insert_term(
						$desc,
						$taxonomy_name,
						array (
							'description' => $desc,
							'slug'        => $abbr,
						)
					);
				}
			}
		}
	}

	//returns an array of all our taxonomy query vars
	static function query_vars_array()
	{
		$arr = array();
		foreach( self::taxonomy_data() as $taxonomy_array )
		{
			if( ! isset( $taxonomy_array['args'] ) || ! isset( $taxonomy_array['args']['query_var'] ) )
			{
				continue;
			}
			$slug = str_replace( ' ', '_', strtolower( $taxonomy_array['args']['query_var'] ) );
			array_push( $arr, $slug );
		}
		return $arr;
	}

	/**
	 * Registers term meta fields for our Location taxonomy to help store phone
	 * numbers and hours of operation. Also allows the storage of the individual
	 * pieces of the address that previously lived only in the term description.
	 */
	function register_meta()
	{
		/**
		 * Register some address fields so the pieces of the address can be
		 * accessed individually. For all of 2015-2019, we left the whole
		 * address in the term description.
		 */
		$address_keys = array(
			'address_street',
			'address_street_line_two',
			'address_city',
			'address_state',
			'address_zip',
		);
		foreach( $address_keys as $meta_key )
		{
			register_term_meta( 'location', $meta_key, array(
				'sanitize_callback' => 'sanitize_text_field',
				'show_in_rest'      => true,
				'single'            => true,
				'type'              => 'string',
			) );
		}

		/**
		 * Phone Numbers
		 */
		$phone_key_suffixes = array(
			'uid',
			'description',
			'number',
		);

		//How many phone numbers do we plan to store per address?
		$loop_max = apply_filters( 'invp_max_phone_numbers_per_address', 10 );

		for( $i=1; $i<=$loop_max; $i++ )
		{
			foreach( $phone_key_suffixes as $suffix )
			{
				$meta_key = 'phone_' . $i . '_' . $suffix;
				register_term_meta( 'location', $meta_key, array(
					'sanitize_callback' => 'sanitize_text_field',
					'show_in_rest'      => true,
					'single'            => true,
					'type'              => 'string',
				) );
			}
		}

		/**
		 * Hours
		 */
		$hours_key_suffixes = array(
			'uid',
			'title',
			'sunday_appt',
			'sunday_open',
			'sunday_close',
			'saturday_appt',
			'saturday_open',
			'saturday_close',
			'friday_appt',
			'friday_open',
			'friday_close',
			'thursday_appt',
			'thursday_open',
			'thursday_close',
			'wednesday_appt',
			'wednesday_open',
			'wednesday_close',
			'tuesday_appt',
			'tuesday_open',
			'tuesday_close',
			'monday_appt',
			'monday_open',
			'monday_close',
		);

		//How many sets of hours do we plan to store per address?
		$loop_max = apply_filters( 'invp_max_hours_sets_per_address', 5 );

		for( $i=1; $i<=$loop_max; $i++ )
		{
			foreach( $hours_key_suffixes as $suffix )
			{
				$meta_key = 'hours_' . $i . '_' . $suffix;
				register_term_meta( 'location', $meta_key, array(
					'sanitize_callback' => 'sanitize_text_field',
					'show_in_rest'      => true,
					'single'            => true,
					'type'              => 'string',
				) );
			}
		}
	}

	/**
	 * @param boolean $network_wide True if this plugin is being Network Activated or Network Deactivated by the multisite admin
	 */
	public static function remove_terms_cron_job( $network_wide )
	{
		if( ! is_multisite() || ! $network_wide )
		{
			wp_unschedule_event( wp_next_scheduled( self::CRON_HOOK_DELETE_TERMS ), self::CRON_HOOK_DELETE_TERMS );
			return;
		}

		$sites = get_sites( array( 'network' => 1, 'limit' => 1000 ) );
		foreach( $sites as $site )
		{
			switch_to_blog( $site->blog_id );
			wp_unschedule_event( wp_next_scheduled( self::CRON_HOOK_DELETE_TERMS ), self::CRON_HOOK_DELETE_TERMS );
			restore_current_blog();
		}
	}

	function save_location_meta( $term_id, $tt_id )
	{
		if ( ! isset( $_POST['hours_title'] ) || ! isset( $_POST['phone_number'] ) )
		{
			return;
		}

		// HOURS
		$hours_count = sizeof( $_POST['hours_title'] ) - 1;
		for ( $i = 0; $i <= $hours_count; $i++ )
		{
			// if this is an update, carry the id through
			$uid = '';
			if ( isset( $_POST['hours_uid'][$i] ) )
			{
				$uid = sanitize_text_field( $_POST['hours_uid'][$i] );
			}
			else
			{
				//generate a unique id for these hours
				$uid = $this->generate_location_uid( $term_id . '_hours_' . $i );
			}
			update_term_meta( $term_id, 'hours_' . strval($i+1) . '_uid', $uid );

			// title of hours set
			update_term_meta( $term_id, 'hours_' . strval($i+1) . '_title', sanitize_text_field( $_POST['hours_title'][$i] ) );

			foreach( array_keys( INVP::weekdays() ) as $d => $day )
			{
				update_term_meta( $term_id, 'hours_' . strval($i+1) . '_' . $day . '_appt', sanitize_text_field( $_POST['hours'][$d]['appt'][$i] ) );
				update_term_meta( $term_id, 'hours_' . strval($i+1) . '_' . $day . '_open', sanitize_text_field( $_POST['hours'][$d]['open'][$i] ) );
				update_term_meta( $term_id, 'hours_' . strval($i+1) . '_' . $day . '_close', sanitize_text_field( $_POST['hours'][$d]['close'][$i] ) );
			}
		}

		// PHONE NUMBERS
		$phones_count = 0;
		foreach ( $_POST['phone_number'] as $i => $phone_number )
		{
			$phone_number = sanitize_text_field( $phone_number );
			if( empty( $phone_number ) )
			{
				continue;
			}

			update_term_meta( $term_id, 'phone_' . strval($i+1) . '_number', $phone_number );
			update_term_meta( $term_id, 'phone_' . strval($i+1) . '_description', sanitize_text_field( $_POST['phone_description'][$i] ) );

			// if this is an update, carry the id through
			$uid = '';
			if ( isset( $_POST['phone_uid'][$i] ) )
			{
				$uid = $_POST['phone_uid'][$i];
			}
			else
			{
				//generate a unique id for this phone number
				$uid = $this->generate_location_uid( $term_id . '_phone_' . $i );
			}
			update_term_meta( $term_id, 'phone_' . strval($i+1) . '_uid', $uid );

			$phones_count++;
		}

		//delete phones and hours in slots higher than we just filled or deletes are not possible
		for( $h=$hours_count+1; $h<self::LOCATION_MAX_HOURS; $h++ )
		{
			delete_term_meta( $term_id, 'hours_' . strval($h) . '_uid' );
			delete_term_meta( $term_id, 'hours_' . strval($h) . '_title' );
			delete_term_meta( $term_id, 'hours_' . strval($h) . '_sunday_appt' );
			delete_term_meta( $term_id, 'hours_' . strval($h) . '_sunday_open' );
			delete_term_meta( $term_id, 'hours_' . strval($h) . '_sunday_close' );
			delete_term_meta( $term_id, 'hours_' . strval($h) . '_saturday_appt' );
			delete_term_meta( $term_id, 'hours_' . strval($h) . '_saturday_open' );
			delete_term_meta( $term_id, 'hours_' . strval($h) . '_saturday_close' );
			delete_term_meta( $term_id, 'hours_' . strval($h) . '_friday_appt' );
			delete_term_meta( $term_id, 'hours_' . strval($h) . '_friday_open' );
			delete_term_meta( $term_id, 'hours_' . strval($h) . '_friday_close' );
			delete_term_meta( $term_id, 'hours_' . strval($h) . '_thursday_appt' );
			delete_term_meta( $term_id, 'hours_' . strval($h) . '_thursday_open' );
			delete_term_meta( $term_id, 'hours_' . strval($h) . '_thursday_close' );
			delete_term_meta( $term_id, 'hours_' . strval($h) . '_wednesday_appt' );
			delete_term_meta( $term_id, 'hours_' . strval($h) . '_wednesday_open' );
			delete_term_meta( $term_id, 'hours_' . strval($h) . '_wednesday_close' );
			delete_term_meta( $term_id, 'hours_' . strval($h) . '_tuesday_appt' );
			delete_term_meta( $term_id, 'hours_' . strval($h) . '_tuesday_open' );
			delete_term_meta( $term_id, 'hours_' . strval($h) . '_tuesday_close' );
			delete_term_meta( $term_id, 'hours_' . strval($h) . '_monday_appt' );
			delete_term_meta( $term_id, 'hours_' . strval($h) . '_monday_open' );
			delete_term_meta( $term_id, 'hours_' . strval($h) . '_monday_close' );
		}
		for( $p=$phones_count+1; $p<self::LOCATION_MAX_PHONES; $p++ )
		{
			delete_term_meta( $term_id, 'phone_' . strval($p) . '_uid' );
			delete_term_meta( $term_id, 'phone_' . strval($p) . '_description' );
			delete_term_meta( $term_id, 'phone_' . strval($p) . '_number' );
		}
	}

	//save custom taxonomy terms when vehicles are saved
	function save_vehicle_taxonomy_terms( $post_id, $is_update )
	{
		foreach( $this->slugs_array() as $slug )
		{
			$taxonomy_name = $slug;
			switch( $slug )
			{
				case 'style':
					$slug = 'body_style';
					break;
				case 'model_year':
					$slug = 'year';
					break;
			}
			$this->save_taxonomy_term( $post_id, $taxonomy_name, apply_filters( 'invp_prefix_meta_key', $slug ) );
		}
	}

	function save_taxonomy_term( $post_id, $taxonomy_name, $element_name )
	{
		if ( ! isset( $_POST[$element_name] ) )
		{
			return;
		}

		$term_slug = sanitize_text_field( $_POST[$element_name] );
		if ( '' == $term_slug )
		{
			// the user is setting the vehicle type to empty string
			wp_remove_object_terms( $post_id, self::get_term_slug( $taxonomy_name, $post_id ), $taxonomy_name );
			return;
		}
		$term = get_term_by( 'slug', $term_slug, $taxonomy_name );
		if ( empty( $term ) || is_wp_error( $term ) )
		{
			//the term does not exist. create it
			$term_arr = array(
				'slug'        => sanitize_title( $term_slug ),
				'description' => $term_slug,
				'name'        => $term_slug,
			);
			$id_arr = wp_insert_term( $term_slug, $taxonomy_name, $term_arr );
			if( ! is_wp_error( $id_arr ) )
			{
				$term->term_id = $id_arr['term_id'];
			}
		}
		$set = wp_set_object_terms( $post_id, $term->term_id, $taxonomy_name, false );
		if ( is_wp_error( $set ) )
		{
			//There was an error setting the term
		}
	}

	/**
	 * @param boolean $network_wide True if this plugin is being Network Activated or Network Deactivated by the multisite admin
	 */
	public static function schedule_terms_cron_job( $network_wide )
	{
		if ( ! wp_next_scheduled( self::CRON_HOOK_DELETE_TERMS ) )
		{
			if( ! is_multisite() || ! $network_wide )
			{
				wp_schedule_event( time(), 'daily', self::CRON_HOOK_DELETE_TERMS );
				return;
			}

			$sites = get_sites( array( 'network' => 1, 'limit' => 1000 ) );
			foreach( $sites as $site )
			{
				switch_to_blog( $site->blog_id );
				wp_schedule_event( time(), 'daily', self::CRON_HOOK_DELETE_TERMS );
				restore_current_blog();
			}
		}
	}

	//returns an array of all our taxonomy slugs
	function slugs_array()
	{
		$arr = array();
		foreach( $this->query_vars_array() as $query_var )
		{
			array_push( $arr, str_replace( '-', '_', $query_var ) );
		}
		return $arr;
	}

	function sort_terms_as_numbers( $order_by, $args, $taxonomies )
	{
		if( '' == $order_by ) { return ''; }

		$taxonomies_to_sort = array(
			'cylinders',
			'model_year',
		);
		foreach( $taxonomies_to_sort as $taxonomy_to_sort )
		{
			if( in_array( $taxonomy_to_sort, $taxonomies ) )
			{
				$order_by .=  '+0';
				break;
			}
		}
		return $order_by;
	}

	//this is an array of taxonomy names and the corresponding arrays of term data
	public static function taxonomy_data()
	{
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
						'meta_box_cb'    => array( 'Inventory_Presser_Taxonomies', 'meta_box_html_condition' ),
						'query_var'      => 'condition',
						'singular_label' => 'Condition',
						'show_in_menu'   => false,
						'show_in_rest'   => true,
						'show_ui'        => true,
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
						'meta_box_cb'    => array( 'Inventory_Presser_Taxonomies', 'meta_box_html_type' ),
						'query_var'      => 'type',
						'rest_base'      => 'inventory_type',
						'singular_label' => 'Type',
						'show_in_menu'   => false,
						'show_in_rest'   => true,
						'show_ui'        => true,
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
						'meta_box_cb'    => array( 'Inventory_Presser_Taxonomies', 'meta_box_html_availability' ),
						'query_var'      => 'availability',
						'singular_label' => 'Availability',
						'show_in_menu'   => false,
						'show_in_rest'   => true,
						'show_ui'        => true,
					),
					'term_data' =>	array (
						'For sale'  => 'For sale',
						'Sold'      => 'Sold',
						'Wholesale' => 'Wholesale',
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
						'meta_box_cb'    => array( 'Inventory_Presser_Taxonomies', 'meta_box_html_drive_type' ),
						'query_var'      => 'drive-type',
						'singular_label' => 'Drive type',
						'show_in_menu'   => false,
						'show_in_rest'   => true,
						'show_ui'        => true,
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
						'meta_box_cb'    => array( 'Inventory_Presser_Taxonomies', 'meta_box_html_propulsion_type' ),
						'query_var'      => 'propulsion-type',
						'singular_label' => 'Propulsion type',
						'show_in_menu'   => false,
						'show_in_rest'   => true,
						'show_ui'        => true,
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
						'meta_box_cb'    => array( 'Inventory_Presser_Taxonomies', 'meta_box_html_fuel' ),
						'query_var'      => 'fuel',
						'singular_label' => 'Fuel',
						'show_in_menu'   => false,
						'show_in_rest'   => true,
						'show_ui'        => true,
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
						'meta_box_cb'    => array( 'Inventory_Presser_Taxonomies', 'meta_box_html_transmission' ),
						'query_var'      => 'transmission',
						'singular_label' => 'Transmission',
						'show_in_menu'   => false,
						'show_in_rest'   => true,
						'show_ui'        => true,
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
						'meta_box_cb'    => array( 'Inventory_Presser_Taxonomies', 'meta_box_html_cylinders' ),
						'query_var'      => 'cylinders',
						'singular_label' => 'Cylinders',
						'show_in_menu'   => false,
						'show_in_rest'   => true,
						'show_ui'        => true,
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
							'search_items'  => __( 'Search locations', 'inventory-presser' ),
							'popular_items' => __( 'Popular locations', 'inventory-presser' ),
							'all_items'     => __( 'All locations', 'inventory-presser' ),
							'edit_item'     => __( 'Edit Location', 'inventory-presser' ),
							'view_item'     => __( 'View Location', 'inventory-presser' ),
							'update_item'   => __( 'Update Location', 'inventory-presser' ),
							'add_new_item'  => __( 'Add New Location', 'inventory-presser' ),
							'new_item_name' => __( 'New Location Name', 'inventory-presser' ),
							'not_found'     => __( 'No locations found', 'inventory-presser' ),
							'no_terms'      => __( 'No locations', 'inventory-presser' ),
							'menu_name'     => __( 'Locations', 'inventory-presser' ),
						),
						'meta_box_cb'    => array( 'Inventory_Presser_Taxonomies', 'meta_box_html_locations' ),
						'query_var'      => 'location',
						'singular_label' => 'Location',
						'show_in_menu'   => true,
						'show_in_rest'   => true,
						'show_ui'        => true,
					),
				),
			)
		);
	}

	static function taxonomy_meta_box_html( $taxonomy_name, $element_name, $post )
	{
		/**
		 * Creates HTML output for a meta box that turns a taxonomy into
		 * a select drop-down list instead of the typical checkboxes. Including
		 * a blank option is the only way a user can remove the value.
		 */
		$HTML = sprintf( '<select name="%s" id="%s"><option></option>',
			$element_name,
			$element_name
		);

		//get all the term names and slugs for $taxonomy_name
		$terms = get_terms( $taxonomy_name,  array( 'hide_empty' => false ) );

		if ( ! empty( $terms ) && ! is_wp_error( $terms ) )
		{
			//get the saved term for this taxonomy
			$saved_term_slug = self::get_term_slug( $taxonomy_name, $post->ID );

			foreach( $terms as $term )
			{
				$HTML .= sprintf(
					'<option value="%s"%s>%s</option>',
					$term->slug,
					selected( strtolower( $term->slug ), strtolower( $saved_term_slug ), false ),
					$term->name
				);
			}
		}
		return $HTML . '</select>';
	}

	function update_term_counts()
	{
		global $wpdb;
		$wpdb->query(
			"UPDATE		$wpdb->term_taxonomy tt

			SET			count = ( 
				
				SELECT		count( p.ID )
				
				FROM		$wpdb->term_relationships tr
							LEFT JOIN $wpdb->posts p ON p.ID = tr.object_id

				WHERE		tr.term_taxonomy_id = tt.term_taxonomy_id
			)"
		);
	}
}
