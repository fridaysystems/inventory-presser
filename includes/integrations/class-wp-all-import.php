<?php
defined( 'ABSPATH' ) or exit;

/**
 * Inventory_Presser_WP_All_Import
 * 
 * Helps vehicle imports using WP All Import Pro by detecting piped options and
 * saving them as individual options.
 */
class Inventory_Presser_WP_All_Import
{	
	/**
	 * detect_piped_options
	 * 
	 * If the value being saved to the meta key inventory_presser_options_array
	 * contains a pipe, split the string on pipe and add each option
	 * individually.
	 *
	 * @param  int $post_id
	 * @param  string $meta_key
	 * @param  mixed $meta_value
	 * @return void
	 */
	public function detect_piped_options( $post_id, $meta_key, $meta_value )
	{
		if( ! class_exists( 'INVP') || INVP::POST_TYPE != get_post_type( $post_id ) )
		{
			return;
		}

		if( apply_filters( 'invp_prefix_meta_key', 'options_array' ) != $meta_key )
		{
			return;
		}

		//Are there even pipes in the value?
		if( false === strpos( $meta_value, '|' ) )
		{
			return;
		}

		//Erase the current value
		delete_post_meta( $post_id, $meta_key );

		//Add each option individually, options_array is a multi-meta value
		foreach( explode( '|', $meta_value ) as $option )
		{
			add_post_meta( $post_id, $meta_key, $option );
		}
	}

	public function add_hooks()
	{
		add_action( 'pmxi_update_post_meta', array( $this, 'detect_piped_options' ), 10, 3 );
	}
}
