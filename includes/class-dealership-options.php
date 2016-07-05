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
		add_options_page(
			'Dealership Options', // page_title
			'Dealership Options', // menu_title
			'manage_options', // capability
			'dealership-options', // menu_slug
			array( $this, 'dealership_options_create_admin_page' ) // function
		);
	}

	public function dealership_options_create_admin_page() {
		$this->_dealer_settings = get_option( '_dealer_settings' ); ?>

		<div class="wrap">
			<h2>Dealership Options</h2>
			<p></p>

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
			'Privacy Page', // title
			array( $this, 'privacy_page_callback' ), // callback
			'dealership-options-admin', // page
			'dealership_options_setting_section' // section
		);

		add_settings_field(
			'append_page', // id
			'Vehicle Append Page', // title
			array( $this, 'append_page_callback' ), // callback
			'dealership-options-admin', // page
			'dealership_options_setting_section' // section
		);

		add_settings_field(
			'use_carfax', // id
			'Use CarFax', // title
			array( $this, 'use_carfax_callback' ), // callback
			'dealership-options-admin', // page
			'dealership_options_setting_section' // section
		);

		add_settings_field(
			'cargurus_badge', // id
			'Cargurus', // title
			array( $this, 'cargurus_badge_callback' ), // callback
			'dealership-options-admin', // page
			'dealership_options_setting_section' // section
		);

		add_settings_field( 
			'hide_contact_button_single', 
			'Availability Button', 
			array( $this, 'contact_button_single_callback'), 
			'dealership-options-admin', 
			'dealership_options_setting_section' 
		);

		add_settings_field( 
			'autocheck_id', 
			'Autocheck ID', 
			array( $this, 'autocheck_callback'), 
			'dealership-options-admin', 
			'dealership_options_setting_section' 
		);

		add_settings_field( 
			'msrp_label', 
			'MSRP Label', 
			array( $this, 'msrp_label_callback'), 
			'dealership-options-admin', 
			'dealership_options_setting_section' 
		);
	}

	public function dealership_options_sanitize($input) {
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

		if ( isset( $input['append_page'] ) ) {
			$sanitary_values['append_page'] = $input['append_page'];
		}

		if ( isset( $input['use_carfax'] ) ) {
			$sanitary_values['use_carfax'] = $input['use_carfax'];
		}

		if ( isset( $input['cargurus_badge_archive'] ) ) {
			$sanitary_values['cargurus_badge_archive'] = $input['cargurus_badge_archive'];
		}

		if ( isset( $input['cargurus_badge_single'] ) ) {
			$sanitary_values['cargurus_badge_single'] = $input['cargurus_badge_single'];
		}

		if ( isset( $input['hide_contact_button_single'] ) ) {
			$sanitary_values['hide_contact_button_single'] = $input['hide_contact_button_single'];
		}

		if ( isset( $input['autocheck_id'] ) ) {
			$sanitary_values['autocheck_id'] = $input['autocheck_id'];
		}

		if ( isset( $input['msrp_label'] ) ) {
			$sanitary_values['msrp_label'] = $input['msrp_label'];
		}

		return $sanitary_values;
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

	}

	public function append_page_callback() {

		$args = array(
		    'depth'                 => 0,
		    'child_of'              => 0,
		    'selected'              => isset($this->_dealer_settings['append_page']) ? $this->_dealer_settings['append_page'] : 0,
		    'echo'                  => 1,
		    'name'                  => '_dealer_settings[append_page]',
		    'show_option_none'      => 'Not Set',
		    'option_none_value'     => '0'
		); 

		wp_dropdown_pages($args);

	}

	public function use_carfax_callback() {
		printf(
			'<input type="checkbox" name="_dealer_settings[use_carfax]" id="use_carfax" value="use_carfax" %s> <label for="use_carfax">Display CarFax Links</label>',
			( isset( $this->_dealer_settings['use_carfax'] ) && $this->_dealer_settings['use_carfax'] === 'use_carfax' ) ? 'checked' : ''
		);
	}

	public function cargurus_badge_callback() {
		printf(
			'<input type="checkbox" name="_dealer_settings[cargurus_badge_archive]" id="cargurus_badge_archive" value="cargurus_badge_archive" %s> <label for="cargurus_badge_archive">Show Cargurus Badge on Vehicle Archive</label>',
			( isset( $this->_dealer_settings['cargurus_badge_archive'] ) && $this->_dealer_settings['cargurus_badge_archive'] === 'cargurus_badge_archive' ) ? 'checked' : ''
		);
		print '<br/>';
		printf(
			'<input type="checkbox" name="_dealer_settings[cargurus_badge_single]" id="cargurus_badge_single" value="cargurus_badge_single" %s> <label for="cargurus_badge_single">Show Cargurus Badge on Vehicle Single</label>',
			( isset( $this->_dealer_settings['cargurus_badge_single'] ) && $this->_dealer_settings['cargurus_badge_single'] === 'cargurus_badge_single' ) ? 'checked' : ''
		);
	}

	public function contact_button_single_callback() {
		printf(
			'<input type="checkbox" name="_dealer_settings[hide_contact_button_single]" id="hide_contact_button_single" value="hide_contact_button_single" %s> <label for="hide_contact_button_single">Hide check availability button on single vehicle page</label>',
			( isset( $this->_dealer_settings['hide_contact_button_single'] ) && $this->_dealer_settings['hide_contact_button_single'] === 'hide_contact_button_single' ) ? 'checked' : ''
		);
	}

	public function autocheck_callback() {
		printf(
			'<input type="text" name="_dealer_settings[autocheck_id]" id="autocheck_id" value="%s">',
			( isset( $this->_dealer_settings['autocheck_id'] )) ? $this->_dealer_settings['autocheck_id'] : ''
		);
	}

	public function msrp_label_callback() {
		printf(
			'<input type="text" name="_dealer_settings[msrp_label]" id="msrp_label" value="%s">',
			( isset( $this->_dealer_settings['msrp_label'] )) ? $this->_dealer_settings['msrp_label'] : ''
		);
	}

}
if ( is_admin() )
	$dealership_options = new _dealer_settings();

/* 
 * Retrieve this value with:
 * $_dealer_settings = get_option( '_dealer_settings' ); // Array of All Options
 * $financing_page = $_dealer_settings['financing_page']; // Financing Page
 * $contact_page = $_dealer_settings['contact_page']; // Contact Page
 * $use_carfax = $_dealer_settings['use_carfax']; // Use CarFax
 */