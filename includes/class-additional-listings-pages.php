<?php
defined( 'ABSPATH' ) || exit;

/**
 * Inventory_Presser_Additional_Listings_Pages
 *
 * This class makes additional listings pages work at URL paths other than
 * /inventory. For example, a dealer might have some vehicles with down payment
 * values while most of their inventory does not have values for down payment,
 * and this class makes it easy to put those specific vehicles on a listings
 * page together.
 */
class Inventory_Presser_Additional_Listings_Pages {



	/**
	 * hooks
	 *
	 * Adds hooks
	 *
	 * @return void
	 */
	function hooks() {
		// Are additional listings pages enabled?
		$settings = INVP::settings();
		if ( empty( $settings['additional_listings_page'] ) || ! $settings['additional_listings_page'] ) {
			return;
		}
		add_filter( 'invp_rewrite_slugs', array( $this, 'add_rewrite_slugs' ) );
		add_filter( 'invp_rewrite_rules', array( $this, 'add_rewrite_rules' ) );
		add_action( 'pre_get_posts', array( $this, 'modify_query' ) );
		add_filter( 'post_type_archive_title', array( $this, 'change_page_title' ), 10, 2 );
	}

	/**
	 * change_page_title
	 *
	 * @param  mixed $post_type_name
	 * @param  mixed $post_type
	 * @return string
	 */
	public function change_page_title( $post_type_name, $post_type ) {
		if ( ! class_exists( 'INVP' ) || INVP::POST_TYPE !== $post_type ) {
			return $post_type_name;
		}

		global $wp;
		foreach ( self::additional_listings_pages_array() as $additional_listing ) {
			// is this rule valid?
			if ( ! self::is_valid_rule( $additional_listing ) ) {
				continue;
			}

			// our URL path will match the beginning of the rewrite rule.
			if ( substr( $wp->matched_rule, 0, strlen( $additional_listing['url_path'] ) ) === $additional_listing['url_path']
				&& ! empty( $additional_listing['title'] ) ) {
				return $additional_listing['title'];
			}
		}
		return $post_type_name;
	}

	/**
	 * add_rewrite_rules
	 *
	 * Adds rewrite rules so WordPress recognizes the places where users want
	 * the additional listing pages to live.
	 *
	 * @param  array $rules
	 * @return array
	 */
	function add_rewrite_rules( $rules ) {
		foreach ( self::additional_listings_pages_array() as $additional_listing ) {
			// is this rule valid?
			if ( ! self::is_valid_rule( $additional_listing ) ) {
				continue;
			}

			/**
			 * Create a base rule for this slug because this isn't a
			 * post type that will just work.
			 */
			$rules[ $additional_listing['url_path'] . '/?$' ] = 'index.php?post_type=' . INVP::POST_TYPE;
		}
		return $rules;
	}

	/**
	 * add_rewrite_slugs
	 *
	 * Adds rewrite slugs
	 *
	 * @param  array $slugs
	 * @return array
	 */
	function add_rewrite_slugs( $slugs ) {
		foreach ( self::additional_listings_pages_array() as $additional_listing ) {
			// is this rule valid?
			if ( ! self::is_valid_rule( $additional_listing ) ) {
				continue;
			}
			$slugs[] = $additional_listing['url_path'];
		}
		return $slugs;
	}

	/**
	 * additional_listings_pages_array
	 *
	 * Makes it easy to get the additional listings pages saved settings.
	 *
	 * @return array
	 */
	public static function additional_listings_pages_array() {
		// Are there additional listings pages configured?
		$settings = INVP::settings();
		if ( ! empty( $settings['additional_listings_page'] )
			&& $settings['additional_listings_page']
			&& ! empty( $settings['additional_listings_pages'] )
		) {
			return $settings['additional_listings_pages'];
		}
		return array();
	}

	/**
	 * get_current_matched_rule
	 *
	 * If the current request is for one of our additional listing pages, return
	 * that listings page rule so it's easy to extract or replicate the meta
	 * query.
	 *
	 * @param  WP_Query $query
	 * @return array|false
	 */
	public static function get_current_matched_rule( $query = null ) {
		if ( null === $query ) {
			global $wp_query;
			$query = $wp_query;
		}

		// Must be the main query on a vehicle archive request
		if ( ! $query->is_main_query() || ! is_post_type_archive( INVP::POST_TYPE ) ) {
			return false;
		}

		global $wp;
		foreach ( self::additional_listings_pages_array() as $additional_listing ) {
			// is this rule valid?
			if ( ! self::is_valid_rule( $additional_listing ) ) {
				continue;
			}

			// our URL path will match the beginning of the rewrite rule
			if ( $additional_listing['url_path'] == substr( $wp->matched_rule, 0, strlen( $additional_listing['url_path'] ) ) ) {
				return $additional_listing;
			}
		}
		return false;
	}

	/**
	 * get_query_meta_array
	 *
	 * Turns additional listing page rules into SQL query pieces.
	 *
	 * @param  array $rule
	 * @return array
	 */
	public static function get_query_meta_array( $rule ) {
		$new = null;
		switch ( $rule['operator'] ) {
			case 'does_not_exist':
				$new = array(
					'relation' => 'AND',
					array(
						'key'     => apply_filters( 'invp_prefix_meta_key', $rule['key'] ),
						'compare' => 'NOT EXISTS',
					),
				);
				break;

			case 'equal_to':
			case 'not_equal_to':
				$new = array(
					'relation' => 'AND',
					array(
						'key'     => apply_filters( 'invp_prefix_meta_key', $rule['key'] ),
						'compare' => ( 'equal_to' == $rule['operator'] ? '=' : '!=' ),
						'value'   => $rule['value'],
					),
				);
				break;

			case 'exists':
				$new = array(
					'relation' => 'AND',
					array(
						'key'     => apply_filters( 'invp_prefix_meta_key', $rule['key'] ),
						'compare' => 'EXISTS',
					),
					array(
						'key'     => apply_filters( 'invp_prefix_meta_key', $rule['key'] ),
						'value'   => array( '', 0 ),
						'compare' => 'NOT IN',
					),
				);
				break;

			case 'greater_than':
			case 'less_than':
				$new = array(
					'relation' => 'AND',
					array(
						'key'     => apply_filters( 'invp_prefix_meta_key', $rule['key'] ),
						'compare' => ( 'greater_than' == $rule['operator'] ? '>' : '<' ),
						'value'   => ( (float) $rule['value'] ),
						'type'    => 'NUMERIC',
					),
				);
				break;
		}
		return $new;
	}

	/**
	 * is_valid_rule
	 *
	 * True or false, a given additional listing page rule has settings that
	 * allow us to create the page. User might not provide the URL path, for
	 * example.
	 *
	 * @param  array $rule
	 * @return bool
	 */
	public static function is_valid_rule( $rule ) {
		if ( empty( $rule ) ) {
			return false;
		}

		if ( empty( $rule['url_path'] ) ) {
			return false;
		}

		/**
		 * If the operator is greater than or less than and there is no
		 * comparison value or a comparison value that is not a number, you
		 * might have a bad time.
		 */
		if ( ( empty( $rule['value'] ) || ! is_numeric( $rule['value'] ) )
			&& ( 'less_than' == $rule['operator'] || 'greater_than' == $rule['operator'] )
		) {
			return false;
		}

		/**
		 * If the key points to a value that is not a number and the operator
		 * is less than or greater than, you might have a bad time.
		 */
		if ( ! INVP::meta_value_is_number( apply_filters( 'invp_prefix_meta_key', $rule['key'] ) )
			&& ( 'less_than' == $rule['operator'] || 'greater_than' == $rule['operator'] )
		) {
			return false;
		}

		// you're probably good man
		return true;
	}

	/**
	 * modify_query
	 *
	 * Changes the query to satisfy the rule for this listings page
	 *
	 * @param  WP_Query $query
	 * @return WP_Query
	 */
	function modify_query( $query ) {
		$additional_listing = self::get_current_matched_rule( $query );
		if ( ! $additional_listing ) {
			return;
		}

		// found it, require the key & enforce the logic in the rule
		$old = $query->get( 'meta_query', array() );
		$new = self::get_query_meta_array( $additional_listing );

		if ( ! empty( $new ) ) {
			$query->set( 'meta_query', array_merge( $old, $new ) );
		}
		return $query;
	}
}
