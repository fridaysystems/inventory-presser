<?php
/**
 * Range Filters
 *
 * Adds min_price, max_price, min_odometer, and max_odometer query parameters.
 * Modifies archive queries to adhere to the filters.
 *
 * @package taxonomy-filters-widget
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Inventory_Presser_Range_Filters' ) ) {
	/**
	 * Inventory_Presser_Range_Filters
	 */
	class Inventory_Presser_Range_Filters {

		/**
		 * Adds hooks that power the feature.
		 *
		 * @return void
		 */
		public static function add_hooks() {
			if ( has_action( 'pre_get_posts', array( __CLASS__, 'modify_query_for_price_and_odometer_ranges' ) ) ) {
				return;
			}
			// Modify the query to implement the price and odometer ranges.
			add_action( 'pre_get_posts', array( __CLASS__, 'modify_query_for_price_and_odometer_ranges' ), 99, 1 );
		}

		/**
		 * Change a query's meta_query value if the meta_query does not already
		 * contain the provided key.
		 *
		 * @param  mixed $meta_query
		 * @param  mixed $key
		 * @param  mixed $value
		 * @param  mixed $compare
		 * @param  mixed $type
		 * @return array
		 */
		protected static function add_meta_query( $meta_query, $key, $value, $compare, $type ) {
			// Make sure there is not already $key item in the meta_query.
			if ( self::meta_query_contains_key( $meta_query, $key ) ) {
				return $meta_query;
			}

			$meta_query[] = array(
				'key'     => $key,
				'value'   => $value,
				'compare' => $compare,
				'type'    => $type,
			);
			return $meta_query;
		}

		/**
		 * Does a WP_Query's meta_query contain a specific key?
		 *
		 * @param  mixed $meta_query
		 * @param  mixed $key
		 * @return bool|null
		 */
		protected static function meta_query_contains_key( $meta_query, $key ) {
			if ( is_array( $meta_query ) ) {
				if ( isset( $meta_query['key'] ) ) {
					return $meta_query['key'] === $key;
				}

				foreach ( $meta_query as $another ) {
					return self::meta_query_contains_key( $another, $key );
				}
			}
			return null;
		}

		/**
		 * Modifies the queries with the vehicle post type to include price and
		 * odometer ranges, if they're also provided.
		 *
		 * @param  mixed $query
		 * @return void
		 */
		public static function modify_query_for_price_and_odometer_ranges( $query ) {
			// Is this query for vehicles?
			if ( INVP::POST_TYPE !== $query->get( 'post_type', '' ) ) {
				// No.
				return;
			}

			if ( is_admin() ) {
				return;
			}

			// Only filter the main query unless an override filter.
			if ( ! $query->is_main_query() && apply_filters( 'invp_range_filters_main_query', true ) ) {
				return;
			}

			if ( ! isset( $_GET['min_price'] )
				&& ! isset( $_GET['max_price'] )
				&& ! isset( $_GET['min_odometer'] )
				&& ! isset( $_GET['max_odometer'] )
			) {
				return;
			}

			// Get original meta query.
			$meta_query = $query->get( 'meta_query', array() );
			if ( ! is_array( $meta_query ) ) {
				$meta_query = array();
			}

			$meta_query['relation'] = 'AND';

			if ( isset( $_GET['max_price'] ) ) {
				$meta_query = self::add_meta_query(
					$meta_query,
					apply_filters( 'invp_prefix_meta_key', 'price' ),
					(int) $_GET['max_price'],
					'<=',
					'numeric'
				);
			}

			if ( isset( $_GET['min_price'] ) ) {
				$meta_query = self::add_meta_query(
					$meta_query,
					apply_filters( 'invp_prefix_meta_key', 'price' ),
					(int) $_GET['min_price'],
					'>=',
					'numeric'
				);
			}

			if ( isset( $_GET['min_odometer'] ) ) {
				$meta_query = self::add_meta_query(
					$meta_query,
					apply_filters( 'invp_prefix_meta_key', 'odometer' ),
					(int) $_GET['min_odometer'],
					'>=',
					'numeric'
				);
			}

			if ( isset( $_GET['max_odometer'] ) ) {
				$meta_query = self::add_meta_query(
					$meta_query,
					apply_filters( 'invp_prefix_meta_key', 'odometer' ),
					(int) $_GET['max_odometer'],
					'<=',
					'numeric'
				);
			}

			$query->set( 'meta_query', $meta_query );
		}
	}
	Inventory_Presser_Range_Filters::add_hooks();
}
