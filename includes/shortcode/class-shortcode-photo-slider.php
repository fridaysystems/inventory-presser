<?php
defined( 'ABSPATH' ) || exit;

/**
 * Inventory_Presser_Shortcode_Photo_Slider
 *
 * Creates a shortcode that produces a photo slider for a single vehicle.
 */
class Inventory_Presser_Shortcode_Photo_Slider {

	/**
	 * add
	 *
	 * Adds two shortcodes
	 *
	 * @return void
	 */
	function add() {
		add_shortcode( 'invp-photo-slider', array( $this, 'content' ) );
		add_shortcode( 'invp_photo_slider', array( $this, 'content' ) );
	}

	/**
	 * hooks
	 *
	 * Adds hooks that power the shortcode
	 *
	 * @return void
	 */
	function hooks() {
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
				'include_carousel' => true,
			),
			$atts,
			'photo_slider'
		); // Use shortcode_atts_inventory_slider to filter the incoming attributes

		if ( is_string( $atts['include_carousel'] ) ) {
			$atts['include_carousel'] = 'true' === $atts['include_carousel'];
		}

		// Need flexslider for this content
		wp_enqueue_style( 'flexslider' );
		wp_enqueue_style( 'invp-flexslider' );
		wp_enqueue_script( 'invp-flexslider', '', array(), false, true );

		$image_url_lists = invp_get_the_photos( array( 'large', 'thumb' ) );

		ob_start();
		?>
		<div id="slider-width"></div>
		<div id="slider" class="flexslider">
			<ul class="slides">
			<?php

			if ( isset( $image_url_lists['large'] ) ) {
				for ( $p = 0; $p < sizeof( $image_url_lists['large'] ); $p++ ) {
					// Inventory Presser versions 8.1.0 and above provide the 'urls'
					if ( isset( $image_url_lists['urls'][ $p ] ) ) {
						printf(
							'<li><a href="%s">%s</a></li>',
							$image_url_lists['urls'][ $p ],
							$image_url_lists['large'][ $p ]
						);
					} else {
						printf(
							'<li>%s</li>',
							$image_url_lists['large'][ $p ]
						);
					}
				}
			}
			?>
			</ul>
		</div>
		<?php

		if ( $atts['include_carousel'] && isset( $image_url_lists['thumb'] ) && count( $image_url_lists['thumb'] ) > 1 ) {
			?>
			<div id="carousel" class="flexslider no-preview">
				<ul class="slides">
				<?php

				foreach ( $image_url_lists['thumb'] as $image ) {
						  printf( '<li>%s</li>', $image );
				}

				?>
				</ul>
			</div>
			<?php
		}

		return ob_get_clean();
	}
}
