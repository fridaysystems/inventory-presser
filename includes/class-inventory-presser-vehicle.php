<?php

if ( !class_exists( 'Inventory_Presser_Vehicle' ) ) {
	class Inventory_Presser_Vehicle {
	
		var $body_style = '';
		var $car_ID = 0;		
		var $color = '';
		var $dealer_ID = 0;
		var $engine = ''; //3.9L 8 cylinder
		var $interior_color = '';
		var $make = '';
		var $model = '';
		var $odometer = '';
		var $options = array();
		var $price = 0;
		var $stock_number = '';
		var $trim = '';
		var $vin = '';
		var $year = 0;

		var $carfax_have_report = '0';
		var $carfax_one_owner = '0';

		function carfax_icon_HTML() {
			if( $this->have_carfax_report() ) {
				if( $this->is_carfax_one_owner() ) {
					return '<a href="http://www.carfax.com/cfm/ccc_DisplayHistoryRpt.cfm?partner=DVW_1&vin=' . $this->vin . '"><img src="' . plugins_url( 'assets/free-carfax-one-owner.png', __FILE__ ) . '" alt="CARFAX 1 OWNER Free CARFAX Report" class="carfax-icon carfax-one-owner"></a>';
				} else {
					return '<a href="http://www.carfax.com/cfm/ccc_DisplayHistoryRpt.cfm?partner=DVW_1&vin=' . $this->vin . '"><img src="' . plugins_url( 'assets/free-carfax-report.png', __FILE__ ) . '" alt="CARFAX Free CARFAX Report" class="carfax-icon carfax-free-report"></a>';
				}
			}
			return '<a href="http://www.carfax.com/cfm/check_order.cfm?partner=DCS_2&VIN=' . $this->vin . '"><img src="' . plugins_url( 'assets/record-check.png', __FILE__ ) . '" alt="CARFAX Free CARFAX Record Check" class="carfax-icon carfax-record-check"></a>';
		}
		
		function __construct( $post_ID ) {
			//get all data using the post ID
			$meta = get_post_meta( $post_ID );
			//get these post meta values
			foreach( $this->keys() as $key ) {
				$filtered_key = apply_filters( 'translate_meta_field_key', $key );
				if( isset( $meta[$filtered_key] ) && isset( $meta[$filtered_key][0] ) ) {
					if( is_array( $this->$key ) ) {
						array_push( $this->$key, $meta[$filtered_key][0] );
					} else {
						$this->$key = $meta[$filtered_key][0];
					}
				}
			}
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
		 * Return the $zero_string when the price is zero.
		 */
		function price( $zero_string ) {
			if( 0 === $this->price ) { return $zero_string; }
			if( function_exists( 'money_format' ) ) { 
				$result = money_format( '%.0n', $this->price );
			} else {
				$result = number_format( $this->price, 0, '.', '' );
			}
			return '$' . $result;
		}
	}
}