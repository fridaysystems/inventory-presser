<?php
/**
 * Avada
 *
 * @package inventory-presser
 * @since      14.16.0
 * @subpackage inventory-presser/includes/integrations
 * @author     Corey Salzano <corey@friday.systems>
 */

defined( 'ABSPATH' ) || exit;

/**
 * Inventory_Presser_Avada
 *
 * Integrates with Avada Builder. Enhances the way Inventory Presser custom
 * fields appear in Avada Builder Text Blocks. Adds all vehicle photos to
 * Featured Images Slider.
 */
class Inventory_Presser_Avada {

	/**
	 * Adds hooks that change the way Avada works.
	 *
	 * @return void
	 */
	public function add_hooks() {
		// Lie to Avada, tell it all attached photos are featured images.
		add_filter( 'fusion_get_all_meta', array( $this, 'avada_featured_images' ), 10, 2 );

		// Change the way some of our meta fields are displayed in Text Block layout elements.
		add_filter( 'fusion_shortcode_content', array( $this, 'avada_text_meta' ), 11, 3 );
	}

	/**
	 * Filter the meta value for vehicle posts that lists the featured images. Lie
	 * to Avada, tell it all attached images are featured images.
	 *
	 * @param  array $meta All the post-meta for the current post.
	 * @param  int   $post_id The post ID.
	 * @return array
	 */
	public function avada_featured_images( $meta, $post_id ) {
		if ( ! class_exists( 'INVP' ) ) {
			return $meta;
		}
		// Is $post_id a vehicle?
		if ( INVP::POST_TYPE !== get_post_type( $post_id ) ) {
			// No.
			return $meta;
		}

		// Photo ordering isn't working for some reason.
		$images = get_posts(
			array(
				'fields'         => 'ids',
				'posts_per_page' => apply_filters( 'invp_query_limit', 1000, __FUNCTION__ ),
				'post_mime_type' => 'image',
				'post_parent'    => $post_id,
				'post_status'    => 'inherit',
				'post_type'      => 'attachment',
			)
		);

		if ( empty( $images ) ) {
			return $meta;
		}
		$meta['_thumbnail_id'] = $images[0];
		$image_count           = count( $images );
		for ( $i = 1; $i < $image_count; $i++ ) {
			if ( ! isset( $images[ $i ] ) ) {
				break;
			}
			$meta[ 'kd_featured-image-' . $i . '_' . INVP::POST_TYPE . '_id' ] = $images[ $i ];
		}
		return $meta;
	}

	/**
	 * Filters the Text Block content and look for our meta keys in the args.
	 *
	 * @param  string $content Content between shortcode.
	 * @param  string $shortcode Shortcode handle.
	 * @param  array  $args Shortcode parameters.
	 * @return string
	 */
	public function avada_text_meta( $content, $shortcode, $args ) {
		if ( ! class_exists( 'INVP' ) ) {
			return $content;
		}
		if ( 'fusion_text' !== $shortcode ) {
			return $content;
		}
		if ( empty( $args['dynamic_params'] ) ) {
			return $content;
		}
		// Avada Builder uses base64 encoding to pass parameters around.
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		$params = json_decode( base64_decode( $args['dynamic_params'], true ) );

		// Does this element display a meta field?
		if ( empty( $params->element_content->key ) ) {
			// No.
			return $content;
		}

		// Is this a custom meta key belonging to Inventory Presser?
		$keys = INVP::keys();
		if ( ! in_array( apply_filters( 'invp_unprefix_meta_key', $params->element_content->key ), $keys, true ) ) {
			// No.
			return $content;
		}

		// Which one?
		$value = '';
		switch ( apply_filters( 'invp_unprefix_meta_key', $params->element_content->key ) ) {

			// Odometer.
			case 'odometer':
				$value = $params->element_content->before . invp_get_the_odometer( ' ' . apply_filters( 'invp_odometer_word', 'mi' ) ) . $params->element_content->after;
				break;

			// Options.
			case 'options_array':
				$options_html = '';
				foreach ( invp_get_the_options() as $option ) {
					$options_html .= sprintf( '<li>%s</li>', $option );
				}
				$value = sprintf(
					'%s<ul class="vehicle-features">%s</ul>%s',
					$params->element_content->before,
					$options_html,
					$params->element_content->after
				);
				break;

			// Price.
			case 'price':
				$value = $params->element_content->before . invp_get_the_price() . $params->element_content->after;
				break;
			default:
				$value = $content;
		}
		if ( empty( $value ) ) {
			$value = $params->element_content->before . $params->element_content->fallback . $params->element_content->after;
		}
		return $value;
	}
}
