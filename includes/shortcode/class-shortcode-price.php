<?php
/**
 * Shortcode Price
 *
 * @package inventory-presser
 */

defined( 'ABSPATH' ) || exit;

/**
 * Inventory_Presser_Shortcode_Price
 *
 * Creates a shortcode that outputs a vehicle identification number.
 */
class Inventory_Presser_Shortcode_Price {

	/**
	 * Adds two shortcodes
	 *
	 * @return void
	 */
	public function add() {
		add_shortcode( 'invp-price', array( $this, 'content' ) );
		add_shortcode( 'invp_price', array( $this, 'content' ) );
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
	 * @return string HTML that renders a vehicle price.
	 */
	public function content( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'          => null,
				'zero_string' => null,
			),
			$atts,
			'invp_price'
		);
		return invp_get_the_price( $atts['zero_string'], $atts['id'] );
	}
}
