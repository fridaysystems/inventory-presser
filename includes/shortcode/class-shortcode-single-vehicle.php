<?php
defined( 'ABSPATH' ) || exit;

/**
 * Inventory_Presser_Shortcode_Single_Vehicle
 *
 * A shortcode that creates a content-single template for the vehicle post type.
 */
class Inventory_Presser_Shortcode_Single_Vehicle {

	/**
	 * Adds two shortcodes
	 *
	 * @return void
	 */
	public function add() {
		add_shortcode( 'invp-single-vehicle', array( $this, 'content' ) );
		add_shortcode( 'invp_single_vehicle', array( $this, 'content' ) );
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
	 * @return string HTML that renders a vehicle single template
	 */
	public function content( $atts ) {
		if ( ! is_singular( INVP::POST_TYPE ) ) {
			return '';
		}

		$image_url_lists = invp_get_the_photos( array( 'full', 'large', 'thumb' ) );

		wp_enqueue_style( 'invp-attribute-table' );
		wp_enqueue_style( 'flexslider' );
		wp_enqueue_style( 'invp-flexslider' );
		wp_enqueue_style( 'invp_single_vehicle' );
		wp_enqueue_script( 'invp-flexslider', '', array(), INVP_PLUGIN_VERSION, true );

		ob_start();

		?><div class="vehicle-info">

			<div class="post-inner">

				<div class="post-thumbnail">
					<div class="vehicle-content">
						<h2 class="post-title vehicle-price">
						<?php
								echo invp_get_the_price();
						?>
						</h2>

						<?php
						// if dealership has multiple locations, display the location of this vehicle.
						$location_sentence = invp_get_the_location_sentence();
						if ( ! empty( $location_sentence ) ) {
							printf(
								'<div class="vehicle-location">%s</div>',
								$location_sentence
							);
						}

						?>
					</div>
					<div class="vehicle-images">
						<div id="slider-width"></div>
						<div id="slider" class="flexslider">
							<ul class="slides">
							<?php

							if ( isset( $image_url_lists['large'] ) ) {
								$photo_count = count( $image_url_lists['large'] );
								for ( $p = 0; $p < $photo_count; $p++ ) {
									// Inventory Presser versions 8.1.0 and above provide the 'urls'.
									if ( isset( $image_url_lists['urls'][ $p ] ) ) {
										printf(
											'<li><a data-href="%s">%s</a></li>',
											esc_attr( $image_url_lists['urls'][ $p ] ),
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

						if ( isset( $image_url_lists['thumb'] ) && count( $image_url_lists['thumb'] ) > 1 ) {
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
						?>
					</div>
				</div><!--/.post-thumbnail-->

				<div class="vehicle-columns">
				<?php

					$attribute_table = apply_shortcodes( '[invp_attribute_table]' );
				if ( ! empty( $attribute_table ) ) {

					?>
						<div class="vehicle-summary">
						<?php

						echo $attribute_table;

						?>
					</div>
					<?php

				}

				?>
					<div class="vehicle-buttons">
					<?php

						do_action( 'invp_single_buttons' );

					?>
					</div>
				</div>

				<div class="vehicle-content">
				<?php

					$sections = array();

					$description = invp_get_the_description();
				if ( ! empty( $description ) ) {
					$sections['description'] = sprintf(
						'<h2 class="vehicle-content-wrap">%s</h2><div class="vehicle-content-wrap">%s</div>',
						__( 'Description', 'inventory-presser' ),
						wpautop( $description )
					);
				}

					// if there's a youtube video associated with this vehicle, embed it.
				if ( invp_get_the_youtube_url() ) {
					$sections['youtube'] = wp_oembed_get( invp_get_the_youtube_url() );
				}

					$options_array = invp_get_the_options();
				if ( ! empty( $options_array ) ) {
					// loop through list of vehicle options.
					$options_html = '';
					foreach ( invp_get_the_options() as $option ) {
						$options_html .= sprintf( '<li>%s</li>', $option );
					}

					$sections['options'] = sprintf(
						'<h2 class="vehicle-features">%s</h2><ul class="vehicle-features">%s</ul>',
						__( 'Options', 'inventory-presser' ),
						$options_html
					);
				}

					$sections = apply_filters( 'invp_single_sections', $sections );

					array_walk(
						$sections,
						function ( $value ) {
							echo $value;
						}
					);

				?>
				</div>
			</div><!--/.post-inner-->
		</div>
		<?php

		return ob_get_clean();
	}
}
