<?php
defined( 'ABSPATH' ) or exit;

class Inventory_Presser_Blocks
{
	function add_category( $categories, $post )
	{
		//is the post a vehicle?
		if( empty( $post ) || empty( $post->post_type )
			|| $post->post_type != Inventory_Presser_Plugin::CUSTOM_POST_TYPE )
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

	function hooks()
	{
		add_action( 'enqueue_block_editor_assets', array( $this, 'register_block_types' ) );
		add_filter( 'block_categories', array( $this, 'add_category' ), 10, 2 );
	}

	/**
	 * True or false, this request originates from the editor and a post in our
	 * post type is what is being created or edited
	 */
	private function editing_a_vehicle()
	{
		global $post_type;
		if( ! empty( $post_type ) )
		{
			if( Inventory_Presser_Plugin::CUSTOM_POST_TYPE != $post_type )
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

		$plugin_root_file_path = dirname( __FILE__, 2 ) . '/inventory-presser.php';

		$asset_file = include( plugin_dir_path( $plugin_root_file_path ) . 'build/index.asset.php' );

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
			plugins_url( 'build/index.js', $plugin_root_file_path ),
			$asset_file['dependencies'],
			$asset_file['version']
		);

		//localize an odometer units word for the edit vehicle page
		wp_localize_script( 'invp-blocks', 'invp_blocks', array(
			'keys'        => Inventory_Presser_Vehicle::keys_and_types(),
			'meta_prefix' => Inventory_Presser_Plugin::meta_prefix(),
		) );

		foreach( $keys as $key )
		{
			register_block_type( 'inventory-presser/' . $key, array(
				'editor_script' => 'invp-blocks',
			) );
		}
	}
}
