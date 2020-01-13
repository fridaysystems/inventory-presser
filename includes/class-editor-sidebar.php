<?php
defined( 'ABSPATH' ) or exit;

/**
 * Adds a sidebar to the WordPress editor so that meta fields can be edited
 * outside of blocks.
 */
class Inventory_Presser_Editor_Sidebar
{
	function sidebar_plugin_register()
	{
		wp_register_script(
			'invp-plugin-sidebar',
			plugins_url( '/js/editor-sidebar.js', dirname( __FILE__, 2 ) . '/inventory-presser.php' ),
			array( 'wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data' )
		);
	}

	function sidebar_plugin_script_enqueue()
	{
		//Are we editing a vehicle?
		global $post;
		if( Inventory_Presser_Plugin::CUSTOM_POST_TYPE != $post->post_type )
		{
			return;
		}

		wp_enqueue_script( 'invp-plugin-sidebar' );
	}

	function hooks()
	{
		add_action( 'enqueue_block_editor_assets', array( $this, 'sidebar_plugin_script_enqueue' ) );
		add_action( 'init', array( $this, 'sidebar_plugin_register' ) );
	}
}
