<?php
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Redirect_404_Vehicles' ) ) {

	/**
	 * Redirect_404_Vehicles
	 *
	 * A class that detects when requests are made for vehicles that are no
	 * longer on the site and redirects the user to that vehicle's make archive.
	 * So, a request for a sold or removed Subaru will redirect the user to the
	 * archive page containing all Subarus.
	 *
	 * @since      3.2.0
	 * @package inventory-presser
	 * @subpackage inventory-presser/includes
	 * @author     Corey Salzano <corey@friday.systems>
	 */
	class Redirect_404_Vehicles {


		/**
		 * Finds the vehicle make from the request whether it was a search or a
		 * request for a specific vehicle that no longer exists.
		 *
		 * @param  WP $wp_obj Current WordPress environment instance (passed by reference).
		 * @return string A vehicle manufacturer name
		 */
		protected function extract_make( $wp_obj ) {

			if ( ! $this->is_request_for_vehicle( $wp_obj ) ) {
				return '';
			}

			// if this is a search, the make might be in its own query variable.
			// $wp_obj is not a WP_Query and does not have a get() method.
			if ( isset( $wp_obj->query_vars['make'] ) && '' !== $wp_obj->query_vars['make'] ) {
				return $wp_obj->query_vars['make'];
			}

			// if this is a request for a single vehicle, parse the make out of the slug.
			if ( isset( $wp_obj->query_vars[ INVP::POST_TYPE ] )
				&& '' !== $wp_obj->query_vars[ INVP::POST_TYPE ] ) {
				$slug_pieces = explode( '-', $wp_obj->query_vars[ INVP::POST_TYPE ] );
				if ( 2 <= count( $slug_pieces )
					// is the first piece a number of no more than 4 digits?
					// We are looking for a slug like 2000-acura-integra.
					&& 4 >= strlen( $slug_pieces[0] )
					&& is_numeric( $slug_pieces[0] )
				) {
					return $slug_pieces[1];
				}
			}

			return '';
		}

		/**
		 * Adds hooks
		 *
		 * @return void
		 */
		public function add_hooks() {
			add_action( 'wp', array( $this, 'maybe_redirect' ) );
		}

		/**
		 * Is the current request for a vehicle?
		 *
		 * @param  WP $wp_obj Current WordPress environment instance (passed by reference).
		 * @return bool
		 */
		protected function is_request_for_vehicle( $wp_obj ) {
			// $wp_obj is not a WP_Query and does not have a get() method.
			return isset( $wp_obj->query_vars )
				&& isset( $wp_obj->query_vars['post_type'] )
				&& INVP::POST_TYPE === $wp_obj->query_vars['post_type'];
		}

		/**
		 * Checks to see if the request is for a vehicle that no longer exists.
		 * If it was, it decides where to redirect the user and performs that
		 * redirect.
		 *
		 * @param  WP $wp_obj Current WordPress environment instance (passed by reference).
		 * @return void
		 */
		public function maybe_redirect( $wp_obj ) {

			if ( is_admin() ) {
				return;
			}

			// is this a 404?
			// is this a request for a vehicle?
			if ( ! is_404() || ! $this->is_request_for_vehicle( $wp_obj ) ) {
				return;
			}

			// base link to the inventory page.
			$url = get_post_type_archive_link( INVP::POST_TYPE );

			// get the make out of the slug.
			$make = $this->extract_make( $wp_obj );
			if ( ! is_string( $make ) || '' == $make ) {
				// no make, redirect to vehicle archive.
				wp_safe_redirect( $url, 302 );
				exit;
			}

			// are there cars in this make?
			$term = get_term_by( 'slug', $make, 'make' );
			if ( ! $term || 0 === $term->count ) {
				// no cars in this make, go to vehicle archive.
				wp_safe_redirect( $url, 302 );
				exit;
			}

			// redirect to this make's archive.
			$url = get_term_link( $make, 'make' );
			if ( is_wp_error( $url ) ) {
				// no link created for this make? go to vehicle archive.
				wp_safe_redirect( $url, 302 );
				exit;
			}

			wp_safe_redirect( $url, 302 );
			exit;
		}
	}
}
