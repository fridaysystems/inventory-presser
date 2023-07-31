<?php
defined( 'ABSPATH' ) || exit;

/**
 * Inventory_Presser_WP_All_Import
 *
 * Helps vehicle imports using WP All Import Pro by detecting piped options and
 * saving them as individual options.
 */
class Inventory_Presser_WP_All_Import {

	public function add_hooks() {
		// Help save options in a multi-valued meta field
		add_action( 'pmxi_update_post_meta', array( $this, 'detect_piped_options' ), 10, 3 );

		// Mark imported vehicles as "For Sale" in the Availability taxonomy
		add_action( 'pmxi_saved_post', array( $this, 'set_availability_for_sale' ), 10, 3 );
	}

	/**
	 * detect_piped_options
	 *
	 * If the value being saved to the meta key inventory_presser_options_array
	 * contains a pipe, split the string on pipe and add each option
	 * individually.
	 *
	 * @param  int    $post_id
	 * @param  string $meta_key
	 * @param  mixed  $meta_value
	 * @return void
	 */
	public function detect_piped_options( $post_id, $meta_key, $meta_value ) {
		if ( ! class_exists( 'INVP' ) || INVP::POST_TYPE != get_post_type( $post_id ) ) {
			return;
		}

		if ( apply_filters( 'invp_prefix_meta_key', 'options_array' ) != $meta_key ) {
			return;
		}

		// Are there even pipes in the value?
		if ( false === strpos( $meta_value ?? '', '|' ) ) {
			return;
		}

		// Erase the current value
		delete_post_meta( $post_id, $meta_key );

		// Add each option individually, options_array is a multi-meta value
		foreach ( explode( '|', $meta_value ) as $option ) {
			add_post_meta( $post_id, $meta_key, $option );
		}
	}

	/**
	 * set_availability_for_sale
	 *
	 * Not all feed imports have a for sale/sold bit that matches up nicely with
	 * our Availability taxonomy. Mark vehicles as for sale if no relationship
	 * exists in the taxonomy.
	 *
	 * @param  int              $post_id
	 * @param  SimpleXMLElement $xml_node
	 * @param  bool             $is_update
	 * @return void
	 */
	public function set_availability_for_sale( $post_id, $xml_node, $is_update ) {
		$taxonomy = 'availability';
		if ( ! empty( wp_get_object_terms( $post_id, $taxonomy ) ) ) {
			// There is already a relationship
			return;
		}

		// Do we have a For Sale term?
		$term = get_term_by( 'slug', 'for-sale', $taxonomy );
		if ( $term ) {
			// Yes, create the relationship
			wp_set_object_terms( $post_id, $term->term_id, $taxonomy );
		}
	}
}
