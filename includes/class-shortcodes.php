<?php
defined( 'ABSPATH' ) OR exit;

class Inventory_Presser_Vehicle_Shortcodes {

	function __construct() {

		add_shortcode('invp-simple-listing', array($this, 'output'));
		add_action('wp_enqueue_scripts', array($this, 'load_scripts'));
		add_action('wp_ajax_get_simple_listing', array($this, 'simple_json') );
		add_action('wp_ajax_nopriv_get_simple_listing', array($this, 'simple_json') );
		add_filter('the_content', array($this, 'filter_single_content'));

	}

	function load_scripts() {

		wp_register_script('flexslider', plugins_url('/js/jquery.flexslider.min.js', dirname(__FILE__)), array('jquery'));
		wp_register_script('invp-simple-listing', plugins_url('/js/invp-simple-listing.js', dirname(__FILE__)), array('flexslider'));
		wp_enqueue_style('invp-simple-listing-style', plugins_url('/css/invp-simple-listing.css', dirname(__FILE__)));


		if (is_singular('inventory_vehicle') && !file_exists(get_stylesheet_directory().'/single-inventory_vehicle.php')) {

			// generate an array of all featured image size urls.  This will be used by jquery to remove all instances
			// of a featured post image in a vehicle page, in the case that a featured image is displayed on a single page
			global $post;

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

			wp_enqueue_script('flexslider');
			wp_enqueue_script('invp-simple-listing');
			// localize it
			wp_localize_script('invp-simple-listing', 'invp_options',array(
				'is_archive'=>false,
				'is_singular'=>true,
				'featured_image_urls'=> array_unique($image_urls)
			));
		}

	}
	
	function output($atts) {
		// process shortcode attributes
		$atts = shortcode_atts(array(
			'per_page' => 10,
		), $atts);
		// get url of template file
		$template = plugins_url('/templates/invp-simple-listing.html', dirname(__FILE__));
		// enqueue the previously registered script
		wp_enqueue_script('invp-simple-listing');
		// localize it
		wp_localize_script('invp-simple-listing', 'invp_options',array(
			'is_archive'=>true,
			'is_singular'=>false,
			'ajax_url'=>admin_url('admin-ajax.php'),
			'per_page'=>$atts['per_page'],
			'template'=>$template
		));

		$output = ''; // start blank, may want to add conditionals later for paging options
		$output .= '<div class="invp-wrapper">';
		$output .= '<div class="invp-pages invp-reset"></div>';
		$output .= '<div class="invp-listing invp-reset"></div>';
		$output .= '<div class="invp-pages invp-reset"></div>';
		$output .= '</div>';

		return $output;
	}

	function simple_json() {

		$output = array();

		try {

			// get count of published inventory
			$total = wp_count_posts('inventory_vehicle');
			$output['total'] = $total->publish;

			// get post vars from request
			$per_page = (int)$_POST['per_page'];
			$cur_page = (int)$_POST['cur_page'];

			// determine offset for select
			$offset = (($per_page * $cur_page) > $output['total']) ? 0 : ($per_page * $cur_page);

			// get post id's only
			$output['inventory'] = array();
			$args = array(
				'posts_per_page'   => $per_page,
				'offset'           => $offset,
				'fields'			=> 'ids',
				//'orderby'          => 'date',
				//'order'            => 'DESC',
				'post_type'        => 'inventory_vehicle',
			);
			$inventory_array = get_posts( $args );

			foreach ($inventory_array as $inventory_id) {
				$vehicle = new Inventory_Presser_Vehicle($inventory_id);
				$output['inventory'][] = array (
						'title' => $vehicle->post_title,
						'price' => $vehicle->price('Call For Price'),
						'miles' => $vehicle->odometer(),
						'color' => $vehicle->color,
						'engine' => $vehicle->engine,
						'url' => $vehicle->url,
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

	// if singular inventory_vehicle post and a theme with no template for vehicles, add output to the content
	function filter_single_content($content) {

		if (is_singular('inventory_vehicle') && !file_exists(get_stylesheet_directory().'/single-inventory_vehicle.php')) {

			global $post;

			$vehicle = new Inventory_Presser_Vehicle($post->ID);

			$large_image_list =  $vehicle->get_images_html_array('large');
			$thumb_image_list =  $vehicle->get_images_html_array('thumb');

			$before =	'<div class="invp-single-wrapper">';

			$before =		'<div class="single-subhead">';
			$before.=			'<div class="left">'.$vehicle->odometer().' Miles</div>';
			$before.=			'<div class="right">'.$vehicle->price('Call For Price').'</div>';
			$before.=			'<div class="clear"></div>';
			$before.=		'</div>';

			$before.=		'<div id="slider" class="flexslider">';
			$before.=		  '<ul class="slides">';
			foreach($large_image_list as $image):
			$before.=		    '<li>'.$image.'</li>';
			endforeach;
			$before.=		  '</ul>';
			$before.=		'</div>';
					
			$before.=		'<div id="carousel" class="flexslider">';
			$before.=		  '<ul class="slides">';
			foreach($thumb_image_list as $image):
			$before.=		    '<li>'.$image.'</li>';
			endforeach;
			$before.=		  '</ul>';
			$before.=		'</div>';

			$after = '';
			$after .=		'</div>';

			$content = $before.$content.$after;



		}

		return $content;

	}

}

$my_ipvs = new Inventory_Presser_Vehicle_Shortcodes();