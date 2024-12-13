<?php
/**
 * WP All Import integration.
 *
 * @package inventory-presser
 * @author Corey Salzano <corey@friday.systems>
 */

defined( 'ABSPATH' ) || exit;

/**
 * Inventory_Presser_WP_All_Import
 *
 * Helps vehicle imports using WP All Import Pro by detecting piped options and
 * saving them as individual options.
 */
class Inventory_Presser_WP_All_Import {

	/**
	 * Adds hooks.
	 *
	 * @return void
	 */
	public function add_hooks() {
		// Help save options in a multi-valued meta field.
		add_action( 'pmxi_update_post_meta', array( $this, 'detect_delimited_options' ), 10, 3 );

		// Mark imported vehicles as "For Sale" in the Availability taxonomy.
		add_action( 'pmxi_saved_post', array( $this, 'set_availability_for_sale' ), 10, 1 );

		// Number photos when they are uploaded.
		add_action( 'pmxi_gallery_image', array( $this, 'number_photos' ), 10, 2 );

		add_action( 'pmxi_before_post_import', array( $this, 'do_not_renumber_on_save' ) );
	}

	/**
	 * Disable a feature that renumbers photos during vehicle saves.
	 *
	 * @return void
	 */
	public function do_not_renumber_on_save() {
		remove_action( 'save_post_' . INVP::POST_TYPE, array( 'Inventory_Presser_Photo_Numberer', 'renumber_photos' ), 10, 1 );
	}

	/**
	 * If the value being saved to the meta key inventory_presser_options_array
	 * contains pipe-delimited or comma-delimited values, split the string and
	 * add each option individually.
	 *
	 * @param  mixed $post_id ID of the post whose meta was updated.
	 * @param  mixed $meta_key The meta key that has been updated.
	 * @param  mixed $meta_value The new meta value.
	 * @return void
	 */
	public function detect_delimited_options( $post_id, $meta_key, $meta_value ) {
		// Is it a vehicle?
		if ( ! class_exists( 'INVP' ) || INVP::POST_TYPE !== get_post_type( $post_id ) ) {
			// No.
			return;
		}

		if ( apply_filters( 'invp_prefix_meta_key', 'options_array' ) !== $meta_key ) {
			return;
		}

		if ( empty( $meta_value ) ) {
			return;
		}

		// Are there lots of commas or pipes in the value?
		$delimiters = array(
			'|' => substr_count( $meta_value ?? '', '|' ),
			',' => substr_count( $meta_value ?? '', ',' ),
			';' => substr_count( $meta_value ?? '', ';' ),
		);
		$delimiters = array_filter(
			$delimiters,
			function ( $value ) {
				return $value > 1;
			}
		);

		if ( empty( $delimiters ) ) {
			// No.
			return;
		}

		$found_delimiter = array_search( max( $delimiters ), $delimiters, true );
		if ( false === $found_delimiter ) {
			// No.
			return;
		}

		// Repeating delimiter found. Erase the current value.
		delete_post_meta( $post_id, $meta_key );

		// Add each option individually, options_array is a multi-meta value.
		foreach ( array_filter( explode( $found_delimiter, $meta_value ) ) as $option ) {
			if ( '' === trim( $option ) ) {
				continue;
			}
			add_post_meta( $post_id, $meta_key, trim( $option ) );
		}
	}

	/**
	 * Add a sequence number and other meta to vehicle photos.
	 *
	 * @param  int $post_id The post ID that's being imported.
	 * @param  int $attachment_id The attachment ID.
	 * @return void
	 */
	public function number_photos( $post_id, $attachment_id ) {
		// The maybe_number_photo() method checks if the parent is a vehicle.
		Inventory_Presser_Photo_Numberer::maybe_number_photo( $attachment_id );
	}

	/**
	 * Not all feed imports have a for sale/sold bit that matches up nicely with
	 * our Availability taxonomy. Mark vehicles as for sale if no relationship
	 * exists in the taxonomy.
	 *
	 * @param  int $post_id The inserted or updated post ID.
	 * @return void
	 */
	public function set_availability_for_sale( $post_id ) {
		// Is it a vehicle?
		if ( ! class_exists( 'INVP' ) || INVP::POST_TYPE !== get_post_type( $post_id ) ) {
			// No.
			return;
		}

		$taxonomy = 'availability';
		if ( ! empty( wp_get_object_terms( $post_id, $taxonomy ) ) ) {
			// There is already a relationship.
			return;
		}

		// Do we have a For Sale term?
		$term = get_term_by( 'slug', 'for-sale', $taxonomy );
		if ( $term ) {
			// Yes, create the relationship.
			wp_set_object_terms( $post_id, $term->term_id, $taxonomy );
		}
	}
}
