<?php
defined( 'ABSPATH' ) OR exit;

class Inventory_Presser_Vehicle_Shortcodes {

	function __construct() {

		add_shortcode('invp-simple-listing', array($this, 'output'));
		add_action('wp_enqueue_scripts', array($this, 'load_scripts'));
		add_action('wp_ajax_get_simple_listing', array($this, 'simple_json') );
		add_action('wp_ajax_nopriv_get_simple_listing', array($this, 'simple_json') );

	}

	function load_scripts() {

		wp_register_script('invp-simple-listing', plugins_url('/js/invp-simple-listing.js', dirname(__FILE__)), array('jquery'));
		wp_enqueue_style('invp-simple-listing-style', plugins_url('/css/invp-simple-listing.css', dirname(__FILE__)));

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
		wp_localize_script('invp-simple-listing', 'invp_options', array('ajax_url'=>admin_url('admin-ajax.php'),'per_page'=>$atts['per_page'], 'template'=>$template));

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
						'colors' => $vehicle->color_string,
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

}

$my_ipvs = new Inventory_Presser_Vehicle_Shortcodes();