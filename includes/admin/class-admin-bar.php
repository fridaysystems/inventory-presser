<?php
defined( 'ABSPATH' ) || exit;

/**
 * Inventory_Presser_Admin_Bar
 *
 * Adds vehicles to the Admin Bar.
 *
 * @since      14.10.0
 * @package    inventory-presser
 * @subpackage inventory-presser/includes/admin
 * @author     Corey Salzano <corey@friday.systems>
 */
class Inventory_Presser_Admin_Bar {

	/**
	 * Adds a Vehicle button to the Admin Bar
	 *
	 * @param WP_Admin_Bar $admin_bar The WP_Admin_Bar object.
	 * @return void
	 */
	public function add_vehicles_to_admin_bar( $admin_bar ) {
		// Do not add an admin bar item if we are looking at the dashboard.
		// Only show this item to users who can edit posts.
		if ( is_admin() || ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		$admin_bar->add_node(
			array(
				'id'     => 'wp-admin-bar-vehicles',
				'title'  => __( 'Vehicles', 'inventory-presser' ),
				'href'   => admin_url( 'edit.php?post_type=' . INVP::POST_TYPE ),
				'parent' => 'site-name',
			)
		);
	}

	/**
	 * Adds hooks
	 *
	 * @return void
	 */
	public function add_hooks() {
		// Add a link to the main menu of the Admin bar.
		add_action( 'admin_bar_menu', array( $this, 'add_vehicles_to_admin_bar' ), 100, 1 );
	}
}
