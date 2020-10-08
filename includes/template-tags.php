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

function invp_get_the_down_payment( $post_ID = null )
{
	$down_payment = INVP::get_meta( 'down_payment', $post_ID );
	if( empty( $down_payment ) )
	{
		return '';
	}

	return '$' . number_format( $down_payment, 0, '.', ',' );
}

function invp_get_the_msrp( $post_ID = null )
{
	return INVP::get_meta( 'msrp', $post_ID );
}

function invp_get_the_payment( $post_ID = null )
{
	return INVP::get_meta( 'payment', $post_ID );
}

function invp_get_the_payment_frequency( $post_ID = null )
{
	return INVP::get_meta( 'payment_frequency', $post_ID );
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
			$msrp = invp_get_the_msrp( $post_ID );
			if( ! empty( $msrp ) )
			{
				return is_numeric( $msrp ) ? '$' . number_format( $msrp, 0, '.', ',' ) : $msrp;
			}
			break;

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
			$msrp = invp_get_the_msrp( $post_ID );
			$price = INVP::get_meta( 'price', $post_ID );
			if( ! empty( $msrp )
				&& ! empty( $price )
				&& $msrp > $price
			)
			{
				return sprintf(
					'<div class="price-was-discount">%s $%s</div>%s $%s<div class="price-was-discount-save">%s $%s</div>',
					__( 'Retail', 'inventory-presser' ),
					number_format( $msrp, 0, '.', ',' ),
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
				'$%s %s',
				number_format( $payment, 0, '.', ',' ),
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
	return INVP::get_meta( 'vin', $post_ID );
}

function invp_is_sold( $post_ID = null )
{
	return false !== strpos( strtolower( INVP::get_meta( 'availability', $post_ID ) ), 'sold' );
}
