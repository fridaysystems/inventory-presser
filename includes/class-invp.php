<?php
/**
 * @package inventory-presser
 */

defined( 'ABSPATH' ) || exit;
/**
 * INVP
 *
 * This API class offers static methods that are useful to more than one other
 * class in this plugin.
 */
class INVP {

	const LOCATION_MAX_PHONES    = 10; // The maximum number of phones a single address holds.
	const LOCATION_MAX_HOURS     = 5; // The maximum number of sets of hours a single address holds.
	const POST_TYPE              = 'inventory_vehicle';
	const OPTION_NAME            = 'inventory_presser';
	const OPTION_PAGE            = 'dealership-options';
	const OPTION_PAGE_TAXONOMIES = 'invp-taxonomies';

	/**
	 * Returns the currency symbol. Default is a United States dollar sign.
	 *
	 * @return string
	 */
	public static function currency_symbol() {
		return apply_filters( 'invp_currency_symbol', '$' );
	}

	/**
	 * This function will operate as an uninstall utility. Removes all the
	 * data we have added to the database including vehicle posts, their
	 * attachments, the option that holds settings, and terms in custom
	 * taxonomies.
	 *
	 * @return void
	 */
	public static function delete_all_data() {
		// delete all the vehicles.
		self::delete_all_inventory();

		// delete unattached photos with meta keys that identify them as vehicle photos.
		self::delete_attachment_orphans();

		// delete pages created during activation.
		// uninstall.php doesn't load the whole plugin but calls this method.
		if ( ! class_exists( 'Inventory_Presser_Allow_Inventory_As_Home_Page' ) ) {
			include_once plugin_dir_path( INVP_PLUGIN_FILE_PATH ) . 'includes/class-allow-inventory-as-home-page.php';
		}
		Inventory_Presser_Allow_Inventory_As_Home_Page::delete_pages();

		// delete all terms.
		if ( ! is_multisite() ) {
			self::delete_all_terms_on_blog();

			// delete the option where all this plugin's settings are stored.
			delete_option( self::OPTION_NAME );
		} else {
			$sites = get_sites(
				array(
					'network' => 1,
					'limit'   => apply_filters( 'invp_query_limit', 1000, __METHOD__ ),
				)
			);
			foreach ( $sites as $site ) {
				switch_to_blog( $site->blog_id );

				self::delete_all_terms_on_blog();

				// delete the option where all this plugin's settings are stored.
				delete_option( self::OPTION_NAME );

				restore_current_blog();
			}
		}

		do_action( 'invp_delete_all_data' );
	}

	/**
	 * This function deletes all posts that exist of our custom post type
	 * and their associated meta data. Returns the number of vehicles
	 * deleted.
	 *
	 * @return int The number of vehicles that were deleted
	 */
	public static function delete_all_inventory() {
		if ( ! current_user_can( 'delete_posts' ) ) {
			return 0;
		}

		set_time_limit( 0 );

		if ( ! is_multisite() ) {
			return self::delete_all_inventory_on_blog();
		}

		$sites = get_sites(
			array(
				'network' => 1,
				'limit'   => apply_filters( 'invp_query_limit', 1000, __METHOD__ ),
			)
		);
		foreach ( $sites as $site ) {
			switch_to_blog( $site->blog_id );
			$count = self::delete_all_inventory_on_blog();
			restore_current_blog();
			return $count;
		}
	}

	/**
	 * Deletes all posts in the vehicle post type.
	 *
	 * @return int
	 */
	private static function delete_all_inventory_on_blog() {
		$args          = array(
			'post_status'    => get_post_stati(),
			'post_type'      => self::POST_TYPE,
			'posts_per_page' => -1,
		);
		$posts         = get_posts( $args );
		$deleted_count = 0;
		$settings      = self::settings();

		if ( $posts ) {
			foreach ( $posts as $post ) {
				// delete the parent post or vehicle.
				if ( $settings['skip_trash'] ) {
					wp_delete_post( $post->ID, $settings['skip_trash'] );
				} else {
					wp_trash_post( $post->ID );
				}
				++$deleted_count;
			}
		}

		do_action( 'invp_delete_all_inventory', $deleted_count );

		return $deleted_count;
	}

	/**
	 * Deletes all terms in the taxonomies created by this plugin.
	 *
	 * @return void
	 */
	private static function delete_all_terms_on_blog() {
		if ( ! class_exists( 'Inventory_Presser_Taxonomies' ) ) {
			include_once plugin_dir_path( INVP_PLUGIN_FILE_PATH ) . 'includes/class-taxonomies.php';
		}
		$taxonomies = new Inventory_Presser_Taxonomies();
		global $wpdb;
		foreach ( $taxonomies->query_vars_array() as $taxonomy ) {
			$taxonomy_name = str_replace( '-', '_', $taxonomy );
			$terms         = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT t.*, tt.* FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id WHERE tt.taxonomy IN ( %s ) ORDER BY t.name ASC",
					$taxonomy_name
				)
			);

			// delete terms.
			if ( $terms ) {
				foreach ( $terms as $term ) {
					$wpdb->delete( $wpdb->term_taxonomy, array( 'term_taxonomy_id' => $term->term_taxonomy_id ) );
					$wpdb->delete( $wpdb->terms, array( 'term_id' => $term->term_id ) );
				}
			}

			// delete taxonomy.
			$wpdb->delete( $wpdb->term_taxonomy, array( 'taxonomy' => $taxonomy_name ), array( '%s' ) );
		}
	}

	/**
	 * Action hook callback. Deletes all a vehicle's attachments when the
	 * vehicle is deleted.
	 *
	 * @param  int $post_id
	 * @return void
	 */
	public static function delete_attachments( $post_id ) {
		// Is $post_id a vehicle?
		if ( self::POST_TYPE !== get_post_type( $post_id ) ) {
			// No, abort.
			return;
		}

		$attachments = get_posts(
			array(
				'post_parent'    => $post_id,
				'post_status'    => 'inherit',
				'post_type'      => 'attachment',
				'posts_per_page' => -1,
			)
		);

		foreach ( $attachments as $attachment ) {
			wp_delete_attachment( $attachment->ID );
		}

		// Delete the transients that hold this vehicle's photos.
		delete_transient( 'invp_get_the_photos_images_' . $post_id );
		delete_transient( 'invp_get_the_photos_image_urls_' . $post_id );
	}

	/**
	 * Deletes media that is managed by this plugin but not attached to a post.
	 *
	 * @return void
	 */
	public static function delete_attachment_orphans() {
		$orphan_media = get_posts(
			array(
				'posts_per_page' => -1,
				'post_status'    => 'any',
				'post_type'      => 'attachment',
				'meta_query'     => array(
					array(
						'key'     => apply_filters( 'invp_prefix_meta_key', 'photo_number' ),
						'compare' => 'EXISTS',
					),
				),
			)
		);
		$settings     = self::settings();
		foreach ( $orphan_media as $post ) {
			wp_delete_attachment( $post->ID, $settings['skip_trash'] );
		}
	}

	/**
	 * Given a string containing HTML <img> element markup, extract the
	 * value of the src attribute and return it.
	 *
	 * @param  string $img_element An HTML <img> element.
	 * @return string The value of the src attribute
	 */
	public static function extract_image_element_src( $img_element ) {
		return preg_replace( '/">?.*/', '', preg_replace( '/.*<img[\s\S]+src="/', '', $img_element ) );
	}

	/**
	 * Given the ID of a term in the locations taxonomy, this method returns an
	 * object with members `lat` and `lon` that contain latitude and longitude
	 * coordinates of the address. If the coordinates are saved in meta keys
	 * `address_lat` and `address_lon`, those are provided. If no coordinates
	 * are saved, it bounces the address off OpenStreetMap.org to see if that
	 * service knows where the address is located. If coordinates cannot be
	 * found, false is returned.
	 *
	 * @param  int $location_term_id The ID of a term in the locations taxonomy.
	 * @return object|false An object with members "lat" and "lon" or false
	 */
	public static function fetch_latitude_and_longitude( $location_term_id ) {
		// Get all term meta for this location.
		$meta = get_term_meta( $location_term_id );
		// Do we already have a saved latitude and longitude pair?
		if ( ! empty( $meta['address_lat'][0] ) && ! empty( $meta['address_lon'][0] ) ) {
			// Yes.
			return (object) array(
				'lat' => $meta['address_lat'][0],
				'lon' => $meta['address_lon'][0],
			);
		}

		/**
		 * Get latitude and longitude using the address, but use a version of
		 * the address without street address line two (unless that version of
		 * the address has been added to OpenStreetMap.org).
		 */
		$address_str = '';
		if ( ! empty( $meta['address_street'][0] ) ) {
			$address_str .= $meta['address_street'][0];
		}
		if ( ! empty( $meta['address_city'][0] ) ) {
			$address_str .= ', ' . $meta['address_city'][0];
		}
		if ( ! empty( $meta['address_state'][0] ) ) {
			$address_str .= ', ' . $meta['address_state'][0];
		}
		if ( ! empty( $meta['address_zip'][0] ) ) {
			$address_str .= ' ' . $meta['address_zip'][0];
		}
		if ( empty( trim( $address_str, ', \n\r\t\v\0' ) ) ) {
			return false;
		}

		$result = wp_remote_get( 'https://nominatim.openstreetmap.org/search?format=json&q=' . rawurlencode( $address_str ) );
		if ( is_wp_error( $result ) ) {
			return false;
		}
		$body = json_decode( wp_remote_retrieve_body( $result ) );
		if ( ! empty( $body[0] ) && ! empty( $body[0]->lat ) && ! empty( $body[0]->lon ) ) {
			/**
			 * Save the latitude and longitude coordinates so we don't have to
			 * look them up again.
			 */
			update_term_meta( $location_term_id, 'address_lat', $body[0]->lat );
			update_term_meta( $location_term_id, 'address_lon', $body[0]->lon );

			return (object) array(
				'lat' => $body[0]->lat,
				'lon' => $body[0]->lon,
			);
		}

		// The address probably doesn't exist on OpenStreetMap.org.
		return false;
	}

	/**
	 * Loads sets of hours from post meta into an array.
	 *
	 * @param  int $term_id The location term ID from which to extract hours.
	 * @return array An array of hours arrays
	 */
	public static function get_hours( $term_id ) {
		$hours     = array();
		$term_meta = get_term_meta( $term_id );

		for ( $h = 1; $h <= self::LOCATION_MAX_HOURS; $h++ ) {
			// Are there hours in this slot?
			if ( empty( $term_meta[ 'hours_' . $h . '_uid' ][0] ) ) {
				// No, we're done with this location.
				break;
			}

			$set = array(
				'uid'   => $term_meta[ 'hours_' . $h . '_uid' ][0],
				'title' => self::meta_array_value_single( $term_meta, 'hours_' . $h . '_title' ),
			);

			$days = array_keys( self::weekdays() );
			for ( $d = 0; $d < 7; $d++ ) {
				$set[ $days[ $d ] . '_appt' ]  = self::meta_array_value_single( $term_meta, 'hours_' . $h . '_' . $days[ $d ] . '_appt' );
				$set[ $days[ $d ] . '_open' ]  = self::meta_array_value_single( $term_meta, 'hours_' . $h . '_' . $days[ $d ] . '_open' );
				$set[ $days[ $d ] . '_close' ] = self::meta_array_value_single( $term_meta, 'hours_' . $h . '_' . $days[ $d ] . '_close' );
			}

			$hours[] = $set;
		}
		return $hours;
	}

	/**
	 * Retrieves post meta values.
	 *
	 * @param  string $unprefixed_meta_key A meta key suffix like 'vin' or 'date_entered'.
	 * @param  int    $post_id A vehicle post ID.
	 * @return string|int|double|array
	 */
	public static function get_meta( $unprefixed_meta_key, $post_id = null ) {
		if ( empty( $post_id ) ) {
			$post_id = get_the_ID();
		}

		$meta_key = apply_filters( 'invp_prefix_meta_key', $unprefixed_meta_key );

		// Options are stored as a multi-valued meta field.
		$single = 'options_array' !== $unprefixed_meta_key;

		$meta_value = get_post_meta( $post_id, $meta_key, $single );
		// Kill dupes for singles. Added after finding duplicate date_entered and last_modified dates.
		if ( $single ) {
			$array_value = get_post_meta( $post_id, $meta_key );
			if ( $array_value && 1 < count( $array_value ) ) {
				delete_post_meta( $post_id, $meta_key );
				update_post_meta( $post_id, $meta_key, $meta_value );
			}
		}

		// If key is a number, return a number/zero instead of empty string.
		if ( self::meta_value_is_number( $meta_key ) ) {
			if ( empty( $meta_value ) ) {
				return 0;
			}
			if ( false === strpos( $meta_value ?? '', '.' ) ) {
				return (int) $meta_value;
			}
			return (float) $meta_value;
		}
		return $meta_value;
	}

	/**
	 * Loads phone numbers from post meta into an array.
	 *
	 * @param  int $term_id The location term ID from which to extract phones.
	 * @return array An array of arrays describing phone numbers
	 */
	public static function get_phones( $term_id ) {
		$phones    = array();
		$term_meta = get_term_meta( $term_id );

		for ( $p = 1; $p <= self::LOCATION_MAX_PHONES; $p++ ) {
			// Is there a phone number in this slot?
			if ( empty( $term_meta[ 'phone_' . $p . '_uid' ][0] ) ) {
				// No, we're done with this location.
				break;
			}

			$phones[] = array(
				'uid'         => $term_meta[ 'phone_' . $p . '_uid' ][0],
				'description' => self::meta_array_value_single( $term_meta, 'phone_' . $p . '_description' ),
				'number'      => self::meta_array_value_single( $term_meta, 'phone_' . $p . '_number' ),
			);
		}
		return $phones;
	}

	/**
	 * This is an array of the post meta keys this object uses. These keys
	 * must be prefixed by an apply_filters() call before use.
	 *
	 * @return array An array of the post meta keys this vehicle object uses
	 */
	public static function keys() {
		return array_column( self::keys_and_types(), 'name' );
	}

	/**
	 * Produces an array of arrays that define all the meta fields that
	 * define our vehicle post type for all vehicle types including boats.
	 *
	 * @return array An array of arrays, defining the meta fields that are registered and used by this class.
	 */
	public static function keys_and_types() {
		return apply_filters(
			'invp_meta_fields',
			array(
				array(
					'label'  => __( 'Availability', 'inventory_presser' ),
					'name'   => 'availability',
					'sample' => __( 'For Sale', 'inventory-presser' ),
					'type'   => 'string',
				),
				array(
					'label' => __( 'Beam', 'inventory_presser' ),
					'name'  => 'beam', // for boats.
					'type'  => 'number',
				),
				array(
					'label'  => __( 'Body Style', 'inventory_presser' ),
					'name'   => 'body_style',
					'sample' => __( 'EXTENDED CAB PICKUP', 'inventory-presser' ),
					'type'   => 'string',
				),
				array(
					'label'  => __( 'KBB Book Value', 'inventory_presser' ), // Kelley Blue Book.
					'name'   => 'book_value_kbb',
					'sample' => 13500,
					'type'   => 'number',
				),
				array(
					'label'  => __( 'NADA Book Value', 'inventory_presser' ), // NADA Guides.
					'name'   => 'book_value_nada',
					'sample' => 13500,
					'type'   => 'number',
				),
				array(
					'label' => __( 'Car ID', 'inventory_presser' ),
					'name'  => 'car_id', // unique identifier.
					'type'  => 'integer',
				),
				array(
					'label' => __( 'Carfax Accident Free', 'inventory_presser' ),
					'name'  => 'carfax_accident_free',
					'type'  => 'boolean',
				),
				array(
					'label' => __( 'Carfax Have Report', 'inventory_presser' ),
					'name'  => 'carfax_have_report',
					'type'  => 'boolean',
				),
				array(
					'label' => __( 'Carfax One Owner', 'inventory_presser' ),
					'name'  => 'carfax_one_owner',
					'type'  => 'boolean',
				),
				array(
					'label' => __( 'Carfax Top Condition', 'inventory_presser' ),
					'name'  => 'carfax_top_condition',
					'type'  => 'boolean',
				),
				array(
					'label' => __( 'Carfax Icon URL', 'inventory_presser' ),
					'name'  => 'carfax_url_icon',
					'type'  => 'string',
				),
				array(
					'label' => __( 'Carfax Accident-Free Badge URL', 'inventory_presser' ),
					'name'  => 'carfax_url_icon_accident_free',
					'type'  => 'string',
				),
				array(
					'label' => __( 'Carfax One-Owner Badge URL', 'inventory_presser' ),
					'name'  => 'carfax_url_icon_one_owner',
					'type'  => 'string',
				),
				array(
					'label' => __( 'Carfax Top-Condition Badge URL', 'inventory_presser' ),
					'name'  => 'carfax_url_icon_top_condition',
					'type'  => 'string',
				),
				array(
					'label' => __( 'Carfax Report URL', 'inventory_presser' ),
					'name'  => 'carfax_url_report',
					'type'  => 'string',
				),
				array(
					'label' => __( 'Certified Pre-owned', 'inventory-presser' ),
					'name'  => 'certified_preowned',
					'type'  => 'boolean',
				),
				array(
					'label'  => __( 'Color', 'inventory_presser' ),
					'name'   => 'color',
					'sample' => __( 'Merlot Jewel Metallic', 'inventory-presser' ),
					'type'   => 'string',
				),
				array(
					'label'  => __( 'Base Color', 'inventory_presser' ),
					'name'   => 'color_base',
					'sample' => __( 'Red', 'inventory-presser' ),
					'type'   => 'string',
				),
				array(
					'label'  => __( 'Boat Condition', 'inventory_presser' ),
					'name'   => 'condition_boat', // for boats.
					'sample' => __( 'Excellent', 'inventory-presser' ),
					'type'   => 'string',
				),
				array(
					'label'  => __( 'Condition', 'inventory_presser' ),
					'name'   => 'condition',
					'sample' => __( 'Used', 'inventory-presser' ),
					'type'   => 'string',
				),
				array(
					'label'  => __( 'Cylinders', 'inventory_presser' ),
					'name'   => 'cylinders',
					'sample' => 6,
					'type'   => 'integer',
				),
				array(
					'label'  => __( 'Date Entered', 'inventory_presser' ),
					'name'   => 'date_entered',
					'sample' => 'Mon, 24 Feb 2020 08:17:37 -0500',
					'type'   => 'string',
				),
				array(
					'label' => __( 'Dealer ID', 'inventory_presser' ),
					'name'  => 'dealer_id',
					'type'  => 'integer',
				),
				array(
					'label'  => __( 'Description', 'inventory_presser' ),
					'name'   => 'description',
					'sample' => __( 'Clean, non-smoker, must-see!', 'inventory-presser' ),
					'type'   => 'string',
				),
				array(
					'label'  => __( 'Doors', 'inventory_presser' ),
					'name'   => 'doors',
					'sample' => 4,
					'type'   => 'number',
				),
				array(
					'label'  => __( 'Down Payment', 'inventory_presser' ),
					'name'   => 'down_payment',
					'sample' => 2500,
					'type'   => 'number',
				),
				array(
					'label'  => __( 'Drive Type', 'inventory_presser' ),
					'name'   => 'drive_type',
					'sample' => __( 'Rear Wheel Drive', 'inventory-presser' ),
					'type'   => 'string',
				),
				array(
					'label' => __( 'Edmunds Style ID', 'inventory_presser' ),
					'name'  => 'edmunds_style_id',
					'type'  => 'integer',
				),
				array(
					'label'  => __( 'Engine', 'inventory_presser' ),
					'name'   => 'engine',
					'sample' => __( '3.7L 5 cylinder', 'inventory-presser' ),
					'type'   => 'string',
				),
				array(
					'label'  => __( '# of Engines', 'inventory_presser' ),
					'name'   => 'engine_count', // For boats.
					'sample' => '1',
					'type'   => 'number',
				),
				array(
					'label'  => __( 'Engine Make', 'inventory_presser' ),
					'name'   => 'engine_make', // For boats.
					'sample' => __( 'Yamaha', 'inventory-presser' ),
					'type'   => 'string',
				),
				array(
					'label'  => __( 'Engine Model', 'inventory_presser' ),
					'name'   => 'engine_model', // For boats.
					'sample' => 'F200LB',
					'type'   => 'string',
				),
				array(
					'label' => __( 'Featured', 'inventory_presser' ),
					'name'  => 'featured',
					'type'  => 'boolean',
				),
				array(
					'label'  => __( 'Fuel', 'inventory_presser' ),
					'name'   => 'fuel',
					'sample' => __( 'Gas', 'inventory-presser' ),
					'type'   => 'string',
				),
				array(
					'label'  => __( 'Fuel Economy Name (fuel type 1)', 'inventory_presser' ),
					'name'   => 'fuel_economy_1_name',
					'sample' => __( 'Regular Gasoline', 'inventory-presser' ),
					'type'   => 'string',
				),
				array(
					'label'  => __( 'Fuel Economy Annual Fuel Consumption (fuel type 1)', 'inventory_presser' ),
					'name'   => 'fuel_economy_1_annual_consumption',
					'sample' => __( '13.18 barrels', 'inventory-presser' ),
					'type'   => 'string',
				),
				array(
					'label'  => __( 'Fuel Economy Annual Fuel Cost (fuel type 1)', 'inventory_presser' ),
					'name'   => 'fuel_economy_1_annual_cost',
					'sample' => 1600,
					'type'   => 'number',
				),
				array(
					'label'  => __( 'Fuel Economy Annual Tailpipe CO2 Emissions (fuel type 1)', 'inventory_presser' ),
					'name'   => 'fuel_economy_1_annual_emissions',
					'sample' => __( '355 grams per mile', 'inventory-presser' ),
					'type'   => 'string',
				),
				array(
					'label'  => __( 'Fuel Economy City (fuel type 1)', 'inventory_presser' ),
					'name'   => 'fuel_economy_1_city',
					'sample' => 22,
					'type'   => 'number',
				),
				array(
					'label'  => __( 'Fuel Economy Combined (fuel type 1)', 'inventory_presser' ),
					'name'   => 'fuel_economy_1_combined',
					'sample' => 25,
					'type'   => 'number',
				),
				array(
					'label'  => __( 'Fuel Economy Highway (fuel type 1)', 'inventory_presser' ),
					'name'   => 'fuel_economy_1_highway',
					'sample' => 31,
					'type'   => 'number',
				),
				array(
					'label'  => __( 'Fuel Economy Name (fuel type 2)', 'inventory_presser' ),
					'name'   => 'fuel_economy_2_name',
					'sample' => __( 'Regular Gasoline', 'inventory-presser' ),
					'type'   => 'string',
				),
				array(
					'label'  => __( 'Fuel Economy Annual Fuel Consumption (fuel type 2)', 'inventory_presser' ),
					'name'   => 'fuel_economy_2_annual_consumption',
					'sample' => __( '13.18 barrels', 'inventory-presser' ),
					'type'   => 'string',
				),
				array(
					'label'  => __( 'Fuel Economy Annual Fuel Cost (fuel type 2)', 'inventory_presser' ),
					'name'   => 'fuel_economy_2_annual_cost',
					'sample' => 1600,
					'type'   => 'number',
				),
				array(
					'label'  => __( 'Fuel Economy Annual Tailpipe CO2 Emissions (fuel type 2)', 'inventory_presser' ),
					'name'   => 'fuel_economy_2_annual_emissions',
					'sample' => __( '355 grams per mile', 'inventory-presser' ),
					'type'   => 'string',
				),
				array(
					'label'  => __( 'Fuel Economy City (fuel type 2)', 'inventory_presser' ),
					'name'   => 'fuel_economy_2_city',
					'sample' => 22,
					'type'   => 'number',
				),
				array(
					'label'  => __( 'Fuel Economy Combined (fuel type 2)', 'inventory_presser' ),
					'name'   => 'fuel_economy_2_combined',
					'sample' => 25,
					'type'   => 'number',
				),
				array(
					'label'  => __( 'Fuel Economy Highway (fuel type 2)', 'inventory_presser' ),
					'name'   => 'fuel_economy_2_highway',
					'sample' => 31,
					'type'   => 'number',
				),
				array(
					'label'  => __( 'Fuel Economy Five Year Savings', 'inventory_presser' ),
					'name'   => 'fuel_economy_five_year_savings',
					'sample' => 2250,
					'type'   => 'number',
				),
				array(
					'label' => __( 'Horsepower', 'inventory_presser' ),
					'name'  => 'horsepower', // for boats.
					'type'  => 'number',
				),
				array(
					'label' => __( 'Hull Material', 'inventory_presser' ),
					'name'  => 'hull_material', // for boats.
					'type'  => 'string',
				),
				array(
					'label'  => __( 'Interior Color', 'inventory_presser' ),
					'name'   => 'interior_color',
					'sample' => __( 'Black', 'inventory-presser' ),
					'type'   => 'string',
				),
				array(
					'label'  => __( 'Last Modified', 'inventory_presser' ),
					'name'   => 'last_modified',
					'sample' => 'Mon, 24 Feb 2020 08:17:37 -0500',
					'type'   => 'string',
				),
				array(
					'label' => __( 'Leads ID', 'inventory_presser' ),
					'name'  => 'leads_id', // dealer id that receives leads.
					'type'  => 'integer',
				),
				array(
					'label' => __( 'Length', 'inventory_presser' ),
					'name'  => 'length', // for boats.
					'type'  => 'integer',
				),
				array(
					'label'  => __( 'Location', 'inventory_presser' ),
					'name'   => 'location',
					'sample' => __( '120 Mall Blvd', 'inventory-presser' ),
					'type'   => 'string',
				),
				array(
					'label'  => __( 'Make', 'inventory_presser' ),
					'name'   => 'make',
					'sample' => __( 'GMC', 'inventory-presser' ),
					'type'   => 'string',
				),
				array(
					'label'  => __( 'Max Draft', 'inventory_presser' ),
					'name'   => 'draft', // for boats.
					'sample' => '1\' 0"',
					'type'   => 'string',
				),
				array(
					'label'  => __( 'Model', 'inventory_presser' ),
					'name'   => 'model',
					'sample' => 'Canyon',
					'type'   => 'string',
				),
				array(
					'label'  => __( 'MSRP', 'inventory_presser' ),
					'name'   => 'msrp',
					'sample' => 23905,
					'type'   => 'number',
				),
				array(
					'label' => __( 'NextGear Inspection URL', 'inventory_presser' ),
					'name'  => 'nextgear_inspection_url',
					'type'  => 'string',
				),
				array(
					'label'  => __( 'Odometer', 'inventory_presser' ), // Do not filter this one. It causes infinite loops.
					'name'   => 'odometer',
					'sample' => '102000',
					'type'   => 'string',
				),
				array(
					'label'  => __( 'Options', 'inventory_presser' ),
					'name'   => 'options_array',
					'sample' => __( 'Heated Seats', 'inventory-presser' ),
					'type'   => 'string',
				),
				array(
					'label'  => __( 'Payment', 'inventory_presser' ),
					'name'   => 'payment',
					'sample' => 200,
					'type'   => 'number',
				),
				array(
					'label'  => __( 'Payment Frequency', 'inventory_presser' ),
					'name'   => 'payment_frequency',
					'sample' => __( 'monthly', 'inventory-presser' ),
					'type'   => 'string',
				),
				array(
					'label'  => __( 'Price', 'inventory_presser' ),
					'name'   => 'price',
					'sample' => 13499,
					'type'   => 'number',
				),
				array(
					'label'  => __( 'Propulsion Type', 'inventory_presser' ),
					'name'   => 'propulsion_type',
					'sample' => __( 'Outboard', 'inventory-presser' ),
					'type'   => 'string',
				),
				array(
					'label'  => __( 'Rate', 'inventory_presser' ),
					'name'   => 'rate',
					'sample' => 6.99,
					'type'   => 'number',
				),
				array(
					'label'  => __( 'Stock Number', 'inventory_presser' ),
					'name'   => 'stock_number',
					'sample' => '147907',
					'type'   => 'string',
				),
				array(
					'label'  => __( 'Term', 'inventory_presser' ),
					'name'   => 'term',
					'sample' => 72,
					'type'   => 'number',
				),
				array(
					'label'  => __( 'Title Status', 'inventory_presser' ),
					'name'   => 'title_status',
					'sample' => __( 'Clean', 'inventory-presser' ),
					'type'   => 'string',
				),
				array(
					'label'  => __( 'Transmission', 'inventory_presser' ),
					'name'   => 'transmission',
					'sample' => __( 'Automatic', 'inventory-presser' ),
					'type'   => 'string',
				),
				array(
					'label'  => __( 'Transmission Speeds', 'inventory_presser' ),
					'name'   => 'transmission_speeds',
					'sample' => '4',
					'type'   => 'string',
				),
				array(
					'label'  => __( 'Trim Level', 'inventory_presser' ),
					'name'   => 'trim',
					'sample' => __( 'SLE-1 Ext. Cab 4WD', 'inventory-presser' ),
					'type'   => 'string',
				),
				array(
					'label'  => __( 'Type', 'inventory_presser' ),
					'name'   => 'type',
					'sample' => __( 'Passenger Car', 'inventory-presser' ),
					'type'   => 'string',
				),
				array(
					'label'  => __( 'VIN', 'inventory_presser' ),
					'name'   => 'vin',
					'sample' => '1GTKTCDE1A8147907',
					'type'   => 'string',
				),
				array(
					'label' => __( 'Is Wholesale', 'inventory_presser' ),
					'name'  => 'wholesale',
					'type'  => 'boolean',
				),
				array(
					'label'  => __( 'Year', 'inventory_presser' ),
					'name'   => 'year',
					'sample' => 2010,
					'type'   => 'integer',
				),
				array(
					'label'  => __( 'YouTube Video ID', 'inventory_presser' ),
					'name'   => 'youtube',
					'sample' => '9pBPgt4VOzM',
					'type'   => 'string',
				),
				array(
					'label'  => __( 'YouTube Embed HTML', 'inventory_presser' ),
					'name'   => 'youtube_embed',
					'sample' => '<iframe width="560" height="315" src="https://www.youtube.com/embed/RLx1rqlmcY4?si=6dLw2OZxR3UA2h4b" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>',
					'type'   => 'string',
				),
			)
		);
	}

	/**
	 * Populates the site with vehicles.
	 *
	 * @return void
	 */
	public static function load_sample_vehicles() {
		$response = wp_remote_get( 'https://demo.inventorypresser.com/wp-json/wp/v2/inventory?orderby=rand' );
		if ( is_wp_error( $response ) ) {
			return;
		}
		$vehicles = json_decode( wp_remote_retrieve_body( $response ) );
		shuffle( $vehicles );
		$current_user_id = wp_get_current_user()->ID;

		// Do not insert duplicates.
		global $wpdb;
		/**
		 * Each photo has a VIN meta key, so this might return hundreds of
		 * duplicate VINs on just a few vehicles with double digit photos each.
		 */
		$current_vins = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT meta_value FROM $wpdb->postmeta WHERE meta_key = %s",
				apply_filters( 'invp_prefix_meta_key', 'vin' )
			)
		);

		foreach ( $vehicles as $vehicle ) {
			// Obscure 17 digit VINs.
			$vin = '';
			if ( 17 === strlen( $vehicle->meta->inventory_presser_vin ) ) {
				$vin                                  = $vehicle->meta->inventory_presser_vin;
				$vehicle->meta->inventory_presser_vin = substr( $vehicle->meta->inventory_presser_vin, 0, 10 ) . 'EXAMPLE';
			}

			// Is this VIN already in inventory?
			if ( in_array( $vehicle->meta->inventory_presser_vin, $current_vins, true ) ) {
				continue;
			}

			$options = $vehicle->meta->inventory_presser_options_array;
			unset( $vehicle->meta->inventory_presser_options_array );
			// Remove dealer-specific data.
			unset( $vehicle->meta->inventory_presser_carfax_url_icon );
			unset( $vehicle->meta->inventory_presser_carfax_url_icon_accident_free );
			unset( $vehicle->meta->inventory_presser_carfax_url_icon_one_owner );
			unset( $vehicle->meta->inventory_presser_carfax_url_icon_top_condition );
			unset( $vehicle->meta->inventory_presser_carfax_url_report );
			unset( $vehicle->meta->inventory_presser_dealer_id );
			unset( $vehicle->meta->inventory_presser_leads_id );
			unset( $vehicle->meta->inventory_presser_location );
			unset( $vehicle->meta->inventory_presser_nextgear_inspection_url );

			$post = array(
				'post_author'       => $current_user_id,
				'post_date'         => $vehicle->date,
				'post_date_gmt'     => $vehicle->date_gmt,
				'post_content'      => $vehicle->content->rendered,
				'post_title'        => $vehicle->title->rendered,
				'post_status'       => 'publish',
				'comment_status'    => 'closed',
				'ping_status'       => 'closed',
				'post_name'         => $vehicle->slug,
				'post_modified'     => $vehicle->modified,
				'post_modified_gmt' => $vehicle->modified_gmt,
				'post_parent'       => 0,
				'post_type'         => $vehicle->type,
				'meta_input'        => (array) $vehicle->meta,
			);
			$id   = wp_insert_post( $post );
			foreach ( $options as $option ) {
				add_post_meta( $id, apply_filters( 'invp_prefix_meta_key', 'options_array' ), $option );
			}

			$endpoint_media = add_query_arg(
				array(
					'orderby' => apply_filters( 'invp_prefix_meta_key', 'photo_number' ),
					'order'   => 'asc',
				),
				$vehicle->_links->{'wp:attachment'}[0]->href
			);
			$response       = wp_remote_get( $endpoint_media );
			if ( is_wp_error( $response ) ) {
				continue;
			}

			$media = json_decode( wp_remote_retrieve_body( $response ) );
			if ( ! is_array( $media ) ) {
				continue;
			}
			$media_count = count( $media );
			for ( $m = 0; $m < $media_count; $m++ ) {
				// Download a copy of the image.
				$media_url = strtok( $media[ $m ]->source_url, '?' );
				$tmp_file  = download_url( $media_url );
				if ( is_wp_error( $tmp_file ) ) {
					continue;
				}
				// Is the VIN also in the file name?
				$file_name  = str_ireplace( $vin, $vehicle->meta->inventory_presser_vin, basename( $media_url ) );
				$upload_dir = wp_upload_dir();
				$file_name  = wp_unique_filename( $upload_dir['path'], $file_name );
				// Move the photo from a temp file to the uploads directory.
				$path = $upload_dir['path'] . DIRECTORY_SEPARATOR . $file_name;
				rename( $tmp_file, $path );
				$media_id = wp_insert_attachment(
					array(
						'post_author'       => $current_user_id,
						'post_date'         => $media[ $m ]->date,
						'post_date_gmt'     => $media[ $m ]->date_gmt,
						'post_title'        => str_ireplace( $vin, $vehicle->meta->inventory_presser_vin, $media[ $m ]->title->rendered ),
						'post_status'       => $media[ $m ]->status,
						'comment_status'    => 'closed',
						'ping_status'       => 'closed',
						'post_name'         => str_replace( $vin, $vehicle->meta->inventory_presser_vin, $media[ $m ]->slug ),
						'post_modified'     => $media[ $m ]->modified,
						'post_modified_gmt' => $media[ $m ]->modified_gmt,
						'post_type'         => $media[ $m ]->type,
						'meta_input'        => (array) $media[ $m ]->meta,
						'post_mime_type'    => $media[ $m ]->mime_type,
					),
					$path,
					$id
				);
				wp_update_attachment_metadata( $media_id, wp_generate_attachment_metadata( $media_id, $path ) );

				// Is this the first image?
				if ( 0 === $m ) {
					set_post_thumbnail( $id, $media_id );
				}
			}
		}
	}


	/**
	 * Given a $meta array collection of a post's entire meta data, retrieves
	 * the single or first value stored in $key.
	 *
	 * @param  array  $meta
	 * @param  string $key  A meta key.
	 * @return string|bool A single meta value or false if the value does not exist
	 */
	protected static function meta_array_value_single( $meta, $key ) {
		return isset( $meta[ $key ][0] ) ? $meta[ $key ][0] : false;
	}

	/**
	 * Returns the prefix added to all vehicle post meta keys.
	 *
	 * @return string The prefix added to all vehicle post meta keys
	 */
	public static function meta_prefix() {
		return apply_filters( 'invp_meta_prefix', 'inventory_presser_' );
	}

	/**
	 * Indicates whether or not a provided $post_meta_key is a number data
	 * type.
	 *
	 * @param  string $post_meta_key
	 * @return bool True if the given $post_meta_key is a number data type.
	 */
	public static function meta_value_is_number( $post_meta_key ) {
		foreach ( self::keys_and_types() as $key_and_type ) {
			if ( apply_filters( 'invp_prefix_meta_key', $key_and_type['name'] ?? '' ) === $post_meta_key ) {
				return 'number' === $key_and_type['type'] ?? '' || 'integer' === $key_and_type['type'] ?? '';
			}
		}
		return false;
	}

	/**
	 * Provides the option group string that is needed for register_setting()
	 * calls.
	 *
	 * @return string
	 */
	public static function option_group() {
		return apply_filters( 'invp_option_group', 'dealership_options_option_group' );
	}

	/**
	 * Provides the option page string that determines where the settings
	 * sections are rendered.
	 *
	 * @return string
	 */
	public static function option_page() {
		return apply_filters( 'invp_option_page', self::OPTION_PAGE . '-admin' );
	}

	/**
	 * Creates HTML to help navigate listings pages. "Older" and "Newer" don't
	 * make sense. Returns HTML that contains a string like "Showing 11 to 20 of
	 * 64 vehicles".
	 *
	 * @return string
	 */
	public static function get_paging_html() {
		$pagination_html = '';

		// previous page link.
		$previous_link = get_previous_posts_link();
		if ( ! empty( $previous_link ) ) {
			$pagination_html .= '<li class="prev left">' . $previous_link . '</li>';
		}

		// clickable page numbers.
		$navigation = get_the_posts_pagination(
			array(
				'mid_size'  => 2,
				'prev_next' => false,
			)
		);
		if ( ! empty( $navigation ) ) {
			$pagination_html .= sprintf(
				'<li>%s</li>',
				$navigation
			);
		}

		// next page link.
		$next_link = get_next_posts_link();
		if ( ! empty( $next_link ) ) {
			$pagination_html .= '<li class="next right">' . $next_link . '</li>';
		}

		// sentence "Showing 1 to 10 of 99 posts".
		global $wp_query;
		$posts_per_page = $wp_query->get( 'posts_per_page', get_option( 'posts_per_page' ) );
		$page_number    = $wp_query->get( 'paged', 1 );
		if ( 0 === $page_number ) {
			// Added this condition for Divi Blog Module because I guess it sets paged = 0.
			$page_number = 1;
		}
		$start_index = $page_number * $posts_per_page - ( $posts_per_page - 1 );
		$end_index   = min( array( $start_index + $posts_per_page - 1, $wp_query->found_posts ) );

		$object_name    = 'posts';
		$post_type_name = $wp_query->get( 'post_type', '' );
		if ( '' !== $post_type_name ) {
			$post_type   = get_post_type_object( $post_type_name );
			$object_name = strtolower( $post_type->labels->name );
		}

		if ( ! empty( $pagination_html ) ) {
			$pagination_html = '<ul class="group">' . $pagination_html . '</ul>';
		}

		$pagination_html .= '<p>' . apply_filters(
			'invp_pagination_sentence',
			sprintf(
				'%s %d %s %d %s %d %s',
				__( 'Showing', 'inventory-presser' ),
				$start_index,
				__( 'to', 'inventory-presser' ),
				$end_index,
				__( 'of', 'inventory-presser' ),
				$wp_query->found_posts,
				$object_name
			)
		)
		. '</p>';

		return '<nav class="invp-pagination pagination group">'
		. apply_filters( 'invp_pagination_html', $pagination_html )
		. '</nav>';
	}

	/**
	 * Takes a phone number and prepares it for the href attribute of a link.
	 *
	 * Removes all non-alphanumeric characters, converts letters to dial pad
	 * digits, and prepends a country dialing code if the number is 10 digits or
	 * less.
	 *
	 * @param  string $number The phone number to prepare.
	 * @return string A version of the phone number provided with letters converted to dial pad digits, non-numeric characters removed, and a country code prepended if the length of the provided number was 10 or less.
	 */
	public static function prepare_phone_number_for_link( $number ) {
		// get rid of anything that isn't a digit or a letter.
		$number = preg_replace( '/[^0-9A-Za-z]+/', '', $number );

		// convert letters to digits.
		$number = preg_replace( '/a|b|c/', '2', strtolower( $number ) );
		$number = preg_replace( '/d|e|f/', '3', strtolower( $number ) );
		$number = preg_replace( '/g|h|i/', '4', strtolower( $number ) );
		$number = preg_replace( '/j|k|l/', '5', strtolower( $number ) );
		$number = preg_replace( '/m|n|o/', '6', strtolower( $number ) );
		$number = preg_replace( '/p|q|r|s/', '7', strtolower( $number ) );
		$number = preg_replace( '/t|u|v/', '8', strtolower( $number ) );
		$number = preg_replace( '/w|x|y|z/', '9', strtolower( $number ) );

		// does it have a country code already?
		if ( 10 < strlen( $number ) ) {
			// yes.
			return $number;
		}

		// no, default to USA.
		$country_code = apply_filters( 'invp_country_calling_code', '1' );
		return $country_code . $number;
	}

	/**
	 * Get this plugin's option mingled with default values.
	 *
	 * @return array An associative array containing this plugin's settings
	 */
	public static function settings() {
		$defaults = array(
			'include_sold_vehicles'       => false,
			'provide_templates'           => true,
			'skip_trash'                  => true,
			'sort_vehicles_by'            => 'make',
			'sort_vehicles_order'         => 'ASC',
			'use_arranger_gallery'        => true,
			'use_carfax'                  => false,
			'use_carfax_provided_buttons' => true,
		);
		$settings = wp_parse_args( get_option( self::OPTION_NAME ), $defaults );

		/**
		 * If the taxonomies settings has never been used, grab defaults so we
		 * don't break functionality.
		 */
		if ( ! isset( $settings['taxonomies'] ) ) {
			if ( ! class_exists( 'Inventory_Presser_Admin_Options' ) ) {
				include_once plugin_dir_path( INVP_PLUGIN_FILE_PATH ) . 'includes/admin/class-admin-options.php';
			}
			$settings['taxonomies'] = Inventory_Presser_Admin_Options::taxonomies_setting_default( $settings );
		}
		return $settings;
	}

	/**
	 * Turns the name of something into a slug that WordPress will accept when
	 * creating objects like terms. WordPress slugs are described as containing
	 * only letters, numbers, and hyphens.
	 *
	 * @param  string $name The string to turn into a slug.
	 * @return string An alteration of $name that WordPress will accept as a term slug
	 */
	public static function sluggify( $name ) {
		if ( null === $name ) {
			return '';
		}
		$name = trim( preg_replace( '/[^a-zA-Z0-9\\- ]/', '', $name ) );
		$name = str_replace( '/', '-', str_replace( ' ', '-', $name ) );
		return strtolower( str_replace( '--', '-', str_replace( '---', '-', $name ) ) );
	}

	/**
	 * Prefixes our post meta field keys so 'make' becomes
	 * 'inventory_presser_make'. Careful to not prefix a key that has
	 * already been prefixed.
	 *
	 * @param  string $nice_name The unprefixed meta key.
	 * @return string The prefixed meta key.
	 */
	public static function translate_custom_field_names( $nice_name ) {
		$nice_name = strtolower( $nice_name );
		$prefix    = self::meta_prefix();

		if ( substr( $nice_name, 0, strlen( $prefix ) ) === $prefix ) {
			return $nice_name;
		}
		return $prefix . $nice_name;
	}

	/**
	 * Removes the prefix from our post meta field keys so
	 * 'inventory_presser_make' becomes 'make'. Careful to not damage any
	 * provided key that does not start with our prefix.
	 *
	 * @param  string $meta_key The prefixed meta key.
	 * @return string The un-prefixed meta key.
	 */
	public static function untranslate_custom_field_names( $meta_key ) {
		if ( empty( $meta_key ) ) {
			return '';
		}
		$meta_key = strtolower( $meta_key );
		// prefix may start with an underscore because previous versions hid some meta keys.
		$prefix = ( '_' === $meta_key[0] ? '_' : '' ) . self::meta_prefix();

		// does $meta_key actually start with the $prefix?
		if ( substr( $meta_key, 0, strlen( $prefix ) ) === $prefix ) {
			// remove the prefix.
			return substr( $meta_key, strlen( $prefix ) );
		}

		return $meta_key;
	}

	/**
	 * If no parameter is provided, returns an array containing lowercase
	 * weekdays as keys and title case, three-letter abbreviations as values.
	 * If a valid parameter is provided, returns the lowercase day name
	 * it identifies. If an invalid parameter is provided, returns false.
	 *
	 * @param  int $zero_through_six A number between 0 and 6 to identify a single day to return as a string.
	 * @return string|Array|false
	 */
	public static function weekdays( $zero_through_six = null ) {
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
		if ( null !== $zero_through_six ) {
			if ( 0 <= $zero_through_six && 6 >= $zero_through_six ) {
				return array_keys( $days )[ $zero_through_six ];
			} else {
				// If an invalid parameter is provided, return false.
				return false;
			}
		}

		return $days;
	}

	/**
	 * This is a wrapper for wp_count_posts() and counts all vehicle posts.
	 *
	 * @return int
	 */
	public static function vehicle_count() {
		$vehicle_counts = wp_count_posts( self::POST_TYPE );
		if ( empty( $vehicle_counts->publish ) ) {
			return 0;
		}
		return $vehicle_counts->publish;
	}
}
