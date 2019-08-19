<?php
defined( 'ABSPATH' ) or exit;

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
		var $transmission_speeds = 0;
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
		var $carfax_url_icon = '';
		var $carfax_url_report = '';

		// taxonomy terms
		var $transmission;
		var $drivetype;
		var $fuel;
		var $location;
		var $availability;

		var $is_sold = false;
		var $is_used = true;

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

			//get taxonomy terms
			$this->transmission = $this->get_term_string('transmission');
			if( ! empty( $this->transmission_speeds ) ) {
				$this->transmission = trim( sprintf(
					'%s %s %s',
					$this->transmission_speeds,
					__( 'Speed', 'inventory-presser' ),
					$this->transmission
				) );
			}
			$this->drivetype = $this->get_term_string('drive_type');
			$this->fuel = $this->get_term_string('fuel');
			$this->location = $this->get_term_string('location');
			$this->availability = $this->get_term_string('availability');
			$this->is_sold = false !== strpos( strtolower( $this->availability ), 'sold' );
			$this->is_used = has_term( 'used', 'condition', $this->post_ID );
			$this->type = $this->get_term_string('type');
		}

		//is this a vehicle for which Carfax maintains data?
		private function carfax_eligible() {
			return strlen( $this->vin ) >= 17 && $this->year >= 1980;
		}

		function carfax_icon_html() {

			if( ! $this->carfax_eligible() || ! $this->have_carfax_report() ) {
				return '';
			}

			return sprintf(
				'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
				$this->carfax_report_url(),
				$this->carfax_icon_svg()
			);
		}

		function carfax_icon_svg() {
			$svg_path = $this->carfax_url_icon;
			if( empty( $svg_path ) ) {
				//fallback to the icon that ships with this plugin
				$svg_path = dirname( dirname( __FILE__ ) ) . '/assets/show-me-carfax';
				if( $this->is_carfax_one_owner() ) {
					$svg_path .= '-1-owner';
				}
				$svg_path .= '.svg';
			}
			return file_get_contents( $svg_path );
		}

		function carfax_report_url() {

			if( ! $this->carfax_eligible() || ! $this->have_carfax_report() ) {
				return '';
			}

			if( ! empty( $this->carfax_url_report ) ) {
				return $this->carfax_url_report;
			}

			//fallback to the pre-August-2019 URLs
			return 'http://www.carfax.com/VehicleHistory/p/Report.cfx?partner=FXI_0&vin=' . $this->vin;
		}

		/**
		 * Returns the down payment as a dollar amount except when it is zero.
		 * Returns empty string if the down payment is zero.
		 *
		 * @return string The down payment formatted as a dollar amount except when the price is zero
		 */
		function down_payment() {

			if ( $this->is_sold ) {
				return '';
			}

			if( empty( $this->down_payment ) ) {
				return '';
			}

			return __( '$', 'inventory-presser' ) . number_format( $this->down_payment, 0, '.', ',' );
		}

		function extract_digits( $str ) {
			return abs( (int) filter_var( $str, FILTER_SANITIZE_NUMBER_INT ) );
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

		// fill arrays of thumb and large <img> elements
		function get_images_html_array( $sizes ) {

			/**
			 * Backwards compatibility to versions before 5.4.0 where the
			 * incoming argument was a string not an array.
			 */
			if( ! is_array( $sizes ) ) {
				$sizes = array( $sizes );
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
			$term_list = wp_get_post_terms( $this->post_ID, $taxonomy, array( 'fields' => 'names' ) );
			return implode( ', ', $term_list );
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
		 * must be prefixed by an apply_filters() call before use.
		 */
		function keys( $include_serialized = true ) {
			$all_keys = array_column( self::keys_and_types( $include_serialized ), 'name' );
			return $include_serialized ? $all_keys : array_diff( $all_keys, array( 'epa_fuel_economy', 'option_array', 'prices' ) );
		}

		public static function keys_and_types( $include_serialized = true ) {
			return array(
				array(
					'name' => 'beam', //for boats
					'type' => 'number',
				),
				array(
					'name' => 'body_style',
					'type' => 'string',
				),
				array(
					'name' => 'car_id', //unique identifier
					'type' => 'integer',
				),
				array(
					'name' => 'carfax_have_report',
					'type' => 'boolean',
				),
				array(
					'name' => 'carfax_one_owner',
					'type' => 'boolean',
				),
				array(
					'name' => 'carfax_url_icon',
					'type' => 'string',
				),
				array(
					'name' => 'carfax_url_report',
					'type' => 'string',
				),
				array(
					'name' => 'color',
					'type' => 'string',
				),
				array(
					'name' => 'dealer_id', //friday systems dealer id
					'type' => 'integer',
				),
				array(
					'name' => 'down_payment',
					'type' => 'number',
				),
				array(
					'name' => 'edmunds_style_id',
					'type' => 'integer',
				),
				array(
					'name' => 'engine',
					'type' => 'string',
				),
				array(
					'name' => 'epa_fuel_economy',
					'type' => null, //'string',
				),
				array(
					'name' => 'featured',
					'type' => 'boolean',
				),
				array(
					'name' => 'hull_material', //for boats
					'type' => 'string',
				),
				array(
					'name' => 'interior_color',
					'type' => 'string',
				),
				array(
					'name' => 'last_modified',
					'type' => 'string',
				),
				array(
					'name' => 'leads_id', //friday systems dealer id that receives leads
					'type' => 'integer',
				),
				array(
					'name' => 'length', //for boats
					'type' => 'integer',
				),
				array(
					'name' => 'make',
					'type' => 'string',
				),
				array(
					'name' => 'model',
					'type' => 'string',
				),
				array(
					'name' => 'msrp',
					'type' => 'number',
				),
				array(
					'name' => 'odometer',
					'type' => 'string',
				),
				array(
					'name' => 'option_array',
					'type' => null, //'string',
				),
				array(
					'name' => 'payment',
					'type' => 'number',
				),
				array(
					'name' => 'payment_frequency',
					'type' => 'string',
				),
				array(
					'name' => 'price',
					'type' => 'number',
				),
				array(
					'name' => 'prices',
					'type' => null, //'string',
				),
				array(
					'name' => 'stock_number',
					'type' => 'string',
				),
				array(
					'name' => 'title_status',
					'type' => 'string',
				),
				array(
					'name' => 'transmission_speeds',
					'type' => 'integer',
				),
				array(
					'name' => 'trim',
					'type' => 'string',
				),
				array(
					'name' => 'vin',
					'type' => 'string',
				),
				array(
					'name' => 'year',
					'type' => 'integer',
				),
				array(
					'name' => 'youtube',
					'type' => 'string',
				),
			);
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

		/**
		 * Returns the payment as a dollar amount except when it is zero or the vehicle is sold.
		 * Returns empty string if the payment is zero or the vehicle is sold.
		 *
		 * @return string The payment formatted as a dollar amount except when the payment is zero or the vehicle is sold
		 */
		function payment() {

			if ( $this->is_sold ) {
				return '';
			}

			if( empty( $this->payment ) ) {
				return '';
			}

			return __( '$', 'inventory-presser' ) . number_format( $this->payment, 0, '.', ',' );
		}

		function payments( $zero_string = '', $separator = '/' ) {

			if ( isset( $this->down_payment ) ) {
				return sprintf(
					'%s %s %s $%s %s',
					$this->down_payment(),
					__( 'Down', 'inventory-presser' ),
					$separator,
					$this->payment(),
					ucfirst( $this->payment_frequency )
				);
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
		 * Returns the price as a dollar amount except when it is zero. Returns
		 * the $zero_string when the price is zero.
		 *
		 * @param string $zero_string The text to display when the price is zero
		 * @return string The price formatted as a dollar amount except when the price is zero
		 */
		function price( $zero_string = '' ) {

			if ( $this->is_sold ) {
				return apply_filters( 'invp_sold_string', sprintf( '<span class="vehicle-sold">%s</span>', __( 'SOLD!', 'inventory-presser' ) ) );
			}

			if( 0 == $this->price ) {
				return apply_filters( 'invp_zero_price_string', $zero_string, $this );
			}

			return __( '$', 'inventory-presser' ) . number_format( $this->price, 0, '.', ',' );
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

		function youtube_url() {

			if ( empty( $this->youtube ) ) {
				return '';
			}

			return 'https://www.youtube.com/watch?v=' . $this->youtube;
		}
	}
}
