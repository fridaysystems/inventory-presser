<?php
defined( 'ABSPATH' ) or exit;

if ( ! class_exists( 'Inventory_Presser_Vehicle' ) )
{	
	/**
	 * Inventory_Presser_Vehicle
	 * 
	 * This class is initialized with a post ID belonging to a vehicle, and its methods simplify accessing vehicle data stored in post meta, taxonomy terms, and the Media Library.
	 */
	class Inventory_Presser_Vehicle
	{
		var $post_ID;
		var $post_title;
		var $url;
		var $image_url;

		/**
		 * @var int @dealer_id	A unique identifier assigned to the dealership by the inventory provider.
		 */
		var $dealer_id = 0;

		/**
		 * @var int @leads_id	If leads generated need to be associated with a different dealership ID than the one stored in $dealer_id, it is stored here, in $leads_id.
		 */
		var $leads_id = 0;

		var $availability = '';
		var $body_style = '';
		var $book_value_kbb = 0;
		var $book_value_nada = 0;
		var $car_id = 0;
		var $color = '';
		var $condition = '';
		var $cylinders;
		var $description = '';
		var $down_payment = 0;
		var $drive_type = '';
		var $edmunds_style_id = 0;
		var $engine = ''; //3.9L 8 cylinder
		var $featured = '0';

		/**
		 * Fuel Economy members are designed in direct support of this EPA data
		 * @see https://fueleconomy.gov/feg/epadata/vehicles.csv.zip
		 */
		var $fuel = '';

		var $fuel_economy_1_name = '';
		var $fuel_economy_1_annual_consumption = ''; //Annual fuel consumption in barrels
		var $fuel_economy_1_annual_cost = 0; //Annual fuel cost in US dollars
		var $fuel_economy_1_annual_emissions = ''; //Annual tailpipe CO2 emissions in grams per mile
		var $fuel_economy_1_city = 0;
		var $fuel_economy_1_combined = 0;
		var $fuel_economy_1_highway = 0;

		var $fuel_economy_2_name = '';
		var $fuel_economy_2_annual_consumption = '';
		var $fuel_economy_2_annual_cost = 0;
		var $fuel_economy_2_annual_emissions = '';
		var $fuel_economy_2_city = 0;
		var $fuel_economy_2_combined = 0;
		var $fuel_economy_2_highway = 0;

		var $fuel_economy_five_year_savings = 0; //Five year savings in fuel costs in US dollars compared to the average vehicle. Savings are positive.


		var $interior_color = '';
		var $last_modified = '';
		var $location = '';
		var $make = '';
		var $model = '';
		var $msrp = 0;
		var $odometer = '';
		var $options_array = array();
		var $payment = 0;
		var $payment_frequency = '';
		var $price = 0;
		var $prices = array();
		var $propulsion_type = '';
		var $stock_number = '';
		var $title_status = '';
		var $transmission = '';
		var $transmission_speeds = 0;
		var $trim = '';
		var $type = '';
		var $vin = '';
		var $wholesale = false;
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

		//Booleans based on the Availability & Condition taxonomy terms
		var $is_sold = false;
		var $is_used = true;
		var $is_wholesale = false;


		/**
		 * __construct
		 * 
		 * Populates the object with vehicle data
		 *
		 * @param  int|null $post_id The post ID of a vehicle
		 * @return void
		 */
		function __construct( $post_id = null )
		{
			//Help the order by logic determine which post meta keys are numbers
			if( ! has_filter( 'invp_meta_value_or_meta_value_num', array( $this, 'indicate_post_meta_values_are_numbers' ) ) )
			{
				add_filter( 'invp_meta_value_or_meta_value_num', array( $this, 'indicate_post_meta_values_are_numbers' ), 10, 2 );
			}

			if( is_null( $post_id ) ) { return; }

			// put wp vars into our object properties
			$this->post_ID = $post_id;
			$this->post_title = get_the_title($this->post_ID);
			$this->url = get_permalink($this->post_ID);

			//Does this vehicle have photos?
			$thumbnail_id = get_post_thumbnail_id( $this->post_ID, 'medium' );
			if( ! is_wp_error( $thumbnail_id ) && ! empty( $thumbnail_id ) )
			{
				$this->image_url = wp_get_attachment_url( $thumbnail_id );
			}
			else
			{
				/**
				 * Allow the URL of the "no photo photo," or the photo we show 
				 * when a vehicle has zero photos, to be changed by other
				 * developers with a filter hook.
				 */
				$this->image_url = apply_filters( 'invp_no_photo_url', plugins_url( 'assets/no-photo.svg', dirname( __FILE__ ) ), $this->post_ID );
			}

			//get all data using the post ID
			$meta = get_post_meta( $this->post_ID );

			//get these post meta values
			foreach( $this->keys() as $key )
			{
				$filtered_key = apply_filters( 'invp_prefix_meta_key', $key );
				if( isset( $meta[$filtered_key] ) && isset( $meta[$filtered_key][0]))
				{
					if( is_array( $this->$key ) )
					{
						$this->$key = unserialize($meta[$filtered_key][0]);
					}
					else
					{
						$this->$key = trim($meta[$filtered_key][0]);
					}
				}
			}

			/**
			 * If we have transmission speeds "6" and transmission string 
			 * "Automatic", change the string to "6 Speed Automatic"
			 */
			if( ! empty( $this->transmission_speeds ) )
			{
				$prefix = sprintf(
					'%s %s',
					$this->transmission_speeds,
					__( 'Speed', 'inventory-presser' )
				);

				if( false === strpos( $this->transmission, $prefix ) )
				{
					$this->transmission = sprintf(
						'%s %s',
						$prefix,
						$this->transmission
					);
				}
			}

			$this->is_sold = false !== strpos( strtolower( $this->availability ), 'sold' );
			$this->is_wholesale = false !== strpos( strtolower( $this->availability ), 'wholesale' );
			$this->is_used = has_term( 'used', 'condition', $this->post_ID );

			/**
			 * We want the term description from the location taxonomy term
			 * because the meta key/term name only contains street address line one.
			 */
			$location_terms = wp_get_post_terms( $this->post_ID, 'location' );
			$this->location = implode( ', ', array_column( $location_terms, 'description' ) );

			//Populate the options array with the multi-valued meta field values
			if( isset( $meta[apply_filters( 'invp_prefix_meta_key', 'options_array' )] ) )
			{
				$this->options_array = $meta[apply_filters( 'invp_prefix_meta_key', 'options_array' )];
				sort( $this->options_array );
			}
		}

		/**
		 * carfax_eligible
		 * 
		 * Answers the question, "is this a vehicle for which Carfax maintains 
		 * data?" The rules are 17 digit vin and 1980 and newer.
		 *
		 * @return bool
		 */
		private function carfax_eligible()
		{
			return strlen( $this->vin ) >= 17 && $this->year >= 1980;
		}
		
		/**
		 * carfax_icon_html
		 * 
		 * Outputs Carfax button HTML or empty string if the vehicle is not 
		 * eligible or does not have a free report.
		 *
		 * @return string HTML that renders a Carfax button or empty string
		 */
		function carfax_icon_html()
		{
			if( ! $this->carfax_eligible() || ! $this->have_carfax_report() )
			{
				return '';
			}

			$svg = $this->carfax_icon_svg();
			if( empty( $svg ) )
			{
				//We might have tried to download an SVG from carfax.com and failed
				return '';
			}

			return sprintf(
				'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
				$this->carfax_report_url(),
				$this->carfax_icon_svg()
			);
		}
	
		/**
		 * carfax_icon_svg
		 * 
		 * Returns an SVG element that is one of various Carfax icons, usually
		 * containing the text, "SHOW ME THE Carfax," but sometimes also
		 * adorned with a green banner that says "GOOD VALUE."
		 *
		 * A setting this plugin provides allows users to cede control of the
		 * <svg> payload to Carfax, by using an SVG provided by a URL instead of
		 * the .svg files that ship with this plugin.
		 *
		 * @return string An <svg> HTML element or empty string
		 */
		function carfax_icon_svg()
		{
			//A per-vehicle icon URL is provided by Carfax during daily IICR
			$svg_path = $this->carfax_url_icon;
			$svg_element = '';

			/**
			 * If we don't have a URL from IICR, or the user has turned off the
			 * newer, dynamic icons, fall back to SVGs that ship with this
			 * plugin.
			 */
			$settings = INVP::settings();
			if( empty( $svg_path ) || ! $settings['use_carfax_provided_buttons'] )
			{
				//fallback to the icon that ships with this plugin
				return $this->carfax_icon_svg_bundled();
			}
			else
			{
				/**
				 * Suppressing two warnings with the @ in front of
				 * file_get_contents() is a short-term fix
				 *  - SSL: Handshake timed out
				 *  - Failed to enable crypto
				 */
				$svg_element = @file_get_contents( $svg_path );
				if( false !== $svg_element )
				{
					/**
					 * Change CSS class names in Carfax icons hosted by Carfax. They
					 * didn't anticipate anyone displaying them inline, and they
					 * get real goofy with certain combinations of duplicate CSS
					 * class names on the page.
					 */
					$stock_number_letters_and_digits = preg_replace( '/[^a-zA-Z0-9]+/', '', $this->stock_number );
					return preg_replace( '/(cls\-[0-9]+)/', '$1-' . $stock_number_letters_and_digits, $svg_element );
				}
				//SVG download from carfax.com failed, fall back to bundled icon
				return $this->carfax_icon_svg_bundled();
			}
		}

		/**
		 * carfax_icon_svg_bundled
		 *
		 * Carfax icons are bundled with this plugin, and this method returns
		 * one of them as an <svg> element.
		 *
		 * @return string An <svg> HTML element or empty string if the asset cannot be found or loaded
		 */
		private function carfax_icon_svg_bundled()
		{
			$svg_path = dirname( dirname( __FILE__ ) ) . '/assets/show-me-carfax';
			if( $this->is_carfax_one_owner() )
			{
				$svg_path .= '-1-owner';
			}
			$svg_path .= '.svg';
			$svg_element = file_get_contents( $svg_path );
			return ( false === $svg_element ? '' : $svg_element );
		}
		
		/**
		 * carfax_report_url
		 *
		 * Returns a link to this vehicle's free Carfax report or an empty 
		 * string is not available.
		 * 
		 * @return string The URL to this vehicle's free Carfax report or empty string.
		 */
		function carfax_report_url()
		{
			if( ! $this->carfax_eligible() || ! $this->have_carfax_report() )
			{
				return '';
			}

			if( ! empty( $this->carfax_url_report ) )
			{
				return $this->carfax_url_report;
			}

			/**
			 * Fallback to the pre-August-2019 URLs, save for the partner 
			 * querystring parameter.
			 */
			return 'http://www.carfax.com/VehicleHistory/p/Report.cfx?vin=' . $this->vin;
		}
		
		/**
		 * delete_attachments
		 * 
		 * Deletes all a post's attachments.
		 *
		 * @param  int $post_id A post ID
		 * @return void
		 */
		function delete_attachments( $post_id = null )
		{
			if( empty( $post_id ) )
			{
				if( empty( $this->post_ID ) )
				{
					return;
				}
				$post_id = $this->post_ID;
			}

			$attachments = get_posts( array(
				'post_parent'    => $post_id,
				'post_status'    => 'inherit',
				'post_type'      => 'attachment',
				'posts_per_page' => -1,
			) );

			foreach ( $attachments as $attachment )
			{
				wp_delete_attachment( $attachment->ID );
			}
		}

		/**
		 * down_payment
		 *
		 * Returns the down payment as a dollar amount except when it is zero.
		 * Returns empty string if the down payment is zero.
		 * 
		 * @return string The down payment formatted as a dollar amount except when the price is zero
		 */
		function down_payment()
		{
			if ( $this->is_sold )
			{
				return '';
			}

			if( empty( $this->down_payment ) )
			{
				return '';
			}

			return __( '$', 'inventory-presser' ) . number_format( $this->down_payment, 0, '.', ',' );
		}
		
		/**
		 * extract_digits
		 * 
		 * Extracts all digits from an input string and returns them as an integer.
		 * 
		 * This method is used to extract odometer values from strings. Dealers
		 * will include commas, periods instead of commas, and other characters
		 * that prevent odometers from being sorted as numbers.
		 *
		 * @param  string $str The input string from which digits will be extracted.
		 * @return int All digits in the input string in the same order, parsed as an integer.
		 */
		private function extract_digits( $str )
		{
			return abs( (int) filter_var( $str, FILTER_SANITIZE_NUMBER_INT ) );
		}

		/**
		 * extract_image_element_src
		 *
		 * Given a string containing HTML <img> element markup, extract the
		 * value of the src element and return it.
		 * 
		 * @param  string $img_element An HTML <img> element
		 * @return string The value of the src attribute
		 */
		private function extract_image_element_src( $img_element )
		{
			return preg_replace( "/\">?.*/", "", preg_replace( "/.*<img[\s\S]+src=\"/", "", $img_element ) );
		}

		/**
		 * get_book_value
		 * 
		 * Returns the higher of the two book value prices among NADA Guides and
		 * Kelley Blue Book.
		 *
		 * @return int
		 */
		function get_book_value()
		{
			return max( intval( $this->book_value_nada ), intval( $this->book_value_kbb ) );
		}

		/**
		 * get_fuel_economy_member
		 * 
		 * Makes retrieving a fuel economy data point from metadata easier.
		 *
		 * @param  int $fuel_type Specifies which of the two fuel types from which to retrieve the value.
		 * @param  string $key One of these fuel economy member suffixes: name, annual_consumption, annual_cost, annual_emissions, combined_mpg, city_mpg, highway_mpg
		 * @return string|null The meta value string corresponding to the provided $key or null
		 */
		public function get_fuel_economy_member( $fuel_type, $key )
		{
			$key = 'fuel_economy_' . $fuel_type . '_' . $key;
			return ! empty( $this->$key ) ? $this->$key : null;
		}

		/**
		 * get_images_html_array
		 * 
		 * Fill arrays of thumb and large <img> elements to simplify the use of 
		 * of vehicle photos.
		 *
		 * @param  array $sizes
		 * @return array An array of thumbnail and full size HTML <img> elements
		 */
		function get_images_html_array( $sizes )
		{
			/**
			 * Backwards compatibility to versions before 5.4.0 where the
			 * incoming argument was a string not an array.
			 */
			if( ! is_array( $sizes ) )
			{
				$sizes = array( $sizes );
			}

			$image_args = array(
				'meta_key'       => apply_filters( 'invp_prefix_meta_key', 'photo_number' ),
				'posts_per_page' => -1,
				'order'          => 'ASC',
				'orderby'        => 'meta_value_num',
				'post_mime_type' => 'image',
				'post_parent'    => $this->post_ID,
				'post_status'    => 'inherit',
				'post_type'      => 'attachment',
			);

			$images = get_posts( $image_args );

			$image_urls = array();
			foreach( $images as $image )
			{
				foreach( $sizes as $size )
				{
					$img_element = wp_get_attachment_image(
						$image->ID,
						$size,
						false,
						array( 'class' => "attachment-$size size-$size invp-image" )
					);

					$image_urls[$size][] = $img_element;

					if( 'large' == $size )
					{
						$image_urls['urls'][] = $this->extract_image_element_src( $img_element );
					}
				}
			}

			/**
			 * Backwards compatibility to versions before 5.4.0 where the
			 * incoming argument was a string not an array.
			 */
			if( 1 == sizeof( $sizes ) )
			{
				return $image_urls[$sizes[0]];
			}

			return $image_urls;
		}
	
		/**
		 * get_term_string
		 * 
		 * Returns taxonomy terms as a comma delimited string.
		 *
		 * @param  string $taxonomy The name of a taxonomy
		 * @return string A comma-separated string of term names.
		 */
		function get_term_string( $taxonomy )
		{
			$term_list = wp_get_post_terms( $this->post_ID, $taxonomy, array( 'fields' => 'names' ) );
			return implode( ', ', $term_list );
		}
		
		/**
		 * have_carfax_report
		 * 
		 * Answers the question, "does this vehicle have a free and 
		 * publicy-accessible Carfax report?"
		 *
		 * @return bool True if this vehicle has a free and publicly-accessible Carfax report.
		 */
		function have_carfax_report()
		{
			return '1' == $this->carfax_have_report;
		}

		/**
		 * indicate_post_meta_values_are_numbers
		 * 
		 * Filter callback. Helps WordPress understand which post meta values 
		 * should be treated as numbers. By default, they are all strings, and 
		 * strings sort differently than numbers.
		 *
		 * @param  string $value
		 * @param  string $meta_key
		 * @return string Returns the input $value or the string 'meta_value_num'
		 */
		function indicate_post_meta_values_are_numbers( $value, $meta_key )
		{
			return ( self::post_meta_value_is_number( $meta_key ) ? 'meta_value_num' : $value );
		}
		
		/**
		 * is_carfax_one_owner
		 * 
		 * Answers the question, "is this vehicle designated a "one owner" by 
		 * Carfax?
		 *
		 * @return bool True if this vehicle is designated as a "one owner" by Carfax
		 */
		function is_carfax_one_owner()
		{
			return '1' == $this->carfax_one_owner;
		}

		/**
		 * keys
		 *
		 * This is an array of the post meta keys this object uses. These keys
		 * must be prefixed by an apply_filters() call before use.
		 * 
		 * @return array An array of the post meta keys this vehicle object uses
		 */
		function keys()
		{
			return array_column( self::keys_and_types(), 'name' );
		}
		
		/**
		 * keys_and_types
		 * 
		 * Produces an array of arrays that define all the meta fields that
		 * define our vehicle post type.
		 *
		 * @return array An array of arrays, defining the meta fields that are registered and used by this class.
		 */
		public static function keys_and_types()
		{
			return array(
				array(
					'label'  => __( 'Availability', 'inventory_presser' ),
					'name'   => 'availability',
					'sample' => 'For Sale',
					'type'   => 'string',
				),
				array(
					'label'  => __( 'Beam', 'inventory_presser' ),
					'name'   => 'beam', //for boats
					'type'   => 'number',
				),
				array(
					'label'  => __( 'Body Style', 'inventory_presser' ),
					'name'   => 'body_style',
					'sample' => 'EXTENDED CAB PICKUP',
					'type'   => 'string',
				),
				array(
					'label'  => __( 'KBB Book Value', 'inventory_presser' ), //Kelley Blue Book
					'name'   => 'book_value_kbb',
					'sample' => 13500,
					'type'   => 'number',
				),
				array(
					'label'  => __( 'NADA Book Value', 'inventory_presser' ), //NADA Guides
					'name'   => 'book_value_nada',
					'sample' => 13500,
					'type'   => 'number',
				),
				array(
					'label'  => __( 'Car ID', 'inventory_presser' ),
					'name'   => 'car_id', //unique identifier
					'type'   => 'integer',
				),
				array(
					'label'  => __( 'Carfax Have Report', 'inventory_presser' ),
					'name'   => 'carfax_have_report',
					'type'   => 'boolean',
				),
				array(
					'label'  => __( 'Carfax One Owner', 'inventory_presser' ),
					'name'   => 'carfax_one_owner',
					'type'   => 'boolean',
				),
				array(
					'label'  => __( 'Carfax Icon URL', 'inventory_presser' ),
					'name'   => 'carfax_url_icon',
					'type'   => 'string',
				),
				array(
					'label'  => __( 'Carfax Report URL', 'inventory_presser' ),
					'name'   => 'carfax_url_report',
					'type'   => 'string',
				),
				array(
					'label'  => __( 'Color', 'inventory_presser' ),
					'name'   => 'color',
					'sample' => 'Merlot Jewel Metallic',
					'type'   => 'string',
				),
				array(
					'label'  => __( 'Condition', 'inventory_presser' ),
					'name'   => 'condition',
					'sample' => 'Used',
					'type'   => 'string',
				),
				array(
					'label'  => __( 'Cylinders', 'inventory_presser' ),
					'name'   => 'cylinders',
					'sample' => 6,
					'type'   => 'integer',
				),
				array(
					'label'  => __( 'Dealer ID', 'inventory_presser' ),
					'name' => 'dealer_id', //friday systems dealer id
					'type' => 'integer',
				),
				array(
					'label'  => __( 'Description', 'inventory_presser' ),
					'name'   => 'description',
					'sample' => 'Clean, non-smoker, must-see!',
					'type'   => 'string',
				),
				array(
					'label'  => __( 'Down Payment', 'inventory_presser' ),
					'name'   => 'down_payment',
					'sample' => 2500,
					'type'   => 'number',
				),
				array(
					'label'  => __( 'Drive Type', 'inventory_presser' ),
					'name'   => 'drive_type',
					'sample' => 'Rear Wheel Drive',
					'type'   => 'string',
				),
				array(
					'label'  => __( 'Edmunds Style ID', 'inventory_presser' ),
					'name'   => 'edmunds_style_id',
					'type'   => 'integer',
				),
				array(
					'label'  => __( 'Engine', 'inventory_presser' ),
					'name'   => 'engine',
					'sample' => '3.7L 5 cylinder',
					'type'   => 'string',
				),
				array(
					'label'  => __( 'Featured', 'inventory_presser' ),
					'name' => 'featured',
					'type' => 'boolean',
				),
				array(
					'label'  => __( 'Fuel', 'inventory_presser' ),
					'name'   => 'fuel',
					'sample' => 'Gas',
					'type'   => 'string',
				),
				array(
					'label'  => __( 'Fuel Economy Name (fuel type 1)', 'inventory_presser' ),
					'name'   => 'fuel_economy_1_name',
					'sample' => 'Regular Gasoline',
					'type'   => 'string',
				),
				array(
					'label'  => __( 'Fuel Economy Annual Fuel Consumption (fuel type 1)', 'inventory_presser' ),
					'name'   => 'fuel_economy_1_annual_consumption',
					'sample' => '13.18 barrels',
					'type'   => 'string',
				),
				array(
					'label'  => __( 'Fuel Economy Annual Fuel Cost (fuel type 1)', 'inventory_presser' ),
					'name'   => 'fuel_economy_1_annual_cost',
					'sample' => 1600,
					'type'   => 'number',
				),
				array(
					'label'  => __( 'Fuel Economy Annual Tailpipe CO2 Emissions (fuel type 1)', 'inventory_presser' ),
					'name'   => 'fuel_economy_1_annual_emissions',
					'sample' => '355 grams per mile',
					'type'   => 'string',
				),
				array(
					'label'  => __( 'Fuel Economy City (fuel type 1)', 'inventory_presser' ),
					'name'   => 'fuel_economy_1_city',
					'sample' => 22,
					'type'   => 'number',
				),
				array(
					'label'  => __( 'Fuel Economy Combined (fuel type 1)', 'inventory_presser' ),
					'name'   => 'fuel_economy_1_combined',
					'sample' => 25,
					'type'   => 'number',
				),
				array(
					'label'  => __( 'Fuel Economy Highway (fuel type 1)', 'inventory_presser' ),
					'name'   => 'fuel_economy_1_highway',
					'sample' => 31,
					'type'   => 'number',
				),
				array(
					'label'  => __( 'Fuel Economy Name (fuel type 2)', 'inventory_presser' ),
					'name'   => 'fuel_economy_2_name',
					'sample' => 'Regular Gasoline',
					'type'   => 'string',
				),
				array(
					'label'  => __( 'Fuel Economy Annual Fuel Consumption (fuel type 2)', 'inventory_presser' ),
					'name'   => 'fuel_economy_2_annual_consumption',
					'sample' => '13.18 barrels',
					'type'   => 'string',
				),
				array(
					'label'  => __( 'Fuel Economy Annual Fuel Cost (fuel type 2)', 'inventory_presser' ),
					'name'   => 'fuel_economy_2_annual_cost',
					'sample' => 1600,
					'type'   => 'number',
				),
				array(
					'label'  => __( 'Fuel Economy Annual Tailpipe CO2 Emissions (fuel type 2)', 'inventory_presser' ),
					'name'   => 'fuel_economy_2_annual_emissions',
					'sample' => '355 grams per mile',
					'type'   => 'string',
				),
				array(
					'label'  => __( 'Fuel Economy City (fuel type 2)', 'inventory_presser' ),
					'name'   => 'fuel_economy_2_city',
					'sample' => 22,
					'type'   => 'number',
				),
				array(
					'label'  => __( 'Fuel Economy Combined (fuel type 2)', 'inventory_presser' ),
					'name'   => 'fuel_economy_2_combined',
					'sample' => 25,
					'type'   => 'number',
				),
				array(
					'label'  => __( 'Fuel Economy Highway (fuel type 2)', 'inventory_presser' ),
					'name'   => 'fuel_economy_2_highway',
					'sample' => 31,
					'type'   => 'number',
				),
				array(
					'label'  => __( 'Fuel Economy Five Year Savings', 'inventory_presser' ),
					'name'   => 'fuel_economy_five_year_savings',
					'sample' => 2250,
					'type'   => 'number',
				),
				array(
					'label'  => __( 'Hull Material', 'inventory_presser' ),
					'name'   => 'hull_material', //for boats
					'type'   => 'string',
				),
				array(
					'label'  => __( 'Interior Color', 'inventory_presser' ),
					'name'   => 'interior_color',
					'sample' => 'Black',
					'type'   => 'string',
				),
				array(
					'label'  => __( 'Last Modified', 'inventory_presser' ),
					'name'   => 'last_modified',
					'sample' => 'Mon, 24 Feb 2020 08:17:37 -0500',
					'type'   => 'string',
				),
				array(
					'label'  => __( 'Leads ID', 'inventory_presser' ),
					'name'   => 'leads_id', //friday systems dealer id that receives leads
					'type'   => 'integer',
				),
				array(
					'label'  => __( 'Length', 'inventory_presser' ),
					'name'   => 'length', //for boats
					'type'   => 'integer',
				),
				array(
					'label'  => __( 'Location', 'inventory_presser' ),
					'name'   => 'location',
					'sample' => '120 Mall Blvd',
					'type'   => 'string',
				),
				array(
					'label'  => __( 'Make', 'inventory_presser' ),
					'name'   => 'make',
					'sample' => 'GMC',
					'type'   => 'string',
				),
				array(
					'label'  => __( 'Model', 'inventory_presser' ),
					'name'   => 'model',
					'sample' => 'Canyon',
					'type'   => 'string',
				),
				array(
					'label'  => __( 'MSRP', 'inventory_presser' ),
					'name'   => 'msrp',
					'sample' => 23905,
					'type'   => 'number',
				),
				array(
					'label'  => __( 'Odometer', 'inventory_presser' ),
					'name'   => 'odometer',
					'sample' => '102000',
					'type'   => 'string',
				),
				array(
					'label'  => __( 'Payment Frequency', 'inventory_presser' ),
					'name'   => 'payment',
					'sample' => 200,
					'type'   => 'number',
				),
				array(
					'label'  => __( 'Payment Frequency', 'inventory_presser' ),
					'name'   => 'payment_frequency',
					'sample' => 'monthly',
					'type'   => 'string',
				),
				array(
					'label'  => __( 'Price', 'inventory_presser' ),
					'name'   => 'price',
					'sample' => 13499,
					'type'   => 'number',
				),
				array(
					'label'  => __( 'Propulsion Type', 'inventory_presser' ),
					'name'   => 'propulsion_type',
					'sample' => 'Outboard',
					'type'   => 'string',
				),
				array(
					'label'  => __( 'Stock Number', 'inventory_presser' ),
					'name'   => 'stock_number',
					'sample' => '147907',
					'type'   => 'string',
				),
				array(
					'label'  => __( 'Title Status', 'inventory_presser' ),
					'name'   => 'title_status',
					'sample' => 'Clean',
					'type'   => 'string',
				),
				array(
					'label'  => __( 'Transmission', 'inventory_presser' ),
					'name'   => 'transmission',
					'sample' => 'Automatic',
					'type'   => 'string',
				),
				array(
					'label'  => __( 'Transmission Speeds', 'inventory_presser' ),
					'name'   => 'transmission_speeds',
					'sample' => '4',
					'type'   => 'string',
				),
				array(
					'label'  => __( 'Trim Level', 'inventory_presser' ),
					'name'   => 'trim',
					'sample' => 'SLE-1 Ext. Cab 4WD',
					'type'   => 'string',
				),
				array(
					'label'  => __( 'Type', 'inventory_presser' ),
					'name'   => 'type',
					'sample' => 'Passenger Car',
					'type'   => 'string',
				),
				array(
					'label'  => __( 'VIN', 'inventory_presser' ),
					'name'   => 'vin',
					'sample' => '1GTKTCDE1A8147907',
					'type'   => 'string',
				),
				array(
					'label'  => __( 'Is Wholesale', 'inventory_presser' ),
					'name'   => 'wholesale',
					'type'   => 'boolean',
				),
				array(
					'label'  => __( 'Year', 'inventory_presser' ),
					'name'   => 'year',
					'sample' => 2010,
					'type'   => 'integer',
				),
				array(
					'label'  => __( 'YouTube Video ID', 'inventory_presser' ),
					'name'   => 'youtube',
					'sample' => '9pBPgt4VOzM',
					'type'   => 'string',
				),
			);
		}

		/**
		 * location_sentence
		 * 
		 * Creates a short sentence identifying the dealership address where
		 * this vehicle is located. If there is only one term in the locations
		 * taxonomy containing vehicles, this method returns an empty string.
		 *
		 * @return string An HTML <div> element containing a sentence that identifies the lot where this vehicle is located.
		 */
		function location_sentence()
		{
			//How many locations does this dealer have?
			$location_terms = get_terms( 'location', array( 'hide_empty' => true ) );
			$location_count = ! is_wp_error( $location_terms ) ? sizeof( $location_terms ) : 0;

			if( 1 >= $location_count || empty( $this->location ) )
			{
				return '';
			}

			$sentence = sprintf(
				'%s %s %s <strong><address>%s</address></strong>',
				__( 'See this', 'inventory-presser' ),
				$this->make,
				__( 'at', 'inventory-presser' ),
				$this->location
			);

			$sentence = apply_filters( 'invp_vehicle_location_sentence', $sentence, $this );

			if( function_exists( 'apply_shortcodes' ) )
			{
				$sentence = apply_shortcodes( $sentence );
			}

			return sprintf(
				'<div class="vehicle-location">%s</div>',
				$sentence
			);
		}

		/**
		 * odometer
		 *
		 * @param  string $append A string to be appended to the odometer value
		 * @return string This vehicle's odometer value
		 */
		function odometer( $append = '' )
		{
			if( '0' == $this->odometer ) { return ''; }

			$odometer = '';
			if( is_numeric( $this->odometer ) )
			{
				$odometer .= number_format( $this->odometer, 0, '.', ',' );
			}
			else
			{
				$odometer .= $this->odometer;
			}
			if( $append )
			{
				$odometer .= $append;
			}
			return $odometer;
		}
		
		/**
		 * options_array
		 * 
		 * Returns an array of vehicle option strings
		 *
		 * @return array An array of this vehicle's options
		 */
		public function options_array()
		{
			if( is_array( empty( $this->options_array ) ) )
			{
				return $this->options_array;
			}
			return array();
		}

		/**
		 * payment
		 *
		 * Returns the payment as a dollar amount except when it is zero or the vehicle is sold.
		 * Returns empty string if the payment is zero or the vehicle is sold.
		 *
		 * @return string The payment formatted as a dollar amount except when the payment is zero or the vehicle is sold
		 */
		function payment()
		{
			if ( $this->is_sold )
			{
				return '';
			}

			if( empty( $this->payment ) )
			{
				return '';
			}

			return __( '$', 'inventory-presser' ) . number_format( $this->payment, 0, '.', ',' );
		}
		
		/**
		 * payments
		 * 
		 * Outputs the down payment and recurring payment in $X Down/$Y Week
		 * format.
		 *
		 * @param  string $zero_string The string to output if the down payment is zero.
		 * @param  string $separator The string that separates the down payment from the recurring payment.
		 * @return string A string like $X Down/$Y Week
		 */
		function payments( $zero_string = '', $separator = '/' )
		{
			if ( ! empty( $this->down_payment ) )
			{
				return sprintf(
					'%s %s %s %s %s',
					$this->down_payment(),
					__( 'Down', 'inventory-presser' ),
					$separator,
					$this->payment(),
					ucfirst( $this->payment_frequency )
				);
			}

			return $this->price( $zero_string );
		}
		
		/**
		 * photo_count
		 * 
		 * Returns the number of image attachments to this post. 
		 *
		 * @return int The number of attachments to this post.
		 */
		function photo_count()
		{
			if( empty( $this->post_ID ) )
			{
				return 0;
			}
			return sizeof( get_children( array( 
				'post_mime_type' => 'image',
				'post_parent'    => $this->post_ID,
				'post_type'      => 'attachment',			
				'posts_per_page' => -1,
			) ) );
		}
		
		/**
		 * post_meta_value_is_number
		 * 
		 * Indicates whether or not a provided $post_meta_key is a number data
		 * type.
		 *
		 * @param  string $post_meta_key
		 * @return bool True if the given $post_meta_key is a number data type.
		 */
		public static function post_meta_value_is_number( $post_meta_key )
		{
			$keys_and_types = self::keys_and_types();
			foreach( $keys_and_types as $key_and_type )
			{
				if( apply_filters( 'invp_prefix_meta_key', $key_and_type['name'] ) == $post_meta_key )
				{
					return 'number' == $key_and_type['type'] || 'integer' == $key_and_type['type'];
				}
			}
			return false;
		}

		/**
		 * price
		 * 
		 * Returns the price as a dollar amount except when it is zero. Returns
		 * the $zero_string when the price is zero.
		 *
		 * @param  string $zero_string The text to display when the price is zero
		 * @return string The price formatted as a dollar amount except when the price is zero
		 */		
		function price( $zero_string = '' )
		{
			//If this vehicle is sold, just say so
			if ( $this->is_sold )
			{
				return apply_filters( 'invp_sold_string', sprintf( '<span class="vehicle-sold">%s</span>', __( 'SOLD!', 'inventory-presser' ) ) );
			}

			if( '' == $zero_string )
			{
				$zero_string = apply_filters( 'invp_zero_price_string', __( 'Call For Price', 'inventory-presser' ), $this );
			}

			//How are we displaying the price?
			$settings = INVP::settings();

			if( ! isset( $settings['price_display'] ) )
			{
				$settings['price_display'] = 'default';
			}

			switch( $settings['price_display'] )
			{
				case 'msrp':
					if ( isset( $this->msrp ) && $this->msrp > 0 )
					{
						return is_numeric( $this->msrp ) ? '$' . number_format( $this->msrp, 0, '.', ',' ) : $this->msrp;
					}
					break;

				//${Price} / ${Down Payment} Down
				case 'full_or_down':

					$price = '';
					if ( $this->price > 0 )
					{
						$price .= sprintf( '$%s', number_format( $this->price, 0, '.', ',' ) );
					}

					if ( $this->down_payment > 0 )
					{
						$price .= sprintf( ' / $%s Down', number_format( $this->down_payment, 0, '.', ',' ) );
					}

					if( '' == $price )
					{
						return $zero_string;
					}
					return $price;

					break;

				// down payment only
				case 'down_only':
					if ( $this->down_payment > 0 )
					{
						return sprintf( '$%s Down', number_format( $this->down_payment, 0, '.', ',' ) );
					}
					break;

				// call_for_price
				case 'call_for_price':

					return __( 'Call For Price', 'inventory-presser' );
					break;

				// was_now_discount - MSRP = was price, regular price = now price, discount = was - now.
				case 'was_now_discount':
					if ( isset( $this->msrp )
						&& $this->msrp > 0
						&& $this->price > 0
						&& $this->msrp > $this->price
					)
					{
						return sprintf(
							'<div class="price-was-discount">%s $%s</div>%s $%s<div class="price-was-discount-save">%s $%s</div>',
							__( 'Retail', 'inventory-presser' ),
							number_format( $this->msrp, 0, '.', ',' ),
							__( 'Now', 'inventory-presser' ),
							number_format( $this->price, 0, '.', ',' ),
							__( 'You Save', 'inventory-presser' ),
							number_format( ( $this->msrp - $this->price ), 0, '.', ',' )
						);
					}
					break;

				//$75 per week
				case 'payment_only':
					if( empty( $this->payment ) || empty( $this->payment_frequency ) )
					{
						return $zero_string;
					}
					return sprintf(
						'$%s %s',
						number_format( $this->payment, 0, '.', ',' ),
						$this->payment_frequency_readable( $this->payment_frequency )
					);
					break;

				case 'default':
					//Normally, show the price field as currency.
					if( 0 == $this->price )
					{
						return $zero_string;
					}
					return '$' . number_format( $this->price, 0, '.', ',' );
					break;

				default:
					/**
					 * The price display type is something beyond what this
					 * plugin supports. Allow the value to be filtered.
					 */
					return apply_filters( 'invp_price_display', __( 'Call For Price', 'inventory-presser' ), $settings['price_display'], $this );
					break;
			}

			return $zero_string;
		}
		
		/**
		 * payment_frequency_readable
		 * 
		 * Translates a payment frequency meta value into readable words.
		 *
		 * @param  string $slug The raw payment frequency meta value.
		 * @return string A reader-friendly payment frequency like "per month"
		 */
		private function payment_frequency_readable( $slug )
		{
			switch( $slug )
			{
				case 'weekly':      return __( 'per week', 'inventory-presser' );
				case 'monthly':     return __( 'per month', 'inventory-presser' );
				case 'biweekly':    return __( 'every other week', 'inventory-presser' );
				case 'semimonthly': return __( 'twice a month', 'inventory-presser' );
				default: return '';
			}
		}
		
		/**
		 * schema_org_drive_type
		 * 
		 * Translates our drive type term name into a schema.org vehicle drive
		 * type value.
		 *
		 * @param  string $drive_type A drive type term name like "Front Wheel Drive"
		 * @return string|null A schema.org vehicle drive type string like "FrontWheelDriveConfiguration"
		 */
		function schema_org_drive_type( $drive_type )
		{
			switch( $drive_type )
			{
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
		 * schema_org_json_ld
		 *
		 * Returns Schema.org markup for this Vehicle as a JSON-LD code block
		 * 
		 * @return string Schema.org JSON script element
		 */
		function schema_org_json_ld()
		{
			$obj = [
				'@context' => 'http://schema.org/',
				'@type'    => 'Vehicle'
			];

			if( isset( $this->post_title ) && '' != $this->post_title )
			{
				$obj['name'] = $this->post_title;
			}

			if( '' != $this->make )
			{
				$obj['brand'] = [
					'@type' => 'Thing',
					'name'  => $this->make
				];
			}

			if( '' != $this->vin )
			{
				$obj['vehicleIdentificationNumber'] = $this->vin;
			}

			if( 0 != $this->year )
			{
				$obj['vehicleModelDate'] = $this->year;
			}

			//if the image does not end with 'no-photo.png'
			if( 'no-photo.png' != substr( $this->image_url, 12 ) )
			{
				$obj['image'] = $this->image_url;
			}

			if( '' != $this->odometer )
			{
				$obj['mileageFromOdometer'] = [
					'@type'    => 'QuantitativeValue',
					'value'    => $this->extract_digits( $this->odometer ),
					'unitCode' => 'SMI'
				];
			}

			if( '' != $this->engine || ( isset( $this->fuel ) && '' != $this->fuel ) )
			{
				$obj['vehicleEngine'] = [];
				if( '' != $this->engine )
				{
					$obj['vehicleEngine']['engineType'] = $this->engine;
				}
				if( isset( $this->fuel ) && '' != $this->fuel )
				{
					$obj['vehicleEngine']['fuelType'] = $this->fuel;
				}
			}

			if( '' != $this->body_style )
			{
				$obj['bodyType'] = $this->body_style;
			}

			if( '' != $this->color )
			{
				$obj['color'] = $this->color;
			}

			if( '' != $this->interior_color )
			{
				$obj['vehicleInteriorColor'] = $this->interior_color;
			}

			if( '' != $this->description )
			{
				$obj['description'] = $this->description;
			}

			$schema_drive_type = $this->schema_org_drive_type( $this->drive_type );
			if( null !== $schema_drive_type )
			{
				$obj['driveWheelConfiguration'] = $schema_drive_type;
			}

			if( isset( $this->transmission ) && '' != $this->transmission )
			{
				$obj['vehicleTransmission'] = $this->transmission;
			}

			return '<script type="application/ld+json">' . json_encode( $obj ) . '</script>';
		}
		
		/**
		 * youtube_url
		 * 
		 * Returns this vehicle's YouTube video URL or empty string.
		 *
		 * @return string This vehicle's YouTube URL or empty string
		 */
		function youtube_url()
		{
			if ( empty( $this->youtube ) )
			{
				return '';
			}

			return 'https://www.youtube.com/watch?v=' . $this->youtube;
		}
	}
}
