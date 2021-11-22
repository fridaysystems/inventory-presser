<?php
defined( 'ABSPATH' ) or exit;

if( ! class_exists( 'Inventory_Presser_Addon_License_Validator' ) ) {

	/**
	 * Inventory_Presser_Addon_License_Validator
	 * 
	 * Helps add-ons connect to inventorypresser.com to validate licenses and 
	 * receive plugin updates. 
	 * 
	 * License in this context means a license key that is bought and grants 
	 * access to premium add-ons. This plugin is free and licensed GPLv2 or 
	 * later. See readme.txt for more information about this plugin.
	 *
	 * @since      12.1.0
	 * @package    Inventory_Presser
	 * @subpackage Inventory_Presser/includes
	 * @author     Corey Salzano <corey@friday.systems>
	 */
	class Inventory_Presser_Addon_License_Validator
	{		
		/**
		 * is_active
		 *
		 * @param  string $product_id 
		 * @param  string $license_key
		 * @return bool
		 */
		public static function is_active( $product_id, $license_key )
		{
			$response = self::api_response( 'check_license', $product_id, $license_key );
			return isset( $response->license ) && 'valid' == $response->license;
		}
		
		/**
		 * activate
		 * 
		 * Activates the license with the plugin store
		 *
		 * @param  string $product_id 
		 * @param  string $license_key
		 * @return bool
		 */
		public static function activate( $product_id, $license_key )
		{
			$response = self::api_response( 'activate_license', $product_id, $license_key );
			return isset( $response->license ) && 'valid' == $response->license;
		}

		/**
		 * api_response
		 * 
		 * Retrieves the license activation response from the plugin store.
		 *
		 * @param  string $action
		 * @param  string $product_id 
		 * @param  string $license_key
		 * @return void
		 */
		private static function api_response( $action, $product_id, $license_key )
		{
			$response = wp_remote_get( esc_url_raw( self::api_url( $action, $license_key, $product_id ) ) );
			return json_decode( wp_remote_retrieve_body( $response ), true );
		}
		
		/**
		 * api_url
		 * 
		 * Creates a URL to the plugin store where this license can be renewed.
		 *
		 * @param  string $action
		 * @param  string $product_id 
		 * @param  string $license_key
		 * @return string A URL
		 */
		private static function api_url( $action, $product_id, $license_key )
		{
			return sprintf(
				'https://inventorypresser.com/?edd_action=%s&item_id=%s&license=%s&url=%s',
				$action,
				$product_id,
				$license_key,
				urlencode( home_url() )
			);
		}
	}
}