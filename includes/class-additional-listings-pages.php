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
	 * Adds hooks
	 *
	 * @return void
	 */
	public function add_hooks() {
		add_filter( 'invp_rewrite_slugs', array( $this, 'add_rewrite_slugs' ) );
		add_filter( 'invp_rewrite_rules', array( $this, 'add_rewrite_rules' ) );
		add_action( 'pre_get_posts', array( $this, 'modify_query' ) );
		add_filter( 'post_type_archive_title', array( $this, 'change_page_title' ), 10, 2 );
	}

	/**
	 * Callback on post_type_archive_title. Changes page titles for listings
	 * pages.
	 *
	 * @param  mixed $post_type_name Post type 'name' label.
	 * @param  mixed $post_type Post type.
	 * @return string
	 */
	public function change_page_title( $post_type_name, $post_type ) {
		if ( ! class_exists( 'INVP' ) || INVP::POST_TYPE !== $post_type ) {
			return $post_type_name;
		}

		global $wp;
		foreach ( self::additional_listings_pages_array() as $additional_listing ) {
			// is this rule valid and active?
			if ( ! self::is_valid_rule( $additional_listing )
				|| ! self::is_active_rule( $additional_listing ) ) {
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
	 * Adds rewrite rules so WordPress recognizes the places where users want
	 * the additional listing pages to live.
	 *
	 * @param  array $rules
	 * @return array
	 */
	public function add_rewrite_rules( $rules ) {
		foreach ( self::additional_listings_pages_array() as $additional_listing ) {
			// is this rule valid and active?
			if ( ! self::is_valid_rule( $additional_listing )
				|| ! self::is_active_rule( $additional_listing ) ) {
				continue;
			}

			// Base rule for this slug, this is not a post type.
			$rules[ $additional_listing['url_path'] . '/?$' ] = 'index.php?post_type=' . INVP::POST_TYPE;
			// Another rule to enable paging.
			$rules[ $additional_listing['url_path'] . '/page/([0-9]{1,})/?$' ] = 'index.php?post_type=' . INVP::POST_TYPE . '&paged=$matches[1]';

		}
		return $rules;
	}

	/**
	 * Adds rewrite slugs
	 *
	 * @param  array $slugs
	 * @return array
	 */
	public function add_rewrite_slugs( $slugs ) {
		foreach ( self::additional_listings_pages_array() as $additional_listing ) {
			// is this rule valid and active?
			if ( ! self::is_valid_rule( $additional_listing )
				|| ! self::is_active_rule( $additional_listing ) ) {
				continue;
			}
			$slugs[] = $additional_listing['url_path'];
		}
		return $slugs;
	}

	/**
	 * Makes it easy to get the additional listings pages saved settings.
	 *
	 * @return array
	 */
	public static function additional_listings_pages_array() {
		// Are there additional listings pages configured?
		$saved = INVP::settings()['additional_listings_pages'] ?? array( array() );
		if ( null === $saved || array() === $saved ) {
			return array( array() );
		}
		return $saved;
	}

	/**
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

		// Must be the main query on a vehicle archive request.
		if ( ! $query->is_main_query() || ! is_post_type_archive( INVP::POST_TYPE ) ) {
			return false;
		}

		global $wp;
		foreach ( self::additional_listings_pages_array() as $additional_listing ) {
			// is this rule valid and active?
			if ( ! self::is_valid_rule( $additional_listing )
				|| ! self::is_active_rule( $additional_listing ) ) {
				continue;
			}

			// our URL path will match the beginning of the rewrite rule.
			if ( substr( $wp->matched_rule, 0, strlen( $additional_listing['url_path'] ) ) === $additional_listing['url_path'] ) {
				return $additional_listing;
			}
		}
		return false;
	}

	/**
	 * Turns additional listing page rules into SQL query pieces.
	 *
	 * @param  array $rule
	 * @return array
	 */
	public static function get_query_meta_array( $rule ) {
		// There may be no filter in the rule.
		if ( '' === $rule['key'] ) {
			return array();
		}
		$new = null;
		switch ( $rule['operator'] ) {
			case 'contains':
				$new = array(
					'relation' => 'AND',
					array(
						'key'     => apply_filters( 'invp_prefix_meta_key', $rule['key'] ),
						'compare' => 'LIKE',
						'value'   => $rule['value'],
					),
				);
				break;

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
						'compare' => ( 'equal_to' === $rule['operator'] ? '=' : '!=' ),
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
						'compare' => ( 'greater_than' === $rule['operator'] ? '>' : '<' ),
						'value'   => ( (float) $rule['value'] ),
						'type'    => 'NUMERIC',
					),
				);
				break;
		}
		return $new;
	}

	/**
	 * Returns true if the additional listing page is active or too old to have
	 * and active switch.
	 *
	 * @param  array $rule
	 * @return bool
	 */
	public static function is_active_rule( $rule ) {
		return ! isset( $rule['active'] ) || $rule['active'];
	}

	/**
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

		// We allow no filter at all.
		if ( '' === $rule['key'] ) {
			return true;
		}

		/**
		 * If the operator is greater than or less than and there is no
		 * comparison value or a comparison value that is not a number, you
		 * might have a bad time.
		 */
		if ( ( empty( $rule['value'] ) || ! is_numeric( $rule['value'] ) )
			&& ( 'less_than' === $rule['operator'] || 'greater_than' === $rule['operator'] )
		) {
			return false;
		}

		/**
		 * If the key points to a value that is not a number and the operator
		 * is less than or greater than, you might have a bad time.
		 */
		if ( ! INVP::meta_value_is_number( apply_filters( 'invp_prefix_meta_key', $rule['key'] ) )
			&& ( 'less_than' === $rule['operator'] || 'greater_than' === $rule['operator'] )
		) {
			return false;
		}

		return true;
	}

	/**
	 * Changes the query to satisfy the rule for this listings page
	 *
	 * @param  WP_Query $query
	 * @return WP_Query
	 */
	public function modify_query( $query ) {
		$additional_listing = self::get_current_matched_rule( $query );
		if ( ! $additional_listing ) {
			return;
		}

		// found it, require the key & enforce the logic in the rule.
		$old = $query->get( 'meta_query', array() );
		$new = self::get_query_meta_array( $additional_listing );

		if ( ! empty( $new ) ) {
			$query->set( 'meta_query', array_merge( $old, $new ) );
		}
		return $query;
	}
}
