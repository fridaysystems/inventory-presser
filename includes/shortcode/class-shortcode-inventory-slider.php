<?php
defined( 'ABSPATH' ) || exit;

/**
 * Inventory_Presser_Shortcode_Slider
 *
 * Creates a shortcode that produces vehicle photo sliders based on FlexSlider.
 */
class Inventory_Presser_Shortcode_Slider {

	/**
	 * add
	 *
	 * Adds two shortcodes
	 *
	 * @return void
	 */
	function add() {
		add_shortcode( 'invp-inventory-slider', array( $this, 'content' ) );
		add_shortcode( 'invp_inventory_slider', array( $this, 'content' ) );
	}

	/**
	 * hooks
	 *
	 * Adds hooks that power the shortcode
	 *
	 * @return void
	 */
	public function add_hooks() {
		add_action( 'init', array( $this, 'add' ) );
	}

	/**
	 * content
	 *
	 * Creates the HTML content of the shortcode
	 *
	 * @param  array $atts
	 * @return string HTML that renders a vehicle photo flexslider
	 */
	function content( $atts ) {

		// Canvass shortcode attributes
		$atts = shortcode_atts(
			array(
				'per_page' => 10,
				'captions' => 'true',
				'orderby'  => 'rand',
				'order'    => 'ASC',
			),
			$atts,
			'inventory_slider'
		); // Use shortcode_atts_inventory_slider to filter the incoming attributes

		// Parse boolean values to make life easy on users.
		$atts['captions'] = filter_var( $atts['captions'], FILTER_VALIDATE_BOOLEAN );

		// Get the vehicle IDs and loop over them
		$inventory_ids = self::get_vehicle_IDs( $atts );
		if ( empty( $inventory_ids ) ) {
			return '';
		}

		// Need flexslider for this content
		wp_enqueue_script( 'invp-flexslider' );
		wp_enqueue_style( 'invp-flexslider' );

		$flex_html = '<div class="flexslider flex-native">'
		. '<ul class="slides">';

		foreach ( $inventory_ids as $inventory_id ) {
			$flex_html .= sprintf(
				'<li><a class="flex-link" href="%s">'
				. '<div class="grid-image" style="background-image: url(\'%s\');">'
				. '</div>',
				get_the_permalink( $inventory_id ),
				invp_get_the_photo_url( 'large', $inventory_id )
			);

			if ( $atts['captions'] ) {
				$flex_html .= sprintf(
					'<p class="flex-caption">%s</p>',
					get_the_title( $inventory_id )
				);
			}

			$flex_html .= '</a></li>';
		}

		return $flex_html . '</ul></div>';
	}

	public static function get_vehicle_IDs( $shortcode_atts ) {
		$gpargs = array(
			'posts_per_page' => 10,
			'post_type'      => INVP::POST_TYPE,
			'meta_query'     => array(
				'relation' => 'AND',
				array(
					'key'     => apply_filters( 'invp_prefix_meta_key', 'featured' ),
					'value'   => 1,
					'compare' => '=',
				),
				array(
					'key'     => '_thumbnail_id',
					'compare' => 'EXISTS',
				),
			),
			'fields'         => 'ids',
			'orderby'        => $shortcode_atts['orderby'],
			'order'          => $shortcode_atts['order'],
		);

		$inventory_ids = get_posts( $gpargs );

		if ( count( $inventory_ids ) < 10 ) {

			$gpargs = array(
				'posts_per_page' => 10 - ( count( $inventory_ids ) ),
				'post_type'      => INVP::POST_TYPE,
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'relation' => 'OR',
						array(
							'key'     => apply_filters( 'invp_prefix_meta_key', 'featured' ),
							'value'   => array( '', '0' ),
							'compare' => 'IN',
						),
						array(
							'key'     => apply_filters( 'invp_prefix_meta_key', 'featured' ),
							'compare' => 'NOT EXISTS',
						),
					),
					array(
						'key'     => '_thumbnail_id',
						'compare' => 'EXISTS',
					),
				),
				'fields'         => 'ids',
				'orderby'        => $shortcode_atts['orderby'],
				'order'          => $shortcode_atts['order'],
			);

			$inventory_ids += get_posts( $gpargs );
		}

		if ( ! $inventory_ids ) {
			return array();
		}

		shuffle( $inventory_ids );

		return $inventory_ids;
	}
}
