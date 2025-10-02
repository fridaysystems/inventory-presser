<?php
/**
 * Shortcode Vin
 *
 * @package inventory-presser
 */

defined( 'ABSPATH' ) || exit;

/**
 * Inventory_Presser_Shortcode_Vin
 *
 * Creates a shortcode that outputs a vehicle identification number.
 */
class Inventory_Presser_Shortcode_Vin {

	/**
	 * Adds two shortcodes
	 *
	 * @return void
	 */
	public function add() {
		add_shortcode( 'invp-vin', array( $this, 'content' ) );
		add_shortcode( 'invp_vin', array( $this, 'content' ) );
	}

	/**
	 * Adds hooks that power the shortcode
	 *
	 * @return void
	 */
	public function add_hooks() {
		add_action( 'init', array( $this, 'add' ) );
	}

	/**
	 * Creates the HTML content of the shortcode
	 *
	 * @param  array $atts Shortcode attributes.
	 * @return string HTML that renders a vehicle VIN
	 */
	public function content( $atts ) {
		$atts = shortcode_atts(
			array(
				'id' => null,
			),
			$atts,
			'invp_vin'
		);
		return invp_get_the_vin( $atts['id'] );
	}
}
