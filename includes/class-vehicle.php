<?php

if ( !class_exists( 'Inventory_Presser_Vehicle' ) ) {
	class Inventory_Presser_Vehicle {

		var $post_ID;
		var $post_title;
		var $url;
		var $image_url;



		/**
		 * A unique identifier assigned by the inventory provider, if a feed is
		 * providing inventory updates.
		 */
		var $dealer_id = 0;

		/**
		 * If leads generated need to be associated with a different dealership
		 * ID than the one stored in $dealer_id, it is stored here, in $leads_id.
		 */
		var $leads_id = 0;

		var $body_style = '';
		var $car_id = 0;
		var $color = '';
		var $down_payment = 0;
		var $edmunds_style_id = 0;
		var $engine = ''; //3.9L 8 cylinder
		var $epa_fuel_economy = array();
		var $featured = '0';
		var $interior_color = '';
		var $last_modified = '';
		var $make = '';
		var $model = '';
		var $msrp = 0;
		var $odometer = '';
		var $option_array = array();
		var $payment = 0;
		var $payment_frequency = '';
		var $price = 0;
		var $prices = array();
		var $stock_number = '';
		var $title_status = '';
		var $trim = '';
		var $type = '';
		var $vin = '';
		var $year = 0;
		var $youtube = '';

		//boat items
		var $beam = '';
		var $length = '';
		var $hull_material = '';

		var $carfax_have_report = '0';
		var $carfax_one_owner = '0';

		// taxonomy terms
		var $transmission;
		var $drivetype;
		var $fuel;
		var $location;
		var $availability;

		var $is_sold = false;
		var $is_used = true;

		// color string for output
		var $color_string = '';

		// constructors
		function __construct( $post_id = null ) {

			//Help the order by logic determine which post meta keys are numbers
			if( ! has_filter( 'invp_meta_value_or_meta_value_num', array( $this, 'indicate_post_meta_values_are_numbers' ) ) ) {
				add_filter( 'invp_meta_value_or_meta_value_num', array( $this, 'indicate_post_meta_values_are_numbers' ), 10, 2 );
			}

			if( is_null( $post_id ) ) { return; }

			// put wp vars into our object properties
			$this->post_ID = $post_id;
			$this->post_title = get_the_title($this->post_ID);
			$this->url = get_permalink($this->post_ID);
			$thumbnail_id = get_post_thumbnail_id( $this->post_ID, 'medium' );
			$this->image_url = ( ! is_wp_error( $thumbnail_id ) && '' != $thumbnail_id ? wp_get_attachment_url( $thumbnail_id ) : plugins_url( 'assets/no-photo.png', dirname( __FILE__ ) ) );

			//get all data using the post ID
			$meta = get_post_meta( $this->post_ID );

			//get these post meta values
			foreach( $this->keys() as $key ) {
				$filtered_key = apply_filters( 'invp_prefix_meta_key', $key );
				if( isset( $meta[$filtered_key] ) && isset( $meta[$filtered_key][0])) {
					if( is_array( $this->$key ) ) {
						$this->$key = unserialize($meta[$filtered_key][0]);
					} else {
						$this->$key = trim($meta[$filtered_key][0]);
					}
				}
			}

			// set up color string
			$colorsArr = array();
			if ($this->color) {
				$colorsArr[] = ucwords(strtolower($this->color)) . ' Exterior';
			}
			if ($this->interior_color) {
				$colorsArr[] = ucwords(strtolower($this->interior_color)) . ' Interior';
			}
			$this->color_string = implode(' / ', $colorsArr);

			// get selected taxonomy terms
			$this->transmission = $this->get_term_string('transmission');
			$this->drivetype = $this->get_term_string('drive_type');
			$this->fuel = $this->get_term_string('fuel');
			$this->location = $this->get_term_string('location');
			$this->availability = $this->get_term_string('availability');

			$pos = strpos(strtolower($this->availability), 'sold');
			if ($pos !== false) {
				$this->is_sold = true;
			}

			$this->is_used = has_term( 'used', 'condition', $this->post_ID );

			$type_array = wp_get_post_terms($this->post_ID, 'type', array("fields" => "slugs"));
			$this->type = (isset($type_array[0])) ? $type_array[0] : '';
		}

		//is this a vehicle for which Carfax maintains data?
		private function carfax_eligible() {
			return strlen( $this->vin ) >= 17 && $this->year >= 1980;
		}

		function carfax_icon_html() {
			if( ! $this->carfax_eligible() || ! $this->have_carfax_report() ) {
				return '';
			}

			$link = '<a href="http://www.carfax.com/VehicleHistory/p/Report.cfx?partner=FXI_0&vin='
				. $this->vin
				. '" target="_blank" rel="noopener noreferrer">';

			$svg_path = dirname( dirname( __FILE__ ) ) . '/assets/show-me-carfax';
			if( $this->is_carfax_one_owner() ) {
				$svg_path .= '-1-owner';
			}
			$svg_path .= '.svg';

			return $link . file_get_contents( $svg_path ) . '</a>';
		}

		function extract_digits( $str ) {
			return abs( (int) filter_var( $str, FILTER_SANITIZE_NUMBER_INT ) );
		}

		function get_book_value() {
			/**
			 * Book value lives in the prices array under
			 * array key 'NADA Book Value' or 'KBB Book Value'
			 */

			$nada = $kbb = 0;
			if( isset( $this->prices['NADA Book Value'])) {
				$nada = intval( $this->prices['NADA Book Value'] );
			}
			if( isset( $this->prices['KBB Book Value'])) {
				$kbb = intval( $this->prices['KBB Book Value'] );
			}
			return max( $nada, $kbb );
		}

		/**
		 * Given a string containing HTML <img> element markup, extract the
		 * value of the src element and return it.
		 *
		 * @param string $img_element An HTML <img> element
		 * @return string The value of the src attribute
		 */
		function extract_image_element_src( $img_element ) {
			return preg_replace( "/\">?.*/", "", preg_replace( "/.*<img[\s\S]+src=\"/", "", $img_element ) );
		}

		// fill arrays of thumb and large <img> elements
		function get_images_html_array( $sizes ) {

			/**
			 * Backwards compatibility to versions before 5.4.0 where the
			 * incoming argument was a string not an array.
			 */
			if( ! is_array( $sizes ) ) {
				$sizes = array( $size );
			}

			$image_args = array(
				'meta_key'       => apply_filters( 'invp_prefix_meta_key', 'photo_number' ),
				'numberposts'    => -1,
				'order'          => 'ASC',
				'orderby'        => 'meta_value_num',
				'post_mime_type' => 'image',
				'post_parent'    => $this->post_ID,
				'post_type'      => 'attachment',
			);

			$images = get_children( $image_args );

			$image_urls = array();
			foreach( $images as $image ) {
				foreach( $sizes as $size ) {

					$img_element = wp_get_attachment_image(
						$image->ID,
						$size,
						false,
						array( 'class' => "attachment-$size size-$size invp-image" )
					);

					$image_urls[$size][] = $img_element;

					if( 'large' == $size ) {
						$image_urls['urls'][] = $this->extract_image_element_src( $img_element );
					}
				}
			}

			/**
			 * Backwards compatibility to versions before 5.4.0 where the
			 * incoming argument was a string not an array.
			 */
			if( 1 == sizeof( $sizes ) ) {
				return $image_urls[$sizes[0]];
			}

			return $image_urls;
		}

		function get_image_count() {
			$image_args = array(
				'post_parent'    => $this->post_ID,
				'numberposts'    => -1,
				'post_type'      => 'attachment',
				'post_mime_type' => 'image',
			);
			$images = get_children($image_args);
			return count($images);
		}

		//return taxonomy terms as a comma delimited string
		function get_term_string( $taxonomy ) {
			$term_list = wp_get_post_terms($this->post_ID, $taxonomy, array("fields" => "names"));
			return implode(', ', $term_list);
		}

		function have_carfax_report() {
			return '1' == $this->carfax_have_report;
		}

		/**
		 * Help WordPress understand which post meta values should be treated as
		 * numbers. By default, they are all strings, and strings sort
		 * differently than numbers.
		 */
		function indicate_post_meta_values_are_numbers( $value, $meta_key ) {
			return ( $this->post_meta_value_is_number( $meta_key ) ? 'meta_value_num' : $value );
		}

		function is_carfax_one_owner() {
			return '1' == $this->carfax_one_owner;
		}

		/**
		 * This is an array of the post meta keys this object uses. These keys
		 * are prefixed by an apply_filters() call.
		 */
		function keys( $include_serialized = true ) {
			$all_keys = array(
				'beam',
				'body_style',
				'car_id',
				'carfax_have_report',
				'carfax_one_owner',
				'color',
				'dealer_id',
				'down_payment',
				'edmunds_style_id',
				'engine',
				'epa_fuel_economy',
				'featured',
				'hull_material',
				'interior_color',
				'last_modified',
				'leads_id',
				'length',
				'make',
				'model',
				'msrp',
				'odometer',
				'option_array',
				'payment',
				'payment_frequency',
				'price',
				'prices',
				'stock_number',
				'title_status',
				'trim',
				'vin',
				'year',
				'youtube',
			);
			return $include_serialized ? $all_keys : array_diff( $all_keys, array( 'option_array', 'prices' ) );
		}

		//if numeric, format the odometer with thousands separators
		function odometer( $append = '' ) {
			if( '0' == $this->odometer ) { return ''; }

			$odometer = '';
			if( is_numeric( $this->odometer ) ) {
				$odometer .= number_format( $this->odometer, 0, '.', ',' );
				if ($append) {
					$odometer .= $append;
				}
			} else {
				$odometer .= $this->odometer;
			}
			return $odometer;
		}

		function payments( $zero_string = '' ) {

			if ( isset( $this->down_payment ) ) {
				return sprintf( '$%s Down / $%s %s', number_format( $this->down_payment, 0, '.', ',' ), number_format( $this->payment, 0, '.', ',' ), ucfirst( $this->payment_frequency ) );
			}
			return $this->price( $zero_string );
		}

		function post_meta_value_is_number( $post_meta_key ) {
			return in_array( $post_meta_key, array(
				apply_filters( 'invp_prefix_meta_key', 'beam' ),
				apply_filters( 'invp_prefix_meta_key', 'car_id' ),
				apply_filters( 'invp_prefix_meta_key', 'dealer_id' ),
				apply_filters( 'invp_prefix_meta_key', 'length' ),
				apply_filters( 'invp_prefix_meta_key', 'odometer' ),
				apply_filters( 'invp_prefix_meta_key', 'price' ),
				apply_filters( 'invp_prefix_meta_key', 'year' ),
			) );
		}

		/**
		 * Return the price as a dollar amount except when it is zero.
		 * Return the $zero_string when the price is zero.
		 */
		function price( $zero_string = '' ) {
			if ( ! $this->is_sold ) {
				if( 0 == $this->price ) {
					return apply_filters( 'invp_zero_price_string', $zero_string, $this );
				}
				return '$' . number_format( $this->price, 0, '.', ',' );
			}

			return apply_filters( 'invp_sold_string', '<span class="vehicle-sold">SOLD!</span>' );
		}

		function schema_org_drive_type( $drive_type ) {

			switch( $drive_type ) {

				case 'Front Wheel Drive w/4x4':
				case 'Rear Wheel Drive w/4x4':
					return 'FourWheelDriveConfiguration';

				case 'Two Wheel Drive':
				case 'Rear Wheel Drive':
					return 'RearWheelDriveConfiguration';

				case 'Front Wheel Drive':
					return 'FrontWheelDriveConfiguration';

				case 'All Wheel Drive':
					return 'AllWheelDriveConfiguration';
			}
			return null;
		}

		/**
		 * Returns Schema.org markup for this Vehicle as a JSON-LD code block
		 */
		function schema_org_json_ld() {

			$obj = [
				'@context' => 'http://schema.org/',
				'@type' => 'Vehicle'
			];

			if( isset( $this->post_title ) && '' != $this->post_title ) {
				$obj['name'] = $this->post_title;
			}

			if( '' != $this->make ) {
				$obj['brand'] = [
					'@type' => 'Thing',
					'name' => $this->make
				];
			}

			if( '' != $this->vin ) {
				$obj['vehicleIdentificationNumber'] = $this->vin;
			}

			if( 0 != $this->year ) {
				$obj['vehicleModelDate'] = $this->year;
			}

			//if the image does not end with 'no-photo.png'
			if( 'no-photo.png' != substr( $this->image_url, 12 ) ) {
				$obj['image'] = $this->image_url;
			}

			if( '' != $this->odometer ) {
				$obj['mileageFromOdometer'] = [
					'@type' => 'QuantitativeValue',
					'value' => $this->extract_digits( $this->odometer ),
					'unitCode' => 'SMI'
				];
			}

			if( '' != $this->engine || ( isset( $this->fuel ) && '' != $this->fuel ) ) {
				$obj['vehicleEngine'] = [];
				if( '' != $this->engine ) {
					$obj['vehicleEngine']['engineType'] = $this->engine;
				}
				if( isset( $this->fuel ) && '' != $this->fuel ) {
					$obj['vehicleEngine']['fuelType'] = $this->fuel;
				}
			}

			if( '' != $this->body_style ) {
				$obj['bodyType'] = $this->body_style;
			}

			if( '' != $this->color ){
				$obj['color'] = $this->color;
			}

			if( '' != $this->interior_color ) {
				$obj['vehicleInteriorColor'] = $this->interior_color;
			}

			global $post;
			if( isset( $post->post_content ) && '' != $post->post_content ) {
				$obj['description'] = $post->post_content;
			}

			$schema_drive_type = $this->schema_org_drive_type( $this->drivetype );
			if( null !== $schema_drive_type ) {
				$obj['driveWheelConfiguration'] = $schema_drive_type;
			}

			if( isset( $this->transmission ) && '' != $this->transmission ) {
				$obj['vehicleTransmission'] = $this->transmission;
			}

			return '<script type="application/ld+json">' . json_encode( $obj ) . '</script>';
		}
	}
}
