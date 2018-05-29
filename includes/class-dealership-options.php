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

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'dealership_options_add_plugin_page' ) );
		add_action( 'admin_init', array( $this, 'dealership_options_page_init' ) );
	}

	public function dealership_options_add_plugin_page() {
		if ( post_type_exists( Inventory_Presser_Plugin::CUSTOM_POST_TYPE ) ) {
			add_submenu_page('edit.php?post_type=' . Inventory_Presser_Plugin::CUSTOM_POST_TYPE,
				'Options', // page_title
				'Options', // menu_title
				'manage_options', // capability
				'dealership-options', // menu_slug
				array( $this, 'dealership_options_create_admin_page' ) // function
			);
		} else {
			add_options_page(
				'Dealership Options', // page_title
				'Dealership Options', // menu_title
				'manage_options', // capability
				'dealership-options', // menu_slug
				array( $this, 'dealership_options_create_admin_page' ) // function
			);
		}
	}

	public function dealership_options_create_admin_page() {
		$this->_dealer_settings = get_option( '_dealer_settings' ); ?>

		<div class="wrap">
			<h2>Dealership Options</h2>
			<?php settings_errors(); ?>

			<form method="post" action="options.php">
				<?php
					settings_fields( 'dealership_options_option_group' );
					do_settings_sections( 'dealership-options-admin' );
					submit_button();
				?>
			</form>
		</div>
	<?php }

	public function dealership_options_page_init() {
		register_setting(
			'dealership_options_option_group', // option_group
			'_dealer_settings', // option_name
			array( $this, 'dealership_options_sanitize' ) // sanitize_callback
		);

		add_settings_section(
			'dealership_options_setting_section', // id
			'Settings', // title
			'__return_empty_string', // callback
			'dealership-options-admin' // page
		);

		//Sort vehicles by [Field] in [Ascending] order
		add_settings_field(
			'sort_vehicles_by', // id
			'Sort Vehicles By', // title
			array( $this, 'sort_vehicles_by_callback' ), // callback
			'dealership-options-admin', // page
			'dealership_options_setting_section' // section
		);

		//[x] Include sold vehicles on listings pages
		add_settings_field(
			'include_sold_vehicles', // id
			'Sold Vehicles', // title
			array( $this, 'include_sold_vehicles_callback' ), // callback
			'dealership-options-admin', // page
			'dealership_options_setting_section' // section
		);

		add_settings_field(
			'use_carfax', // id
			'CARFAX', // title
			array( $this, 'use_carfax_callback' ), // callback
			'dealership-options-admin', // page
			'dealership_options_setting_section' // section
		);

		add_settings_field(
			'autocheck_id',
			'Autocheck ID',
			array( $this, 'autocheck_callback'),
			'dealership-options-admin',
			'dealership_options_setting_section'
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

		if ( isset( $input['use_carfax'] ) ) {
			$sanitary_values['use_carfax'] = $input['use_carfax'];
		}

		if ( isset( $input['autocheck_id'] ) ) {
			$sanitary_values['autocheck_id'] = sanitize_text_field( $input['autocheck_id'] );
		}

		return apply_filters( 'invp_options_page_sanitized_values', $input, $sanitary_values );
	}

	function include_sold_vehicles_callback() {
		printf(
			'<input type="checkbox" name="_dealer_settings[include_sold_vehicles]" id="include_sold_vehicles" value="include_sold_vehicles" %s> <label for="include_sold_vehicles">Include sold vehicles on listings pages</label>',
			( isset( $this->_dealer_settings['include_sold_vehicles'] ) && $this->_dealer_settings['include_sold_vehicles'] === 'include_sold_vehicles' ) ? 'checked' : ''
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

		echo '</select> in <select name="_dealer_settings[sort_vehicles_order]" id="sort_vehicles_order">';

		foreach( array( 'ascending' => 'ASC', 'descending' => 'DESC' ) as $direction => $abbr ) {
			echo '<option value="'. $abbr . '"';
			if( isset( $this->_dealer_settings['sort_vehicles_order'] ) ) {
				selected( $this->_dealer_settings['sort_vehicles_order'], $abbr );
			}
			echo '>' . $direction . '</option>';
		}
		echo '</select> order';
	}

	public function use_carfax_callback() {
		printf(
			'<input type="checkbox" name="_dealer_settings[use_carfax]" id="use_carfax" value="use_carfax" %s> <label for="use_carfax">Display CARFAX buttons near vehicles that link to free CARFAX reports</label>',
			( isset( $this->_dealer_settings['use_carfax'] ) && $this->_dealer_settings['use_carfax'] === 'use_carfax' ) ? 'checked' : ''
		);
	}

	public function autocheck_callback() {
		printf(
			'<input type="text" name="_dealer_settings[autocheck_id]" id="autocheck_id" value="%s">',
			( isset( $this->_dealer_settings['autocheck_id'] )) ? $this->_dealer_settings['autocheck_id'] : ''
		);
	}
}
if ( is_admin() ) { $dealership_options = new _dealer_settings(); }
