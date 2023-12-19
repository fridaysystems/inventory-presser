<?php
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Inventory_Presser_Addon_License' ) ) {

	/**
	 * Inventory_Presser_Addon_License
	 *
	 * Helps send a license key to inventorypresser.com for this core plugin or any
	 * of its add-ons to verify an active site and allow updates to be downloaded.
	 *
	 * @since      5.4.0
	 * @package    inventory-presser
	 * @subpackage inventory-presser/includes
	 * @author     Corey Salzano <corey@friday.systems>
	 */
	class Inventory_Presser_Addon_License {


		const STORE_URL = 'https://inventorypresser.com/';
		var $license_key;
		var $product_id;

		/**
		 * __construct
		 *
		 * Populates class members
		 *
		 * @param  string $license_key
		 * @param  int    $product_id
		 * @return void
		 */
		function __construct( $license_key, $product_id ) {
			$this->license_key = $license_key;
			$this->product_id  = $product_id;
		}

		/**
		 * is_active
		 *
		 * @return bool
		 */
		public function is_active() {
			$response = $this->api_response( 'check_license', $this->license_key );
			return isset( $response['license'] ) && 'valid' == $response['license'];
		}

		/**
		 * activate
		 *
		 * Activates the license with the plugin store
		 *
		 * @return bool
		 */
		public function activate() {
			$response = $this->api_response( 'activate_license', $this->license_key, $this->product_id );
			return isset( $response['license'] ) && 'valid' == $response['license'];
		}

		/**
		 * api_response
		 *
		 * Retrieves the license activation response from the plugin store.
		 *
		 * @param  string $action
		 * @return void
		 */
		private function api_response( $action ) {
			$response = wp_remote_get( esc_url_raw( $this->api_url( $action, $this->license_key, $this->product_id ) ) );
			return json_decode( wp_remote_retrieve_body( $response ), true );
		}

		/**
		 * api_url
		 *
		 * Creates a URL to the plugin store where this license can be renewed.
		 *
		 * @param  string $action
		 * @return string A URL
		 */
		private function api_url( $action ) {
			return sprintf(
				'%s?edd_action=%s&item_id=%s&license=%s&url=%s',
				self::STORE_URL,
				$action,
				$this->product_id,
				$this->license_key,
				urlencode( home_url() )
			);
		}
	}
}
