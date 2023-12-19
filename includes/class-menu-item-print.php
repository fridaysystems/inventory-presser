<?php
defined( 'ABSPATH' ) || exit;

/**
 * Inventory_Presser_Menu_Item_Print
 *
 * Enable a print command when a specific menu item is clicked.
 *
 * If a menu item of type "Custom Link" exists with "#" set as the target and
 * the CSS class "invp-print-button", tell the browser to print the page when a
 * visitor taps the button.
 *
 * @since      9.0.0
 * @package    inventory-presser
 * @subpackage inventory-presser/includes
 * @author     Corey Salzano <corey@friday.systems>
 */
class Inventory_Presser_Menu_Item_Print {


	/**
	 * Set up filter and action hooks
	 *
	 * @uses add_filter()
	 *
	 * @return void
	 */
	public function add_hooks() {
		add_filter( 'walker_nav_menu_start_el', array( $this, 'maybe_insert_print_button_javascript' ), 11, 4 );
	}

	/**
	 * maybe_insert_print_button_javascript
	 *
	 * Replace a link target of "#" with JavaScript that prints the page when a
	 * menu item has a CSS class of "invp-print-button".
	 *
	 * @param  string  $menu_item item HTML
	 * @param  WP_Post $item      post object for the menu item
	 * @param  int     $depth     depth of the item for padding
	 * @param  object  $args      nav menu arguments
	 * @return string item HTML
	 */
	function maybe_insert_print_button_javascript( $menu_item, $item, $depth, $args ) {

		// does it have the magic CSS class?
		if ( ! in_array( 'invp-print-button', $item->classes ) ) {
			return $menu_item;
		}

		// is it a custom link?
		if ( 'Custom Link' != $item->type_label ) {
			// no
			return $menu_item;
		}

		// is the link target a #?
		if ( false !== strpos( $menu_item ?? '', 'href="#"' ) ) {
			// yes, change it to JavaScript that prints the page
			$menu_item = str_replace( 'href="#"', 'href="javascript:window.print();"', $menu_item );
		}

		return $menu_item;
	}
}
