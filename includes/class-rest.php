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
		/*
			Adds
				/invp/v1/settings
				/invp/v1/feed-complete routes.
		*/
		add_action( 'rest_api_init', array( $this, 'add_routes' ) );

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
	 * Adds /invp/v1/settings & /invp/v1/feed-complete routes.
	 *
	 * @return void
	 */
	public function add_routes() {
		register_rest_route(
			'invp/v1',
			'/settings/',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'response_settings' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			'invp/v1',
			'/feed-complete/',
			array(
				'methods'             => 'PUT',
				'callback'            => array( $this, 'response_feed_complete' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}

	/**
	 * Creates the response data for the /feed-complete/ route.
	 *
	 * @return array
	 */
	public function response_feed_complete() {
		/**
		 * A successful PUT request to /wp-json/invp/v1/feed-complete has
		 * occurred. Assume a client has just updated the entire list of
		 * inventory posts and attachments.
		 */
		do_action( 'invp_feed_complete' );

		// Tell the user what just happened.
		return array(
			'action'        => 'invp_feed_complete',
			'documentation' => 'https://inventorypresser.com/docs/reference/hooks/invp_feed_complete/',
		);
	}

	/**
	 * Creates the response data for the /settings/ route.
	 *
	 * @return array
	 */
	public function response_settings() {
		$public_keys = array(
			'use_carfax',
		);

		return array_filter(
			INVP::settings(),
			function ( $k ) use ( $public_keys ) {
				return in_array( $k, $public_keys, true );
			},
			ARRAY_FILTER_USE_KEY
		);
	}
}
