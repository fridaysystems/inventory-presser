<?php
defined( 'ABSPATH' ) or exit;

/**
 * Inventory_Presser_Admin_Editor_Sidebar
 * 
 * Adds a sidebar to the WordPress editor so that meta fields can be edited
 * outside of blocks.
 */
class Inventory_Presser_Admin_Editor_Sidebar
{	
	/**
	 * sidebar_plugin_register
	 * 
	 * Registers a JavaScript file
	 *
	 * @return void
	 */
	function sidebar_plugin_register()
	{
		wp_register_script(
			'invp-plugin-sidebar',
			plugins_url( '/js/editor-sidebar.min.js', INVP_PLUGIN_FILE_PATH ),
			array( 'wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data' )
		);
	}
	
	/**
	 * sidebar_plugin_script_enqueue
	 * 
	 * Includes the JavaScript file when editing a vehicle in the dashboard.
	 *
	 * @return void
	 */
	function sidebar_plugin_script_enqueue()
	{
		//Are we editing a vehicle?
		global $post;
		if( empty( $post ) || INVP::POST_TYPE != $post->post_type )
		{
			return;
		}
		wp_enqueue_script( 'invp-plugin-sidebar' );
	}
	
	/**
	 * hooks
	 * 
	 * Adds hooks
	 *
	 * @return void
	 */
	function hooks()
	{
		add_action( 'enqueue_block_editor_assets', array( $this, 'sidebar_plugin_script_enqueue' ) );
		add_action( 'init', array( $this, 'sidebar_plugin_register' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'include_javascript_backbone' ) );
	}
	
	/**
	 * include_javascript_backbone
	 * 
	 * Includes the wp-api JavaScript
	 *
	 * @return void
	 */
	function include_javascript_backbone()
	{
		wp_enqueue_script( 'wp-api' );
	}	
}
