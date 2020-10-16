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
	 * @param  array $categories
	 * @param  WP_Post $post
	 * @return array
	 */
	function add_category( $categories, $post )
	{
		//is the post a vehicle?
		if( empty( $post ) || empty( $post->post_type )
			|| $post->post_type != INVP::POST_TYPE )
		{
			return $categories;
		}

		return array_merge(
			$categories,
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
		add_filter( 'block_categories', array( $this, 'add_category' ), 10, 2 );
	}

	/**
	 * editing_a_vehicle
	 * 
	 * True or false, this request originates from the editor and a post in our
	 * post type is what is being created or edited
	 *
	 * @return bool
	 */
	private function editing_a_vehicle()
	{
		global $post_type;
		if( ! empty( $post_type ) )
		{
			if( INVP::POST_TYPE != $post_type )
			{
				return false;
			}
		}
		elseif( empty( $_POST['post_type'] ) )
		{
			return false;
		}
		return true;
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

		if( ! $this->editing_a_vehicle() )
		{
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

		//localize an odometer units word for the edit vehicle page
		wp_localize_script( 'invp-blocks', 'invp_blocks', array(
			'keys'        => INVP::keys_and_types(),
			'meta_prefix' => INVP::meta_prefix(),
		) );

		foreach( $keys as $key )
		{
			register_block_type( 'inventory-presser/' . $key, array(
				'editor_script' => 'invp-blocks',
			) );
		}
	}
}
