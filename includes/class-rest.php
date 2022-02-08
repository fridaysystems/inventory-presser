<?php
defined( 'ABSPATH' ) or exit;

/**
 * Inventory_Presser_REST
 * 
 * Adds routes to the REST API at /wp-json/invp/
 */
class Inventory_Presser_REST
{
	public function add_hooks()
	{
		add_action( 'rest_api_init', array( $this, 'add_route' ) );
	}

	public function add_route()
	{
		register_rest_route( 'invp/v1', '/settings/', array(
			'methods' => 'GET',
			'callback' => array( $this, 'response' ),
		) );
	}

	public function response()
	{
		/**
		 * This is controversial. Some people think options table data will 
		 * never be public. Only allow these keys:
		 */

		$public_keys = array(
			'use_carfax',
		);

		return array_filter( INVP::settings(), function( $k ) use( $public_keys ) { return in_array( $k, $public_keys ); }, ARRAY_FILTER_USE_KEY );
	}
}
