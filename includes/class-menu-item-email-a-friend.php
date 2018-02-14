<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Implement a mailto: link in a specific menu item in a specific menu that
 * often exists in Inventory Presser websites.
 *
 * If a menu item of type "Custom Link" exists with "Email a Friend" set as the
 * "Navigation Label", this class will change the URL to a mailto: link
 * containing vehicle information so the vehicle can be sent to a friend via
 * email.
 *
 *
 * @since      3.8.0
 * @package    Inventory_Presser
 * @subpackage Inventory_Presser/includes
 * @author     Corey Salzano <corey@friday.systems>
 */
class Inventory_Presser_Email_A_Friend{

	/**
	 * Set up filter and action hooks
	 *
	 * @uses add_action(), load_plugin_textdomain(), add_filter()
	 *
	 * @return void
	 */
	function hooks() {
		//Allow translations
		add_action( 'plugins_loaded', function() {
			load_plugin_textdomain( 'inventory-presser-email-button', false, __DIR__ );
		} );

		add_filter( 'walker_nav_menu_start_el', array( $this, 'maybe_change_link' ), 11, 4 );
	}

	/**
	 * Fills out a mailto: link with vehicle information
	 *
	 * Target a specific button in a specific menu and modify the URL.
	 *
	 * @uses get_bloginfo(), get_permalink()
	 *
	 * @param string $menu_item item HTML
	 * @param object $item post object for the menu item
	 * @param int $depth depth of the item for padding
	 * @param object $args nav menu arguments
	 * @return string
	 */
	function maybe_change_link( $menu_item, $item, $depth, $args ) {
		//looking for $menu_item = <a href="mailto:">Email a Friend</a>

		//is it the vehicle details menu?
		if( __( 'Vehicle Details Buttons', 'inventory-presser' ) != $args->menu->name ) {
			//no
			return $menu_item;
		}

		//is it a custom link labeled email a friend?
		if( 'Custom Link' != $item->type_label || strtolower( __( 'Email a Friend', 'inventory-presser' ) ) != strtolower( $item->title ) ) {
			//no
			return $menu_item;
		}

		//change the link to contain vehicle information
		global $post;
		$subject = 'Check out this ' . $post->post_title;
		$body = sprintf(
			'Please look at this %s for sale at %s:

%s',
			$post->post_title,
			get_bloginfo(),
			get_permalink( $post )
		);

		$menu_item = str_replace(
			'mailto:',
			sprintf(
				'mailto:?subject=%s&body=%s" target="_blank',
				urlencode( $subject ),
				urlencode( $body )
			),
			$menu_item
		);

		return $menu_item;
	}
}
