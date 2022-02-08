<?php
defined( 'ABSPATH' ) or exit;

/**
 * Inventory_Presser_Shortcode_Vin
 * 
 * Creates a shortcode that outputs a vehicle identification number.
 */
class Inventory_Presser_Shortcode_Vin
{
	/**
	 * add
	 * 
	 * Adds two shortcodes
	 *
	 * @return void
	 */
	function add()
	{
		add_shortcode( 'invp-vin', array( $this, 'content') );
		add_shortcode( 'invp_vin', array( $this, 'content') );
	}

	/**
	 * hooks
	 * 
	 * Adds hooks that power the shortcode
	 *
	 * @return void
	 */
	function hooks()
	{
		add_action( 'init', array( $this, 'add' ) );
	}

	/**
	 * content
	 * 
	 * Creates the HTML content of the shortcode
	 *
	 * @param  array $atts
	 * @return string The return value of the template tag invp_get_the_vin()
	 */
	function content( $atts ) {

		return invp_get_the_vin();
	}
}
