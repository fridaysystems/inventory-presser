<?php
/**
 * An object that redirects URLs based on a VIN to proper permalinks.
 *
 * Create a redirect that takes a URL like this:
 * 		http://localhost/vin/JM1NB354940406328/
 *
 * and redirects to the post that lives at some permalink like:
 * 		http://localhost/inventory/2004-mazda-speed-miata/
 *
 * @since      1.2.1
 * @package    Inventory_Presser
 * @subpackage Inventory_Presser/includes
 * @author     Corey Salzano <corey@fridaynet.com>, John Norton <norton@fridaynet.com>
 */

if ( ! class_exists( 'Vehicle_URLs_By_VIN' ) ) {
	class Vehicle_URLs_By_VIN {

		function __construct() {
			add_action( 'init', array( $this, 'add_vin_rewrite' ) );
			add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
			add_action( 'template_redirect', array( $this, 'redirect_vin_urls' ) );
		}

		function add_vin_rewrite() {
			//allow VINs as short as five digits because classic cars
			add_rewrite_rule( '^vin/([A-Z0-9]{5,17})/?$', 'index.php?vin=$matches[1]', 'top' );
		}

		// add 'vin' to WP recognized query vars
		function add_query_vars( $vars ) {
		    $vars[] = 'vin';
		    return $vars;
		}

		// if there's a vin query var, redirect based on that
		function redirect_vin_urls() {
			$vin = get_query_var('vin');
			if ( $vin ) {
				exit( wp_redirect( $this->find_vehicle_url( $vin ) ) );
			}
		}

		function find_vehicle_url( $vin ) {
			return $this->get_permalink_by_meta_value( apply_filters( 'invp_prefix_meta_key', 'vin' ), $vin );
		}

		function get_permalink_by_meta_value( $meta_key, $meta_value ) {
			$posts = get_posts( array(
				'post_type'   => Inventory_Presser_Plugin::CUSTOM_POST_TYPE,
				'post_status' => 'publish',
				'meta_key'    => $meta_key,
				'meta_value'  => $meta_value,
			) );
			if( 1 === sizeof( $posts ) ) {
				return get_permalink( $posts[0] );
			}
			return get_post_type_archive_link( Inventory_Presser_Plugin::CUSTOM_POST_TYPE );
		}
	}
}
