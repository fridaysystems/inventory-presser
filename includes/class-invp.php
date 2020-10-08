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
	const POST_TYPE = 'inventory_vehicle';
	const OPTION_NAME = 'inventory_presser';

	public static function add_hooks()
	{
		//Translate friendly names to actual custom field keys and the other way
		add_filter( 'invp_prefix_meta_key', array( 'INVP', 'translate_custom_field_names' ) );
		add_filter( 'invp_unprefix_meta_key', array( 'INVP', 'untranslate_custom_field_names' ) );
	}

	/**
	 * delete_all_data
	 *
	 * This function will operate as an uninstall utility. Removes all the
	 * data we have added to the database including vehicle posts, their 
	 * attachments, the option that holds settings, and terms in custom
	 * taxonomies.
	 * 
	 * @return void
	 */
	public static function delete_all_data()
	{
		//delete all the vehicles
		self::delete_all_inventory();

		//delete pages created during activation
		//uninstall.php doesn't load the whole plugin but calls this method
		if( ! class_exists( 'Inventory_Presser_Allow_Inventory_As_Home_Page' ) )
		{
			require_once( 'class-allow-inventory-as-home-page.php' );
		}
		Inventory_Presser_Allow_Inventory_As_Home_Page::delete_pages();

		//delete all terms
		if( ! is_multisite() )
		{
			self::delete_all_terms_on_blog();

			//delete the option where all this plugin's settings are stored
			delete_option( self::OPTION_NAME );
		}
		else
		{
			$sites = get_sites( array( 'network' => 1, 'limit' => 1000 ) );
			foreach( $sites as $site )
			{
				switch_to_blog( $site->blog_id );

				self::delete_all_terms_on_blog();

				//delete the option where all this plugin's settings are stored
				delete_option( self::OPTION_NAME );

				restore_current_blog();
			}
		}

		do_action( 'invp_delete_all_data' );
	}

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

		if( ! is_multisite() )
		{
			return self::delete_all_inventory_on_blog();
		}

		$sites = get_sites( array( 'network' => 1, 'limit' => 1000 ) );
		foreach( $sites as $site )
		{
			switch_to_blog( $site->blog_id );
			$count = self::delete_all_inventory_on_blog();
			restore_current_blog();
			return $count;
		}
	}

	private static function delete_all_inventory_on_blog()
	{
		$args = array(
			'post_status'    => get_post_stati(),
			'post_type'      => self::POST_TYPE,
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

	private static function delete_all_terms_on_blog()
	{	
		if( ! class_exists( 'Inventory_Presser_Taxonomies' ) )
		{
			require_once( 'class-taxonomies.php' );
		}
		$taxonomies = new Inventory_Presser_Taxonomies();
		global $wpdb;
		foreach( $taxonomies->query_vars_array() as $taxonomy )
		{
			$taxonomy_name = str_replace( '-', '_', $taxonomy );
			$terms = $wpdb->get_results( $wpdb->prepare( 
				"SELECT t.*, tt.* FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id WHERE tt.taxonomy IN ('%s') ORDER BY t.name ASC", 
				$taxonomy_name
			) );
		
			//delete terms
			if ( $terms )
			{
				foreach ( $terms as $term )
				{
					$wpdb->delete( $wpdb->term_taxonomy, array( 'term_taxonomy_id' => $term->term_taxonomy_id ) );
					$wpdb->delete( $wpdb->terms, array( 'term_id' => $term->term_id ) );
				}
			}
			
			//delete taxonomy
			$wpdb->delete( $wpdb->term_taxonomy, array( 'taxonomy' => $taxonomy_name ), array( '%s' ) );
		}
	}
	
	/**
	 * get_meta
	 *
	 * @param  string $unprefixed_meta_key
	 * @param  int $post_ID
	 * @return string|int|double
	 */
	public static function get_meta( $unprefixed_meta_key, $post_ID = null )
	{
		if( empty( $post_ID ) )
		{
			$post_ID = get_the_ID();
		}

		$meta_key = apply_filters( 'invp_prefix_meta_key', $unprefixed_meta_key );
		$meta_value = get_post_meta( $post_ID, $meta_key, true );

		//If the meta key is a number, return a number, and zero instead of empty string
		if( self::meta_value_is_number( $meta_key ) )
		{
			if( empty( $meta_value ) )
			{
				return 0;
			}
			if( false === strpos( $meta_value, '.' ) )
			{
				return (int) $meta_value;
			}
			return (double) $meta_value;
		}
		return $meta_value;
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
	 * meta_value_is_number
	 * 
	 * Indicates whether or not a provided $post_meta_key is a number data
	 * type.
	 *
	 * @param  string $post_meta_key
	 * @return bool True if the given $post_meta_key is a number data type.
	 */
	public static function meta_value_is_number( $post_meta_key )
	{
		$keys_and_types = self::keys_and_types();
		foreach( $keys_and_types as $key_and_type )
		{
			if( apply_filters( 'invp_prefix_meta_key', $key_and_type['name'] ) == $post_meta_key )
			{
				return 'number' == $key_and_type['type'] || 'integer' == $key_and_type['type'];
			}
		}
		return false;
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
		return wp_parse_args( get_option( self::OPTION_NAME ), $defaults );
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
	 * translate_custom_field_names
	 *
	 * Prefixes our post meta field keys so 'make' becomes
	 * 'inventory_presser_make'. Careful to not prefix a key that has
	 * already been prefixed.
	 * 
	 * @param  string $nice_name The unprefixed meta key.
	 * @return string The prefixed meta key.
	 */
	public static function translate_custom_field_names( $nice_name )
	{
		$nice_name = strtolower( $nice_name );
		$prefix = INVP::meta_prefix();

		if( $prefix == substr( $nice_name, 0, strlen( $prefix ) ) )
		{
			return $nice_name;
		}
		return $prefix . $nice_name;
	}

	/**
	 * untranslate_custom_field_names
	 * 
	 * Removes the prefix from our post meta field keys so
	 * 'inventory_presser_make' becomes 'make'. Careful to not damage any
	 * provided key that does not start with our prefix.
	 *
	 * @param  string $meta_key The prefixed meta key.
	 * @return string The un-prefixed meta key.
	 */	
	public static function untranslate_custom_field_names( $meta_key )
	{
		if( empty( $meta_key ) )
		{
			return '';
		}
		$meta_key = strtolower( $meta_key );
		//prefix may start with an underscore because previous versions hid some meta keys
		$prefix = ( '_' == $meta_key[0] ? '_' : '' ) . INVP::meta_prefix();

		//does $meta_key actually start with the $prefix?
		if( $prefix == substr( $meta_key, 0, strlen( $prefix ) ) )
		{
			//remove the prefix
			return substr( $meta_key, strlen( $prefix ) );
		}

		return $meta_key;
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
