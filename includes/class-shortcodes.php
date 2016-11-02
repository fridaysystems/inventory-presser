<?php
defined( 'ABSPATH' ) OR exit;

class Inventory_Presser_Vehicle_Shortcodes {

	function __construct() {

		add_shortcode('invp-simple-listing', array($this, 'simple_listing'));
		add_shortcode('invp-inventory-slider', array($this, 'inventory_slider'));
		add_shortcode('invp-inventory-grid', array($this, 'inventory_grid'));
		add_shortcode( 'iframe', array($this, 'iframe_unqprfx_embed_shortcode'));

		add_action('wp_enqueue_scripts', array($this, 'load_scripts'));
		add_action('wp_ajax_get_simple_listing', array($this, 'simple_json') );
		add_action('wp_ajax_nopriv_get_simple_listing', array($this, 'simple_json') );
		add_filter('the_content', array($this, 'filter_single_content'));

	}

	function load_scripts() {

		wp_enqueue_style('font-awesome', '//maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css');

		global $post;
		if( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'invp-simple-listing') || is_post_type_archive('inventory_vehicle')) {
			wp_enqueue_style('invp-simple-listing-style', plugins_url('/css/invp-simple-listing.css', dirname(__FILE__)));
		}

		wp_register_script('flexslider', plugins_url('/js/jquery.flexslider.min.js', dirname(__FILE__)), array('jquery'));
		wp_register_script('invp-simple-listing', plugins_url('/js/invp-simple-listing.js', dirname(__FILE__)), array('flexslider'));
		
		if (is_singular('inventory_vehicle') && !file_exists(get_stylesheet_directory().'/single-inventory_vehicle.php')) {

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

	function inventory_slider($atts) {
		// process shortcode attributes
		$atts = shortcode_atts(array(
			'per_page' => 10,
			'captions' => 'true',
		), $atts);

		$atts['captions'] = 'true' === $atts['captions'];

		$args=array(
			'numberposts'=>$atts['per_page'],
			'post_type'=>'inventory_vehicle',
			'meta_key'=>'_thumbnail_id',
			'fields' => 'ids',
			'orderby'=>'rand',
			'order' => 'ASC'
		);

		$inventory_ids = get_posts( $args );

		$flexHtml = '';

		if ($inventory_ids) {

			$flexHtml .= "<div class=\"flexslider flex-native\">\n";
			$flexHtml .= "<ul class=\"slides\">\n";

			foreach ($inventory_ids as $inventory_id) {

				$vehicle = new Inventory_Presser_Vehicle($inventory_id);


				$flexHtml .= '<li><a class="flex-link" href="'.$vehicle->url.'">';

				$flexHtml .= '<div class="grid-image" style="background-image: url('.wp_get_attachment_image_url(get_post_thumbnail_id($inventory_id), 'large').');">';
				$flexHtml .= "</div>";

				if ($atts['captions']) {
					$flexHtml .= '<p class="flex-caption">';
					$flexHtml .= $vehicle->post_title;
					$flexHtml .= "</p>";
				}

				$flexHtml .= "</a></li>\n";

			}

			$flexHtml .= "</ul></div>";

		}

		return $flexHtml;

	}

	function inventory_grid($atts) {
		// process shortcode attributes
		$atts = shortcode_atts(array(
			'per_page' => 15,
			'captions' => 'true',
			'button' => 'true',
		), $atts);

		$atts['captions'] = 'true' === $atts['captions'];
		$atts['button'] = 'true' === $atts['button'];

		$args=array(
			'posts_per_page'=>$atts['per_page'],
			'post_type'=>'inventory_vehicle',
			'meta_key'=>'_thumbnail_id',
			'fields' => 'ids',
			'orderby'=>'rand',
			'order' => 'ASC'
		);

		$inventory_ids = get_posts( $args );

		$grid_html = '';

		if ($inventory_ids) {

			$grid_html .= '<div class="invp-grid pad cf">';
			$grid_html .= '<ul class="grid-slides">';

			foreach ($inventory_ids as $inventory_id) {

				$vehicle = new Inventory_Presser_Vehicle($inventory_id);

				$grid_html .= '<li class="grid one-third"><a class="grid-link" href="'.$vehicle->url.'">';

				$grid_html .= '<div class="grid-image" style="background-image: url('.wp_get_attachment_image_url(get_post_thumbnail_id($inventory_id), 'medium').');">';
				$grid_html .= "</div>";

				if ($atts['captions']) {
					$grid_html .= "<p class=\"grid-caption\">";
					$grid_html .= $vehicle->post_title;
					$grid_html .= "</p>";
				}

				$grid_html .= "</a></li>\n";

			}

			$grid_html .= '</ul><div class="clear"></div>';
			$grid_html .= "</div>";
			if ($atts['button']) {
				$grid_html .= '<a href="'.get_post_type_archive_link( 'inventory_vehicle' ).'" class="_button _button-med">Full Inventory</a>';
			}

		}

		return $grid_html;

	}
	
	function simple_listing($atts) {
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
		$output .= '<div class="invp-listing invp-cf invp-reset"></div>';
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
						'miles' => $vehicle->odometer(' Miles'),
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

		if (is_singular('inventory_vehicle') && !file_exists(get_template_directory().'/single-inventory_vehicle.php') && !file_exists(get_stylesheet_directory().'/single-inventory_vehicle.php')) {

			global $post;

			$vehicle = new Inventory_Presser_Vehicle($post->ID);

			$large_image_list =  $vehicle->get_images_html_array('large');
			$thumb_image_list =  $vehicle->get_images_html_array('thumb');

			$before =	'<div class="invp-single-wrapper">';

			$before.=		'<div class="invp-single-subhead invp-cf">';
			$before.=			'<div class="invp-left">'.$vehicle->odometer(' Miles').'</div>';
			$before.=			'<div class="invp-right">'.$vehicle->price('Call For Price').'</div>';
			$before.=			'<div class="clear"></div>';
			$before.=		'</div>';

			// if there are images, display them
			if (count($thumb_image_list) > 0) {

				$before.=		'<div id="slider" class="flexslider">';
				$before.=		  '<ul class="slides">';
				foreach($large_image_list as $image):
				$before.=		    '<li>'.$image.'</li>';
				endforeach;
				$before.=		  '</ul>';
				$before.=		'</div>';

				
				// if only 1 image, skip the nav
				if (count($thumb_image_list) > 1) {
					$before.=		'<div id="carousel" class="flexslider">';
					$before.=		  '<ul class="slides">';
					foreach($thumb_image_list as $image):
					$before.=		    '<li>'.$image.'</li>';
					endforeach;
					$before.=		  '</ul>';
					$before.=		'</div>';				
				}

			}

			$before.= '<ul>';
			$before.= '	<li>'.$vehicle->color.'</li>';
			$before.= '	<li>'.$vehicle->engine.'</li>';
			$before.= '</ul>';

			$after = '';

			$after .= '<ul class="vehicle-features">';
			foreach($vehicle->option_array as $option):
			$after .= '<li>'.$option.'</li>';
			endforeach;
			$after .= '</ul>';

			$after .= '</div>';

			$content = $before.$content.$after;



		}

		return $content;

	}

	function iframe_unqprfx_embed_shortcode( $atts ) {

		// qstring variable is 'stock'

		$defaults = array(
			'width' => '100%',
			'height' => '500',
			'scrolling' => 'yes',
			'class' => 'iframe-class',
			'frameborder' => '0'
		);

		foreach ( $defaults as $default => $value ) { // add defaults
			if ( ! @array_key_exists( $default, $atts ) ) { // mute warning with "@" when no params at all
				$atts[$default] = $value;
			}
		}

		if (isset($_GET['stock'])) {
			$atts['src'] .= '&stock='.$_GET['stock'];
		}

		$html = "\n".'<!-- iframe plugin v.4.2 wordpress.org/plugins/iframe/ -->'."\n";
		$html .= '<iframe';
		foreach( $atts as $attr => $value ) {
			if ( strtolower($attr) != 'same_height_as' AND strtolower($attr) != 'onload'
				AND strtolower($attr) != 'onpageshow' AND strtolower($attr) != 'onclick') { // remove some attributes
				if ( $value != '' ) { // adding all attributes
					$html .= ' ' . esc_attr( $attr ) . '="' . esc_attr( $value ) . '"';
				} else { // adding empty attributes
					$html .= ' ' . esc_attr( $attr );
				}
			}
		}
		$html .= '></iframe>'."\n";

		if ( isset( $atts["same_height_as"] ) ) {
			$html .= '
				<script>
				document.addEventListener("DOMContentLoaded", function(){
					var target_element, iframe_element;
					iframe_element = document.querySelector("iframe.' . esc_attr( $atts["class"] ) . '");
					target_element = document.querySelector("' . esc_attr( $atts["same_height_as"] ) . '");
					iframe_element.style.height = target_element.offsetHeight + "px";
				});
				</script>
			';
		}

		return $html;
	}


}

$my_ipvs = new Inventory_Presser_Vehicle_Shortcodes();