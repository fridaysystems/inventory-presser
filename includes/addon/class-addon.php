<?php
defined( 'ABSPATH' ) or exit;

if( ! class_exists( 'Inventory_Presser_Addon' ) )
{
	/**
	 * Inventory_Presser_Addon
	 * 
	 * A base class for add-on plugins. Provides methods to initialize an 
	 * updater that connects to inventorypresser.com to check for updates and 
	 * store a license key in the site that permits said updates.
	 */
	abstract class Inventory_Presser_Addon
	{		
		/**
		 * initialize_updater
		 *
		 * @param  string $class_name The class name in the add-on plugin's main file that contains the VERSION and LICENSE_ITEM constants
		 * @param  string $plugin_path The file path to the plugin for which we are checking for updates
		 * @param  string $license_key The license key provided by inventorypresser.com upon purchase
		 * @param  string $include_betas Set to true if you wish customers to receive update notifications of beta releases
		 * @return void
		 */
		public static function initialize_updater( $class_name, $plugin_path, $license_key, $include_betas = false )
		{
			//check for existence of updater class
			if( ! class_exists( 'Inventory_Presser_Addon_Updater' ) )
			{
				return;
			}

			//Does the plugin class have the VERSION and LICENSE_ITEM constants?
			if( empty( $class_name::VERSION ) || empty( $class_name::LICENSE_ITEM ) )
			{
				return;
			} 

			$updater = new Inventory_Presser_Addon_Updater( 'https://inventorypresser.com', $plugin_path, array(
				'version' 	=> $class_name::VERSION, //current version number
				'license' 	=> $license_key,
				'item_id'   => $class_name::LICENSE_ITEM, //id of this download in inventorypresser.com
				'author' 	=> 'Corey Salzano',
				'url'       => home_url(), //the site requesting the update, this site
				'beta'      => $include_betas
			) );
		}

		public static function add_license_key_box( $option_name, $settings_section, $sanitize_values_hook_name, $license_key_key_name = 'license_key' )
		{
			add_action( 'admin_init', function() use ( $option_name, $settings_section, $license_key_key_name ) {

				//is $settings_section an existing section?
				global $wp_settings_sections;
				if( empty( $wp_settings_sections[INVP::option_page()][$settings_section] ) )
				{
					//no, abort
					return;
				}

				$option = get_option( $option_name );
				$current_value = empty( $option[$license_key_key_name] ) ? '' : $option[$license_key_key_name];

				add_settings_field(
					$license_key_key_name, // id
					__( 'License key', 'inventory-presser' ), // title
					function() use ( $option_name, $current_value, $license_key_key_name ) {
						?><p><input type="text" name="<?php echo $option_name; ?>[<?php echo $license_key_key_name; ?>]" class="regular-text code" id="<?php echo $option_name; ?>[<?php echo $license_key_key_name; ?>]" value="<?php echo $current_value; ?>" /></p>
						<p class="description"><?php printf( '%s <a href="https://inventorypresser.com/">https://inventorypresser.com/</a> %s', __( 'Obtain a key at', 'inventory-presser' ), __( 'to receive plugin updates.', 'inventory-presser' ) ); ?></p><?php
					}, // callback
					INVP::option_page(), // page
					$settings_section // section
				);
			}, 20 );

			$hook_parameters_count = 'license_key' == $license_key_key_name ? 3 : 4;
			add_filter( $sanitize_values_hook_name, array( __CLASS__, 'sanitize_and_activate_license_key' ), 10, $hook_parameters_count );
		}

		public static function make_sure_license_is_activated( $license_item, $license_key )
		{
			$license = new Inventory_Presser_Addon_License(
				$license_key,
				$license_item
			);
			if( ! $license->is_active() ) { $license->activate(); }
		}
		
		public static function sanitize_and_activate_license_key( $sanitized, $input, $license_item, $license_key_key_name = 'license_key' )
		{				
			if ( isset( $input[$license_key_key_name] ) )
			{
				$sanitized[$license_key_key_name] = sanitize_text_field( $input[$license_key_key_name] );
				self::make_sure_license_is_activated( $license_item, $sanitized[$license_key_key_name] );
			}
			return $sanitized;
		}
	}
}
