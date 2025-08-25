<?php
defined( 'ABSPATH' ) || exit;

/**
 * Inventory_Presser_Blocks
 *
 * Creates blocks
 */
class Inventory_Presser_Blocks {

	/**
	 * Adds a block category to hold all our blocks
	 *
	 * @param  array $block_categories
	 * @return array
	 */
	public function add_category( $block_categories ) {
		return array_merge(
			$block_categories,
			array(
				array(
					'slug'  => 'inventory-presser',
					'title' => __( 'Inventory Presser', 'inventory-presser' ),
					'icon'  => 'dashicons-admin-network', // it's a key.
				),
			)
		);
	}

	/**
	 * Adds hooks
	 *
	 * @return void
	 */
	public function add_hooks() {
		add_action( 'init', array( $this, 'register_block_types' ) );
		add_action( 'enqueue_block_assets', array( $this, 'enqueue_block_editor_assets' ) );
		add_filter( 'block_categories_all', array( $this, 'add_category' ), 10, 1 );
	}

	/**
	 * Enqueues block editor assets
	 *
	 * @return void
	 */
	public function enqueue_block_editor_assets() {
		if ( is_admin() ) {
			wp_enqueue_script( 'invp-blocks' );
			wp_enqueue_style( 'invp-block-editor' );
		}
	}

	/**
	 * Registers block types
	 *
	 * @return void
	 */
	public function register_block_types() {
		if ( ! function_exists( 'register_block_type' ) ) {
			// running on WordPress < 5.0.0, no blocks for you.
			return;
		}

		$block_types = array(
			'year-make-model-and-trim',
			'beam', // For boats.
			'body-style',
			'color',
			'description',
			'down-payment',
			'engine',
			'interior-color',
			'last-modified',
			'length', // For trailers & boats.
			'make',
			'model',
			'msrp',
			'odometer',
			'payment',
			'price',
			'stock-number',
			'title-status',
			'transmission-speeds',
			'trim',
			'vin',
			'year',
			'youtube',
		);
		foreach ( $block_types as $block_type ) {
			register_block_type( dirname( INVP_PLUGIN_FILE_PATH ) . '/build/blocks/' . $block_type . '/block.json' );
		}
	}
}
