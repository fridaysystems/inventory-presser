<?php
defined( 'ABSPATH' ) or exit;

/**
 * Inventory_Presser_Uninstaller
 * 
 * This class makes it easy for users to delete all the data managed by the 
 * plugin from the site.
 */
class Inventory_Presser_Uninstaller
{
	//We'll use query variables to catch requests and perform the actions
	const QUERY_VAR_DELETE_VEHICLES = 'invp_delete_all_vehicles';

	public function hooks()
	{
		//Add links to the Plugins page for single sites
		add_filter( 'plugin_action_links_' . INVP_PLUGIN_BASE, array( $this, 'add_delete_vehicles_link' ), 10, 1 );

		//Look for our query variables and dispatch the actions
		add_action( 'admin_init', array( $this, 'detect_query_vars' ) );
	}

	/**
	 * add_delete_vehicles_link
	 * 
	 * Adds a Delete all Vehicles link near the Deactivate link on the plugins page.
	 *
	 * @param string[] $actions     An array of plugin action links. By default this can include 'activate',
	 *                              'deactivate', and 'delete'.
	 * @return string[] The changed array of plugin action links.
	 */
	public function add_delete_vehicles_link( $actions )
	{
		if( ! current_user_can( 'delete_posts' ) )
		{
			return $actions;
		}

		$label = __( 'Delete all Vehicles', 'inventory-presser' );
		$nonce = wp_create_nonce( self::QUERY_VAR_DELETE_VEHICLES );
		$link = sprintf( 
			'<a href="%s">%s</a>', 
			esc_url( admin_url( sprintf( 'plugins.php?%s=1&_wpnonce=%s', self::QUERY_VAR_DELETE_VEHICLES, $nonce ) ) ), 
			$label
		);
		return array_merge( $actions, array( INVP::sluggify( $label ) . ' delete' => $link ) );
	}

	public function detect_query_vars()
	{
		//Is this a GET request?
		if( 'GET' != $_SERVER['REQUEST_METHOD'] )
		{
			return;
		}

		if( empty( $_GET['_wpnonce'] ) || empty( $_GET[ self::QUERY_VAR_DELETE_VEHICLES ] ) )
		{
			return;
		}

		if( ! wp_verify_nonce( $_GET['_wpnonce'], self::QUERY_VAR_DELETE_VEHICLES ) )
		{
			return;

		}
		
		//dispatch the delete all vehicles call
		INVP::delete_all_inventory();

		//redirect to a URL that won't cause more deletes on page reloads
		wp_redirect( admin_url( 'plugins.php' ) );
		exit;
	}
}
