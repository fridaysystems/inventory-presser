<?php
defined( 'ABSPATH' ) or exit;

/**
 * Inventory_Presser_Options
 * 
 * Creates an options page in the dashboard to hold this plugin and its add-ons
 * settings.
 *
 * @since      0.5
 * @package    Inventory_Presser
 * @subpackage Inventory_Presser/includes
 * @author     Corey Salzano <corey@friday.systems>
 */
class Inventory_Presser_Options
{
	//This plugin's option that holds all the settings
	private $option;
	
	/**
	 * hooks
	 * 
	 * Adds hooks
	 *
	 * @return void
	 */
	public function hooks()
	{
		add_action( 'admin_enqueue_scripts', array( $this, 'register_javascript' ) );
		add_action( 'admin_init', array( $this, 'add_settings' ) );
		add_action( 'admin_menu', array( $this, 'add_options_page' ) );

		/**
		 * When the option is updated, check if the additional listings pages 
		 * settings changed. If they have, flush permalinks.
		 */
		add_action( 'update_option_' . INVP::OPTION_NAME, array( $this, 'maybe_flush_permalinks' ), 10, 2 );
	}

	/**
	 * maybe_flush_permalinks
	 *
	 * If the additional listing pages settings are changed, or the switch
	 * is on and the array of pages is different, flush rewrite rules
	 * 
	 * @param  string $option
	 * @param  mixed $old_value
	 * @param  mixed $value
	 * @return void
	 */
	public function maybe_flush_permalinks( $old_value, $value )
	{
		//Did the additional listings pages settings change?
		if ( ( ! isset( $old_value['additional_listings_pages'] ) && isset( $value['additional_listings_pages'] ) )
			|| ( isset( $old_value['additional_listings_pages'] ) && ! isset( $value['additional_listings_pages'] ) )
			|| ( isset( $old_value['additional_listings_pages'] ) && isset( $value['additional_listings_pages'] ) && $old_value['additional_listings_pages'] !== $value['additional_listings_pages'] ) )
		{
			if( ! is_multisite() )
			{
				flush_rewrite_rules();
				return;
			}
	
			$sites = get_sites( array( 'network' => 1, 'limit' => 1000 ) );
			foreach( $sites as $site )
			{
				switch_to_blog( $site->blog_id );
				global $wp_rewrite;
				$wp_rewrite->init(); //important...
				$wp_rewrite->flush_rules();
				restore_current_blog();
			}
		}
	}

	/**
	 * register_javascript
	 * 
	 * Registers a JavaScript file that powers the settings page.
	 *
	 * @return void
	 */
	function register_javascript()
	{
		wp_register_script(
			'invp_page_settings',
			plugins_url( '/js/page-settings.min.js', INVP_PLUGIN_FILE_PATH ),
			array( 'jquery' )
		);
	}
	
	/**
	 * add_options_page
	 * 
	 * Adds an options page to the dashboard to hold all this plugin's settings.
	 *
	 * @return void
	 */
	public function add_options_page()
	{
		if ( post_type_exists( INVP::POST_TYPE ) )
		{
			add_submenu_page('edit.php?post_type=' . INVP::POST_TYPE,
				__( 'Options', 'inventory-presser' ), // page_title
				__( 'Options', 'inventory-presser' ), // menu_title
				'manage_options', // capability
				'dealership-options', // menu_slug
				array( $this, 'options_page_content' ) // function
			);
			return;
		}

		add_options_page(
			__( 'Inventory Presser', 'inventory-presser' ), // page_title
			__( 'Inventory Presser', 'inventory-presser' ), // menu_title
			'manage_options', // capability
			'dealership-options', // menu_slug
			array( $this, 'options_page_content' ) // function
		);
	}
	
	/**
	 * add_settings
	 * 
	 * Registers sections and settings using the Settings API.
	 *
	 * @return void
	 */
	public function add_settings()
	{
		register_setting(
			'dealership_options_option_group', // option_group
			INVP::OPTION_NAME, // option_name
			array( $this, 'sanitize_options' ) // sanitize_callback
		);

		/**
		 * SECTION Settings
		 */
		add_settings_section(
			'dealership_options_setting_section', // id
			__( 'General', 'inventory-presser' ), // title
			'__return_empty_string', // callback
			'dealership-options-admin' // page
		);

		//Price Display
		add_settings_field(
			'price_display', // id
			__( 'Price Display', 'inventory-presser' ), // title
			array( $this, 'callback_price_display' ), // callback
			'dealership-options-admin', // page
			'dealership_options_setting_section' // section
		);

		//[x] Include sold vehicles
		add_settings_field(
			'include_sold_vehicles', // id
			__( 'Sold Vehicles', 'inventory-presser' ), // title
			array( $this, 'callback_include_sold_vehicles' ), // callback
			'dealership-options-admin', // page
			'dealership_options_setting_section' // section
		);

		//[x] Show all taxonomies under Vehicles menu in Dashboard
		add_settings_field(
			'show_all_taxonomies', // id
			__( 'Show All Taxonomies', 'inventory-presser' ), // title
			array( $this, 'callback_show_all_taxonomies' ), // callback
			'dealership-options-admin', // page
			'dealership_options_setting_section' // section
		);

		//[x] Skip trash when deleting vehicles and delete permanently
		add_settings_field(
			'skip_trash', // id
			__( 'Skip Trash', 'inventory-presser' ), // title
			array( $this, 'callback_skip_trash' ), // callback
			'dealership-options-admin', // page
			'dealership_options_setting_section' // section
		);

		//MapBox Public Token [____________]
		add_settings_field(
			'mapbox_public_token', // id
			__( 'MapBox Public Token', 'inventory-presser' ), // title
			array( $this, 'callback_mapbox_public_token' ), // callback
			'dealership-options-admin', // page
			'dealership_options_setting_section' // section
		);

		/**
		 * SECTION Listings
		 */
		add_settings_section(
			'dealership_options_section_listings', // id
			__( 'Listings', 'inventory-presser' ), // title
			'__return_empty_string', // callback
			'dealership-options-admin' // page
		);

		//Sort vehicles by [Field] in [Ascending] order
		add_settings_field(
			'sort_vehicles_by', // id
			__( 'Sort Vehicles By', 'inventory-presser' ), // title
			array( $this, 'callback_sort_vehicles_by' ), // callback
			'dealership-options-admin', // page
			'dealership_options_section_listings' // section
		);

		/**
		 * Create an additional inventory archive at pmgautosales.com/[cash-deals]
		 * that contains vehicles that have a value for field [Down Payment]
		 */
		add_settings_field(
			'additional_listings_page', // id
			__( 'Additional Listings Page', 'inventory-presser' ), // title
			array( $this, 'callback_additional_listings_page' ), // callback
			'dealership-options-admin', // page
			'dealership_options_section_listings' // section
		);

		/**
		 * SECTION Carfax
		 */
		add_settings_section(
			'dealership_options_section_carfax', // id
			__( 'Carfax', 'inventory-presser' ), // title
			'__return_empty_string', // callback
			'dealership-options-admin' // page
		);

		//[x] Display Carfax buttons near vehicles that link to free Carfax reports
		add_settings_field(
			'use_carfax', // id
			__( 'Enable Carfax', 'inventory-presser' ), // title
			array( $this, 'callback_use_carfax' ), // callback
			'dealership-options-admin', // page
			'dealership_options_section_carfax' // section
		);

		//[x] Use Carfax-provided, dynamic buttons that may also say things like "GOOD VALUE"
		add_settings_field(
			'use_carfax_provided_buttons', // id
			__( 'Use Newest Buttons', 'inventory-presser' ), // title
			array( $this, 'callback_use_carfax_provided_buttons' ), // callback
			'dealership-options-admin', // page
			'dealership_options_section_carfax' // section
		);
	}
	
	/**
	 * boolean_checkbox_setting_callback
	 * 
	 * Outputs HTML that renders checkboxes.
	 *
	 * @param  string $setting_name The name of the setting and control
	 * @param  string $checkbox_label The checkbox label that the user sees
	 * @return void
	 */
	function boolean_checkbox_setting_callback( $setting_name, $checkbox_label )
	{
		printf(
			'<input type="checkbox" name="%s[%s]" id="%s" %s> <label for="%s">%s</label>',
			INVP::OPTION_NAME,
			$setting_name,
			$setting_name,
			isset( $this->option[$setting_name] ) ? checked( $this->option[$setting_name], true, false ) : '',
			$setting_name,
			$checkbox_label
		);
	}
	
	/**
	 * callback_additional_listings_page
	 * 
	 * Outputs controls to manage additional inventory listing pages.
	 * 
	 * Helps users create an additional inventory archive at 
	 * example.com/[cash-deals] that contains vehicles that have a value for 
	 * field [Down Payment].
	 *
	 * @return void
	 */
	function callback_additional_listings_page()
	{
		?><p><?php

		echo $this->boolean_checkbox_setting_callback(
			'additional_listings_page',
			__( 'Create additional inventory archive(s)', 'inventory-presser' )
		);

			//output a table to hold the settings for additional sheets
			?></p>
		<div id="additional_listings_pages_settings">
			<table class="wp-list-table widefat striped additional_listings_pages">
				<thead>
					<tr>
						<th><?php _e( 'URL path', 'inventory-presser' ); ?></th>
						<th><?php _e( 'Field', 'inventory-presser' ); ?></th>
						<th><?php _e( 'Operator', 'inventory-presser' ); ?></th>
						<th><?php _e( 'Value', 'inventory-presser' ); ?></th>
						<th></th>
					</tr>
				</thead>
				<tbody><?php

				//output a row for each saved additional listing page + one blank
				$additional_listings = Inventory_Presser_Additional_Listings_Pages::additional_listings_pages_array();
				if( empty( $additional_listings ) )
				{
					$additional_listings = array( array() );
				}
				$keys = array(
					'url_path',
					'key',
					'operator',
					'value',
				);
				for( $a=0; $a<sizeof( $additional_listings ); $a++ )
				{
					foreach( $keys as $key )
					{
						if( ! isset( $additional_listings[$a][$key] ) )
						{
							$additional_listings[$a][$key] = '';
						}
					}

					?><tr id="row_<?php echo $a; ?>">
						<td><?php

						//url path
						printf(
							'%s/<input type="text" id="additional_listings_pages_slug_%s" name="%s[additional_listings_pages][%s][url_path]" value="%s" />',
							site_url(),
							$a,
							INVP::OPTION_NAME,
							$a,
							$additional_listings[$a]['url_path']
						);

						?></td>
						<td><?php

						//select list of vehicle fields
						echo $this->html_select_vehicle_keys( array(
							'id'   => 'additional_listings_pages_key_' . $a,
							'name' => INVP::OPTION_NAME . '[additional_listings_pages][' . $a . '][key]',
						), $additional_listings[$a]['key'] );

						?></td>
						<td><?php

						//select list of operators
						echo $this->html_select_operator( array(
							'id'    => 'additional_listings_pages_operator_' . $a,
							'name'  => INVP::OPTION_NAME . '[additional_listings_pages][' . $a . '][operator]',
							'class' => 'operator',
						), $additional_listings[$a]['operator'] );

						?></td>
						<td><?php

						//text box for comparison value
						printf(
							'<input type="text" id="additional_listings_pages_value_%s" name="%s[additional_listings_pages][%s][value]" value="%s" />',
							$a,
							INVP::OPTION_NAME,
							$a,
							$additional_listings[$a]['value']
						);

						?></td>
						<td>
							<a href="<?php echo site_url( $additional_listings[$a]['url_path'] ); ?>" class="button action" title="View this page"><span class="dashicons dashicons-welcome-view-site"></span></a><?php
							?><button class="button action delete-button" id="delete_<?php echo $a; ?>" title="Delete this page"><span class="dashicons dashicons-trash"></span></button>
						</td>
					</tr><?php

				}

				?></tbody>
			</table>
			<button class="button action" id="add_additional_listings_page">Add Additional Listings Page</button>
		</div><?php
	}
	
	/**
	 * callback_include_sold_vehicles
	 * 
	 * Outputs a checkbox control for the Include Sold Vehicles setting.
	 *
	 * @return void
	 */
	function callback_include_sold_vehicles()
	{
		$this->boolean_checkbox_setting_callback(
			'include_sold_vehicles',
			__( 'Include sold vehicles in listings and search results', 'inventory-presser' )
		);
	}
	
	/**
	 * callback_mapbox_public_token
	 * 
	 * Outputs a text box control for the MapBox Public Token setting
	 *
	 * @return void
	 */
	function callback_mapbox_public_token()
	{
		printf(
			'<p><input type="text" name="%1$s" class="regular-text code" id="%1$s" value="%2$s" /></p><p class="description">%3$s <a href="%4$s">%5$s</a> %6$s</p>',
			'inventory_presser[mapbox_public_token]',
			isset( $this->option['mapbox_public_token'] ) ? $this->option['mapbox_public_token'] : '',
			__( 'Obtain a key at', 'inventory-presser' ),
			'https://mapbox.com/',
			'mapbox.com',
			__( 'to use the Map widget.', 'inventory-presser' )
		);
	}

	
	/**
	 * callback_price_display
	 * 
	 * Outputs a dropdown select control for the Price Display setting
	 *
	 * @return void
	 */
	function callback_price_display()
	{
		$price_display_options = apply_filters( 'invp_price_display_options', array(
			'default'          => '${Price}',
			'msrp'             => '${MSRP}',
			'full_or_down'     => '${Price} / ${Down Payment} Down',
			'down_only'        => '${Down Payment} Down',
			'was_now_discount' => 'Retail ${MSRP} Now ${Price} You Save ${MSRP}-{Price}',
			'payment_only'     => '${Payment} {Frequency}',
			'down_and_payment' => '${Down payment} / ${Payment} {Frequency}',
			'call_for_price'   => __( 'Call For Price', 'inventory-presser' ),			
		) );

		$selected_val = null;
		if ( isset( $this->option['price_display'] ) )
		{
			$selected_val = $this->option['price_display'];
		}

		printf(
			'<select name="%s[price_display]" id="price_display">',
			INVP::OPTION_NAME
		);
		foreach( $price_display_options as $val => $name )
		{
			printf(
				'<option value="%s"%s>%s</option>',
				$val,
				selected( $val, $selected_val, false ),
				$name
			);
		}
		printf(
			'</select><p class="description" id="%s[price_display]-description">&quot;%s&quot; %s.</p>',
			INVP::OPTION_NAME,
			__( 'Call for Price', 'inventory-presser' ),
			__( 'will display for any price that is zero', 'inventory-presser' )
		);
	}

	/**
	 * callback_show_all_taxonomies
	 * 
	 * Output the controls that create the Show all Taxonomies setting.
	 *
	 * @return void
	 */
	function callback_show_all_taxonomies()
	{
		$this->boolean_checkbox_setting_callback(
			'show_all_taxonomies',
			__( 'Show all taxonomies under Vehicles menu in Dashboard', 'inventory-presser' )
		);
	}

	/**
	 * callback_skip_trash
	 * 
	 * Output the controls that create the Skip Trash setting.
	 *
	 * @return void
	 */
	function callback_skip_trash()
	{
		$this->boolean_checkbox_setting_callback(
			'skip_trash',
			__( 'Skip trash when deleting vehicles and delete permanently', 'inventory-presser' )
		);
	}
	
	/**
	 * callback_sort_vehicles_by
	 * 
	 * Output the controls that create the default vehicle sort setting.
	 *
	 * @return void
	 */
	function callback_sort_vehicles_by()
	{
		//use these default values if we have none
		if( ! isset( $this->option['sort_vehicles_by'] ) )
		{
			$this->option['sort_vehicles_by'] = 'make';
		}
		if( ! isset( $this->option['sort_vehicles_order'] ) )
		{
			$this->option['sort_vehicles_order'] = 'ASC';
		}

		$select = $this->html_select_vehicle_keys( array(
			'name' => INVP::OPTION_NAME . '[sort_vehicles_by]',
			'id'   => 'sort_vehicles_by',
		), $this->option['sort_vehicles_by'] );

		printf(
			'%s %s <select name="%s[sort_vehicles_order]" id="sort_vehicles_order">',
			$select,
			__( 'in', 'inventory-presser' ),
			INVP::OPTION_NAME
		);

		foreach( array( 'ascending' => 'ASC', 'descending' => 'DESC' ) as $direction => $abbr )
		{
			echo '<option value="'. $abbr . '"';
			if( isset( $this->option['sort_vehicles_order'] ) )
			{
				selected( $this->option['sort_vehicles_order'], $abbr );
			}
			echo '>' . $direction . '</option>';
		}
		echo '</select> ' . __( 'order', 'inventory-presser' );
	}
	
	/**
	 * callback_use_carfax
	 * 
	 * Output the controls that create the Display Carfax Buttons setting.
	 *
	 * @return void
	 */
	function callback_use_carfax()
	{
		$this->boolean_checkbox_setting_callback(
			'use_carfax',
			__( 'Display Carfax buttons near vehicles that link to free Carfax reports', 'inventory-presser' )
		);
	}
	
	/**
	 * callback_use_carfax_provided_buttons
	 * 
	 * Output the controls that create the Use Carfax-provided Buttons setting.
	 *
	 * @return void
	 */
	function callback_use_carfax_provided_buttons()
	{
		$this->boolean_checkbox_setting_callback(
			'use_carfax_provided_buttons',
			__( 'Use Carfax-provided, dynamic buttons that may also say things like "GOOD VALUE"', 'inventory-presser' )
		);
	}
	
	/**
	 * html_select_operator
	 * 
	 * Creates a dropdown select that contains logical operators. 
	 *
	 * @param  array $attributes
	 * @param  string $selected_value
	 * @return string
	 */
	private function html_select_operator( $attributes = null, $selected_value = null )
	{
		$keys = array(
			'exists',
			'does not exist',
			'greater than',
			'less than',
			'equal to',
			'not equal to',
		);

		$options = '';
		foreach( $keys as $key )
		{
			$slug = str_replace( ' ', '_', $key );
			$options .= sprintf(
				'<option value="%s"%s>%s</option>',
				$slug,
				selected( $selected_value, $slug, false ),
				$key
			);
		}

		$attribute_string = '';
		if( ! empty( $attributes ) )
		{
			$attribute_string = ' ' . str_replace( "=", '="', http_build_query( $attributes, null, '" ', PHP_QUERY_RFC3986 ) ) . '"';
		}

		return sprintf(
			'<select%s>%s</select>',
			urldecode( $attribute_string ),
			$options
		);
	}
	
	/**
	 * html_select_vehicle_keys
	 *
	 * Get a list of all the post meta keys in our CPT. Let the user choose one
	 * as a default sort.
	 * 
	 * @param  array $attributes
	 * @param  string $selected_value
	 * @return string
	 */
	private function html_select_vehicle_keys( $attributes = null, $selected_value = null )
	{
		$options = '';
		foreach( INVP::keys( false ) as $key )
		{
			$meta_key = apply_filters( 'invp_prefix_meta_key', $key );

			//Skip hidden post meta keys
			if( '_' == $meta_key[0] )
			{
				continue;
			}

			$options .= sprintf(
				'<option value="%s"%s>%s</option>',
				$key,
				selected( $selected_value, $key, false ),
				str_replace( '_', ' ', ucfirst( $key ) )
			);
		}

		$attribute_string = '';
		if( ! empty( $attributes ) )
		{
			$attribute_string = ' ' . str_replace( "=", '="', http_build_query( $attributes, null, '" ', PHP_QUERY_RFC3986 ) ) . '"';
		}

		return sprintf(
			'<select%s>%s</select>',
			urldecode( $attribute_string ),
			$options
		);
	}
	
	/**
	 * options_page_content
	 * 
	 * Outputs the settings page HTML content
	 *
	 * @return void
	 */
	public function options_page_content()
	{
		wp_enqueue_script( 'invp_page_settings' );

		$this->option = INVP::settings();

		?><div class="wrap">
			<h2><?php _e( 'Inventory Presser Settings', 'inventory-presser' ); ?></h2>
			<?php settings_errors();

			?><form method="post" action="options.php">
				<?php
					settings_fields( 'dealership_options_option_group' );
					do_settings_sections( 'dealership-options-admin' );
					submit_button();

			?></form>
		</div><?php
	}
	
	/**
	 * sanitize_options
	 * 
	 * Santitizes the user input into the options inputs before they are saved.
	 *
	 * @param  array $input
	 * @return array
	 */
	public function sanitize_options( $input )
	{
		$sanitary_values = array();

		$boolean_settings = array(
			'additional_listings_page',
			'include_sold_vehicles',
			'show_all_taxonomies',
			'skip_trash',
			'use_carfax',
			'use_carfax_provided_buttons',
		);
		foreach( $boolean_settings as $b )
		{
			$sanitary_values[$b] = isset( $input[$b] );
		}

		if ( isset( $input['price_display'] ) )
		{
			$sanitary_values['price_display'] = $input['price_display'];
		}

		if ( isset( $input['mapbox_public_token'] ) )
		{
			$sanitary_values['mapbox_public_token'] = $input['mapbox_public_token'];
		}

		if ( isset( $input['sort_vehicles_by'] ) )
		{
			$sanitary_values['sort_vehicles_by'] = $input['sort_vehicles_by'];
		}

		if ( isset( $input['sort_vehicles_order'] ) )
		{
			$sanitary_values['sort_vehicles_order'] = $input['sort_vehicles_order'];
		}

		if( is_array( $input['additional_listings_pages'] ) )
		{
			/**
			 * array_values() re-indexes the array starting at zero in case the
			 * first rule was deleted and index 0 doesn't exist.
			 */
			$sanitary_values['additional_listings_pages'] = array_values( $input['additional_listings_pages'] );
			/**
			 * This feature doesn't work when two rules have the same URL path.
			 * Drop any duplicates. Also reject any invalid rules.
			 */
			$url_paths = array();
			$unique_rules = array();
			foreach( $sanitary_values['additional_listings_pages'] as $additional_listing )
			{
				//Is this even a valid rule?
				if( ! Inventory_Presser_Additional_Listings_Pages::is_valid_rule( $additional_listing ) )
				{
					//No
					continue;
				}

				if( in_array( $additional_listing['url_path'], $url_paths ) )
				{
					//sorry!
					continue;
				}
				$unique_rules[] = $additional_listing;
				$url_paths[] = $additional_listing['url_path'];
			}
			$sanitary_values['additional_listings_pages'] = $unique_rules;
		}

		return apply_filters( 'invp_options_page_sanitized_values', $sanitary_values, $input );
	}
}
