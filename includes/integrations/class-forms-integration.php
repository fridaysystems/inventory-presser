<?php
defined( 'ABSPATH' ) || exit;

/**
 * Inventory_Presser_Forms_Integration
 */
abstract class Inventory_Presser_Forms_Integration {
	/**
	 * Given a submitted form value like "2020 Toyota Sienna LE, 10329A",
	 * find and return the vehicle's post ID.
	 *
	 * @param  string $value An <option> value of a drop down containing vehicles in lead form.
	 * @return int|false
	 */
	protected function extract_post_id_from_value( $value ) {
		// submitted "2020 Toyota Sienna LE, 10329A".
		$pieces = explode( ', ', $value );
		if ( 1 === count( $pieces ) ) {
			// delimiter not found.
			return false;
		}
		$stock_number = $pieces[ count( $pieces ) - 1 ];
		$post_ids     = get_posts(
			array(
				'fields'         => 'ids',
				'meta_key'       => apply_filters( 'invp_prefix_meta_key', 'stock_number' ),
				'meta_value'     => $stock_number,
				'post_status'    => 'publish',
				'post_type'      => INVP::POST_TYPE,
				'posts_per_page' => 1,
			)
		);

		if ( empty( $post_ids ) || ! is_array( $post_ids ) ) {
			return false;
		}

		return $post_ids[0];
	}
}
