<?php

if( ! class_exists( 'Inventory_Presser_License' ) ) {
	class Inventory_Presser_License{

		const STORE_URL = 'https://inventorypresser.com/';
		var $license_key;
		var $product_id;

		function __construct( $license_key, $product_id ) {
			$this->license_key = $license_key;
			$this->product_id = $product_id;
		}

		public function is_active() {
			$response = $this->api_response( 'check_license', $this->license_key );
			return isset( $response->license ) && 'valid' == $response->license;
		}

		public function activate() {
			$response = $this->api_response( 'activate_license', $this->license_key, $this->product_id );
			return isset( $response->license ) && 'valid' == $response->license;
		}

		/* Private */

		private function api_response( $action ) {
			$response = wp_remote_get( esc_url_raw( $this->api_url( $action, $this->license_key, $this->product_id ) ) );
			return json_decode( wp_remote_retrieve_body( $response ), true );
		}

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
