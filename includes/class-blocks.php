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
	 * @param  array                   $block_categories
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
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );
		add_filter( 'block_categories_all', array( $this, 'add_category' ), 10, 1 );
	}

	/**
	 * Enqueues block editor assets
	 *
	 * @return void
	 */
	public function enqueue_block_editor_assets() {
		wp_enqueue_script( 'invp-blocks' );
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

		register_block_type( dirname( INVP_PLUGIN_FILE_PATH ) . '/build/blocks/year-make-model-and-trim' );

		// These are meta keys that can be managed by a simple text box.
		$simple_meta_keys = array(
			'body_style',
			'color',
			'down_payment',
			'engine',
			'interior_color',
			'last_modified',
			'make',
			'model',
			'msrp',
			'odometer',
			'payment',
			'price',
			'stock_number',
			'title_status',
			'transmission_speeds',
			'trim',
			'vin',
			'year',
			'youtube',
		);
		foreach ( $simple_meta_keys as $key ) {
			register_block_type(
				'inventory-presser/' . str_replace( '_', '-', $key ),
				array(
					'render_callback' => array( $this, 'simple_renderer' ),
				)
			);
		}
	}

	public function simple_renderer( $block_attributes, $content ) {
		if ( empty( $block_attributes ) ) {
			return '';
		}

		// Do we have a template tag for this meta key?
		$value = '';
		if ( is_callable( 'invp_get_the_' . $block_attributes['key'] ) ) {
			// Yes, use it.
			$value = call_user_func( 'invp_get_the_' . $block_attributes['key'] );
		} else {
			$value = INVP::get_meta( $block_attributes['key'] );
		}
		return sprintf( '<span %s>%s</span>', wp_kses_data( get_block_wrapper_attributes() ), $value );
	}
}
