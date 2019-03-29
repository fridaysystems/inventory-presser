<?php
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

class _dealer_settings {
	private $_dealer_settings;

	public function hooks() {
		add_action( 'admin_menu', array( $this, 'add_options_page' ) );
		add_action( 'admin_init', array( $this, 'add_settings' ) );
	}

	public function add_options_page() {
		if ( post_type_exists( Inventory_Presser_Plugin::CUSTOM_POST_TYPE ) ) {
			add_submenu_page('edit.php?post_type=' . Inventory_Presser_Plugin::CUSTOM_POST_TYPE,
				__( 'Options', 'inventory-presser' ), // page_title
				__( 'Options', 'inventory-presser' ), // menu_title
				'manage_options', // capability
				'dealership-options', // menu_slug
				array( $this, 'options_page_content' ) // function
			);
		} else {
			add_options_page(
				__( 'Inventory Presser', 'inventory-presser' ), // page_title
				__( 'Inventory Presser', 'inventory-presser' ), // menu_title
				'manage_options', // capability
				'dealership-options', // menu_slug
				array( $this, 'options_page_content' ) // function
			);
		}
	}

	public function options_page_content() {
		$this->_dealer_settings = get_option( '_dealer_settings' );

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

	public function add_settings() {
		register_setting(
			'dealership_options_option_group', // option_group
			'_dealer_settings', // option_name
			array( $this, 'dealership_options_sanitize' ) // sanitize_callback
		);

		add_settings_section(
			'dealership_options_setting_section', // id
			__( 'Settings', 'inventory-presser' ), // title
			'__return_empty_string', // callback
			'dealership-options-admin' // page
		);

		//Sort vehicles by [Field] in [Ascending] order
		add_settings_field(
			'sort_vehicles_by', // id
			__( 'Sort Vehicles By', 'inventory-presser' ), // title
			array( $this, 'sort_vehicles_by_callback' ), // callback
			'dealership-options-admin', // page
			'dealership_options_setting_section' // section
		);

		//[x] Include sold vehicles on listings pages
		add_settings_field(
			'include_sold_vehicles', // id
			__( 'Sold Vehicles', 'inventory-presser' ), // title
			array( $this, 'include_sold_vehicles_callback' ), // callback
			'dealership-options-admin', // page
			'dealership_options_setting_section' // section
		);

		add_settings_field(
			'use_carfax', // id
			__( 'CARFAX', 'inventory-presser' ), // title
			array( $this, 'use_carfax_callback' ), // callback
			'dealership-options-admin', // page
			'dealership_options_setting_section' // section
		);

		//[x] Show all taxonomies under Vehicles menu in Dashboard
		add_settings_field(
			'show_all_taxonomies', // id
			__( 'Show All Taxonomies', 'inventory-presser' ), // title
			array( $this, 'show_all_taxonomies_callback' ), // callback
			'dealership-options-admin', // page
			'dealership_options_setting_section' // section
		);
	}

	public function dealership_options_sanitize( $input ) {
		$sanitary_values = array();

		if ( isset( $input['sort_vehicles_by'] ) ) {
			$sanitary_values['sort_vehicles_by'] = $input['sort_vehicles_by'];
		}

		if ( isset( $input['sort_vehicles_order'] ) ) {
			$sanitary_values['sort_vehicles_order'] = $input['sort_vehicles_order'];
		}

		if ( isset( $input['include_sold_vehicles'] ) ) {
			$sanitary_values['include_sold_vehicles'] = $input['include_sold_vehicles'];
		}

		if ( isset( $input['show_all_taxonomies'] ) ) {
			$sanitary_values['show_all_taxonomies'] = true;
		}

		if ( isset( $input['use_carfax'] ) ) {
			$sanitary_values['use_carfax'] = $input['use_carfax'];
		}

		return apply_filters( 'invp_options_page_sanitized_values', $input, $sanitary_values );
	}

	function include_sold_vehicles_callback() {
		printf(
			'<input type="checkbox" name="_dealer_settings[include_sold_vehicles]" id="include_sold_vehicles" value="include_sold_vehicles" %s> <label for="include_sold_vehicles">%s</label>',
			( isset( $this->_dealer_settings['include_sold_vehicles'] ) && $this->_dealer_settings['include_sold_vehicles'] === 'include_sold_vehicles' ) ? 'checked' : '',
			__( 'Include sold vehicles on listings pages', 'inventory-presser' )
		);
	}

	public function sort_vehicles_by_callback() {

		//use these default values if we have none
		if( ! isset( $this->_dealer_settings['sort_vehicles_by'] ) ) {
			$this->_dealer_settings['sort_vehicles_by'] = apply_filters( 'invp_prefix_meta_key', 'make' );
		}
		if( ! isset( $this->_dealer_settings['sort_vehicles_order'] ) ) {
			$this->_dealer_settings['sort_vehicles_order'] = 'ASC';
		}

		echo '<select name="_dealer_settings[sort_vehicles_by]" id="sort_vehicles_by">';

		/**
		 * Get a list of all the post meta keys in our
		 * CPT. Let the user choose one as a default
		 * sort.
		 */
		$vehicle = new Inventory_Presser_Vehicle();
		foreach( $vehicle->keys( false ) as $key ) {

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
			if( in_array( $key, $non_sortable_keys ) ) { continue; }

			$meta_key = apply_filters( 'invp_prefix_meta_key', $key );

			//Skip hidden post meta keys
			if( '_' == $meta_key[0] ) { continue; }

			echo '<option value="'. $meta_key . '"';
			if( isset( $this->_dealer_settings['sort_vehicles_by'] ) ) {
				selected( $this->_dealer_settings['sort_vehicles_by'], $meta_key );
			}
			echo '>' . str_replace( '_', ' ', ucfirst( $key ) ) . '</option>';
		}

		printf(
			'</select> %s <select name="_dealer_settings[sort_vehicles_order]" id="sort_vehicles_order">',
			__( 'in', 'inventory-presser' )
		);

		foreach( array( 'ascending' => 'ASC', 'descending' => 'DESC' ) as $direction => $abbr ) {
			echo '<option value="'. $abbr . '"';
			if( isset( $this->_dealer_settings['sort_vehicles_order'] ) ) {
				selected( $this->_dealer_settings['sort_vehicles_order'], $abbr );
			}
			echo '>' . $direction . '</option>';
		}
		echo '</select> ' . __( 'order', 'inventory-presser' );
	}

	function boolean_checkbox_setting_callback( $setting_name, $checkbox_label ) {
		printf(
			'<input type="checkbox" name="_dealer_settings[%s]" id="%s" %s> <label for="%s">%s</label>',
			$setting_name,
			$setting_name,
			( isset( $this->_dealer_settings[$setting_name] ) && $this->_dealer_settings[$setting_name] ) ? 'checked' : '',
			$setting_name,
			$checkbox_label
		);
	}

	/**
	 * Output the controls that create the Show all Taxonomies setting.
	 *
	 * @return void
	 */
	function show_all_taxonomies_callback() {
		$this->boolean_checkbox_setting_callback( 'show_all_taxonomies', __( 'Show all taxonomies under Vehicles menu in Dashboard', 'inventory-presser' ) );
	}

	public function use_carfax_callback() {
		printf(
			'<input type="checkbox" name="_dealer_settings[use_carfax]" id="use_carfax" value="use_carfax" %s> <label for="use_carfax">%s</label>',
			( isset( $this->_dealer_settings['use_carfax'] ) && $this->_dealer_settings['use_carfax'] === 'use_carfax' ) ? 'checked' : '',
			__( 'Display CARFAX buttons near vehicles that link to free CARFAX reports', 'inventory-presser' )
		);
	}
}
if ( is_admin() ) {
	$invp_options_019230128080 = new _dealer_settings();
	$invp_options_019230128080->hooks();
}
