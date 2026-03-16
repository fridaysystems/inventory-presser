<?php
defined( 'ABSPATH' ) || exit;

/**
 * Inventory_Presser_Shortcode_Slider
 *
 * Creates a shortcode that produces vehicle photo sliders based on FlexSlider.
 */
class Inventory_Presser_Shortcode_Slider {

	/**
	 * Counter for generating unique slider IDs
	 *
	 * @var int
	 */
	private static $slider_counter = 0;

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
				'type'      => '',
				'condition' => '',
				'orderby'   => 'rand',
				'order'     => 'ASC',
				'showcount' => 3, // How many vehicles are shown at one time?
			),
			$atts,
			'invp_inventory_slider'
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

		// Generate unique IDs for this slider instance.
		++self::$slider_counter;
		$slider_id        = 'invp-slider-' . self::$slider_counter;
		$slider_width_id  = 'slider-width-' . self::$slider_counter;
		$widget_slider_id = 'widget_slider-' . self::$slider_counter;

		// Enqueue the script first so inline scripts can be attached.
		wp_enqueue_script( 'invp-slider' );

		// Store slider configuration in a global object for JavaScript.
		// Initialize the object only on the first shortcode instance.
		if ( 1 === self::$slider_counter ) {
			wp_add_inline_script(
				'invp-slider',
				'if (typeof window.invpSliderConfigs === "undefined") { window.invpSliderConfigs = {}; }',
				'before'
			);
		}

		// Add this slider's configuration.
		wp_add_inline_script(
			'invp-slider',
			'window.invpSliderConfigs["' . esc_js( $slider_id ) . '"] = ' . wp_json_encode(
				array(
					'showcount' => $atts['showcount'],
					'sliderId'  => $slider_id,
				)
			) . ';',
			'before'
		);

		$flex_html = '<div class="widget__invp_slick" data-slider-id="' . esc_attr( $slider_id ) . '">'
		. '<div id="' . esc_attr( $slider_width_id ) . '" class="slider-width"></div>'
		. '<div id="' . esc_attr( $widget_slider_id ) . '" class="flexslider flex-native">'
		. '<ul class="slides">';

		foreach ( $inventory_ids as $inventory_id ) {
			$flex_html .= sprintf(
				'<li><a class="flex-link" href="%s">'
				. '%s',
				esc_url( get_the_permalink( $inventory_id ) ),
				get_the_post_thumbnail( $inventory_id, 'large' )
			);

			if ( $atts['captions'] ) {
				$flex_html .= sprintf(
					'<p class="flex-caption">%s</p>',
					esc_html( get_the_title( $inventory_id ) )
				);
			}

			$flex_html .= '</a></li>';
		}

		return $flex_html . '</ul></div></div>';
	}

	protected static function add_make_and_model_query_args( $query_args, $shortcode_atts ) {
		// Ensure meta_query exists and is an array.
		if ( ! isset( $query_args['meta_query'] ) || ! is_array( $query_args['meta_query'] ) ) {
			$query_args['meta_query'] = array();
		}

		// Ensure tax_query exists and is an array.
		if ( ! isset( $query_args['tax_query'] ) || ! is_array( $query_args['tax_query'] ) ) {
			$query_args['tax_query'] = array();
		}

		// Make filter.
		if ( ! empty( $shortcode_atts['make'] ) ) {
			$query_args['tax_query'][] = array(
				'taxonomy' => 'make',
				'field'    => 'slug',
				'terms'    => $shortcode_atts['make'],
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

		// Type filter - type is a taxonomy, not a meta field.
		if ( ! empty( $shortcode_atts['type'] ) ) {
			// Support multiple types separated by commas.
			$type_terms = $shortcode_atts['type'];
			if ( is_string( $type_terms ) && strpos( $type_terms, ',' ) !== false ) {
				// Split comma-separated values and trim whitespace.
				$type_terms = array_map( 'trim', explode( ',', $type_terms ) );
			}
			// Ensure it's an array for consistent handling.
			if ( ! is_array( $type_terms ) ) {
				$type_terms = array( $type_terms );
			}
			// Convert all to lowercase.
			$type_terms = array_map( 'strtolower', $type_terms );

			$query_args['tax_query'][] = array(
				'taxonomy' => 'type',
				'field'    => 'slug',
				'terms'    => $type_terms,
			);
		}

		// Condition filter.
		if ( ! empty( $shortcode_atts['condition'] ) ) {
			$query_args['meta_query'][] = array(
				'key'     => apply_filters( 'invp_prefix_meta_key', 'condition' ),
				'value'   => $shortcode_atts['condition'],
				'compare' => '=',
			);
		}

		return $query_args;
	}

	public static function get_vehicle_IDs( $shortcode_atts ) {
		// Check if any filters are specified.
		$has_filters = ! empty( $shortcode_atts['make'] )
			|| ! empty( $shortcode_atts['model'] )
			|| ! empty( $shortcode_atts['type'] )
			|| ! empty( $shortcode_atts['condition'] );

		// Base query arguments.
		$gpargs = array(
			'posts_per_page' => 10,
			'post_type'      => INVP::POST_TYPE,
			'meta_query'     => array(
				'relation' => 'AND',
				array(
					'key'     => '_thumbnail_id',
					'compare' => 'EXISTS',
				),
			),
			'fields'         => 'ids',
			'orderby'        => $shortcode_atts['orderby'],
			'order'          => $shortcode_atts['order'],
		);

		// Only require featured vehicles if no filters are specified.
		if ( ! $has_filters ) {
			$gpargs['meta_query'][] = array(
				'key'     => apply_filters( 'invp_prefix_meta_key', 'featured' ),
				'value'   => 1,
				'compare' => '=',
			);
		}

		// Add filter arguments (make, model, type, condition).
		$gpargs = self::add_make_and_model_query_args( $gpargs, $shortcode_atts );

		$inventory_ids = get_posts( $gpargs );

		// If we found less than 10 vehicles and no filters were specified, try non-featured vehicles.
		if ( count( $inventory_ids ) < 10 && ! $has_filters ) {
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
