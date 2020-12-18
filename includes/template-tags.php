<?php
defined( 'ABSPATH' ) or exit;

/**
 * Template tags: functions that make it easy for other developers to get data
 * about the current vehicle.
 *
 * @since      4.1.0
 * @package    Inventory_Presser
 * @subpackage Inventory_Presser/includes
 * @author     Corey Salzano <corey@friday.systems>
 */

function invp_get_the_availability( $post_ID = null )
{
	if( empty( $post_ID ) )
	{
		$post_ID = get_the_ID();
	}
	return INVP::get_meta( 'availability', $post_ID );
}

/**
 * invp_get_the_beam
 * 
 * Template tag. Boat field. A boat's width at its widest point.
 *
 * @param  int $post_ID
 * @return double
 */
function invp_get_the_beam( $post_ID = null )
{
	if( empty( $post_ID ) )
	{
		$post_ID = get_the_ID();
	}
	return INVP::get_meta( 'beam', $post_ID );
}

function invp_get_the_body_style( $post_ID = null )
{
	if( empty( $post_ID ) )
	{
		$post_ID = get_the_ID();
	}
	return INVP::get_meta( 'body_style', $post_ID );
}

/**
 * invp_get_raw_book_value
 * 
 * Template tag. Returns the raw book value price as a number and therefore no
 * string formatting or dollar sign.
 *
 * @param  mixed $post_ID
 * @return double
 */
function invp_get_raw_book_value( $post_ID = null )
{
	if( empty( $post_ID ) )
	{
		$post_ID = get_the_ID();
	}
	$raw_kbb = INVP::get_meta( 'book_value_kbb', $post_ID );
	$raw_nada = INVP::get_meta( 'book_value_nada', $post_ID );
	return max( $raw_nada, $raw_kbb );
}

/**
 * get_book_value
 * 
 * Returns the higher of the two book value prices among NADA Guides and
 * Kelley Blue Book.
 *
 * @return string
 */
function invp_get_the_book_value( $post_ID = null )
{
	if( empty( $post_ID ) )
	{
		$post_ID = get_the_ID();
	}
	return '$' . number_format( invp_get_raw_book_value( $post_ID ), 0, '.', ',' );
}

/**
 * invp_get_the_carfax_icon_html
 * 
 * Outputs Carfax button HTML or empty string if the vehicle is not 
 * eligible or does not have a free report.
 *
 * @return string HTML that renders a Carfax button or empty string
 */
function invp_get_the_carfax_icon_html( $post_ID = null )
{
	if( empty( $post_ID ) )
	{
		$post_ID = get_the_ID();
	}

	//Does this vehicle have a Carfax-eligible VIN? 
	if( strlen( invp_get_the_VIN( $post_ID ) ) != 17 || invp_get_the_year( $post_ID ) < 1980 )
	{
		return '';
	}

	/**
	 * Do we have a report? Can't just rely on there being a report URL
	 * because as long as we have a VIN we can create a fallback report
	 * URL.
	 */
	if( ! invp_have_carfax_report( $post_ID ) )
	{
		return '';
	}

	$svg_url = invp_get_the_carfax_url_svg( $post_ID );
	if( empty( $svg_url ) )
	{
		return '';
	}

	return sprintf(
		'<a href="%s" target="_blank" rel="noopener noreferrer"><img src="%s" alt="SHOW ME THE CARFAX" /></a>',
		invp_get_the_carfax_url_report( $post_ID ),
		$svg_url
	);
}

/**
 * invp_get_the_carfax_url_svg
 * 
 * Returns a URL to an SVG image button that users click to view Carfax reports
 * or empty string. Sometimes the URL points to an SVG hosted on carfax.com,
 * sometimes it's an SVG bundled with this plugin.
 *
 * @param  int $post_ID 
 * @return string A URL to an SVG image or empty string
 */
function invp_get_the_carfax_url_svg( $post_ID = null )
{
	if( empty( $post_ID ) )
	{
		$post_ID = get_the_ID();
	}

	$url = INVP::get_meta( 'carfax_url_icon', $post_ID );

	/**
	 * If we don't have a URL from Carfax IICR, or the user has turned off the
	 * newer, dynamic icons, fall back to SVGs that ship with this
	 * plugin.
	 */
	if( empty( $url ) || ! INVP::settings()['use_carfax_provided_buttons'] )
	{
		//fallback to the icon that ships with this plugin
		$url = plugin_dir_url( INVP_PLUGIN_FILE_PATH ) . '/assets/show-me-carfax';
		if( invp_is_carfax_one_owner( $post_ID ) )
		{
			$url .= '-1-owner';
		}
		$url .= '.svg';
	}

	return $url;
}

function invp_get_the_carfax_url_report( $post_ID = null )
{
	if( empty( $post_ID ) )
	{
		$post_ID = get_the_ID();
	}

	$raw = INVP::get_meta( 'carfax_url_report', $post_ID );
	if( ! empty( $raw ) )
	{
		return $raw;
	}
	
	/**
	 * Fallback to the pre-August-2019 URLs, save for the partner 
	 * querystring parameter.
	 */
	return 'http://www.carfax.com/VehicleHistory/p/Report.cfx?vin=' . invp_get_the_VIN();
}

/**
 * invp_get_the_color
 * 
 * Template tag. Returns the exterior color of the vehicle.
 *
 * @param  int $post_ID
 * @return string
 */
function invp_get_the_color( $post_ID = null )
{
	if( empty( $post_ID ) )
	{
		$post_ID = get_the_ID();
	}
	return INVP::get_meta( 'color', $post_ID );
}

function invp_get_the_description( $post_ID = null )
{
	if( empty( $post_ID ) )
	{
		$post_ID = get_the_ID();
	}
	return INVP::get_meta( 'description', $post_ID );
}

function invp_get_the_down_payment( $post_ID = null )
{
	if( empty( $post_ID ) )
	{
		$post_ID = get_the_ID();
	}
	
	$down_payment = INVP::get_meta( 'down_payment', $post_ID );
	if( empty( $down_payment ) )
	{
		return '';
	}

	return '$' . number_format( $down_payment, 0, '.', ',' );
}

/**
 * invp_get_the_drive_type
 * 
 * Template tag. Returns the drive type, or a description of how many driven 
 * wheels are on the vehicle.
 *
 * @param  int $post_ID
 * @return string
 */
function invp_get_the_drive_type( $post_ID = null )
{
	if( empty( $post_ID ) )
	{
		$post_ID = get_the_ID();
	}
	return INVP::get_meta( 'drive_type', $post_ID );
}

function invp_get_the_edmunds_style_id( $post_ID = null )
{
	if( empty( $post_ID ) )
	{
		$post_ID = get_the_ID();
	}
	return INVP::get_meta( 'edmunds_style_id', $post_ID );
}

function invp_get_the_engine( $post_ID = null )
{
	if( empty( $post_ID ) )
	{
		$post_ID = get_the_ID();
	}
	return INVP::get_meta( 'engine', $post_ID );
}

function invp_get_the_fuel( $post_ID = null )
{
	if( empty( $post_ID ) )
	{
		$post_ID = get_the_ID();
	}
	return INVP::get_meta( 'fuel', $post_ID );
}

/**
 * invp_get_the_fuel_economy_value
 * 
 * Makes retrieving a fuel economy data point from metadata easier.
 *
 * @param  string $key One of these fuel economy member suffixes: name, annual_consumption, annual_cost, annual_emissions, combined_mpg, city_mpg, highway_mpg
 * @param  int $fuel_type Specifies which of the two fuel types from which to retrieve the value.
 * @return string The meta value string corresponding to the provided $key or empty string.
 */
function invp_get_the_fuel_economy_value( $key, $fuel_type = 1, $post_ID = null )
{
	if( empty( $post_ID ) )
	{
		$post_ID = get_the_ID();
	}
	
	/**
	 * The meta key fuel_economy_five_year_savings does not apply to either fuel
	 * type, so ignore $fuel_type when this key is passed.
	 */
	if( 'fuel_economy_five_year_savings' == $key )
	{
		$key = 'fuel_economy_' . $key;
	}
	else
	{
		$key = 'fuel_economy_' . $fuel_type . '_' . $key;
	}

	return INVP::get_meta( $key, $post_ID );
}

/**
 * invp_get_the_hull_material
 * 
 * Template tag. Boat field.
 *
 * @param  int $post_ID
 * @return string
 */
function invp_get_the_hull_material( $post_ID = null )
{
	if( empty( $post_ID ) )
	{
		$post_ID = get_the_ID();
	}
	return INVP::get_meta( 'hull_material', $post_ID );
}

function invp_get_the_interior_color( $post_ID = null )
{
	if( empty( $post_ID ) )
	{
		$post_ID = get_the_ID();
	}
	return INVP::get_meta( 'interior_color', $post_ID );
}

/**
 * invp_get_the_length
 * 
 * Template tag. Boat field. Returns the length of the boat.
 *
 * @param  int $post_ID
 * @return int
 */
function invp_get_the_length( $post_ID = null )
{
	if( empty( $post_ID ) )
	{
		$post_ID = get_the_ID();
	}
	return INVP::get_meta( 'length', $post_ID );
}

/**
 * invp_get_the_location
 *
 * Template tag. Returns the address where this vehicle is located. Address may
 * contain line break characters.
 * 
 * The location taxonomy terms contain full street addresses in the term 
 * description.
 * 
 * @param  int $post_ID
 * @return string
 */
function invp_get_the_location( $post_ID = null )
{
	if( empty( $post_ID ) )
	{
		$post_ID = get_the_ID();
	}

	/**
	 * We want the term description from the location taxonomy term
	 * because the meta key/term name only contains street address line one.
	 */
	$location_terms = wp_get_post_terms( $post_ID, 'location' );
	return implode( ', ', array_column( $location_terms, 'description' ) );
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
function invp_get_the_location_sentence( $post_ID = null )
{
	if( empty( $post_ID ) )
	{
		$post_ID = get_the_ID();
	}

	/**
	 * How many locations *with vehicles* does this dealer have? If only one, 
	 * return empty string because there's no reason to point out where this 
	 * vehicle is, the dealership address is all over the website.
	 */
	$location_terms = get_terms( 'location', array( 'hide_empty' => true ) );
	$location_count = ! is_wp_error( $location_terms ) ? sizeof( $location_terms ) : 0;

	if( 1 >= $location_count )
	{
		return '';
	}

	/**
	 * We want the term description from the location taxonomy term because the 
	 * meta key/term name only contains street address line one. The term 
	 * description has the full address.
	 */
	$location_terms = wp_get_post_terms( $post_ID, 'location' );
	
	//Could have two locations on the same vehicle, so just take the first
	$location = str_replace( chr( 13 ) . chr( 10 ), ', ', $location_terms[0]->description );
	if( empty( $location ) )
	{
		return '';
	}

	$sentence = sprintf(
		'%s %s %s <strong><address>%s</address></strong>',
		__( 'See this', 'inventory-presser' ),
		invp_get_the_make( $post_ID ),
		__( 'at', 'inventory-presser' ),
		apply_filters( 'invp_vehicle_location_sentence_address', $location )
	);

	//Does this location have a phone number?
	$phones = Inventory_Presser_Taxonomies::get_phones( $location_terms[0]->term_id );
	if( 0 < sizeof( $phones ) )
	{
		//Yes, at least one.
		foreach( $phones as $phone )
		{
			//Try to avoid fax numbers
			if( preg_match( '/\bfax\b/i', $phone['description'] ) )
			{
				continue;
			}

			$number = apply_filters( 'invp_vehicle_location_sentence_phone', $phone['number'] );
			$sentence .= sprintf( 
				'<span class="location-phone">%s <a href="tel:+%s">%s</a></span>',
				__( 'Call', 'inventory-presser' ),
				INVP::prepare_phone_number_for_link( $number ),
				$number
			);
			break; //only add one phone number to the sentence
		}
	}

	$sentence = apply_filters( 'invp_vehicle_location_sentence', $sentence, $post_ID );

	if( function_exists( 'apply_shortcodes' ) )
	{
		$sentence = apply_shortcodes( $sentence );
	}

	return $sentence;
}

function invp_get_the_make( $post_ID = null )
{
	if( empty( $post_ID ) )
	{
		$post_ID = get_the_ID();
	}
	return INVP::get_meta( 'make', $post_ID );
}

function invp_get_the_model( $post_ID = null )
{
	if( empty( $post_ID ) )
	{
		$post_ID = get_the_ID();
	}
	return INVP::get_meta( 'model', $post_ID );
}

function invp_get_the_msrp( $post_ID = null )
{
	if( empty( $post_ID ) )
	{
		$post_ID = get_the_ID();
	}

	$msrp = INVP::get_meta( 'msrp', $post_ID );
	if( empty( $msrp ) )
	{
		return '';
	}

	return '$' . number_format( $msrp, 0, '.', ',' );
}

/**
 * invp_get_the_odometer
 * 
 * Template tag. Returns the odometer formatted as a number with comma separators if it is numeric. Returns any other non-zero value without any formatting. Adds the $append value to any return value but an empty string.
 *
 * @param  string $append A string to append after the odometer value. If the vehicle has no odometer value, then this parameter is ignored.
 * @param  int $post_ID
 * @return string
 */
function invp_get_the_odometer( $append = '', $post_ID = null )
{
	$raw = INVP::get_meta( 'odometer', $post_ID );
	if( '0' == $raw )
	{
		return '';
	}

	$odometer = '';
	if( is_numeric( $raw ) )
	{
		$odometer .= number_format( $raw, 0, '.', ',' );
	}
	else
	{
		$odometer .= $raw;
	}

	//Did the user pass a string to append?
	if( $append )
	{
		$odometer .= $append;
	}
	return $odometer;
}

/**
 * invp_get_the_options
 *
 * @param  int $post_ID
 * @return array An array of vehicle options
 */
function invp_get_the_options( $post_ID = null )
{
	if( empty( $post_ID ) )
	{
		$post_ID = get_the_ID();
	}

	$raw = INVP::get_meta( 'options_array', $post_ID );
	if( empty( $raw ) )
	{
		return array();
	}
	sort( $raw );
	return $raw;
}

function invp_get_the_payment( $post_ID = null )
{
	if( empty( $post_ID ) )
	{
		$post_ID = get_the_ID();
	}

	if ( invp_is_sold( $post_ID ) )
	{
		return '';
	}

	$payment = INVP::get_meta( 'payment', $post_ID );
	if( empty( $payment ) )
	{
		return '';
	}

	return '$' . number_format( $payment, 0, '.', ',' );
}

function invp_get_the_payment_frequency( $post_ID = null )
{
	if( empty( $post_ID ) )
	{
		$post_ID = get_the_ID();
	}
	return INVP::get_meta( 'payment_frequency', $post_ID );
}

/**
 * invp_get_the_photo_count
 * 
 * Template tag. Returns the number of images attached to the vehicle post.
 *
 * @param  int $post_ID
 * @return int
 */
function invp_get_the_photo_count( $post_ID = null )
{
	if( empty( $post_ID ) )
	{
		$post_ID = get_the_ID();
	}

	return sizeof( get_children( array( 
		'post_mime_type' => 'image',
		'post_parent'    => $post_ID,
		'post_type'      => 'attachment',
		'posts_per_page' => -1,
	) ) );
}

/**
 * invp_get_the_photo_url
 *
 * @param  int $post_ID
 * @return string A URL that points to a photo
 */
function invp_get_the_photo_url( $size = 'medium', $post_ID = null )
{
	if( empty( $size ) )
	{
		$size = 'medium';
	}

	if( empty( $post_ID ) )
	{
		$post_ID = get_the_ID();
	}

	$thumbnail_id = get_post_thumbnail_id( $post_ID, $size );
	if( ! is_wp_error( $thumbnail_id ) && ! empty( $thumbnail_id ) )
	{
		return wp_get_attachment_url( $thumbnail_id );
	}

	return apply_filters( 'invp_no_photo_url', plugins_url( 'assets/no-photo.svg', dirname( __FILE__ ) ), $post_ID );	
}

/**
 * invp_get_the_photos
 * 
 * Fill arrays of thumb and large <img> elements and URLs to simplify the use of 
 * of vehicle photos.
 *
 * @param  array $sizes
 * @return array An array of thumbnail and full size HTML <img> elements plus URLs
 */
function invp_get_the_photos( $sizes, $post_ID = null )
{
	/**
	 * Backwards compatibility to versions before 5.4.0 where the
	 * incoming argument was a string not an array.
	 */
	if( ! is_array( $sizes ) )
	{
		$sizes = array( $sizes );
	}

	if( empty( $post_ID ) )
	{
		$post_ID = get_the_ID();
	}

	$image_args = array(
		'meta_key'       => apply_filters( 'invp_prefix_meta_key', 'photo_number' ),
		'posts_per_page' => -1,
		'order'          => 'ASC',
		'orderby'        => 'meta_value_num',
		'post_mime_type' => 'image',
		'post_parent'    => $post_ID,
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
				$image_urls['urls'][] = INVP::extract_image_element_src( $img_element );
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

function invp_get_raw_price( $post_ID = null )
{
	if( empty( $post_ID ) )
	{
		$post_ID = get_the_ID();
	}
	return INVP::get_meta( 'price', $post_ID );
}

/**
 * invp_get_the_price
 *
 * Template tag. Returns the vehicle's price.
 * 
 * Returns the price as a dollar amount except when it is zero. Returns
 * the $zero_string when the price is zero.
 * 
 * @param  string $zero_string The text to display when the price is zero
 * @param  int|null $post_ID The post ID of a vehicle. Must be passed when using this method outside the loop.
 * @return string
 */
function invp_get_the_price( $zero_string = null, $post_ID = null )
{
	if( empty( $zero_string ) )
	{
		$zero_string = __( 'Call For Price', 'inventory-presser' );
	}

	if( empty( $post_ID ) )
	{
		$post_ID = get_the_ID();
	}

	//If this vehicle is sold, just say so
	if ( invp_is_sold( $post_ID ) )
	{
		return apply_filters( 'invp_sold_string', sprintf( '<span class="vehicle-sold">%s</span>', __( 'SOLD!', 'inventory-presser' ) ) );
	}

	if( '' == $zero_string )
	{
		$zero_string = apply_filters( 'invp_zero_price_string', $zero_string, $post_ID );
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
			return invp_get_the_msrp( $post_ID );

		//${Price} / ${Down Payment} Down
		case 'full_or_down':
			$output = '';
			$price = invp_get_raw_price( $post_ID );
			if( ! empty( $price ) )
			{
				$output .= sprintf( '$%s', number_format( $price, 0, '.', ',' ) );
			}
			
			$down_payment = invp_get_the_down_payment();
			if( ! empty( $down_payment ) )
			{
				$output .= sprintf( ' / %s Down', $down_payment );
			}

			if( '' == $output )
			{
				return $zero_string;
			}
			return $output;

		// down payment only
		case 'down_only':
			$down_payment = invp_get_the_down_payment();
			if( ! empty( $down_payment ) )
			{
				return sprintf( '%s Down', $down_payment );
			}
			break;

		// call_for_price
		case 'call_for_price':
			//Not $zero_string, but explicity "Call for Price"
			return __( 'Call For Price', 'inventory-presser' );
			break;

		// was_now_discount - MSRP = was price, regular price = now price, discount = was - now.
		case 'was_now_discount':
			$msrp = INVP::get_meta( 'msrp', $post_ID );
			$price = invp_get_raw_price( $post_ID );
			if( ! empty( $msrp )
				&& ! empty( $price )
				&& $msrp > $price
			)
			{
				return sprintf(
					'<div class="price-was-discount">%s %s</div>%s $%s<div class="price-was-discount-save">%s $%s</div>',
					__( 'Retail', 'inventory-presser' ),
					invp_get_the_msrp( $post_ID ),
					__( 'Now', 'inventory-presser' ),
					number_format( $price, 0, '.', ',' ),
					__( 'You Save', 'inventory-presser' ),
					number_format( ( $msrp - $price ), 0, '.', ',' )
				);
			}
			break;

		//$75 per week
		case 'payment_only':
			$payment = invp_get_the_payment( $post_ID );
			$payment_frequency = invp_get_the_payment_frequency( $post_ID );
			if( empty( $payment ) || empty( $payment_frequency ) )
			{
				return $zero_string;
			}

			switch( $payment_frequency )
			{
				case 'weekly':
					$payment_frequency = __( 'per week', 'inventory-presser' );
					break;

				case 'monthly':
					$payment_frequency = __( 'per month', 'inventory-presser' );
					break;

				case 'biweekly':
					$payment_frequency = __( 'every other week', 'inventory-presser' );
					break;

				case 'semimonthly':
					$payment_frequency = __( 'twice a month', 'inventory-presser' );
					break;
			}
			return sprintf(
				'%s %s',
				$payment,
				$payment_frequency
			);
			break;

		case 'default':
			//Normally, show the price field as currency.
			$price = invp_get_raw_price( $post_ID );
			if( empty( $price ) )
			{
				return $zero_string;
			}
			return '$' . number_format( $price, 0, '.', ',' );
			break;

		case 'down_and_payment':
			$down_payment = invp_get_the_down_payment();
			$payment = invp_get_the_payment( $post_ID );
			if ( ! empty( $down_payment ) && ! empty( $payment ) )
			{
				return sprintf(
					'%s %s / %s %s',
					$down_payment,
					__( 'Down', 'inventory-presser' ),
					$payment,
					ucfirst( invp_get_the_payment_frequency() )
				);
			}

		default:
			/**
			 * The price display type is something beyond what this
			 * plugin supports. Allow the value to be filtered.
			 */
			return apply_filters( 'invp_price_display', $zero_string, $settings['price_display'], $post_ID );
			break;
	}

	return $zero_string;
}

function invp_get_the_stock_number( $post_ID = null )
{
	if( empty( $post_ID ) )
	{
		$post_ID = get_the_ID();
	}
	return INVP::get_meta( 'stock_number', $post_ID );
}

function invp_get_the_title_status( $post_ID = null )
{
	if( empty( $post_ID ) )
	{
		$post_ID = get_the_ID();
	}
	return INVP::get_meta( 'title_status', $post_ID );
}

function invp_get_the_transmission( $post_ID = null )
{
	if( empty( $post_ID ) )
	{
		$post_ID = get_the_ID();
	}
	$raw = INVP::get_meta( 'transmission', $post_ID );

	/**
	 * If we have transmission speeds "6" and transmission string 
	 * "Automatic", change the string to "6 Speed Automatic"
	 */
	if( ! empty( invp_get_the_transmission_speeds( $post_ID ) ) )
	{
		$prefix = sprintf(
			'%s %s',
			invp_get_the_transmission_speeds( $post_ID ),
			__( 'Speed', 'inventory-presser' )
		);

		if( false === strpos( $raw, $prefix ) )
		{
			$raw = sprintf(
				'%s %s',
				$prefix,
				$raw
			);
		}
	}

	return $raw;
}

function invp_get_the_transmission_speeds( $post_ID )
{
	if( empty( $post_ID ) )
	{
		$post_ID = get_the_ID();
	}
	return INVP::get_meta( 'transmission_speeds', $post_ID );
}

function invp_get_the_trim( $post_ID = null )
{
	if( empty( $post_ID ) )
	{
		$post_ID = get_the_ID();
	}
	return INVP::get_meta( 'trim', $post_ID );
}

function invp_get_the_type( $post_ID = null )
{
	if( empty( $post_ID ) )
	{
		$post_ID = get_the_ID();
	}
	return INVP::get_meta( 'type', $post_ID );
}

/**
 * invp_get_the_VIN
 * 
 * Template tag. Returns the vehicles VIN.
 *
 * @return string
 */
function invp_get_the_VIN( $post_ID = null )
{
	if( empty( $post_ID ) )
	{
		$post_ID = get_the_ID();
	}
	return INVP::get_meta( 'vin', $post_ID );
}

function invp_get_the_year( $post_ID = null )
{
	if( empty( $post_ID ) )
	{
		$post_ID = get_the_ID();
	}
	return INVP::get_meta( 'year', $post_ID );
}

/**
 * invp_get_the_youtube_url
 * 
 * Returns this vehicle's YouTube video URL or empty string.
 *
 * @return string This vehicle's YouTube URL or empty string
 */
function invp_get_the_youtube_url( $post_ID = null )
{
	if( empty( $post_ID ) )
	{
		$post_ID = get_the_ID();
	}

	$video_id = INVP::get_meta( 'youtube', $post_ID );
	if ( empty( $video_id ) )
	{
		return '';
	}

	return 'https://www.youtube.com/watch?v=' . $video_id;
}

function invp_have_carfax_report( $post_ID = null )
{
	if( empty( $post_ID ) )
	{
		$post_ID = get_the_ID();
	}

	return ! empty( INVP::get_meta( 'carfax_have_report', $post_ID ) );
}

/**
 * invp_is_carfax_one_owner
 * 
 * Answers the question, "is this vehicle designated a "one owner" by 
 * Carfax?
 *
 * @return bool True if this vehicle is designated as a "one owner" by Carfax
 */
function invp_is_carfax_one_owner( $post_ID = null )
{
	if( empty( $post_ID ) )
	{
		$post_ID = get_the_ID();
	}

	return ! empty( INVP::get_meta( 'carfax_one_owner', $post_ID ) );
}

function invp_is_sold( $post_ID = null )
{
	if( empty( $post_ID ) )
	{
		$post_ID = get_the_ID();
	}
	return false !== strpos( strtolower( INVP::get_meta( 'availability', $post_ID ) ), 'sold' );
}

function invp_is_used( $post_ID = null )
{
	if( empty( $post_ID ) )
	{
		$post_ID = get_the_ID();
	}
	return has_term( 'used', 'condition', $post_ID );
}

function invp_is_wholesale( $post_ID = null )
{
	if( empty( $post_ID ) )
	{
		$post_ID = get_the_ID();
	}
	return false !== strpos( strtolower( INVP::get_meta( 'availability', $post_ID ) ), 'wholesale' );
}