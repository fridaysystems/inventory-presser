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
		add_action( 'admin_menu', array( $this, 'add_options_page' ) );
		add_action( 'admin_init', array( $this, 'add_settings' ) );

		/**
		 * In September 2019, I decided to rename this plugin's option. I made
		 * this decision after realizing that the _dealer theme uses the same
		 * option to store its settings. Also, I've always felt that it has been
		 * unfortunately named something that does not indicate to strangers
		 * that it belongs to this plugin.
		 */
		add_action( 'plugins_loaded', array( $this, 'rename_option' ) );
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

		add_settings_section(
			'dealership_options_setting_section', // id
			__( 'Settings', 'inventory-presser' ), // title
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

		//Sort vehicles by [Field] in [Ascending] order
		add_settings_field(
			'sort_vehicles_by', // id
			__( 'Sort Vehicles By', 'inventory-presser' ), // title
			array( $this, 'callback_sort_vehicles_by' ), // callback
			'dealership-options-admin', // page
			'dealership_options_setting_section' // section
		);

		//[x] Include sold vehicles on listings pages
		add_settings_field(
			'include_sold_vehicles', // id
			__( 'Sold Vehicles', 'inventory-presser' ), // title
			array( $this, 'callback_include_sold_vehicles' ), // callback
			'dealership-options-admin', // page
			'dealership_options_setting_section' // section
		);

		add_settings_field(
			'use_carfax', // id
			__( 'CARFAX', 'inventory-presser' ), // title
			array( $this, 'callback_use_carfax' ), // callback
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
	}

	function boolean_checkbox_setting_callback( $setting_name, $checkbox_label )
	{
		printf(
			'<input type="checkbox" name="%s[%s]" id="%s" %s> <label for="%s">%s</label>',
			Inventory_Presser_Plugin::OPTION_NAME,
			$setting_name,
			$setting_name,
			( isset( $this->option[$setting_name] ) && $this->option[$setting_name] ) ? 'checked' : '',
			$setting_name,
			$checkbox_label
		);
	}

	function callback_include_sold_vehicles()
	{
		$this->boolean_checkbox_setting_callback(
			'include_sold_vehicles',
			__( 'Include sold vehicles on listings pages', 'inventory-presser' )
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
			$this->option['sort_vehicles_by'] = apply_filters( 'invp_prefix_meta_key', 'make' );
		}
		if( ! isset( $this->option['sort_vehicles_order'] ) )
		{
			$this->option['sort_vehicles_order'] = 'ASC';
		}

		printf(
			'<select name="%s[sort_vehicles_by]" id="sort_vehicles_by">',
			Inventory_Presser_Plugin::OPTION_NAME
		);

		/**
		 * Get a list of all the post meta keys in our CPT. Let the user choose
		 * one as a default sort.
		 */
		$vehicle = new Inventory_Presser_Vehicle();
		foreach( $vehicle->keys( false ) as $key )
		{
			//Skip post meta keys that make no sense as a sort key
			$non_sortable_keys = array(
				'color',
				'engine',
				'hull_material',
				'interior_color',
				'stock_number',
				'trim',
				'vin',
				'youtube',
			);
			if( in_array( $key, $non_sortable_keys ) )
			{
				continue;
			}

			$meta_key = apply_filters( 'invp_prefix_meta_key', $key );

			//Skip hidden post meta keys
			if( '_' == $meta_key[0] )
			{
				continue;
			}

			echo '<option value="'. $meta_key . '"';
			if( isset( $this->option['sort_vehicles_by'] ) )
			{
				selected( $this->option['sort_vehicles_by'], $meta_key );
			}
			echo '>' . str_replace( '_', ' ', ucfirst( $key ) ) . '</option>';
		}

		printf(
			'</select> %s <select name="%s[sort_vehicles_order]" id="sort_vehicles_order">',
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
			__( 'Display CARFAX buttons near vehicles that link to free CARFAX reports', 'inventory-presser' )
		);
	}

	public function options_page_content()
	{
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
		$new_option = get_option( Inventory_Presser_Plugin::OPTION_NAME );
		if( $new_option ) {
			return;
		}

		$old_option = get_option( $old_option_name );
		if( ! $old_option ) {
			return;
		}

		$new_option = array(
			'price_display'         => $old_option['price_display_type'], //Rename this key
			'sort_vehicles_by'      => $old_option['sort_vehicles_by'],
			'sort_vehicles_order'   => $old_option['sort_vehicles_order'],
			'include_sold_vehicles' => $old_option['include_sold_vehicles'],
			'show_all_taxonomies'   => $old_option['show_all_taxonomies'],
			'use_carfax'            => $old_option['use_carfax'],
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

		if ( isset( $input['include_sold_vehicles'] ) )
		{
			$sanitary_values['include_sold_vehicles'] = true;
		}

		if ( isset( $input['show_all_taxonomies'] ) )
		{
			$sanitary_values['show_all_taxonomies'] = true;
		}

		if ( isset( $input['use_carfax'] ) )
		{
			$sanitary_values['use_carfax'] = true;
		}

		return apply_filters( 'invp_options_page_sanitized_values', $input, $sanitary_values );
	}
}
