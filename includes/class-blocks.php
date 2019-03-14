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
		add_action( 'init', array( $this, 'body_style' ) );
		add_filter( 'block_categories', array( $this, 'add_category' ), 10, 2 );
	}

	function body_style() {

		wp_register_script(
			'block-body-style',
			plugins_url( 'js/block-body-style.js', dirname( __FILE__ ) ),
			array( 'wp-blocks', 'wp-element' )
		);

		if( ! function_exists( 'register_block_type' ) ) {
			//running on WordPress < 5.0.0, no blocks for you
			return;
		}

		register_block_type( 'inventory-presser/body-style', array(
			'editor_script' => 'block-body-style',
		) );
	}
}
