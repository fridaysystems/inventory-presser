<?php
defined( 'ABSPATH' ) or exit;

/**
 * Inventory_Presser_Blocks
 * 
 * Creates blocks
 */
class Inventory_Presser_Blocks
{	
	/**
	 * add_category
	 * 
	 * Adds a block category to hold all our blocks
	 *
	 * @param  array $block_categories
	 * @param  WP_Block_Editor_Context $block_editor_context
	 * @return array
	 */
	function add_category( $block_categories, $block_editor_context )
	{
		//is the post a vehicle?
		if( empty( $block_editor_context->post->post_type )
			|| $block_editor_context->post->post_type != INVP::POST_TYPE )
		{
			return $block_categories;
		}

		return array_merge(
			$block_categories,
			array(
				array(
					'slug'  => 'inventory-presser',
					'title' => __( 'Inventory Presser', 'inventory-presser' ),
					'icon'  => 'dashicons-admin-network', //it's a key
				),
			)
		);
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
		add_action( 'enqueue_block_editor_assets', array( $this, 'register_block_types' ) );
		add_filter( 'block_categories_all', array( $this, 'add_category' ), 10, 2 );
	}
	
	/**
	 * register_block_types
	 * 
	 * Registers block types
	 *
	 * @return void
	 */
	function register_block_types()
	{
		if( ! function_exists( 'register_block_type' ) )
		{
			//running on WordPress < 5.0.0, no blocks for you
			return;
		}

		$asset_file = include( plugin_dir_path( INVP_PLUGIN_FILE_PATH ) . 'build/index.asset.php' );

		$keys = array(
			// 'beam',
			// 'body-style',
			// 'color',
			// 'engine',
			// 'hull-material',
			// 'interior-color',
			// 'last-modified',
			// 'length',
			'make',
			'model',
			// 'odometer',
		);

		wp_enqueue_script(
			'invp-blocks',
			plugins_url( 'build/index.js', INVP_PLUGIN_FILE_PATH ),
			$asset_file['dependencies'],
			$asset_file['version']
		);

		$settings = INVP::settings();

		//Provide the vehicle post type meta keys and prefix to JavaScript
		wp_add_inline_script( 'invp-blocks', 'const invp_blocks = ' . json_encode( array(
			'keys'                        => INVP::keys_and_types(),
			'meta_prefix'                 => INVP::meta_prefix(),
			'use_carfax'                  => $settings['use_carfax'],
			'use_carfax_provided_buttons' => $settings['use_carfax_provided_buttons'],
		) ), 'before' );


		foreach( $keys as $key )
		{
			register_block_type( 'inventory-presser/' . $key, array(
				'editor_script' => 'invp-blocks',
			) );
		}
	}
}
