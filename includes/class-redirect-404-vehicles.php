<?php
defined( 'ABSPATH' ) or exit;

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
	 * @package    Inventory_Presser
	 * @subpackage Inventory_Presser/includes
	 * @author     Corey Salzano <corey@friday.systems>
	 */
	class Redirect_404_Vehicles{
		
		/**
		 * extract_make
		 * 
		 * Finds the vehicle make from the request whether it was a search or a
		 * request for a specific vehicle that no longer exists.
		 *
		 * @param  mixed $wp_obj
		 * @return string A vehicle manufacturer name
		 */
		function extract_make( $wp_obj ) {

			if( ! $this->is_request_for_vehicle( $wp_obj ) ) {
				return '';
			}

			//if this is a search, the make might be in its own query variable
			if( isset( $wp_obj->query_vars['make'] ) ) {
				return $wp_obj->query_vars['make'];
			}

			//if this is a request for a single vehicle, parse the make out of the slug
			if( isset( $wp_obj->query_vars[INVP::POST_TYPE] ) ) {
				$slug_pieces = explode( '-', $wp_obj->query_vars[INVP::POST_TYPE] );
				if( 2 <= sizeof( $slug_pieces )
					//is the first piece a number of no more than 4 digits?
					&& 4 >= strlen( $slug_pieces[0] )
					&& is_numeric( $slug_pieces[0] ) )
				{
					return $slug_pieces[1];
				}
			}

			return '';
		}
		
		/**
		 * hooks
		 * 
		 * Adds hooks
		 *
		 * @return void
		 */
		function hooks() {
			add_action( 'wp', array( $this, 'maybe_redirect' ) );
		}
		
		/**
		 * is_request_for_vehicle
		 *
		 * @param  mixed $wp_obj
		 * @return bool
		 */
		function is_request_for_vehicle( $wp_obj ) {
			return isset( $wp_obj->query_vars ) && isset( $wp_obj->query_vars['post_type'] )
				&& INVP::POST_TYPE == $wp_obj->query_vars['post_type'];
		}
		
		/**
		 * maybe_redirect
		 * 
		 * Checks to see if the request is for a vehicle that no longer exists.
		 * If it was, it decides where to redirect the user and performs that
		 * redirect.
		 *
		 * @param  mixed $wp_obj
		 * @return void
		 */
		function maybe_redirect( $wp_obj ) {

			//is this a request for a vehicle?
			//is this a 404?
			if( ! $this->is_request_for_vehicle( $wp_obj ) || ! is_404() ) {
				return;
			}

			//base link to the inventory page
			$url = get_post_type_archive_link( INVP::POST_TYPE );

			//get the make out of the slug
			$make = $this->extract_make( $wp_obj );
			if( ! is_string( $make ) || '' == $make ) {
				//no make, redirect to vehicle archive
				wp_safe_redirect( $url, 302 );
				exit;
			}

			//are there cars in this make?
			$term = get_term_by( 'slug', $make, 'make' );
			if( ! $term || 0 == $term->count ) {
				//no cars in this make, go to vehicle archive
				wp_safe_redirect( $url, 302 );
				exit;
			}

			//redirect to this make's archive
			$url = get_term_link( $make, 'make' );
			if( is_wp_error( $url ) ) {
				//no link created for this make? go to vehicle archive
				wp_safe_redirect( $url, 302 );
				exit;
			}

			wp_safe_redirect( $url, 302 );
			exit;
		}
	}
}
