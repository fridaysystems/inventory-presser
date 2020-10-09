<?php
defined( 'ABSPATH' ) or exit;

/**
 * Inventory_Presser_Schema_Org_Generator
 * 
 * This class creates schema.org structured data and includes it in the JSON-LD
 * format on vehicle single pages.
 * 
 * @see https://schema.org/Vehicle
 * @since 12.0.0
 */
class Inventory_Presser_Schema_Org_Generator
{
	public function hooks()
	{
		add_action( 'wp_body_open', array( $this, 'include_json_ld' ) );
	}

	public function include_json_ld()
	{
		if( ! is_singular( INVP::POST_TYPE ) )
		{
			return;
		}

		if( ! apply_filters( 'invp_include_schema_org_json_ld', true ) )
		{
			return;
		}

		global $post;
		echo $this->schema_org_json_ld( $post->ID );
	}
	
	/**
	 * schema_org_drive_type
	 * 
	 * Translates our drive type term name into a schema.org vehicle drive
	 * type value.
	 *
	 * @param  string $drive_type A drive type term name like "Front Wheel Drive"
	 * @return string|null A schema.org vehicle drive type string like "FrontWheelDriveConfiguration"
	 */
	function schema_org_drive_type( $drive_type )
	{
		switch( $drive_type )
		{
			case 'Front Wheel Drive w/4x4':
			case 'Rear Wheel Drive w/4x4':
				return 'FourWheelDriveConfiguration';

			case 'Two Wheel Drive':
			case 'Rear Wheel Drive':
				return 'RearWheelDriveConfiguration';

			case 'Front Wheel Drive':
				return 'FrontWheelDriveConfiguration';

			case 'All Wheel Drive':
				return 'AllWheelDriveConfiguration';
		}
		return null;
	}
	
	/**
	 * schema_org_json_ld
	 *
	 * Returns Schema.org markup for this Vehicle as a JSON-LD code block
	 * 
	 * @return string Schema.org JSON script element
	 */
	function schema_org_json_ld( $post_ID )
	{
		$obj = [
			'@context' => 'http://schema.org/',
			'@type'    => 'Vehicle'
		];

		$obj['name'] = get_the_title( $post_ID );

		$vehicle = new Inventory_Presser_Vehicle( $post_ID );
		
		if( '' != $vehicle->make )
		{
			$obj['brand'] = [
				'@type' => 'Thing',
				'name'  => $vehicle->make
			];
		}

		$vin = invp_get_the_VIN( $post_ID );
		if( '' != $vin )
		{
			$obj['vehicleIdentificationNumber'] = $vin;
		}

		$year = invp_get_the_year( $post_ID );
		if( 0 != $year )
		{
			$obj['vehicleModelDate'] = $year;
		}

		//if the image does not end with 'no-photo.png'
		if( 'no-photo.png' != substr( $vehicle->image_url, 12 ) )
		{
			$obj['image'] = $vehicle->image_url;
		}

		if( '' != $vehicle->odometer )
		{
			//Extract just digits from the odometer value
			$odometer_digits = abs( (int) filter_var( $vehicle->odometer, FILTER_SANITIZE_NUMBER_INT ) );
			$obj['mileageFromOdometer'] = [
				'@type'    => 'QuantitativeValue',
				'value'    => $odometer_digits,
				'unitCode' => 'SMI'
			];
		}

		if( '' != $vehicle->engine || ( isset( $vehicle->fuel ) && '' != $vehicle->fuel ) )
		{
			$obj['vehicleEngine'] = [];
			if( '' != $vehicle->engine )
			{
				$obj['vehicleEngine']['engineType'] = $vehicle->engine;
			}
			if( isset( $vehicle->fuel ) && '' != $vehicle->fuel )
			{
				$obj['vehicleEngine']['fuelType'] = $vehicle->fuel;
			}
		}

		if( '' != $vehicle->body_style )
		{
			$obj['bodyType'] = $vehicle->body_style;
		}

		if( '' != $vehicle->color )
		{
			$obj['color'] = $vehicle->color;
		}

		if( '' != $vehicle->interior_color )
		{
			$obj['vehicleInteriorColor'] = $vehicle->interior_color;
		}

		if( '' != $vehicle->description )
		{
			$obj['description'] = $vehicle->description;
		}

		$schema_drive_type = $this->schema_org_drive_type( $vehicle->drive_type );
		if( null !== $schema_drive_type )
		{
			$obj['driveWheelConfiguration'] = $schema_drive_type;
		}

		if( isset( $vehicle->transmission ) && '' != $vehicle->transmission )
		{
			$obj['vehicleTransmission'] = $vehicle->transmission;
		}

		return '<script type="application/ld+json">' . json_encode( $obj ) . '</script>';
	}
}
