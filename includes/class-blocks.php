<?php
defined( 'ABSPATH' ) or exit;

class Inventory_Presser_Blocks
{
	function add_category( $categories, $post )
	{
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
		//Only load our blocks when editing or creating vehicles
		if( ! $this->editing_a_vehicle() )
		{
			return;
		}

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

		$keys = array(
			'beam',
			'body-style',
			'color',
			'engine',
			'hull-material',
			'interior-color',
			'last-modified',
			'length',
			'make',
			'model',
			'odometer',
		);

		foreach( $keys as $key )
		{
			wp_register_script(
				'block-' . $key,
				plugins_url( 'js/blocks/' . $key . '.js', dirname( __FILE__ ) ),
				array( 'wp-blocks', 'wp-element' )
			);

			register_block_type( 'inventory-presser/' . $key, array(
				'editor_script' => 'block-' . $key,
			) );
		}
	}
}
