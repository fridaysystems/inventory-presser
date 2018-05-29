<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * A class that fetches AutoCheck reports using an API hosted by Friday
 * Systems.
 *
 * @since      3.3.0
 * @package    Inventory_Presser
 * @subpackage Inventory_Presser/includes
 * @author     Corey Salzano <corey@friday.systems>
 */

if ( ! class_exists( 'Inventory_Vehicle_Reports' ) ) {
	class Inventory_Vehicle_Reports {

		function get_autocheck_report() {

			if( ! isset( $_GET['vin'] ) ) {
				exit();
			}

			$_dealer_settings = get_option('_dealer_settings');

			if( ! isset( $_dealer_settings['autocheck_id'] ) || '' == $_dealer_settings['autocheck_id'] ) {
				echo 'Autocheck ID not set';
				exit();
			}


			$querystring = http_build_query( array(
				'DealerID' => $_dealer_settings['autocheck_id'],
				'VIN' => $_GET['vin'],
			) );
			echo file_get_contents( 'http://api.friday.systems/api/AutoCheck?' . $querystring );
			exit();
		}

		function hooks() {
			add_action( 'wp_ajax_autocheck', array( $this, 'get_autocheck_report' ) );
			add_action( 'wp_ajax_nopriv_autocheck', array( $this, 'get_autocheck_report' ) );
		}

	}
}
