<?php
defined( 'ABSPATH' ) or exit;

/**
 * INVP
 * 
 * This API class offers static methods that are useful to more than one other 
 * class in this plugin.
 */
class INVP
{
	/**
	 * delete_all_inventory
	 *
	 * This function deletes all posts that exist of our custom post type
	 * and their associated meta data. Returns the number of vehicles
	 * deleted.
	 * 
	 * @return int The number of vehicles that were deleted
	 */
	public static function delete_all_inventory()
	{
		if( ! current_user_can( 'delete_posts' ) )
		{
			return 0;
		}
		
		set_time_limit( 0 );

		$args = array(
			'post_status'    => 'any',
			'post_type'      => Inventory_Presser_Plugin::CUSTOM_POST_TYPE,
			'posts_per_page' => -1,
		);
		$posts = get_posts( $args );
		$deleted_count = 0;
		if ( $posts )
		{
			$upload_dir = wp_upload_dir();
			foreach( $posts as $post )
			{
				//delete post attachments
				$attachment = array(
					'posts_per_page' => -1,
					'post_type'      => 'attachment',
					'post_parent'    => $post->ID,
				);

				$attachment_dir = '';

				foreach( get_posts( $attachment ) as $attached )
				{
					$attachment_dir = get_attached_file( $attached->ID );
					//delete the attachment
					wp_delete_attachment( $attached->ID, true );
				}

				//delete the parent post or vehicle
				wp_delete_post( $post->ID, true );
				$deleted_count++;

				//delete the photo folder if it exists (and is empty)
				if( '' != $attachment_dir )
				{
					$dir_path = dirname( $attachment_dir );
					if( is_dir( $dir_path ) && $dir_path != $upload_dir['basedir'] )
					{
						@rmdir( $dir_path );
					}
				}
			}
		}
		/**
		 * Delete media that is managed by this plugin but may not be attached
		 * to a vehicle at this time.
		 */
		$orphan_media = get_posts( array(
			'posts_per_page' => -1,
			'post_status'    => 'any',
			'post_type'      => 'attachment',
			'meta_query'     => array(
				array(
					'key'     => apply_filters( 'invp_prefix_meta_key', 'photo_number' ),
					'compare' => 'EXISTS'
				)
			),
		) );
		foreach( $orphan_media as $post )
		{
			wp_delete_attachment( $post->ID, true );
		}

		do_action( 'invp_delete_all_inventory', $deleted_count );

		return $deleted_count;
	}

	/**
	 * meta_prefix
	 * 
	 * Returns the prefix added to all vehicle post meta keys.
	 *
	 * @return string The prefix added to all vehicle post meta keys
	 */
	public static function meta_prefix()
	{
		return apply_filters( 'invp_meta_prefix', 'inventory_presser_' );
	}

	/**
	 * settings
	 * 
	 * Get this plugin's option mingled with default values.
	 *
	 * @return array An associative array containing this plugin's settings
	 */
	public static function settings()
	{
		$defaults = array(
			'sort_vehicles_by'            => 'make',
			'sort_vehicles_order'         => 'ASC',
			'use_carfax_provided_buttons' => true,
		);
		return wp_parse_args( get_option( Inventory_Presser_Plugin::OPTION_NAME ), $defaults );
	}

	/**
	 * sluggify
	 *
	 * Turns the name of something into a slug that WordPress will accept when
	 * creating objects like terms. WordPress slugs are described as containing
	 * only letters, numbers, and hyphens.
	 * 
	 * @param  string $name
	 * @return string An alteration of $name that WordPress will accept as a term slug
	 */
	public static function sluggify( $name )
	{
		$name = preg_replace( '/[^a-zA-Z0-9\\-]/', '', str_replace( '/', '-', str_replace( ' ', '-', $name ) ) );
		return strtolower( str_replace( '--', '-', str_replace( '---', '-', $name ) ) );
	}

	/**
	 * weekdays
	 * 
	 * If no parameter is provided, returns an array containing lowercase 
	 * weekdays as keys and title case, three-letter abbreviations as values.
	 * If a valid parameter is provided, returns the lowercase day name
	 * it identifies. If an invalid parameter is provided, returns false.
	 *
	 * @param  int $zero_through_six A number between 0 and 6 to identify a single day to return as a string.
	 * @return string|Array|false
	 */
	public static function weekdays( $zero_through_six = null )
	{
		$days = array(
			'monday'    => __( 'Mon', 'inventory-presser' ),
			'tuesday'   => __( 'Tue', 'inventory-presser' ),
			'wednesday' => __( 'Wed', 'inventory-presser' ),
			'thursday'  => __( 'Thu', 'inventory-presser' ),
			'friday'    => __( 'Fri', 'inventory-presser' ),
			'saturday'  => __( 'Sat', 'inventory-presser' ),
			'sunday'    => __( 'Sun', 'inventory-presser' ),
		);

		/**
		 * If a valid parameter is provided, return the lowercase day name
		 * it identifies.
		 */
		if( null !== $zero_through_six )
		{
			if( 0 <= $zero_through_six && 6 >= $zero_through_six )
			{
				return array_keys( $days )[$zero_through_six];
			}
			else
			{
				//If an invalid parameter is provided, return false
				return false;
			}
		}

		//Return the array
		return $days;
	}
}
