<?php
defined( 'ABSPATH' ) OR exit;

class Inventory_Presser_Vehicle_Shortcode {

	function __construct() {

		add_shortcode('inventory-press', array($this, 'output'));
		add_action('wp_enqueue_scripts', array($this, 'load_scripts'));

	}

	function load_scripts() {

		wp_register_script('inventory-presser-shortcode', plugins_url('/js/vehicle-shortcode-listing.js', dirname(__FILE__)), array('jquery'));
		wp_enqueue_style('inventory-presser-scstyle', plugins_url('/css/vehicle-shortcode-listing.css', dirname(__FILE__)));

	}
	
	function output($atts) {

		$atts = shortcode_atts(array(
			'per_page' => 10,
		), $atts );

		$atts['per_page'] = !is_int($atts['per_page']) ? $atts['per_page'] : 10;

		// enqueue the previously registered script
		wp_enqueue_script('inventory-presser-shortcode');
		wp_localize_script('inventory-presser-shortcode', 'ipvs_vars', array(''));

		$template_file = dirname(plugin_dir_path( __FILE__ )).'/templates/shortcode-vehicle-listing.html';

		ob_start();
		include $template_file;
		$output = ob_get_contents();
		ob_end_clean();

		return $output;
	}

}

$my_ipvs = new Inventory_Presser_Vehicle_Shortcode();