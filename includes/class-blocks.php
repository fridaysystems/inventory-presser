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

		/**
		 * When a vehicle is opened in the block editor, add a Description block
		 * to the post.
		 */
		add_action( 'the_post', array( $this, 'create_block_description' ), 10, 1 );
	}

	/**
	 * Adds a Description Block to the post when a vehicle is opened in the
	 * block editor.
	 *
	 * @param  WP_Post $post The post loaded into the block editor.
	 * @return void
	 */
	public function create_block_description( $post ) {
		global $pagenow;
		// Is the user adding or editing a vehicle?
		if ( ! is_admin()
			|| get_post_type() !== INVP::POST_TYPE
			|| ( 'post-new.php' !== $pagenow && 'post.php' !== $pagenow ) ) {
			// No.
			return;
		}

		// Does the post content contain a Description block?
		$blocks = parse_blocks( $post->post_content );
		foreach ( $blocks as $block ) {
			// Is this a Description block?
			if ( ! empty( $block['blockName'] ) && 'inventory-presser/description' === $block['blockName'] ) {
				// Yes. The post already has a Description block.
				return;
			}
		}

		// The post does not have a Description block. Add one.
		$blocks[]           = array(
			'blockName'    => 'inventory-presser/description',
			'attrs'        => array(),
			'innerBlocks'  => array(),
			'innerHTML'    => '',
			'innerContent' => array(),
		);
		$post->post_content = serialize_blocks( $blocks );
		wp_update_post( $post );
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
