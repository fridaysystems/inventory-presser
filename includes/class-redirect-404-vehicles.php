<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * A class that detects when requests are made for vehicles that are no longer
 * on the site and redirects the user to that vehicle's make archive. So, a
 * request for a sold or removed Subaru will redirect the user to the archive
 * page containing all Subarus.
 *
 * @since      3.2.0
 * @package    Inventory_Presser
 * @subpackage Inventory_Presser/includes
 * @author     Corey Salzano <corey@friday.systems>
 */

if ( ! class_exists( 'Redirect_404_Vehicles' ) ) {
	class Redirect_404_Vehicles{

		function extract_make( $wp_obj ) {

			if( ! $this->is_request_for_vehicle( $wp_obj ) ) {
				return '';
			}

			//example slug '2016-chevrolet-malibu'
			//so we assume all vehicles have year and make
			$slug_pieces = explode( '-', $wp_obj->query_vars[Inventory_Presser_Plugin::CUSTOM_POST_TYPE] );
			if( 2 <= sizeof( $slug_pieces )
				//is the first piece a number of no more than 4 digits?
				&& 4 >= strlen( $slug_pieces[0] )
				&& is_numeric( $slug_pieces[0] ) )
			{
				return $slug_pieces[1];
			}

			return '';
		}

		function hooks() {
			add_action( 'wp', array( $this, 'maybe_redirect' ) );
		}

		function is_request_for_vehicle( $wp_obj ) {
			return isset( $wp_obj->query_vars ) && isset( $wp_obj->query_vars['post_type'] )
				&& Inventory_Presser_Plugin::CUSTOM_POST_TYPE == $wp_obj->query_vars['post_type'];
		}

		function maybe_redirect( $wp_obj ) {

			//is this a request for a vehicle?
			//is this a 404?
			if( ! $this->is_request_for_vehicle( $wp_obj ) || ! is_404() ) {
				return;
			}

			//base link to the inventory page
			$url = get_post_type_archive_link( Inventory_Presser_Plugin::CUSTOM_POST_TYPE );

			//get the make out of the slug
			$make = $this->extract_make( $wp_obj );
			if( '' == $make ) {
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
