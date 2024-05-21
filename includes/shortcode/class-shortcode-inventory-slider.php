<?php
defined( 'ABSPATH' ) || exit;

/**
 * Inventory_Presser_Shortcode_Slider
 *
 * Creates a shortcode that produces vehicle photo sliders based on FlexSlider.
 */
class Inventory_Presser_Shortcode_Slider {

	/**
	 * Adds two shortcodes
	 *
	 * @return void
	 */
	public function add() {
		add_shortcode( 'invp-inventory-slider', array( $this, 'content' ) );
		add_shortcode( 'invp_inventory_slider', array( $this, 'content' ) );
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
	 * Creates the HTML content of the shortcode
	 *
	 * @param  array $atts
	 * @return string HTML that renders a vehicle photo flexslider
	 */
	public function content( $atts ) {
		$atts = shortcode_atts(
			array(
				'captions'  => 'true',
				'make'      => '',
				'model'     => '',
				'orderby'   => 'rand',
				'order'     => 'ASC',
				'showcount' => 3, // How many vehicles are shown at one time?
			),
			$atts,
			'inventory_slider'
		); // Use shortcode_atts_inventory_slider to filter the incoming attributes.

		// Parse boolean values to make life easy on users.
		$atts['captions'] = filter_var( $atts['captions'], FILTER_VALIDATE_BOOLEAN );

		// Get the vehicle IDs and loop over them.
		$inventory_ids = self::get_vehicle_IDs( $atts );
		if ( empty( $inventory_ids ) ) {
			return '';
		}

		if ( ! wp_script_is( 'invp-slider', 'registered' ) ) {
			Inventory_Presser_Plugin::include_scripts_and_styles();
		}
		// Need flexslider for this content.
		wp_enqueue_style( 'flexslider' );
		wp_enqueue_style( 'invp-flexslider' );
		wp_enqueue_style( 'invp-slider' );
		// Provide one of the widget settings to JavaScript.
		wp_add_inline_script(
			'invp-slider',
			'const widget_slider = ' . wp_json_encode(
				array(
					'showcount' => $atts['showcount'],
				)
			),
			'before'
		);
		wp_enqueue_script( 'invp-slider' );

		$flex_html = '<div class="widget__invp_slick"><div id="slider-width"></div><div id="widget_slider" class="flexslider flex-native">'
		. '<ul class="slides">';

		foreach ( $inventory_ids as $inventory_id ) {
			$flex_html .= sprintf(
				'<li><a class="flex-link" href="%s">'
				. '%s',
				get_the_permalink( $inventory_id ),
				get_the_post_thumbnail( $inventory_id, 'large' )
			);

			if ( $atts['captions'] ) {
				$flex_html .= sprintf(
					'<p class="flex-caption">%s</p>',
					get_the_title( $inventory_id )
				);
			}

			$flex_html .= '</a></li>';
		}

		return $flex_html . '</ul></div></div>';
	}

	protected static function add_make_and_model_query_args( $query_args, $shortcode_atts ) {
		// Make filter.
		if ( ! empty( $shortcode_atts['make'] ) ) {
			$query_args['tax_query'] = array(
				array(
					'taxonomy' => 'make',
					'field'    => 'slug',
					'terms'    => $shortcode_atts['make'],
				),
			);
		}

		// Model filter.
		if ( ! empty( $shortcode_atts['model'] ) ) {
			$query_args['meta_query'][] = array(
				'key'     => apply_filters( 'invp_prefix_meta_key', 'model' ),
				'value'   => $shortcode_atts['model'],
				'compare' => 'LIKE',
			);
		}

		return $query_args;
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

		$inventory_ids = get_posts( self::add_make_and_model_query_args( $gpargs, $shortcode_atts ) );

		// If we found less than 10 vehicles, do not required featured vehicles.
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

			$inventory_ids += get_posts( self::add_make_and_model_query_args( $gpargs, $shortcode_atts ) );
		}

		if ( ! $inventory_ids ) {
			return array();
		}

		shuffle( $inventory_ids );

		return $inventory_ids;
	}
}
