<?php
defined( 'ABSPATH' ) || exit;

/**
 * Inventory_Presser_Shortcode_Vin
 *
 * Creates a shortcode that outputs a vehicle identification number.
 */
class Inventory_Presser_Shortcode_Vin {

	/**
	 * add
	 *
	 * Adds two shortcodes
	 *
	 * @return void
	 */
	function add() {
		add_shortcode( 'invp-vin', 'invp_get_the_vin' );
		add_shortcode( 'invp_vin', 'invp_get_the_vin' );
	}

	/**
	 * hooks
	 *
	 * Adds hooks that power the shortcode
	 *
	 * @return void
	 */
	function hooks() {
		add_action( 'init', array( $this, 'add' ) );
	}
}
