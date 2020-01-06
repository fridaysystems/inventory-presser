<?php

class Inventory_Presser_Template_Shortcode
{
	protected function vehicle_attribute_table( $vehicle )
	{
		$invp_settings = Inventory_Presser_Plugin::settings();

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
			$book_value = $vehicle->get_book_value();
			if( $book_value > 0  && $book_value > intval( $vehicle->price ) )
			{
				$table_items[] = array(
					'member' => 'book_value',
					'label'  => __( 'Book Value', 'inventory-presser' ),
					'value'  => '$' . number_format( $book_value, 0, '.', ',' ),
				);
			}
		}

		//Odometer
		if( 'boat' != $vehicle->type )
		{
			$table_items[] = array(
				'member' => 'odometer',
				'label'  => apply_filters( 'invp_label-odometer', apply_filters( 'invp_odometer_word', __( 'Mileage', 'inventory-presser' ) ) ),
				'value'  => $vehicle->odometer( ' ' . apply_filters( 'invp_odometer_word', 'Miles' ) ),
			);
		}

		$table_items = array_merge( $table_items, array(

			//Exterior Color
			array(
				'member' => 'color',
				'label'  => __( 'Color', 'inventory_presser' ),
			),

			//Interior Color
			array(
				'member' => 'interior_color',
				'label'  => __( 'Interior', 'inventory_presser' ),
			),

			//Fuel + Engine
			array(
				'member' => 'engine',
				'label'  => __( 'Engine', 'inventory-presser' ),
				'value'  => implode( ' ', array( $vehicle->fuel, $vehicle->engine ) ),
			),

			//Transmission
			array(
				'member' => 'transmission',
				'label'  => __( 'Transmission', 'inventory-presser' ),
			),

			//Drive Type
			array(
				'member' => 'drivetype',
				'label'  => __( 'Drive Type', 'inventory-presser' ),
			),

			//Stock Number
			array(
				'member' => 'stock_number',
				'label'  => __( 'Stock', 'inventory-presser' ),
			),

			//VIN
			array(
				'member' => 'vin',
				'label'  => 'boat' == $vehicle->type ? __( 'HIN', 'inventory-presser' ) : __( 'VIN', 'inventory-presser' ),
				'value'  => $vehicle->vin,
			),
		) );

		//Boat-specific fields
		if( 'boat' == $vehicle->type )
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

		$html = '';

		foreach( $table_items as $item )
		{
			//does the vehicle have a value for this member?
			$member = $item['member'];
			if( empty( $item['value'] ) && empty( $vehicle->$member ) )
			{
				//no
				continue;
			}

			$html .= sprintf(
				'<div class="item"><div class="label">%s</div><div class="value vehicle-content-initcaps">%s</div></div>',
				apply_filters( 'invp_label-' . $member, $item['label'] ),
				empty( $item['value'] ) ? strtolower( $vehicle->$member ) : $item['value']
			);
		}

		return $html;
	}
}