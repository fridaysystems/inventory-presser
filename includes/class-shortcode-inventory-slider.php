<?php
defined( 'ABSPATH' ) or exit;

/**
 * Inventory_Presser_Shortcode_Slider
 * 
 * This class creates a shortcode to make adding vehicle photo sliders easy
 */
class Inventory_Presser_Shortcode_Slider
{
	/**
	 * add
	 * 
	 * Adds two shortcodes
	 *
	 * @return void
	 */
	function add()
	{
		add_shortcode( 'invp-inventory-slider', array( $this, 'content') );
		add_shortcode( 'invp_inventory_slider', array( $this, 'content') );
	}

	/**
	 * hooks
	 * 
	 * Adds hooks that power the shortcode
	 *
	 * @return void
	 */
	function hooks()
	{
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

		//Need flexslider for this content
		wp_enqueue_script( 'invp-flexslider' );
		wp_enqueue_style( 'invp-flexslider' );

		// process shortcode attributes
		$atts = shortcode_atts( array(
			'per_page' => 10,
			'captions' => 'true',
			'orderby'  => 'rand',
			'order'    => 'ASC',
		), $atts, 'inventory_slider' );

		//Use shortcode_atts_inventory_slider to filter the incoming attributes

		$atts['captions'] = 'true' === $atts['captions'];

		$gpargs = array(
			'posts_per_page' => 10,
			'post_type'   => INVP::POST_TYPE,
			'meta_query'  => array(
				'relation' => 'AND',
				array(
					'key'     => apply_filters( 'invp_prefix_meta_key', 'featured' ),
					'value'   => 1,
					'compare' => '=',
				),
				array(
					'key'	  => '_thumbnail_id',
					'compare' => 'EXISTS',
				)
			),
			'fields'  => 'ids',
			'orderby' => $atts['orderby'],
			'order'   => $atts['order'],
		);

		$inventory_ids = get_posts($gpargs);

		if (count($inventory_ids) < 10) {

			$gpargs = array(
				'posts_per_page' => 10 - (count($inventory_ids)),
				'post_type'   => INVP::POST_TYPE,
				'meta_query'  => array(
					'relation' => 'AND',
					array(
						'relation' => 'OR',
						array(
							'key'     => apply_filters( 'invp_prefix_meta_key', 'featured' ),
							'value'   => array( '', '0'),
							'compare' => 'IN'
						),
						array(
							'key'     => apply_filters( 'invp_prefix_meta_key', 'featured' ),
							'compare' => 'NOT EXISTS',
						),
					),
					array(
						'key'	  => '_thumbnail_id',
						'compare' => 'EXISTS'
					)
				),
				'fields'  => 'ids',
				'orderby' => $atts['orderby'],
				'order'   => $atts['order'],
			);

			$inventory_ids += get_posts($gpargs);
		}

		if( ! $inventory_ids ) {
			return '';
		}

		shuffle( $inventory_ids );

		$flex_html = '<div class="flexslider flex-native">'
			. '<ul class="slides">';

		foreach( $inventory_ids as $inventory_id ) {

			$vehicle = new Inventory_Presser_Vehicle( $inventory_id );

			$flex_html .= sprintf(
				'<li><a class="flex-link" href="%s">'
				. '<div class="grid-image" style="background-image: url(\'%s\');">'
				. '</div>',
				get_the_permalink( $inventory_id ),
				invp_get_the_photo_url( 'large', $inventory_id )
			);

			if( $atts['captions'] ) {
				$flex_html .= sprintf(
					'<p class="flex-caption">%s</p>',
					get_the_title( $inventory_id )
				);
			}

			$flex_html .= '</a></li>';
		}

		return $flex_html . '</ul></div>';
	}
}
