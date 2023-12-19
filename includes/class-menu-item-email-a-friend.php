<?php
defined( 'ABSPATH' ) || exit;

/**
 * Inventory_Presser_Email_A_Friend
 *
 * Implement a mailto: link in a specific menu item in a specific menu that
 * often exists in Inventory Presser websites.
 *
 * If a menu item of type "Custom Link" exists with "Email a Friend" set as the
 * "Navigation Label", this class will change the URL to a mailto: link
 * containing vehicle information so the vehicle can be sent to a friend via
 * email.
 *
 * @since      3.8.0
 * @package    inventory-presser
 * @subpackage inventory-presser/includes
 * @author     Corey Salzano <corey@friday.systems>
 */
class Inventory_Presser_Email_A_Friend {


	/**
	 * hooks
	 *
	 * Set up filter and action hooks
	 *
	 * @uses add_filter()
	 *
	 * @return void
	 */
	public function add_hooks() {
		add_filter( 'walker_nav_menu_start_el', array( $this, 'maybe_change_link' ), 11, 4 );
	}

	/**
	 * maybe_change_link
	 *
	 * Fills out a mailto: link with vehicle information
	 *
	 * Target a specific button in a specific menu and modify the URL.
	 *
	 * @uses get_bloginfo(), get_permalink()
	 *
	 * @param  string  $menu_item item HTML
	 * @param  WP_Post $item      post object for the menu item
	 * @param  int     $depth     depth of the item for padding
	 * @param  object  $args      nav menu arguments
	 * @return string
	 */
	function maybe_change_link( $menu_item, $item, $depth, $args ) {
		// looking for $menu_item = <a href="mailto:">Email a Friend</a>

		// is it the vehicle details menu?
		if ( empty( $args ) || empty( $args->menu ) || empty( $args->menu->name ) || __( 'Vehicle Details Buttons', 'inventory-presser' ) != $args->menu->name ) {
			// no
			return $menu_item;
		}

		// is it a custom link labeled email a friend?
		if ( 'Custom Link' != $item->type_label || strtolower( __( 'Email a Friend', 'inventory-presser' ) ) != strtolower( $item->title ) ) {
			// no
			return $menu_item;
		}

		// change the link to contain vehicle information
		global $post;
		$menu_item = str_replace(
			'mailto:',
			$this->url( $post ) . '" target="_blank',
			$menu_item
		);

		return $menu_item;
	}

	/**
	 * Creates a mailto: url to draft an email containing vehicle information.
	 *
	 * @uses get_bloginfo(), get_permalink()
	 *
	 * @param  WP_Post $post A vehicle post object.
	 * @return string
	 */
	public function url( $post ) {

		if ( ! isset( $post->post_title ) ) {
			return '';
		}

		$subject = 'Check out this ' . $post->post_title;
		$body    = sprintf(
			'Please look at this %s for sale at %s:

%s',
			$post->post_title,
			html_entity_decode( get_bloginfo(), ENT_QUOTES ), // WordPress encodes quotes in site names
			get_permalink( $post )
		);
		return sprintf(
			'mailto:?subject=%s&body=%s',
			rawurlencode( htmlspecialchars_decode( $subject ) ),
			rawurlencode( htmlspecialchars_decode( $body ) )
		);
	}
}
