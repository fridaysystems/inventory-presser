<?php
defined( 'ABSPATH' ) or exit;

/**
 * Creates an options page in the dashboard to hold this plugin and its add-ons
 * settings.
 *
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

	public function hooks()
	{
		add_action( 'admin_enqueue_scripts', array( $this, 'register_javascript' ) );
		add_action( 'admin_init', array( $this, 'add_settings' ) );
		add_action( 'admin_menu', array( $this, 'add_options_page' ) );

		/**
		 * In September 2019, I decided to rename this plugin's option. I made
		 * this decision after realizing that the _dealer theme uses the same
		 * option to store its settings. Also, I've always felt that it has been
		 * unfortunately named something that does not indicate to strangers
		 * that it belongs to this plugin.
		 */
		add_action( 'plugins_loaded', array( $this, 'rename_option' ) );
	}

	/**
	 * Registers JavaScript for this settings page.
	 */
	function register_javascript()
	{
		wp_register_script(
			'invp_page_settings',
			plugins_url( '/js/page-settings.js', dirname( __FILE__, 2 ) . '/inventory-presser.php' ),
			array( 'jquery' )
		);
	}

	public function add_options_page()
	{
		if ( post_type_exists( Inventory_Presser_Plugin::CUSTOM_POST_TYPE ) )
		{
			add_submenu_page('edit.php?post_type=' . Inventory_Presser_Plugin::CUSTOM_POST_TYPE,
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

	public function add_settings()
	{
		register_setting(
			'dealership_options_option_group', // option_group
			Inventory_Presser_Plugin::OPTION_NAME, // option_name
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

	function boolean_checkbox_setting_callback( $setting_name, $checkbox_label )
	{
		printf(
			'<input type="checkbox" name="%s[%s]" id="%s" %s> <label for="%s">%s</label>',
			Inventory_Presser_Plugin::OPTION_NAME,
			$setting_name,
			$setting_name,
			isset( $this->option[$setting_name] ) ? checked( $this->option[$setting_name], true, false ) : '',
			$setting_name,
			$checkbox_label
		);
	}

	function callback_additional_listings_page()
	{
		/**
		 * Create an additional inventory archive at pmgautosales.com/[cash-deals]
		 * that contains vehicles that have a value for field [Down Payment]
		 */

		$url_slug_id = 'additional_listings_pages_slug';
		$url_slug_name = Inventory_Presser_Plugin::OPTION_NAME . '[additional_listings_pages][0][url_path]';
		$saved_key = ! empty( $this->option['additional_listings_pages'][0]['key'] ) ? $this->option['additional_listings_pages'][0]['key'] : '';

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
						<td style="width: 100%;"><?php

						//url path
						printf(
							'%s/<input type="text" id="additional_listings_pages_slug_%s" name="%s[additional_listings_pages][%s][url_path]" value="%s" />',
							site_url(),
							$a,
							Inventory_Presser_Plugin::OPTION_NAME,
							$a,
							$additional_listings[$a]['url_path']
						);

						?></td>
						<td><?php

						//select list of vehicle fields
						echo $this->html_select_vehicle_keys( array(
							'id'   => 'additional_listings_pages_key_' . $a,
							'name' => Inventory_Presser_Plugin::OPTION_NAME . '[additional_listings_pages][' . $a . '][key]',
						), $additional_listings[$a]['key'] );

						?></td>
						<td><?php

						//select list of operators
						echo $this->html_select_operator( array(
							'id'    => 'additional_listings_pages_operator_' . $a,
							'name'  => Inventory_Presser_Plugin::OPTION_NAME . '[additional_listings_pages][' . $a . '][operator]',
							'class' => 'operator',
						), $additional_listings[$a]['operator'] );

						?></td>
						<td><?php

						//text box for comparison value
						printf(
							'<input type="text" id="additional_listings_pages_value_%s" name="%s[additional_listings_pages][%s][value]" value="%s" />',
							$a,
							Inventory_Presser_Plugin::OPTION_NAME,
							$a,
							$additional_listings[$a]['value']
						);

						?></td>
						<td>
							<button class="button action delete-button" id="delete_<?php echo $a; ?>" title="Delete this page"><span class="dashicons dashicons-trash"></span></button>
						</td>
					</tr><?php

				}

				?></tbody>
			</table>
			<button class="button action" id="add_additional_listings_page">Add Additional Listings Page</button>
		</div><?php
	}

	function callback_include_sold_vehicles()
	{
		$this->boolean_checkbox_setting_callback(
			'include_sold_vehicles',
			__( 'Include sold vehicles in listings and search results', 'inventory-presser' )
		);
	}

	function callback_price_display()
	{
		$price_display_options = apply_filters( 'invp_price_display_options', array(
			'default'          => '${Price}',
			'msrp'             => '${MSRP}',
			'full_or_down'     => '${Price} / ${Down Payment} Down',
			'down_only'        => '${Down Payment} Down',
			'was_now_discount' => 'Retail ${MSRP} Now ${Price} You Save ${MSRP}-{Price}',
			'payment_only'     => '${Payment} {Frequency}',
			'call_for_price'   => __( 'Call For Price', 'inventory-presser' ),
		) );

		$selected_val = null;
		if ( isset( $this->option['price_display'] ) )
		{
			$selected_val = $this->option['price_display'];
		}

		printf(
			'<select name="%s[price_display]" id="price_display">',
			Inventory_Presser_Plugin::OPTION_NAME
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
			Inventory_Presser_Plugin::OPTION_NAME,
			__( 'Call for Price', 'inventory-presser' ),
			__( 'will display for any price that is zero', 'inventory-presser' )
		);
	}

	/**
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
			'name' => Inventory_Presser_Plugin::OPTION_NAME . '[sort_vehicles_by]',
			'id'   => 'sort_vehicles_by',
		), $this->option['sort_vehicles_by'] );

		printf(
			'%s %s <select name="%s[sort_vehicles_order]" id="sort_vehicles_order">',
			$select,
			__( 'in', 'inventory-presser' ),
			Inventory_Presser_Plugin::OPTION_NAME
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

	function callback_use_carfax()
	{
		$this->boolean_checkbox_setting_callback(
			'use_carfax',
			__( 'Display Carfax buttons near vehicles that link to free Carfax reports', 'inventory-presser' )
		);
	}

	function callback_use_carfax_provided_buttons()
	{
		$this->boolean_checkbox_setting_callback(
			'use_carfax_provided_buttons',
			__( 'Use Carfax-provided, dynamic buttons that may also say things like "GOOD VALUE"', 'inventory-presser' )
		);
	}

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

	private function html_select_vehicle_keys( $attributes = null, $selected_value = null )
	{
		/**
		 * Get a list of all the post meta keys in our CPT. Let the user choose
		 * one as a default sort.
		 */
		$vehicle = new Inventory_Presser_Vehicle();
		$options = '';
		foreach( $vehicle->keys( false ) as $key )
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

	public function options_page_content()
	{
		wp_enqueue_script( 'invp_page_settings' );

		$this->option = Inventory_Presser_Plugin::settings();

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
	 * Rename the option used by this plugin from "_dealer_settings" to
	 * "inventory_presser"
	 */
	function rename_option()
	{
		$old_option_name = '_dealer_settings';

		//Only do this once
		//Do not use Inventory_Presser_Plugin::settings() because it populates defaults
		$new_option = get_option( Inventory_Presser_Plugin::OPTION_NAME );
		if( $new_option )
		{
			return;
		}

		$old_option = get_option( $old_option_name );
		if( ! $old_option )
		{
			return;
		}

		$new_option = array(
			'price_display'         => $old_option['price_display_type'], //Rename this key
			'sort_vehicles_by'      => $old_option['sort_vehicles_by'],
			'sort_vehicles_order'   => $old_option['sort_vehicles_order'],
			'include_sold_vehicles' => isset( $old_option['include_sold_vehicles'] ) && $old_option['include_sold_vehicles'],
			'show_all_taxonomies'   => isset( $old_option['show_all_taxonomies'] ) && $old_option['show_all_taxonomies'],
			'use_carfax'            => isset( $old_option['use_carfax'] ) && $old_option['use_carfax'],
		);

		update_option( Inventory_Presser_Plugin::OPTION_NAME, $new_option );

		/**
		 * Now remove this plugin's keys from the old option and update it. Why?
		 * Because the old option is actually the option used by the _dealer
		 * theme. The theme was created after the plugin and a bad decision to
		 * share the same option was made. A more accurate name for this method
		 * might be split_the_option().
		 */
		unset( $old_option['price_display_type'] );
		unset( $old_option['sort_vehicles_by'] );
		unset( $old_option['sort_vehicles_order'] );
		unset( $old_option['include_sold_vehicles'] );
		unset( $old_option['show_all_taxonomies'] );
		unset( $old_option['use_carfax'] );

		update_option( $old_option_name, $old_option );
	}

	public function sanitize_options( $input )
	{
		$sanitary_values = array();

		$boolean_settings = array(
			'additional_listings_page',
			'include_sold_vehicles',
			'show_all_taxonomies',
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
			 * Drop any duplicates.
			 */
			$url_paths = array();
			$unique_rules = array();
			foreach( $sanitary_values['additional_listings_pages'] as $additional_listing )
			{
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

		//if the additional listing pages switch is changed, flush rewrite rules
		//if the switch is on and the array of pages is different, flush rewrite rules
		$settings = Inventory_Presser_Plugin::settings();
		if( $sanitary_values['additional_listings_page'] != $settings['additional_listings_page']
			|| ( $sanitary_values['additional_listings_page'] && $sanitary_values['additional_listings_pages'] != $settings['additional_listings_pages'] ) )
		{
			flush_rewrite_rules();
		}

		return apply_filters( 'invp_options_page_sanitized_values', $sanitary_values, $input );
	}
}
