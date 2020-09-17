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
	const QUERY_VAR_UNINSTALL = 'invp_uninstall';

	public function hooks()
	{
		//Add links to the Plugins page for single sites
		add_filter( 'plugin_action_links_' . INVP_PLUGIN_BASE, array( $this, 'add_delete_vehicles_link' ), 10, 1 );
		add_filter( 'plugin_action_links_' . INVP_PLUGIN_BASE, array( $this, 'add_uninstall_link' ), 10, 1 );		

		//Add links to the Plugins page for multisite network admins
		add_filter( 'network_admin_plugin_action_links_' . INVP_PLUGIN_BASE, array( $this, 'add_uninstall_link' ), 10, 1 );
		
	}
		
	/**
	 * add_uninstall_link
	 * 
	 * Adds an Uninstall link near the Deactivate link on the plugins page.
	 *
	 * @param string[] $actions     An array of plugin action links. By default this can include 'activate',
	 *                              'deactivate', and 'delete'.
	 * @return string[] The changed array of plugin action links.
	 */
	public function add_uninstall_link( $actions )
	{
		if( is_multisite() && ! is_network_admin() )
		{
			/**
			 * Don't ever allow a single site in a multisite network to uninstall 
			 * the plugin because uninstalling includes deactivation.
			 */
			return $actions;
		}

		if( ! current_user_can( 'activate_plugins' ) )
		{
			return $actions;
		}

		$label = __( 'Uninstall', 'inventory-presser' );
		$link = sprintf( 
			'<a href="%s">%s</a>', 
			esc_url( admin_url( sprintf( 'plugins.php?%s=1', self::QUERY_VAR_UNINSTALL ) ) ), 
			$label
		);
		return array_merge( $actions, array( INVP::sluggify( $label ) . ' delete' => $link ) );
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
		$link = sprintf( 
			'<a href="%s">%s</a>', 
			esc_url( admin_url( sprintf( 'plugins.php?%s=1', self::QUERY_VAR_DELETE_VEHICLES ) ) ), 
			$label
		);
		return array_merge( $actions, array( INVP::sluggify( $label ) . ' delete' => $link ) );
	}
}
