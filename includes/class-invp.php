<?php
defined( 'ABSPATH' ) or exit;

class INVP
{
	/**
	 * The prefix given to all our post meta keys
	 */
	public static function meta_prefix()
	{
		return apply_filters( 'invp_meta_prefix', 'inventory_presser_' );
	}

	/**
	 * Get this plugin's option mingled with default values
	 */
	public static function settings()
	{
		$defaults = array(
			'sort_vehicles_by'            => 'make',
			'sort_vehicles_order'         => 'ASC',
			'use_carfax_provided_buttons' => true,
		);
		return wp_parse_args( get_option( Inventory_Presser_Plugin::OPTION_NAME ), $defaults );
	}

	/**
	 * @return string|Array|false
	 */
	public static function weekdays( $zero_through_six = null )
	{
		$days = array(
			'monday'    => __( 'Mon', 'inventory-presser' ),
			'tuesday'   => __( 'Tue', 'inventory-presser' ),
			'wednesday' => __( 'Wed', 'inventory-presser' ),
			'thursday'  => __( 'Thu', 'inventory-presser' ),
			'friday'    => __( 'Fri', 'inventory-presser' ),
			'saturday'  => __( 'Sat', 'inventory-presser' ),
			'sunday'    => __( 'Sun', 'inventory-presser' ),
		);

		/**
		 * If a valid parameter is provided, return the lowercase day name
		 * it identifies.
		 */
		if( null !== $zero_through_six )
		{
			if( 0 <= $zero_through_six && 6 >= $zero_through_six )
			{
				return array_keys( $days )[$zero_through_six];
			}
			else
			{
				//If an invalid parameter is provided, return false
				return false;
			}
		}

		//Return the array
		return $days;
	}

}
