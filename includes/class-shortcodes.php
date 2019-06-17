<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Creates shortcodes to make designing pages easier.
 *
 *
 * @since      2.3.2
 * @package    Inventory_Presser
 * @subpackage Inventory_Presser/includes
 * @author     Corey Salzano <corey@friday.systems>
 */

class Inventory_Presser_Shortcodes {

	function hooks() {

		add_shortcode( 'invp-simple-listing', array( $this, 'simple_listing') );
		add_shortcode( 'invp-inventory-slider', array( $this, 'inventory_slider') );
		add_shortcode( 'invp-inventory-grid', array( $this, 'inventory_grid') );
		add_shortcode( 'iframe', array( $this, 'iframe_embed_shortcode') );

		add_action( 'wp_enqueue_scripts', array( $this, 'load_scripts') );
		add_action( 'wp_ajax_get_simple_listing', array( $this, 'simple_json') );
		add_action( 'wp_ajax_nopriv_get_simple_listing', array( $this, 'simple_json') );
		add_filter( 'the_content', array( $this, 'filter_single_content') );
	}

	function load_scripts() {

		$handle = 'font-awesome';
		if( ! wp_style_is( $handle, 'enqueued' ) ) {
			wp_enqueue_style(
				$handle,
				'//maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css'
			);
		}

		global $post;
		if( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'invp-simple-listing') || is_post_type_archive( Inventory_Presser_Plugin::CUSTOM_POST_TYPE )) {
			wp_enqueue_style('invp-simple-listing-style', plugins_url('/css/invp-simple-listing.css', dirname(__FILE__)));
		}

		wp_register_script('flexslider', plugins_url('/js/jquery.flexslider.min.js', dirname(__FILE__)), array('jquery'));
		wp_register_script('invp-simple-listing', plugins_url('/js/invp-simple-listing.js', dirname(__FILE__)), array('flexslider'));

		if (is_singular( Inventory_Presser_Plugin::CUSTOM_POST_TYPE ) && !file_exists(get_stylesheet_directory().'/single-' . Inventory_Presser_Plugin::CUSTOM_POST_TYPE . '.php')) {

			// generate an array of all featured image size urls.  This will be used by jquery to remove all instances
			// of a featured post image in a vehicle page, in the case that a featured image is displayed on a single page

			$image_urls = array();

			$featured_image_id = get_post_thumbnail_id($post->ID);
			if ($featured_image_id) {
				$sizes = get_intermediate_image_sizes();
				$sizes[] = 'full';
				foreach ($sizes as $size) {
					$image_src = wp_get_attachment_image_src($featured_image_id, $size );
					$image_urls[] = $image_src[0];
				}
			}

			wp_enqueue_script( 'flexslider' );
			wp_enqueue_script( 'invp-simple-listing' );
			wp_localize_script( 'invp-simple-listing', 'invp_options', array(
				'is_archive'          => false,
				'is_singular'         => true,
				'featured_image_urls' => array_unique($image_urls)
			) );
		}

	}

	function inventory_slider($atts) {
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
			'numberposts' => 10,
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
				'numberposts' => 10 - (count($inventory_ids)),
				'post_type'   => Inventory_Presser_Plugin::CUSTOM_POST_TYPE,
				'meta_query'  => array(
					'relation' => 'AND',
					array(
						'key'     => apply_filters( 'invp_prefix_meta_key', 'featured' ),
						'value' => array( '', '0'),
						'compare' => 'IN'
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
			error_log( 'Shortcode [invp-inventory-slider] found no vehicles.' );
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

	function inventory_grid( $atts ) {
		// process shortcode attributes
		$atts = shortcode_atts( array(
			'per_page'   => 15,
			'captions'   => 'true',
			'button'     => 'true',
			'show_price' => 'false',
			'size'       => 'one-third',
		), $atts );

		$atts['captions'] = 'true' === $atts['captions'];
		$atts['button'] = 'true' === $atts['button'];
		$atts['show_price'] = 'true' === $atts['show_price'];

		$args = array(
			'posts_per_page' => $atts['per_page'],
			'post_type'      => Inventory_Presser_Plugin::CUSTOM_POST_TYPE,
			'meta_key'       => '_thumbnail_id',
			'fields'         => 'ids',
			'orderby'        => 'rand',
			'order'          => 'ASC'
		);

		$inventory_ids = get_posts( $args );

		$grid_html = '';

		if ( $inventory_ids ) {

			$grid_html .= '<div class="invp-grid pad cf">'
				. '<ul class="grid-slides">';

			foreach( $inventory_ids as $inventory_id ) {

				$vehicle = new Inventory_Presser_Vehicle( $inventory_id );

				$grid_html .= sprintf(
					'<li class="grid %s"><a class="grid-link" href="%s">'
					. '<div class="grid-image" style="background-image: url(\'%s\');">'
					. '</div>',
					$atts['size'],
					$vehicle->url,
					wp_get_attachment_image_url( get_post_thumbnail_id( $inventory_id ), 'large' )
				);

				if ( $atts['captions'] ) {
					$grid_html .= '<p class="grid-caption">'
						. $vehicle->post_title;
					if ( $atts['show_price'] ) {
						$grid_html .= '&nbsp;&nbsp;' . $vehicle->price(' ');
					}
					$grid_html .= '</p>';
				}

				$grid_html .= '</a></li>';

			}

			$grid_html .= '</ul><div class="clear"></div></div>';
			if ( $atts['button'] ) {
				$grid_html .= sprintf(
					'<a href="%s" class="_button _button-med">Full Inventory</a>',
					get_post_type_archive_link( Inventory_Presser_Plugin::CUSTOM_POST_TYPE )
				);
			}

		}
		return $grid_html;
	}

	function simple_listing( $atts ) {
		// process shortcode attributes
		$atts = shortcode_atts( array(
			'per_page' => 10,
		), $atts );
		// get url of template file
		$template = plugins_url( '/templates/invp-simple-listing.html', dirname(__FILE__) );
		// enqueue the previously registered script
		wp_enqueue_script( 'invp-simple-listing' );
		// localize it
		wp_localize_script( 'invp-simple-listing', 'invp_options', array(
			'is_archive'  => true,
			'is_singular' => false,
			'ajax_url'    => admin_url('admin-ajax.php'),
			'per_page'    => $atts['per_page'],
			'template'    => $template
		) );

		return '<div class="invp-wrapper">'
			. '<div class="invp-pages invp-reset"></div>'
			. '<div class="invp-listing invp-cf invp-reset"></div>'
			. '<div class="invp-pages invp-reset"></div>'
			. '</div>';
	}

	function simple_json() {

		$output = array();

		try {

			// get count of published inventory
			$total = wp_count_posts( Inventory_Presser_Plugin::CUSTOM_POST_TYPE );
			$output['total'] = $total->publish;

			// get post vars from request
			$per_page = (int)$_POST['per_page'];
			$cur_page = (int)$_POST['cur_page'];

			// determine offset for select
			$offset = (($per_page * $cur_page) > $output['total']) ? 0 : ($per_page * $cur_page);

			// get post id's only
			$output['inventory'] = array();
			$args = array(
				'posts_per_page' => $per_page,
				'offset'         => $offset,
				'fields'         => 'ids',
				'post_type'      => Inventory_Presser_Plugin::CUSTOM_POST_TYPE,
			);
			$inventory_array = get_posts( $args );

			foreach ( $inventory_array as $inventory_id ) {
				$vehicle = new Inventory_Presser_Vehicle( $inventory_id );
				$output['inventory'][] = array (
					'title'     => $vehicle->post_title,
					'price'     => $vehicle->price('Call For Price'),
					'miles'     => $vehicle->odometer(' Miles'),
					'color'     => $vehicle->color,
					'engine'    => $vehicle->engine,
					'url'       => $vehicle->url,
					'image_url' => $vehicle->image_url,
				);
			}

			$output['status'] = 'ok';

		} catch (Exception $e) {

			$output['message'] = $e;
			$output['status'] = 'error';

		}

		echo json_encode($output, JSON_PRETTY_PRINT);
		exit();
	}

	// if singular post and a theme with no template for vehicles, add output to the content
	function filter_single_content( $content ) {

		if ( ! is_singular( Inventory_Presser_Plugin::CUSTOM_POST_TYPE )
			|| file_exists( get_template_directory()   . '/single-' . Inventory_Presser_Plugin::CUSTOM_POST_TYPE . '.php' )
			|| file_exists( get_stylesheet_directory() . '/single-' . Inventory_Presser_Plugin::CUSTOM_POST_TYPE . '.php') )
		{
			return $content;
		}

		global $post;
		$vehicle = new Inventory_Presser_Vehicle( $post->ID );

		$image_url_lists = $vehicle->get_images_html_array( array( 'large', 'thumb' ) );

		$before = sprintf(
			'<div class="invp-single-wrapper">'
			. '<div class="invp-single-subhead invp-cf">'
			. '<div class="invp-left">%s</div>'
			. '<div class="invp-right">%s</div>'
			. '<div class="clear"></div>'
			. '</div>',
			$vehicle->odometer(' Miles'),
			$vehicle->price('Call For Price')
		);

		// if there are images, display them
		if( isset( $image_url_lists['large'] ) && 0 < count( $image_url_lists['large'] ) ) {

			$before .= '<div id="slider" class="flexslider"><ul class="slides">';
			foreach( $image_url_lists['large'] as $image ) {
				$before .= sprintf( '<li>%s</li>', $image );
			}
			$before .= '</ul></div>';

			// if only 1 image, skip the nav
			if( isset( $image_url_lists['thumb'] ) && 1 < count( $image_url_lists['thumb'] ) ) {
				$before .= '<div id="carousel" class="flexslider"><ul class="slides">';
				foreach( $image_url_lists['thumb'] as $image ) {
					$before .= sprintf( '<li>%s</li>', $image );
				}
				$before .= '</ul></div>';
			}
		}

		$before .= sprintf(
			'<ul><li>%s</li><li>%s</li></ul>',
			$vehicle->color,
			$vehicle->engine
		);

		$after = '<ul class="vehicle-features">';
		foreach( $vehicle->option_array as $option ) {
			$after .= sprintf( '<li>%s</li>', $option );
		}
		$after .= '</ul></div>';

		return $before . $content . $after;
	}

	function iframe_embed_shortcode( $atts ) {

		$atts = shortcode_atts( array(
			'width'       => '100%',
			'height'      => '5000',
			'scrolling'   => 'yes',
			'src'         => '',
			'class'       => 'iframe-class',
			'frameborder' => '0'
		), $atts );

		//Stock number may arrive in a querystring variable with key 'stock'
		if( isset( $_GET['stock'] ) ) {
			$atts['src'] = esc_url( add_query_arg( 'stock', $_GET['stock'], $atts['src'] ) );
		}

		$html = '<iframe';
		foreach( $atts as $attr => $value ) {

			//ignore some attributes
			$ignored_atts = array(
				'onclick',
				'onload',
				'onpageshow',
				'same_height_as',
			);
			if( in_array( strtolower( $attr), $ignored_atts ) ) { continue; }

			if ( $value != '' ) { // adding all attributes
				$html .= ' ' . esc_attr( $attr ) . '="' . esc_attr( $value ) . '"';
			} else { // adding empty attributes
				$html .= ' ' . esc_attr( $attr );
			}
		}
		$html .= '></iframe>';

		if ( isset( $atts["same_height_as"] ) ) {
			$html .= sprintf( '<script>
				document.addEventListener("DOMContentLoaded", function(){
					var target_element, iframe_element;
					iframe_element = document.querySelector("iframe.%s");
					target_element = document.querySelector("%s");
					iframe_element.style.height = target_element.offsetHeight + "px";
				});
				</script>',
				esc_attr( $atts["class"] ),
				esc_attr( $atts["same_height_as"] )
			);
		}

		return $html;
	}
}
