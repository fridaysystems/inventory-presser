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
			_doing_it_wrong(
				__FUNCTION__,
				__( 'The vehicle class Inventory_Presser_Vehicle was deprecated as a method to access vehicle attributes in version 12.0.0. Use template tags instead. See https://inventorypresser.com/docs/template-tags/', 'inventory-presser' ),
				'12.0.0'
			);

			//Help the order by logic determine which post meta keys are numbers
			if( ! has_filter( 'invp_meta_value_or_meta_value_num', array( $this, 'indicate_post_meta_values_are_numbers' ) ) )
			{
				add_filter( 'invp_meta_value_or_meta_value_num', array( $this, 'indicate_post_meta_values_are_numbers' ), 10, 2 );
			}

			if( is_null( $post_id ) ) { return; }

			$this->post_ID = $post_id;
			$this->image_url = invp_get_the_photo_url( 'medium', $post_id );

			//get all data using the post ID
			$meta = get_post_meta( $post_id );

			//get these post meta values
			foreach( INVP::keys() as $key )
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

			$this->transmission = invp_get_the_transmission( $post_id );
			$this->is_sold = invp_is_sold( $post_id );
			$this->is_wholesale = invp_is_wholesale( $post_id );
			$this->is_used = invp_is_used( $post_id );
			$this->location = invp_get_the_location( $post_id );
			$this->options_array = invp_get_the_options( $post_id );
		}
		
		/**
		 * carfax_icon_html
		 * 
		 * Outputs Carfax button HTML or empty string if the vehicle is not 
		 * eligible or does not have a free report.
		 *
		 * @deprecated 12.0.0 Use invp_get_the_carfax_icon_html() instead.
		 * @return string HTML that renders a Carfax button or empty string
		 */
		function carfax_icon_html()
		{
			return invp_get_the_carfax_icon_html( $this->post_ID );
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
		 * @deprecated 12.0.0 Use invp_get_the_fuel_economy_value() instead.
		 * @param  int $fuel_type Specifies which of the two fuel types from which to retrieve the value.
		 * @param  string $key One of these fuel economy member suffixes: name, annual_consumption, annual_cost, annual_emissions, combined_mpg, city_mpg, highway_mpg
		 * @return string|null The meta value string corresponding to the provided $key or null
		 */
		public function get_fuel_economy_member( $fuel_type, $key )
		{
			return invp_get_the_fuel_economy_value( $fuel_type, $key, $this->post_ID );
		}

		/**
		 * get_images_html_array
		 * 
		 * Fill arrays of thumb and large <img> elements to simplify the use of 
		 * of vehicle photos.
		 *
		 * @deprecated 12.0.0 Use invp_get_the_photos() instead.
		 * @param  array $sizes
		 * @return array An array of thumbnail and full size HTML <img> elements
		 */
		function get_images_html_array( $sizes )
		{
			return invp_get_the_photos( $sizes, $this->post_ID );
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
