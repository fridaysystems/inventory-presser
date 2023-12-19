<?php
/**
 * Add-on License Validator
 *
 * @package inventory-presser
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Inventory_Presser_Addon_License_Validator' ) ) {

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
	 * @package    inventory-presser
	 * @subpackage inventory-presser/includes
	 * @author     Corey Salzano <corey@friday.systems>
	 */
	class Inventory_Presser_Addon_License_Validator {

		/**
		 * Checks if a license key is active.
		 *
		 * @param  string $product_id The post ID of the download on inventorypresser.com.
		 * @param  string $license_key The license key sold to the user.
		 * @return bool
		 */
		public static function is_active( $product_id, $license_key ) {
			$transient_key = 'invp_addon_' . $product_id;
			$response      = get_transient( $transient_key );
			if ( false === $response ) {
				// Cached value is missing, hit inventorypresser.com.
				$response = self::api_response( 'check_license', $product_id, $license_key );
				set_transient( $transient_key, $response, 24 * HOUR_IN_SECONDS );
			}
			return isset( $response->license ) && 'valid' === $response->license;
		}

		/**
		 * Activates the license with the plugin store.
		 *
		 * @param  string $product_id The post ID of the download on inventorypresser.com.
		 * @param  string $license_key The license key sold to the user.
		 * @return bool
		 */
		public static function activate( $product_id, $license_key ) {
			$response = self::api_response( 'activate_license', $product_id, $license_key );
			return isset( $response->license ) && 'valid' === $response->license;
		}

		/**
		 * Retrieves the license activation response from the plugin store.
		 *
		 * @param  string $action One of 'activate_license' or 'check_license'.
		 * @param  string $product_id The post ID of the download on inventorypresser.com.
		 * @param  string $license_key The license key sold to the user.
		 * @return array
		 */
		private static function api_response( $action, $product_id, $license_key ) {
			$response = wp_remote_get( esc_url_raw( self::api_url( $action, $license_key, $product_id ) ) );
			return json_decode( wp_remote_retrieve_body( $response ), true );
		}

		/**
		 * Creates a URL to the plugin store where this license can be renewed.
		 *
		 * @param  string $action One of 'activate_license' or 'check_license'.
		 * @param  string $product_id The post ID of the download on inventorypresser.com.
		 * @param  string $license_key The license key sold to the user.
		 * @return string A URL
		 */
		private static function api_url( $action, $product_id, $license_key ) {
			return sprintf(
				'https://inventorypresser.com/?edd_action=%s&item_id=%s&license=%s&url=%s',
				$action,
				$product_id,
				$license_key,
				rawurlencode( home_url() )
			);
		}
	}
}
