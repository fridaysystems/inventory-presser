<?php
defined( 'ABSPATH' ) or exit;

/**
 * Inventory_Presser_Shortcode_Vin
 * 
 * Creates a shortcode that outputs a vehicle identification number.
 */
class Inventory_Presser_Shortcode_Attribute_Table
{
	/**
	 * add
	 * 
	 * Adds two shortcodes
	 *
	 * @return void
	 */
	function add()
	{
		add_shortcode( 'invp-attribute-table', array( $this, 'content' ) );
		add_shortcode( 'invp_attribute_table', array( $this, 'content' ) );
	}

	public function content( $atts )
	{
		$atts = shortcode_atts( array(
			//uh
		), $atts, 'attribute_table' ); //Use shortcode_atts_attribute_table to filter the incoming attributes

		$post_ID = get_the_ID();
		$invp_settings = INVP::settings();

		/**
		 * Build an array of items that will make up a table
		 * of vehicle attributes. If a value key is not
		 * provided, the member will be used directly on the
		 * vehicle object to find the value.
		 */
		$table_items = array();

		//Book Value
		if( ! isset( $invp_settings['price_display'] ) || 'genes' != $invp_settings['price_display'] )
		{
			$book_value = invp_get_the_book_value( $post_ID );
			if( ! empty( $book_value )
				&& invp_get_raw_book_value( $post_ID ) > invp_get_raw_price( $post_ID ) )
			{
				$table_items[] = array(
					'member' => 'book_value',
					'label'  => __( 'Book Value', 'inventory-presser' ),
					'value'  => $book_value,
				);
			}
		}

		//Odometer
		if( 'boat' != strtolower( invp_get_the_type( $post_ID ) ) )
		{
			$table_items[] = array(
				'member' => 'odometer',
				'label'  => apply_filters( 'invp_label-odometer', apply_filters( 'invp_odometer_word', __( 'Mileage', 'inventory-presser' ) ) ),
				'value'  => invp_get_the_odometer( ' ' . apply_filters( 'invp_odometer_word', 'Miles' ), $post_ID ),
			);
		}

		$table_items = array_merge( $table_items, array(

			//Transmission
			array(
				'member' => 'transmission',
				'label'  => __( 'Transmission', 'inventory-presser' ),
				'value'  => invp_get_the_transmission( $post_ID ),
			),

			//Exterior Color
			array(
				'member' => 'color',
				'label'  => __( 'Color', 'inventory_presser' ),
				'value'  => invp_get_the_color( $post_ID ),
			),

			//Drive Type
			array(
				'member' => 'drive_type',
				'label'  => __( 'Drive Type', 'inventory-presser' ),
				'value'  => invp_get_the_drive_type( $post_ID ),
			),

			//Interior Color
			array(
				'member' => 'interior_color',
				'label'  => __( 'Interior', 'inventory_presser' ),
				'value'  => invp_get_the_interior_color( $post_ID ),
			),

			//Doors
			array(
				'member' => 'doors',
				'label'  => __( 'Doors', 'inventory-presser' ),
				'value'  => invp_get_the_doors( $post_ID ),
			),

			//Stock Number
			array(
				'member' => 'stock_number',
				'label'  => __( 'Stock', 'inventory-presser' ),
				'value'  => invp_get_the_stock_number( $post_ID ),
			),

			//Fuel + Engine
			array(
				'member' => 'engine',
				'label'  => __( 'Engine', 'inventory-presser' ),
				'value'  => trim( implode( ' ', array( invp_get_the_fuel( $post_ID ), invp_get_the_engine( $post_ID ) ) ) ),
			),

			//VIN
			array(
				'member' => 'vin',
				'label'  => 'boat' == strtolower( invp_get_the_type( $post_ID ) ) ? __( 'HIN', 'inventory-presser' ) : __( 'VIN', 'inventory-presser' ),
				'value'  => invp_get_the_VIN( $post_ID ),
			),
		) );

		//Boat-specific fields
		if( 'boat' == strtolower( invp_get_the_type( $post_ID ) ) )
		{
			//Beam
			$table_items[] = array(
				'member' => 'beam',
				'label'  => __( 'Beam', 'inventory-presser' ),
			);

			//Length
			$table_items[] = array(
				'member' => 'length',
				'label'  => __( 'Length', 'inventory-presser' ),
			);

			//Hull material
			$table_items[] = array(
				'member' => 'hull_material',
				'label'  => __( 'Hull Material', 'inventory-presser' ),
			);
		}

		$table_items = apply_filters( 'invp_vehicle_attribute_table_items', $table_items );

		$html = '';
		foreach( $table_items as $item )
		{
			//does the vehicle have a value for this member?
			$member = $item['member'];
			if( empty( $item['value'] ) && empty( INVP::get_meta( $member, $post_ID ) ) )
			{
				//no
				continue;
			}

			$html .= sprintf(
				'<div class="item"><div class="label">%s</div><div class="value vehicle-content-initcaps">%s</div></div>',
				apply_filters( 'invp_label-' . $member, $item['label'] ),
				apply_filters( 'invp_vehicle_attribute_table_cell', empty( $item['value'] ) ? strtolower( INVP::get_meta( $member, $post_ID ) ) : $item['value'] )
			);
		}

		return apply_filters( 'invp_vehicle_attribute_table', $html );
	}

	/**
	 * hooks
	 * 
	 * Adds hooks that power the shortcode
	 *
	 * @return void
	 */
	function hooks()
	{
		add_action( 'init', array( $this, 'add' ) );
	}
}
