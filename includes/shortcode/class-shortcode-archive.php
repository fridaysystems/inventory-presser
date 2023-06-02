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
		return $atts;
	}

	/**
	 * Creates the HTML content of the shortcode
	 *
	 * @param  array $atts
	 * @return string HTML that renders an archive-vehicle template
	 */
	public function content( $atts ) {
		wp_enqueue_style( 'invp-attribute-table' );
		wp_enqueue_style( 'invp_archive_vehicle' );

		$atts = shortcode_atts(
			array(
				'paged'          => ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1,
				'posts_per_page' => get_option( 'posts_per_page' ),
				'post_status'    => 'publish',
				'show_titles'    => true,
			),
			$atts
		);

		// Parse boolean values to make life easy on users.
		$atts['show_titles'] = filter_var( $atts['show_titles'], FILTER_VALIDATE_BOOLEAN );
		$atts['post_type']   = INVP::POST_TYPE;

		// Add all taxonomy query vars to $atts so filters work.
		$taxonomies = get_object_taxonomies( $atts['post_type'], 'objects' );
		foreach ( $taxonomies as $taxonomy ) {
			$atts[ $taxonomy->query_var ] = get_query_var( $taxonomy->query_var );
		}

		/**
		 * Handle querystring filters min_price, max_price, and max_odometer.
		 * This array $querystring_filters has no significance other than
		 * allowing the foreach loop below to handle 3 parameters similarly.
		 */
		$querystring_filters = array(
			array(
				'param'    => 'min_price', // querystring parameter name.
				'field'    => 'price', // meta field suffix.
				'operator' => '>=', // comparison operator.
			),
			array(
				'param'    => 'max_price',
				'field'    => 'price',
				'operator' => '<=',
			),
			array(
				'param'    => 'max_odometer',
				'field'    => 'odometer',
				'operator' => '<=',
			),
		);

		foreach ( $querystring_filters as $arr ) {
			// Do we even have the querystring parameter?
			if ( empty( $_GET[ $arr['param'] ] ) ) {
				continue;
			}

			$atts['meta_query'] = Inventory_Presser_Plugin::maybe_add_meta_query(
				$atts['meta_query'],
				apply_filters( 'invp_prefix_meta_key', $arr['field'] ),
				(int) $_GET[ $arr['param'] ],
				$arr['operator'],
				'numeric'
			);
			if ( ! empty( $atts['meta_key'] ) ) {
				unset( $atts['meta_key'] );
			}
		}

		// Allow our order by mods to affect this query_posts() call.
		add_filter( 'invp_apply_orderby_to_main_query_only', '__return_false' );
		query_posts( $this->clean_attributes_for_query( $atts ) );
		remove_filter( 'invp_apply_orderby_to_main_query_only', '__return_false' );

		$output = '';
		if ( have_posts() ) {
			while ( have_posts() ) {
				the_post();
				$shortcode = sprintf( '[invp_archive_vehicle show_titles="%s"]', strval( $atts['show_titles'] ) );
				$output   .= apply_shortcodes( $shortcode );
			}
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
						__( 'No vehicles found.', 'inventory-presser' ),
						__( 'Search Inventory', 'inventory-presser' ),
						apply_filters( 'invp_archive_shortcode_no_results_search', get_search_form() )
					)
				);
			}
		}

		// Paged navigation.
		$output .= INVP::get_paging_html();

		wp_reset_query();
		return $output;
	}
}
