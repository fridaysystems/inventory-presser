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
		// Adds the inventory_type_slugs field to the response of the /wp-json/wp/v2/inventory_vehicle route.
		add_action( 'rest_api_init', array( $this, 'add_inventory_type_slugs_to_posts' ) );

		// Allow attachments to be ordered by the inventory_presser_photo_number meta value.
		add_filter( 'rest_attachment_collection_params', array( $this, 'allow_orderby_photo_number' ) );
		add_filter( 'rest_attachment_query', array( $this, 'orderby_photo_number' ), 10, 2 );

		if ( defined( 'INVP::POST_TYPE' ) ) {
			// Allow vehicles to be returned in a random order.
			add_filter( 'rest_' . INVP::POST_TYPE . '_collection_params', array( $this, 'allow_orderby_rand' ) );
		}
	}

	/**
	 * Adds an attribute `inventory_type_slugs` to the response of the
	 * /wp-json/wp/v2/inventory_vehicle route.
	 *
	 * @return void
	 */
	public function add_inventory_type_slugs_to_posts() {
		register_rest_field(
			INVP::POST_TYPE,
			'inventory_type_slugs',
			array(
				'get_callback' => array( $this, 'get_the_type_slugs' ),
				'schema'       => array(
					'description' => __( 'The type taxonomy term slugs.', 'inventory-presser' ),
					'type'        => 'string',
				),
			)
		);
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
	 * Allow vehicles to be returned in a random order.
	 *
	 * @param  array        $query_params
	 * @param  WP_Post_Type $post_type
	 * @return array
	 */
	public function allow_orderby_rand( $query_params ) {
		$query_params['orderby']['enum'][] = 'rand';
		return $query_params;
	}

	/**
	 * Given a post object, returns the term slugs of the type taxonomy.
	 *
	 * @param  array $post_object An array representing a post object.
	 * @return array Term slugs of the type taxonomy.
	 */
	public function get_the_type_slugs( $post_object ) {
		$terms = get_the_terms( $post_object['id'], 'type' );
		if ( is_array( $terms ) ) {
			return wp_list_pluck( $terms, 'slug' );
		}
		return array();
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
			$args['orderby']  = 'meta_value_num'; // user 'meta_value_num' for numerical fields.
		}
		return $args;
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
		if ( ! class_exists( 'INVP' ) ) {
			return array();
		}

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
