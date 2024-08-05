<?php
/**
 * Inventory_Presser_Schema_Org_Generator
 *
 * This class creates schema.org structured data and includes it in the JSON-LD
 * format on vehicle single pages.
 *
 * @package inventory-presser
 * @see   https://schema.org/Vehicle
 * @since 12.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Inventory_Presser_Schema_Org_Generator
 */
class Inventory_Presser_Schema_Org_Generator {

	/**
	 * Adds hooks that power the feature.
	 *
	 * @return void
	 */
	public function add_hooks() {
		add_action( 'wp_body_open', array( $this, 'include_json_ld' ) );
	}

	/**
	 * Decides whether to output schema.org data and outputs the data.
	 *
	 * @return void
	 */
	public function include_json_ld() {
		if ( ! is_singular( INVP::POST_TYPE ) ) {
			return;
		}

		if ( ! apply_filters( 'invp_include_schema_org_json_ld', true ) ) {
			return;
		}

		global $post;
		echo $this->schema_org_json_ld( $post->ID );
	}

	/**
	 * Translates our drive type term name into a schema.org vehicle drive
	 * type value.
	 *
	 * @param  string $drive_type A drive type term name like "Front Wheel Drive"
	 * @return string|null A schema.org vehicle drive type string like "FrontWheelDriveConfiguration"
	 */
	protected function schema_org_drive_type( $drive_type ) {
		switch ( $drive_type ) {
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
	 * Returns Schema.org markup for this Vehicle as a JSON-LD code block
	 *
	 * @param  int $post_ID The current post ID.
	 * @return string Schema.org JSON script element
	 */
	protected function schema_org_json_ld( $post_ID ) {
		$obj = array(
			'@context' => 'https://schema.org/',
			'@type'    => 'Vehicle',
		);

		$obj['name'] = get_the_title( $post_ID );

		$make = invp_get_the_make( $post_ID );
		if ( '' !== $make ) {
			$obj['brand'] = array(
				'@type' => 'Thing',
				'name'  => $make,
			);
		}

		$vin = invp_get_the_VIN( $post_ID );
		if ( '' !== $vin ) {
			$obj['vehicleIdentificationNumber'] = $vin;
		}

		$year = invp_get_the_year( $post_ID );
		if ( 0 !== $year ) {
			$obj['vehicleModelDate'] = $year;
		}

		// Do we have photos?
		if ( 0 < invp_get_the_photo_count( $post_ID ) ) {
			$obj['image'] = invp_get_the_photo_url( $post_ID );
		}

		$odometer = invp_get_the_odometer( '', $post_ID );
		if ( '' !== $odometer ) {
			// Extract just digits from the odometer value.
			$odometer_digits            = abs( (int) filter_var( $odometer, FILTER_SANITIZE_NUMBER_INT ) );
			$obj['mileageFromOdometer'] = array(
				'@type'    => 'QuantitativeValue',
				'value'    => $odometer_digits,
				'unitCode' => 'SMI',
			);
		}

		if ( '' !== invp_get_the_engine( $post_ID ) || '' !== invp_get_the_fuel( $post_ID ) ) {
			$obj['vehicleEngine'] = array();
			if ( '' !== invp_get_the_engine( $post_ID ) ) {
				$obj['vehicleEngine']['engineType'] = invp_get_the_engine( $post_ID );
			}
			if ( '' !== invp_get_the_fuel( $post_ID ) ) {
				$obj['vehicleEngine']['fuelType'] = invp_get_the_fuel( $post_ID );
			}
		}

		if ( '' !== invp_get_the_body_style( $post_ID ) ) {
			$obj['bodyType'] = invp_get_the_body_style( $post_ID );
		}

		if ( '' !== invp_get_the_color( $post_ID ) ) {
			$obj['color'] = invp_get_the_color( $post_ID );
		}

		if ( '' !== invp_get_the_interior_color( $post_ID ) ) {
			$obj['vehicleInteriorColor'] = invp_get_the_interior_color( $post_ID );
		}

		if ( invp_get_the_description( $post_ID ) ) {
			$obj['description'] = invp_get_the_description( $post_ID );
		}

		$schema_drive_type = $this->schema_org_drive_type( invp_get_the_drive_type( $post_ID ) );
		if ( null !== $schema_drive_type ) {
			$obj['driveWheelConfiguration'] = $schema_drive_type;
		}

		if ( '' !== invp_get_the_transmission( $post_ID ) ) {
			$obj['vehicleTransmission'] = invp_get_the_transmission( $post_ID );
		}

		return '<script type="application/ld+json">' . wp_json_encode( $obj ) . '</script>';
	}
}
