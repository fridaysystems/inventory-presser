<?php
defined( 'ABSPATH' ) || exit;

/**
 * Inventory_Presser_Admin_Editor_Sidebar
 *
 * Adds a sidebar to the WordPress editor so that meta fields can be edited
 * outside of blocks.
 */
class Inventory_Presser_Admin_Editor_Sidebar {

	/**
	 * Registers a JavaScript file
	 *
	 * @return void
	 */
	public function sidebar_plugin_register() {
		$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		wp_register_script(
			'invp-plugin-sidebar',
			plugins_url( "/js/editor-sidebar{$min}.js", INVP_PLUGIN_FILE_PATH ),
			array( 'wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data', 'wp-hooks', 'wp-i18n' ),
			INVP_PLUGIN_VERSION,
			true
		);
	}

	/**
	 * Includes the JavaScript file when editing a vehicle in the dashboard.
	 *
	 * @return void
	 */
	public function sidebar_plugin_script_enqueue() {
		// Are we editing a vehicle?
		global $post;
		if ( empty( $post->post_type ) || INVP::POST_TYPE !== $post->post_type ) {
			return;
		}
		wp_enqueue_script( 'invp-plugin-sidebar' );
	}

	/**
	 * Adds hooks
	 *
	 * @return void
	 */
	public function add_hooks() {
		add_action( 'enqueue_block_assets', array( $this, 'sidebar_plugin_script_enqueue' ) );
		add_action( 'init', array( $this, 'sidebar_plugin_register' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'scripts_and_styles' ) );
	}

	/**
	 * Includes the wp-api JavaScript
	 *
	 * @return void
	 */
	public function scripts_and_styles() {
		$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		wp_enqueue_script( 'wp-api' );
		wp_enqueue_style(
			'invp-editor-sidebar',
			plugins_url( '/css/editor-sidebar.min.css', INVP_PLUGIN_FILE_PATH ),
			array(),
			INVP_PLUGIN_VERSION
		);

		// Provide data to JavaScript for the editor.
		wp_add_inline_script(
			'wp-api',
			'const invp = ' . wp_json_encode(
				array(
					'hull_materials'      => apply_filters(
						'invp_default_hull_materials',
						array(
							__( 'Aluminum', 'inventory-presser' ),
							__( 'Carbon Fiber', 'inventory-presser' ),
							__( 'Composite', 'inventory-presser' ),
							__( 'Ferro-Cement', 'inventory-presser' ),
							__( 'Fiberglass', 'inventory-presser' ),
							__( 'Hypalon', 'inventory-presser' ),
							__( 'Other', 'inventory-presser' ),
							__( 'PVC', 'inventory-presser' ),
							__( 'Steel', 'inventory-presser' ),
							__( 'Wood', 'inventory-presser' ),
						)
					),
					'meta_prefix'         => INVP::meta_prefix(),
					'odometer_label'      => apply_filters( 'invp_odometer_word', __( 'Odometer', 'inventory-presser' ) ),
					'odometer_units'      => apply_filters( 'invp_odometer_word', __( 'miles', 'inventory-presser' ) ),
					'payment_frequencies' => apply_filters(
						'invp_default_payment_frequencies',
						array(
							'Monthly'      => 'monthly',
							'Weekly'       => 'weekly',
							'Bi-weekly'    => 'biweekly',
							'Semi-monthly' => 'semimonthly',
						)
					),
					'title_statuses'      => apply_filters(
						'invp_default_title_statuses',
						array(
							'Unspecified',
							'Clear',
							'Clean',
							'Flood, Water Damage',
							'Lemon and Manufacturers Buyback',
							'Rebuild, Rebuildable, and Reconstructed',
							'Salvage',
							'Other',
						)
					),
				)
			),
			'before'
		);
	}
}
