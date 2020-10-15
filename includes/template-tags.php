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
	 * How many locations does this dealer have? If only one, return empty 
	 * string because there's no reason to point out where this vehicle is, the
	 * dealership address is all over the website.
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
	$location = implode( ', ', array_column( $location_terms, 'description' ) );

	if( empty( $location ) )
	{
		return '';
	}

	$sentence = sprintf(
		'%s %s %s <strong><address>%s</address></strong>',
		__( 'See this', 'inventory-presser' ),
		invp_get_the_make( $post_ID ),
		__( 'at', 'inventory-presser' ),
		$location
	);

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
			$price = INVP::get_meta( 'price', $post_ID );
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
			$price = INVP::get_meta( 'price', $post_ID );
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
			$price = INVP::get_meta( 'price', $post_ID );
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

/**
 * invp_get_the_VIN
 * 
 * Template tag. Returns the vehicles VIN. Must be used inside the loop.
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