<?php
defined( 'ABSPATH' ) || exit;

/**
 * Inventory_Presser_Shortcode_Archive
 *
 * A shortcode that allows themes that do not provide a content-archive template
 * to show a vehicle archive.
 */
class Inventory_Presser_Shortcode_Archive {


	/**
	 * Adds two shortcodes
	 *
	 * @return void
	 */
	public function add() {
		add_shortcode( 'invp-archive', array( $this, 'content' ) );
		add_shortcode( 'invp_archive', array( $this, 'content' ) );
	}

	/**
	 * Adds hooks that power the shortcode
	 *
	 * @return void
	 */
	public function add_hooks() {
		add_action( 'init', array( $this, 'add' ) );
	}

	/**
	 * Removes shortcode attributes from the attributes array that are not also
	 * query parameters for a posts query.
	 *
	 * @param  array $atts
	 * @return array
	 */
	private function clean_attributes_for_query( $atts ) {
		unset( $atts['show_titles'] );

		// Turn the location attribute into a tax_query.
		if ( ! empty( $atts['location'] ) ) {
			$atts['tax_query'] = array(
				array(
					'taxonomy' => 'location',
					'field'    => 'slug',
					'terms'    => $atts['location'],
				),
			);
		}
		return $atts;
	}

	/**
	 * Creates the HTML content of the shortcode
	 *
	 * @param  array $atts
	 * @return string HTML that renders an archive-vehicle template
	 */
	public function content( $atts ) {
		if ( ! wp_style_is( 'invp-attribute-table', 'registered' ) ) {
			Inventory_Presser_Plugin::include_scripts_and_styles();
		}
		wp_enqueue_style( 'invp-attribute-table' );
		wp_enqueue_style( 'invp_archive_vehicle' );

		$atts = shortcode_atts(
			array(
				'location'       => '',
				'order'          => get_query_var( 'order' ),
				'paged'          => ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1,
				'posts_per_page' => get_option( 'posts_per_page' ),
				'post_status'    => 'publish',
				'show_titles'    => true,
				'style'          => 'a',
			),
			$atts,
			'invp_archive'
		);

		// Allow orderby and order $_GET parameters to change the sort order.
		$orderby = get_query_var( 'orderby' );
		if ( '' !== $orderby && 'meta_value' !== $orderby ) {
			$atts['meta_key'] = apply_filters( 'invp_prefix_meta_key', $orderby );
			$atts['orderby']  = 'meta_value';
		}

		// Parse boolean values to make life easy on users.
		$atts['show_titles'] = filter_var( $atts['show_titles'], FILTER_VALIDATE_BOOLEAN );
		$atts['post_type']   = INVP::POST_TYPE;

		// Ensure style is "a" or "b".
		$atts['style'] = in_array( $atts['style'], array( 'a', 'b' ), true ) ? $atts['style'] : 'a';

		// Add all taxonomy query vars to $atts so filters work.
		$taxonomies = get_object_taxonomies( $atts['post_type'], 'objects' );
		foreach ( $taxonomies as $taxonomy ) {
			$query_value = get_query_var( $taxonomy->query_var );
			if ( '' !== $query_value ) {
				$atts[ $taxonomy->query_var ] = $query_value;
			}
		}

		// Query vehicles.
		add_filter( 'invp_range_filters_main_query', '__return_false' );
		$vehicles_query = new WP_Query( $this->clean_attributes_for_query( $atts ) );
		remove_filter( 'invp_range_filters_main_query', '__return_false' );

		// Create the HTML output.
		$have_posts = $vehicles_query->have_posts();
		$output     = '' . apply_filters( '', 'invp_archive_shortcode_before', $have_posts, $atts );
		if ( $have_posts ) {
			while ( $vehicles_query->have_posts() ) {
				$vehicles_query->the_post();
				$shortcode = sprintf( '[invp_archive_vehicle show_titles="%s" style="%s"]', strval( $atts['show_titles'] ), $atts['style'] );
				$output   .= apply_shortcodes( $shortcode );
			}

			/**
			 * Paged navigation. Overwrite the global query with this shortcode's
			 * query so the core pagination functions work as expected. The
			 * query is rest on the following line outside this condition block.
			 */
			global $wp_query;
			$wp_query = $vehicles_query;
			$output  .= INVP::get_paging_html();
		} else {
			/**
			 * Do not encourage the user to search if there are zero vehicles.
			 * This query could be a filtered result that's empty, so check
			 * explicitly.
			 */
			$vehicle_count = INVP::vehicle_count();
			if ( 0 < $vehicle_count ) {
				$output .= apply_filters(
					'invp_archive_shortcode_no_results',
					sprintf(
						'<p>%s</p><h2>%s</h2><p>%s</p>',
						esc_html__( 'No vehicles found.', 'inventory-presser' ),
						esc_html__( 'Search Inventory', 'inventory-presser' ),
						apply_filters( 'invp_archive_shortcode_no_results_search', get_search_form() )
					)
				);
			}
		}

		// Restore original query & post data.
		wp_reset_query();

		return $output . apply_filters( '', 'invp_archive_shortcode_after', $have_posts, $atts );
	}
}
