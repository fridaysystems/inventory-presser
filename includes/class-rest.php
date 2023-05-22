<?php
defined( 'ABSPATH' ) || exit;

/**
 * Inventory_Presser_REST
 *
 * Adds routes to the REST API at /wp-json/invp/
 */
class Inventory_Presser_REST {
	/**
	 * add_hooks
	 *
	 * @return void
	 */
	public function add_hooks() {
		// Adds a /invp/v1/settings route.
		add_action( 'rest_api_init', array( $this, 'add_route' ) );

		// Allow attachments to be ordered by the inventory_presser_photo_number meta value.
		add_filter( 'rest_attachment_collection_params', array( $this, 'allow_orderby_photo_number' ) );
		add_filter( 'rest_attachment_query', array( $this, 'orderby_photo_number' ), 10, 2 );
	}

	/**
	 * Adds the photo_number meta field to the allowed orderby values.
	 *
	 * @param  array $params
	 * @return array
	 */
	public function allow_orderby_photo_number( $params ) {
		$params['orderby']['enum'][] = apply_filters( 'invp_prefix_meta_key', 'photo_number' );
		return $params;
	}

	/**
	 * Changes the query args for requests to order attachments by the
	 * photo_number meta key.
	 *
	 * @param  array           $args Array of arguments for WP_Query.
	 * @param  WP_REST_Request $request The REST API request.
	 * @return array
	 */
	public function orderby_photo_number( $args, $request ) {
		$order_by = $request->get_param( 'orderby' );
		if ( isset( $order_by ) && apply_filters( 'invp_prefix_meta_key', 'photo_number' ) === $order_by ) {
			$args['meta_key'] = $order_by;
			$args['orderby']  = 'meta_value_num'; // user 'meta_value_num' for numerical fields
		}
		return $args;
	}

	public function order_attachments( $params ) {
		// Is the parent of this attachment a vehicle?
		$params['orderby']['enum'][] = apply_filters( 'invp_prefix_meta_key', 'photo_number' );
		return $params;
	}

	/**
	 * Adds a /invp/v1/settings route.
	 *
	 * @return void
	 */
	public function add_route() {
		register_rest_route(
			'invp/v1',
			'/settings/',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'response' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * response
	 *
	 * @return void
	 */
	public function response() {
		$public_keys = array(
			'use_carfax',
		);

		return array_filter(
			INVP::settings(),
			function ( $k ) use ( $public_keys ) {
				return in_array( $k, $public_keys );
			},
			ARRAY_FILTER_USE_KEY
		);
	}
}
