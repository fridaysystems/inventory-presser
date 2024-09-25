<?php
/**
 * Vehicle_URLs_By_VIN
 *
 * This class redirects URLs based on a VIN to proper permalinks
 *
 * Create a redirect that takes a URL like this:
 * https://demo.inventorypresser.com/vin/JM1NB354940406328/ and redirects to the post that
 * lives at some permalink like
 * https://demo.inventorypresser.com/inventory/2004-mazda-speed-miata/
 *
 * @since      1.2.1
 * @package inventory-presser
 * @subpackage inventory-presser/includes
 * @author     Corey Salzano <corey@friday.systems>, John Norton <norton@fridaynet.com>
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Vehicle_URLs_By_VIN' ) ) {

	/**
	 * Vehicle_URLs_By_VIN
	 */
	class Vehicle_URLs_By_VIN {

		/**
		 * Adds hooks that power the URL redirects.
		 *
		 * @return void
		 */
		public function add_hooks() {
			add_filter( 'invp_rewrite_rules', array( $this, 'add_vin_rewrite_rule' ) );
			add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
			add_action( 'template_redirect', array( $this, 'redirect_vin_urls' ) );
		}

		/**
		 * Adds a rewrite rule to redirect URLs like https://demo.inventorypresser.com/vin/JM1NB354940406328/
		 *
		 * @param  array $rules An array of rewrite rules.
		 * @return array
		 */
		public function add_vin_rewrite_rule( $rules ) {
			// Allow VINs as short as five digits because classic cars.
			$rules['^vin/([A-Z0-9]{5,17})/?$'] = 'index.php?vin=$matches[1]';
			return $rules;
		}

		/**
		 * Adds 'vin' to the list of recognized query variables.
		 *
		 * @param  array $vars An array of query variables.
		 * @return array The expanded array of query variables
		 */
		public function add_query_vars( $vars ) {
			$vars[] = 'vin';
			return $vars;
		}

		/**
		 * Redirects the request to a vehicle permalink if a 'vin' query
		 * variable is present.
		 *
		 * @return void
		 */
		public function redirect_vin_urls() {
			$vin = get_query_var( 'vin' );
			if ( $vin ) {
				wp_safe_redirect( $this->find_vehicle_url( $vin ) );
				exit;
			}
		}

		/**
		 * Takes a VIN and finds its permalink. If the vehicle is not in
		 * inventory, the vehicle archive URL is returned.
		 *
		 * @param  string $vin A vehicle identification number.
		 * @return string A vehicle permalink or the link to the vehicle archive.
		 */
		protected function find_vehicle_url( $vin ) {
			$posts = get_posts(
				array(
					'post_type'   => INVP::POST_TYPE,
					'post_status' => 'publish',
					'meta_key'    => apply_filters( 'invp_prefix_meta_key', 'vin' ),
					'meta_value'  => $vin,
				)
			);
			if ( 1 === count( $posts ) ) {
				return get_permalink( $posts[0] );
			}
			return get_post_type_archive_link( INVP::POST_TYPE );
		}
	}
}
