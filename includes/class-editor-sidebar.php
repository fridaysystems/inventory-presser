<?php
defined( 'ABSPATH' ) or exit;

/**
 * Adds a sidebar to the WordPress editor so that meta fields can be edited
 * outside of blocks.
 */
class Inventory_Presser_Editor_Sidebar
{
	function sidebar_plugin_register()
	{
		wp_register_script(
			'invp-plugin-sidebar',
			plugins_url( '/js/editor-sidebar.js', dirname( dirname( __FILE__ ) ) . '/inventory-presser.php' ),
			array( 'wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data' )
		);
	}

	function sidebar_plugin_script_enqueue()
	{
		//Are we editing a vehicle?
		global $post;
		if( Inventory_Presser_Plugin::CUSTOM_POST_TYPE != $post->post_type )
		{
			return;
		}
		wp_enqueue_script( 'invp-plugin-sidebar' );
	}

	function hooks()
	{
		add_action( 'enqueue_block_editor_assets', array( $this, 'sidebar_plugin_script_enqueue' ) );
		add_action( 'init', array( $this, 'sidebar_plugin_register' ) );

		/**
		 * When a few meta fields are edited, also update the taxonomy terms
		 * that overlap.
		 */
		//add_action( 'update_post_meta', array( $this, 'maybe_update_term' ), 10, 4 );

		add_action( 'admin_enqueue_scripts', array( $this, 'include_javascript_backbone' ) );
	}

	function include_javascript_backbone()
	{
		wp_enqueue_script( 'wp-api' );
	}

	function maybe_update_term( $meta_id, $object_id, $meta_key, $meta_value )
	{
		$meta_keys_and_taxonomies = array(
			apply_filters( 'invp_prefix_meta_key', 'body_style' ) => 'style', //different
			apply_filters( 'invp_prefix_meta_key', 'make' )       => 'make',
			apply_filters( 'invp_prefix_meta_key', 'model' )      => 'model',
			apply_filters( 'invp_prefix_meta_key', 'model_year' ) => 'model_year',
		);

		if( ! isset( $meta_keys_and_taxonomies[$meta_key] ) )
		{
			return;
		}

		//does a term for this exist?
		$terms = get_terms( array(
			'hide_empty' => false,
			'name'       => $meta_value,
			'taxonomy'   => $meta_keys_and_taxonomies[$meta_key],
		) );

		$term_id = 0;
		if( empty( $terms ) )
		{
			//insert the term
			$result = wp_insert_term( $meta_value, $meta_keys_and_taxonomies[$meta_key] );
			/*
				array(
				    'term_id'          => $term_id,
				    'term_taxonomy_id' => $tt_id,
				);
			*/
			if( is_array( $result ) )
			{
				$term_id = $result['term_id'];
			}
		}
		else
		{
			$term_id = $terms[0]->term_id;
		}

		wp_set_object_terms( $object_id, $term_id, $meta_keys_and_taxonomies[$meta_key] );
	}
}
