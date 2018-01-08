<?php
/**
 * Generated by the WordPress Option Page generator
 * at http://jeremyhixon.com/wp-tools/option-page/
 *
 * This handles a couple options for _dealer theme, will likely combine this with import options
 * Found in admin under Settings -> Dealership Options
 *
 */

class _dealer_settings {
	private $_dealer_settings;

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'dealership_options_add_plugin_page' ) );
		add_action( 'admin_init', array( $this, 'dealership_options_page_init' ) );
	}

	public function dealership_options_add_plugin_page() {
		if (post_type_exists( 'inventory_vehicle' )) {
			add_submenu_page('edit.php?post_type=inventory_vehicle',
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
			array( $this, 'dealership_options_section_info' ), // callback
			'dealership-options-admin' // page
		);

		add_settings_field(
			'financing_page', // id
			'Financing Page', // title
			array( $this, 'financing_page_callback' ), // callback
			'dealership-options-admin', // page
			'dealership_options_setting_section' // section
		);

		add_settings_field(
			'contact_page', // id
			'Contact Page', // title
			array( $this, 'contact_page_callback' ), // callback
			'dealership-options-admin', // page
			'dealership_options_setting_section' // section
		);

		add_settings_field(
			'privacy_page', // id
			'Privacy Policy Page', // title
			array( $this, 'privacy_page_callback' ), // callback
			'dealership-options-admin', // page
			'dealership_options_setting_section' // section
		);

		//Sort vehicles by [Field] in [Ascending] order
		add_settings_field(
			'sort_vehicles_by', // id
			'Sort Vehicles By', // title
			array( $this, 'sort_vehicles_by_callback' ), // callback
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

		if ( isset( $input['financing_page'] ) ) {
			$sanitary_values['financing_page'] = $input['financing_page'];
		}

		if ( isset( $input['contact_page'] ) ) {
			$sanitary_values['contact_page'] = $input['contact_page'];
		}

		if ( isset( $input['privacy_page'] ) ) {
			$sanitary_values['privacy_page'] = $input['privacy_page'];
		}

		if ( isset( $input['sort_vehicles_by'] ) ) {
			$sanitary_values['sort_vehicles_by'] = $input['sort_vehicles_by'];
		}

		if ( isset( $input['sort_vehicles_order'] ) ) {
			$sanitary_values['sort_vehicles_order'] = $input['sort_vehicles_order'];
		}

		if ( isset( $input['use_carfax'] ) ) {
			$sanitary_values['use_carfax'] = $input['use_carfax'];
		}

		if ( isset( $input['autocheck_id'] ) ) {
			$sanitary_values['autocheck_id'] = sanitize_text_field( $input['autocheck_id'] );
		}

		return apply_filters( 'invp_options_page_sanitized_values', $input, $sanitary_values );
	}

	public function dealership_options_section_info() {

	}

	public function financing_page_callback() {

		$args = array(
		    'depth'                 => 0,
		    'child_of'              => 0,
		    'selected'              => isset($this->_dealer_settings['financing_page']) ? $this->_dealer_settings['financing_page'] : 0,
		    'echo'                  => 1,
		    'name'                  => '_dealer_settings[financing_page]',
		    'show_option_none'      => 'Not Set',
		    'option_none_value'     => '0'
		);

		wp_dropdown_pages($args);
		echo '<p class="description" id="_dealer_settings[financing_page]-description">Identifying your financing application here allows themes to create "Apply Now" links and buttons near vehicles that lead to the correct page.</strong></p>';
	}

	public function contact_page_callback() {

		$args = array(
		    'depth'                 => 0,
		    'child_of'              => 0,
		    'selected'              => isset($this->_dealer_settings['contact_page']) ? $this->_dealer_settings['contact_page'] : 0,
		    'echo'                  => 1,
		    'name'                  => '_dealer_settings[contact_page]',
		    'show_option_none'      => 'Not Set',
		    'option_none_value'     => '0'
		);

		wp_dropdown_pages($args);
		echo '<p class="description" id="_dealer_settings[contact_page]-description">Identifying your contact page here allows themes to create "Contact Us" links and buttons near vehicles that lead to the correct page.</strong></p>';
	}

	public function privacy_page_callback() {

		$args = array(
		    'depth'                 => 0,
		    'child_of'              => 0,
		    'selected'              => isset($this->_dealer_settings['privacy_page']) ? $this->_dealer_settings['privacy_page'] : 0,
		    'echo'                  => 1,
		    'name'                  => '_dealer_settings[privacy_page]',
		    'show_option_none'      => 'Not Set',
		    'option_none_value'     => '0'
		);

		wp_dropdown_pages($args);
		echo '<p class="description" id="_dealer_settings[privacy_page]-description">Identifying your privacy policy page here allows themes to create links to the correct page.</strong></p>';
	}

	public function sort_vehicles_by_callback() {

		//use these default values if we have none
		if( ! isset( $this->_dealer_settings['sort_vehicles_by'] ) ) {
			$this->_dealer_settings['sort_vehicles_by'] = apply_filters( 'translate_meta_field_key', 'make' );
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

			$meta_key = apply_filters( 'translate_meta_field_key', $key );

			//Skip hidden post meta keys
			if( '_' == $meta_key[0] ) { continue; }

			echo '<option value="'. $meta_key . '"';
			if( isset( $this->_dealer_settings['sort_vehicles_by'] ) ) {
				selected( $this->_dealer_settings['sort_vehicles_by'], $meta_key );
			}
			echo '>' . str_replace( '_', ' ', ucfirst( apply_filters( 'untranslate_meta_field_key', $key ) ) ) . '</option>';
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
if ( is_admin() )
	$dealership_options = new _dealer_settings();
