<?php

if ( !class_exists( 'Inventory_Presser_Vehicle' ) ) {
	class Inventory_Presser_Vehicle {

		var $post_ID;
	
		var $body_style = '';
		var $car_ID = 0;		
		var $color = '';
		var $dealer_ID = 0;
		var $engine = ''; //3.9L 8 cylinder
		var $interior_color = '';
		var $make = '';
		var $model = '';
		var $odometer = '';
		var $option_array = array();
		var $price = 0;
		var $stock_number = '';
		var $trim = '';
		var $vin = '';
		var $year = 0;

		var $carfax_have_report = '0';
		var $carfax_one_owner = '0';

		// taxonomy terms
		var $transmission;
		var $drivetrain;

		// images
		var $images = array();
		
		// constructor
		function __construct($post_id) {

			$this->post_ID = $post_id;

			//get all data using the post ID
			$meta = get_post_meta($this->post_ID);

			//get these post meta values
			foreach( $this->keys() as $key ) {
				$filtered_key = apply_filters( 'translate_meta_field_key', $key );
				if( isset( $meta[$filtered_key] ) && isset( $meta[$filtered_key][0])) {
					if( is_array( $this->$key ) ) {
						$this->$key = unserialize($meta[$filtered_key][0]);
					} else {
						$this->$key = $meta[$filtered_key][0];
					}
				}
			}

			// get selected taxonomy terms
			$this->transmission = $this->get_term_string('Transmission');
			$this->drivetype = $this->get_term_string('Drive type');

		}

		function carfax_icon_HTML() {
			if( $this->have_carfax_report() ) {
				if( $this->is_carfax_one_owner() ) {
					return '<a href="http://www.carfax.com/cfm/ccc_DisplayHistoryRpt.cfm?partner=DVW_1&vin=' . $this->vin . '"><img src="' . plugins_url( '../assets/free-carfax-one-owner.png', __FILE__ ) . '" alt="CARFAX 1 OWNER Free CARFAX Report" class="carfax-icon carfax-one-owner"></a>';
				} else {
					return '<a href="http://www.carfax.com/cfm/ccc_DisplayHistoryRpt.cfm?partner=DVW_1&vin=' . $this->vin . '"><img src="' . plugins_url( '../assets/free-carfax-report.png', __FILE__ ) . '" alt="CARFAX Free CARFAX Report" class="carfax-icon carfax-free-report"></a>';
				}
			}
			return '<a href="http://www.carfax.com/cfm/check_order.cfm?partner=DCS_2&VIN=' . $this->vin . '"><img src="' . plugins_url( '../assets/record-check.png', __FILE__ ) . '" alt="CARFAX Free CARFAX Record Check" class="carfax-icon carfax-record-check"></a>';
		}

		function have_carfax_report() {
			return '1' == $this->carfax_have_report;
		}
		
		function is_carfax_one_owner() {
			return '1' == $this->carfax_one_owner;
		}
		
		/**
		 * This is an array of the post meta keys this object uses. These keys
		 * are prefixed by an apply_filters() call.
		 */
		function keys( ) {
			return array(
				'body_style',
				'car_ID',
				'carfax_have_report', 
				'carfax_one_owner',
				'color',
				'dealer_ID',
				'engine',
				'interior_color',
				'make',
				'model',
				'odometer',
				'option_array',
				'price',
				'stock_number',
				'trim',
				'vin',
				'year',
			);
		}
		
		//if numeric, format the odometer with thousands separators
		function odometer( ) {
			if( is_numeric( $this->odometer ) ) {
				return number_format( $this->odometer, 0, '.', ',' );
			} else {
				return $this->odometer;
			}
		}
		
		/**
		 * Return the price as a dollar amount except when it is zero.
		 * Return the $zero_string when the price is zero.sadfasdf
		 */
		function price( $zero_string ) {
			if( 0 === $this->price ) { return $zero_string; }
			if( function_exists( 'money_format' ) ) { 
				$result = money_format( '%.0n', $this->price );
			} else {
				$result = number_format( $this->price, 0, '.', ',' );
			}
			return '$' . $result;
		}

		//return taxonomy terms as a comma delimited string
		function get_term_string( $taxonomy ) {
			$term_list = wp_get_post_terms($this->post_ID, $taxonomy, array("fields" => "names"));
			return implode(', ', $term_list);
		}

		// fill arrays of thumb and large image URI's
		function get_images_html_array( $size ) {

			$this->images[$size] = array();

			$image_args = array('post_parent' =>$this->post_ID,
					'numberposts' => -1,
					'post_type' => 'attachment',
					'post_mime_type' => 'image',
					'order' => 'ASC',
					'orderby' => 'menu_order ID');

			$images = get_children($image_args);

			foreach($images as $image):
				$this->images[$size][] = wp_get_attachment_image($image->ID, $size);
			endforeach;

			return $this->images[$size];

		}

	}
}