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
	 * @return void
	 */
	function add_vehicles_to_admin_bar( $admin_bar ) {
		// do not do this if we are already looking at the dashboard
		if ( is_admin() ) {
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
