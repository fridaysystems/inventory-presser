<?php
defined( 'ABSPATH' ) || exit;

/**
 * Inventory_Presser_Shortcode_Grid
 *
 * A shortcode that piggybacks on the Inventory Grid widget by delivering the
 * same features but as a shortcode.
 */
class Inventory_Presser_Shortcode_Grid {

	/**
	 * Adds two shortcodes
	 *
	 * @return void
	 */
	public function add() {
		add_shortcode( 'invp-inventory-grid', array( $this, 'content' ) );
		add_shortcode( 'invp_inventory_grid', array( $this, 'content' ) );
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
	 * @return string HTML that renders a vehicle photo grid
	 */
	public function content( $atts ) {

		/**
		 * Shortcode attributes & default values. Some of these overlap to
		 * provide backwards compatibility with a time when this widget and the
		 * shortcode did not share any code. (Widget? What? See comment below.)
		 */
		$new_atts = shortcode_atts(
			array(

				/*
					Old attributes
					----------
					'per_page'   15
					'captions'   true
					'button'     true
					'show_price' false
					'size'       'one-third'
				*/

				'columns'                 => 3, // replaces 'size'.
				'featured_only'           => false,
				'limit'                   => 15, // replaces 'per_page'.
				'make'                    => '',
				'model'                   => '',
				'newest_first'            => false,
				'priced_first'            => false,
				'show_button'             => true, // replaces 'button'.
				'show_captions'           => false, // replaces 'captions'.
				'show_odometers'          => false,
				'show_prices'             => false, // replaces 'show_price'.
				'suppress_call_for_price' => false, // When the price setting is {$Price}, this prevents "Call for price" in the grid.
			),
			$atts
		);

		// Handle the old attribute names.
		if ( isset( $atts['per_page'] ) && ! isset( $atts['limit'] ) ) {
			$atts['limit'] = $atts['per_page'];
		}
		if ( isset( $atts['captions'] ) && ! isset( $atts['show_captions'] ) ) {
			$new_atts['show_captions'] = $atts['captions'];
		}
		if ( isset( $atts['button'] ) && ! isset( $atts['show_button'] ) ) {
			$new_atts['show_button'] = $atts['button'];
		}
		if ( isset( $atts['show_price'] ) && ! isset( $atts['show_prices'] ) ) {
			$new_atts['show_prices'] = $atts['show_price'];
		}
		if ( isset( $atts['size'] ) && ! isset( $atts['columns'] ) ) {
			switch ( $atts['size'] ) {
				case 'one-third':
					$new_atts['columns'] = 3;
					break;
				case 'one-fourth':
					$new_atts['columns'] = 4;
					break;
				case 'one-fifth':
					$new_atts['columns'] = 5;
					break;
			}
		}

		// Parse boolean values to make life easy on users.
		$new_atts['featured_only']           = filter_var( $new_atts['featured_only'], FILTER_VALIDATE_BOOLEAN );
		$new_atts['newest_first']            = filter_var( $new_atts['newest_first'], FILTER_VALIDATE_BOOLEAN );
		$new_atts['priced_first']            = filter_var( $new_atts['priced_first'], FILTER_VALIDATE_BOOLEAN );
		$new_atts['show_button']             = filter_var( $new_atts['show_button'], FILTER_VALIDATE_BOOLEAN );
		$new_atts['show_captions']           = filter_var( $new_atts['show_captions'], FILTER_VALIDATE_BOOLEAN );
		$new_atts['show_odometers']          = filter_var( $new_atts['show_odometers'], FILTER_VALIDATE_BOOLEAN );
		$new_atts['show_prices']             = filter_var( $new_atts['show_prices'], FILTER_VALIDATE_BOOLEAN );
		$new_atts['suppress_call_for_price'] = filter_var( $new_atts['suppress_call_for_price'], FILTER_VALIDATE_BOOLEAN );

		/**
		 * We actually use the Grid widget to generate the output of this
		 * shortcode since version 10.1.0. The output was very similiar but
		 * the arguments didn't overlap completely before that, so they were
		 * merged to make life easy.
		 */
		$widget = new Inventory_Presser_Grid();
		return $widget->content( $new_atts );
	}
}
