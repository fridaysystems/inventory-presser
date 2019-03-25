<?php

class Inventory_Presser_Blocks{

	function add_category( $categories, $post ) {
		if ( $post->post_type !== Inventory_Presser_Plugin::CUSTOM_POST_TYPE ) {
			return $categories;
		}
		return array_merge(
			$categories,
			array(
				array(
					'slug' => 'inventory-presser',
					'title' => __( 'Inventory Presser', 'inventory-presser' ),
					'icon'  => 'dashicons-admin-network', //it's a key
				),
			)
		);
	}

	function hooks() {
		add_action( 'init', array( $this, 'register_block_types' ) );
		add_filter( 'block_categories', array( $this, 'add_category' ), 10, 2 );
	}

	function register_block_types() {

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

		foreach( $keys as $key ) {

			wp_register_script(
				'block-' . $key,
				plugins_url( 'js/blocks/' . $key . '.js', dirname( __FILE__ ) ),
				array( 'wp-blocks', 'wp-element' )
			);

			if( ! function_exists( 'register_block_type' ) ) {
				//running on WordPress < 5.0.0, no blocks for you
				continue;
			}

			register_block_type( 'inventory-presser/' . $key, array(
				'editor_script' => 'block-' . $key,
			) );
		}
	}
}
