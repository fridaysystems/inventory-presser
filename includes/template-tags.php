<?php
defined( 'ABSPATH' ) or exit;

/**
 * Template tags: functions that make it easy for other developers to get data
 * about the current vehicle.
 *
 *
 * @since      4.1.0
 * @package    Inventory_Presser
 * @subpackage Inventory_Presser/includes
 * @author     Corey Salzano <corey@friday.systems>
 */

/**
 * invp_get_the_vin
 * 
 * Returns the vehicles VIN. Must be used inside the loop.
 *
 * @return string
 */
function invp_get_the_vin()
{
	return get_post_meta( get_the_ID(), apply_filters( 'invp_prefix_meta_key', 'vin' ), true );
}

/**
 * invp_get_the_price
 *
 * Returns the vehicle's price. Must be used inside the loop.
 * 
 * @return string
 */
function invp_get_the_price()
{
	$vehicle = new Inventory_Presser_Vehicle( get_the_ID() );
	return $vehicle->price();
}
