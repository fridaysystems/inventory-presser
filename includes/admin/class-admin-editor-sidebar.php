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
	 * sidebar_plugin_register
	 *
	 * Registers a JavaScript file
	 *
	 * @return void
	 */
	function sidebar_plugin_register() {
		wp_register_script(
			'invp-plugin-sidebar',
			plugins_url( '/js/editor-sidebar.min.js', INVP_PLUGIN_FILE_PATH ),
			array( 'wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data', 'wp-hooks' )
		);
	}

	/**
	 * sidebar_plugin_script_enqueue
	 *
	 * Includes the JavaScript file when editing a vehicle in the dashboard.
	 *
	 * @return void
	 */
	function sidebar_plugin_script_enqueue() {
		// Are we editing a vehicle?
		global $post;
		if ( empty( $post->post_type ) || INVP::POST_TYPE != $post->post_type ) {
			return;
		}
		wp_enqueue_script( 'invp-plugin-sidebar' );
	}

	/**
	 * hooks
	 *
	 * Adds hooks
	 *
	 * @return void
	 */
	public function add_hooks() {
		add_action( 'enqueue_block_editor_assets', array( $this, 'sidebar_plugin_script_enqueue' ) );
		add_action( 'init', array( $this, 'sidebar_plugin_register' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'scripts_and_styles' ) );
	}

	/**
	 * include_javascript_backbone
	 *
	 * Includes the wp-api JavaScript
	 *
	 * @return void
	 */
	public function scripts_and_styles() {
		$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		wp_enqueue_script( 'wp-api' );
		wp_enqueue_style( 'invp-editor-sidebar', plugins_url( '/css/editor-sidebar.min.css', INVP_PLUGIN_FILE_PATH ) );

		// Provide data to JavaScript for the editor.
		wp_add_inline_script(
			'wp-api',
			'const invp = ' . json_encode(
				array(
					'hull_materials'         => apply_filters(
						'invp_default_hull_materials',
						array(
							'Aluminum',
							'Carbon Fiber',
							'Composite',
							'Ferro-Cement',
							'Fiberglass',
							'Hypalon',
							'Other',
							'PVC',
							'Steel',
							'Wood',
						)
					),
					'miles_word'             => apply_filters( 'invp_odometer_word', 'miles' ),
					'meta_prefix'            => INVP::meta_prefix(),
					'payment_frequencies'    => apply_filters(
						'invp_default_payment_frequencies',
						array(
							'Monthly'      => 'monthly',
							'Weekly'       => 'weekly',
							'Bi-weekly'    => 'biweekly',
							'Semi-monthly' => 'semimonthly',
						)
					),
					'title_statuses'         => apply_filters(
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
