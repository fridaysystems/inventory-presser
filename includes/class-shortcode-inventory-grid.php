<?php
defined( 'ABSPATH' ) or exit;

/**
 * Inventory_Presser_Shortcode_Grid
 * 
 * Shortcode [invp-inventory-grid] piggybacks on a widget with the same features
 */
class Inventory_Presser_Shortcode_Grid
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
		add_shortcode( 'invp-inventory-grid', array( $this, 'content' ) );
		add_shortcode( 'invp_inventory_grid', array( $this, 'content' ) );
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
	 * @return string HTML that renders a vehicle photo grid
	 */
	function content( $atts ) {

		/**
		 * Shortcode attributes & default values. Some of these overlap to
		 * provide backwards compatibility with a time when this widget and the
		 * shortcode did not share any code. (Widget? What? See comment below.)
		 */
		$new_atts = shortcode_atts( array(
			/*  old attributes
			'per_page'     => 15,
			'captions'     => true,
			'button'       => true,
			'show_price'   => false,
			'size'         => 'one-third', */

 			'columns'       => 3, //replaces 'size'
 			'featured_only' => false,
 			'limit'         => 15, //replaces 'per_page'
 			'newest_first'  => false,
 			'show_button'   => true, //replaces 'button'
 			'show_captions' => false, //replaces 'captions'
 			'show_prices'   => false, //replaces 'show_price'
		), $atts );

		//Handle the old attribute names
		if( isset( $atts['per_page'] ) && ! isset( $atts['limit'] ) ) {
			$atts['limit'] = $atts['per_page'];
		}
		if( isset( $atts['captions'] ) && ! isset( $atts['show_captions'] ) ) {
			$new_atts['show_captions'] = $atts['captions'];
		}
		if( isset( $atts['button'] ) && ! isset( $atts['show_button'] ) ) {
			$new_atts['show_button'] = $atts['button'];
		}
		if( isset( $atts['show_price'] ) && ! isset( $atts['show_prices'] ) ) {
			$new_atts['show_prices'] = $atts['show_price'];
		}
		if( isset( $atts['size'] ) && ! isset( $atts['columns'] ) ) {
			switch( $atts['size'] ) {
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
