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
	public function add_hooks() {
		add_action( 'init', array( $this, 'add' ) );
	}

	/**
	 * Creates the HTML content of the shortcode
	 *
	 * @param  array $atts Shortcode attributes array.
	 * @return string HTML that renders a vehicle photo flexslider
	 */
	public function content( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'               => null,
				'include_carousel' => true,
			),
			$atts,
			'photo_slider'
		); // Use shortcode_atts_inventory_slider to filter the incoming attributes.

		// Parse boolean values to make life easy on users.
		$atts['include_carousel'] = filter_var( $atts['include_carousel'], FILTER_VALIDATE_BOOLEAN );

		// Need flexslider for this content.
		wp_enqueue_style( 'flexslider' );
		wp_enqueue_style( 'invp-flexslider' );
		wp_enqueue_script( 'invp-flexslider', '', array(), false, true );

		// Was the post ID passed in as an argument?
		if ( ! empty( $atts['id'] ) ) {
			$post_id = intval( $atts['id'] );
		} else {
			$post_id = get_the_ID();
		}

		$image_url_lists = invp_get_the_photos( array( 'full', 'large', 'thumb' ), $post_id );

		ob_start();
		?>
		<div id="slider-width"></div>
		<div id="slider" class="flexslider">
			<ul class="slides">
			<?php

			if ( isset( $image_url_lists['large'] ) ) {
				$image_count = count( $image_url_lists['large'] );
				for ( $p = 0; $p < $image_count; $p++ ) {
					// Inventory Presser versions 8.1.0 and above provide the 'urls'.
					if ( isset( $image_url_lists['urls'][ $p ] ) ) {
						printf(
							'<li><a data-href="%s">%s</a></li>',
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
