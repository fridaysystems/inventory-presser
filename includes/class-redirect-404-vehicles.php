<?php
/**
 * A class that detects when requests are made for vehicles that are no longer
 * on the site and redirects the user to that vehicle's make archive. So, a
 * request for a sold or removed Subaru will redirect the user to the archive
 * page containing all Subarus.
 *
 * @since      3.2.0
 * @package    Inventory_Presser
 * @subpackage Inventory_Presser/includes
 * @author     Corey Salzano <corey.salzano@gmail.com>
 */

if ( ! class_exists( 'Redirect_404_Vehicles' ) ) {
	class Redirect_404_Vehicles{

		const CUSTOM_POST_TYPE = 'inventory_vehicle';

		function extract_make( $wp_obj ) {
			//example slug '2016-chevrolet-malibu'
			//so we assume all vehicles have year and make
			if( isset( $wp_obj->query_vars[self::CUSTOM_POST_TYPE] ) ) {
				$slug_pieces = explode( '-', $wp_obj->query_vars[self::CUSTOM_POST_TYPE] );
				if( 2 <= sizeof( $slug_pieces )
					&& 4 == strlen( $slug_pieces[0] ) //is the first piece a year?
					&& is_numeric( $slug_pieces[0] ) ) {

					return $slug_pieces[1];
				}
			}
			return '';
		}

		function hooks() {
			add_action( 'wp', array( $this, 'maybe_redirect' ) );
		}

		function is_request_for_vehicle( $wp_obj ) {
			return isset( $wp_obj->query_vars ) && isset( $wp_obj->query_vars['post_type'] )
				&& self::CUSTOM_POST_TYPE == $wp_obj->query_vars['post_type'];
		}

		function maybe_redirect( $wp_obj ) {

			//is this a request for a vehicle?
			//is this a 404?
			if( ! $this->is_request_for_vehicle( $wp_obj ) || ! is_404() ) {
				return;
			}

			//base link to the inventory page
			$url = get_post_type_archive_link( self::CUSTOM_POST_TYPE );

			//get the make out of the slug
			$make = $this->extract_make( $wp_obj );
			if( '' == $make ) {
				wp_safe_redirect( $url, 302 );
				exit;
			}

			//are there cars in this make?
			$term = get_term_by( 'slug', $make, 'make' );
			if( ! $term || 0 == $term->count ) {
				//no? just go to inventory page
				wp_safe_redirect( $url, 302 );
				exit;
			}

			//redirect to this make's archive
			$url = get_term_link( $make, 'make' );
			if( is_wp_error( $url ) ) {
				wp_safe_redirect( $url, 302 );
				exit;
			}

			wp_safe_redirect( $url, 302 );
			exit;
		}
	}
}
