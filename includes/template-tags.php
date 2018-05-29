<?php
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

function invp_get_the_vin() {
	return get_post_meta( get_the_ID(), apply_filters( 'invp_prefix_meta_key', 'vin' ), true );
}

function invp_get_the_price() {
	return get_post_meta( get_the_ID(), apply_filters( 'invp_prefix_meta_key', 'price' ), true );
}
