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
		 * carfax_icon_html
		 * 
		 * Outputs Carfax button HTML or empty string if the vehicle is not 
		 * eligible or does not have a free report.
		 *
		 * @return string HTML that renders a Carfax button or empty string
		 */
		function carfax_icon_html()
		{
			//Does this vehicle have a Carfax-eligible VIN? 
			if( strlen( invp_get_the_VIN( $this->post_ID ) ) != 17 || invp_get_the_year( $this->post_ID ) < 1980 )
			{
				return '';
			}

			/**
			 * Do we have a report? Can't just rely on there being a report URL
			 * because as long as we have a VIN we can create a fallback report
			 * URL.
			 */
			if( ! invp_have_carfax_report( $this->post_ID ) )
			{
				return '';
			}

			$svg = invp_get_the_carfax_icon_svg( $this->post_ID );
			if( empty( $svg ) )
			{
				//We might have tried to download an SVG from carfax.com and failed
				return '';
			}

			return sprintf(
				'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
				invp_get_the_carfax_url_report( $this->post_ID ),
				$svg
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
		 * @deprecated 12.0.0 Use invp_get_the_carfax_icon_svg() instead.
		 * @return string An <svg> HTML element or empty string
		 */
		function carfax_icon_svg()
		{
			return invp_get_the_carfax_icon_svg( $this->post_ID );
		}

		/**
		 * carfax_icon_svg_bundled
		 *
		 * Carfax icons are bundled with this plugin, and this method returns
		 * one of them as an <svg> element.
		 *
		 * @deprecated 12.0.0 Use invp_get_the_carfax_icon_svg_bundled() instead.
		 * @return string An <svg> HTML element or empty string if the asset cannot be found or loaded
		 */
		private function carfax_icon_svg_bundled()
		{
			return invp_get_the_carfax_icon_svg_bundled( $this->post_ID );
		}
		
		/**
		 * carfax_report_url
		 *
		 * Returns a link to this vehicle's free Carfax report or an empty 
		 * string is not available.
		 * 
		 * @deprecated 12.0.0 Use invp_get_the_carfax_url_report() instead.
		 * @return string The URL to this vehicle's free Carfax report or empty string.
		 */
		function carfax_report_url()
		{
			return invp_get_the_carfax_url_report( $this->post_ID );
		}
		
		/**
		 * down_payment
		 *
		 * Returns the down payment as a dollar amount except when it is zero.
		 * Returns empty string if the down payment is zero.
		 * 
		 * @deprecated 12.0.0 Use invp_get_the_down_payment() instead.
		 * @return string The down payment formatted as a dollar amount except when the price is zero
		 */
		function down_payment()
		{
			return invp_get_the_down_payment( $this->post_ID );
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
		 * @deprecated 12.0.0 Use invp_get_the_book_value() instead.
		 * @return int
		 */
		function get_book_value()
		{
			return invp_get_the_book_value( $this->post_ID );
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
		 * have_carfax_report
		 * 
		 * Answers the question, "does this vehicle have a free and 
		 * publicy-accessible Carfax report?"
		 *
		 * @deprecated 12.0.0 Use invp_have_carfax_report() instead.
		 * @return bool True if this vehicle has a free and publicly-accessible Carfax report.
		 */
		function have_carfax_report()
		{
			return invp_have_carfax_report( $this->post_ID );
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
			return ( INVP::meta_value_is_number( $meta_key ) ? 'meta_value_num' : $value );
		}
		
		/**
		 * is_carfax_one_owner
		 * 
		 * Answers the question, "is this vehicle designated a "one owner" by 
		 * Carfax?
		 *
		 * @deprecated 12.0.0 Use invp_is_carfax_one_owner() instead.
		 * @return bool True if this vehicle is designated as a "one owner" by Carfax
		 */
		function is_carfax_one_owner()
		{
			return invp_is_carfax_one_owner( $this->post_ID );
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
		 * @deprecated 12.0.0 Use invp_get_the_location_sentence() instead.
		 * @return string An HTML <div> element containing a sentence that identifies the lot where this vehicle is located.
		 */
		function location_sentence()
		{
			return invp_get_the_location_sentence( $this->post_ID );
		}

		/**
		 * odometer
		 *
		 * @deprecated 12.0.0 Use invp_get_the_odometer() instead.
		 * @param  string $append A string to be appended to the odometer value
		 * @return string This vehicle's odometer value
		 */
		function odometer( $append = '' )
		{
			return invp_get_the_odometer( $append, $this->post_ID );
		}
		
		/**
		 * options_array
		 * 
		 * Returns an array of vehicle option strings
		 *
		 * @deprecated 12.0.0 Use invp_get_the_options() instead.
		 * @return array An array of this vehicle's options
		 */
		public function options_array()
		{
			return invp_get_the_options( $this->post_ID );
		}

		/**
		 * payment
		 *
		 * Returns the payment as a dollar amount except when it is zero or the vehicle is sold.
		 * Returns empty string if the payment is zero or the vehicle is sold.
		 *
		 * @deprecated 12.0.0 Use invp_get_the_payment() instead.
		 * @return string The payment formatted as a dollar amount except when the payment is zero or the vehicle is sold
		 */
		function payment()
		{
			return invp_get_the_payment( $this->post_ID );
		}
		
		/**
		 * payments
		 * 
		 * Outputs the down payment and recurring payment in $X Down/$Y Week
		 * format.
		 *
		 * @deprecated 12.0.0 Use invp_get_the_price() instead.
		 * @param  string $zero_string The string to output if the down payment is zero.
		 * @param  string $separator The string that separates the down payment from the recurring payment.
		 * @return string A string like $X Down/$Y Week
		 */
		function payments( $zero_string = '', $separator = '/' )
		{
			return invp_get_the_price( $zero_string, $this->post_ID );
		}
		
		/**
		 * photo_count
		 * 
		 * Returns the number of image attachments to this post. 
		 *
		 * @deprecated 12.0.0 Use invp_get_the_photo_count() instead.
		 * @return int The number of attachments to this post.
		 */
		function photo_count()
		{
			return invp_get_the_photo_count( $this->post_ID );
		}
		
		/**
		 * post_meta_value_is_number
		 * 
		 * Indicates whether or not a provided $post_meta_key is a number data
		 * type.
		 *
		 * @deprecated 12.0.0 Use INVP::meta_value_is_number() instead.
		 * @param  string $post_meta_key
		 * @return bool True if the given $post_meta_key is a number data type.
		 */
		public static function post_meta_value_is_number( $post_meta_key )
		{
			return INVP::meta_value_is_number( $post_meta_key );
		}

		/**
		 * price
		 * 
		 * Returns the price as a dollar amount except when it is zero. Returns
		 * the $zero_string when the price is zero.
		 *
		 * @deprecated 12.0.0 Use invp_get_the_price() instead.
		 * @param  string $zero_string The text to display when the price is zero
		 * @return string
		 */		
		function price( $zero_string = '' )
		{
			_doing_it_wrong(
				__FUNCTION__,
				sprintf(
					__( '%s was deprecated in version 12.0.0. Use %s instead.', 'inventory-presser' ),
					'<code>Inventory_Presser_Vehicle->price()</code>',
					'<code>invp_get_the_price()</code>'
				),
				'12.0.0'
			);
			return invp_get_the_price( $zero_string. $this->post_ID );
		}
		
		/**
		 * youtube_url
		 * 
		 * Returns this vehicle's YouTube video URL or empty string.
		 *
		 * @deprecated 12.0.0 Use invp_get_the_youtube_url() instead.
		 * @return string This vehicle's YouTube URL or empty string
		 */
		function youtube_url()
		{
			return invp_get_the_youtube_url( $this->post_ID );
		}
	}
}
