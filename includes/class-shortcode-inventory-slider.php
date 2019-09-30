<?php
defined( 'ABSPATH' ) or exit;

class Inventory_Presser_Shortcode_Slider {

	function hooks() {
		add_shortcode( 'invp-inventory-slider', array( $this, 'content') );
	}

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
			'post_type'   => Inventory_Presser_Plugin::CUSTOM_POST_TYPE,
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
				'post_type'   => Inventory_Presser_Plugin::CUSTOM_POST_TYPE,
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
				$vehicle->url,
				wp_get_attachment_image_url( get_post_thumbnail_id( $inventory_id ), 'large')
			);

			if( $atts['captions'] ) {
				$flex_html .= sprintf(
					'<p class="flex-caption">%s</p>',
					$vehicle->post_title
				);
			}

			$flex_html .= '</a></li>';
		}

		return $flex_html . '</ul></div>';
	}
}
