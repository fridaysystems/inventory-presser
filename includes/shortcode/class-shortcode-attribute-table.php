<?php
/**
 * Creates a shortcode [invp_attribute_table].
 *
 * @package inventory-presser
 * @author Corey Salzano <corey@friday.systems>
 */

defined( 'ABSPATH' ) || exit;

/**
 * Inventory_Presser_Shortcode_Attribute_Table
 *
 * Creates a shortcode that outputs a vehicle identification number.
 */
class Inventory_Presser_Shortcode_Attribute_Table {

	/**
	 * Adds two shortcodes.
	 *
	 * @return void
	 */
	public function add() {
		add_shortcode( 'invp-attribute-table', array( $this, 'content' ) );
		add_shortcode( 'invp_attribute_table', array( $this, 'content' ) );
	}

	/**
	 * Adds hooks that power the shortcode.
	 *
	 * @return void
	 */
	public function add_hooks() {
		add_action( 'init', array( $this, 'add' ) );
	}

	/**
	 * Creates the string content that replaces the shortcode.
	 *
	 * @param  array $atts Shortcode attributes.
	 * @return string
	 */
	public function content( $atts ) {
		$atts = shortcode_atts(
			array(
			// uh.
			),
			$atts,
			'attribute_table'
		); // Use shortcode_atts_attribute_table to filter the incoming attributes.

		$post_ID       = get_the_ID();
		$invp_settings = INVP::settings();

		/**
		 * Build an array of items that will make up a table
		 * of vehicle attributes. If a value key is not
		 * provided, the member will be used directly on the
		 * vehicle object to find the value.
		 */
		$table_items = array();

		// Book Value.
		if ( ! isset( $invp_settings['price_display'] ) || 'genes' !== $invp_settings['price_display'] ) {
			$book_value = invp_get_the_book_value( $post_ID );
			if ( ! empty( $book_value )
				&& invp_get_raw_book_value( $post_ID ) > invp_get_raw_price( $post_ID )
			) {
				$table_items[] = array(
					'member' => 'book_value',
					'label'  => __( 'Book Value', 'inventory-presser' ),
					'value'  => $book_value,
				);
			}
		}

		// Boat-specific fields.
		if ( invp_is_boat( $post_ID ) ) {
			// Body Style. For Boats!
			$table_items[] = array(
				'member' => 'body_style',
				'label'  => __( 'Body Style', 'inventory-presser' ),
				'value'  => invp_get_the_body_style( $post_ID ),
			);
		}

		// Odometer.
		$table_items[] = array(
			'member' => 'odometer',
			'label'  => apply_filters( 'invp_odometer_word', __( 'Mileage', 'inventory-presser' ) ),
			'value'  => invp_get_the_odometer( ' ' . apply_filters( 'invp_odometer_word', 'Miles' ), $post_ID ),
		);

		// Transmission.
		$table_items[] = array(
			'member' => 'transmission',
			'label'  => __( 'Transmission', 'inventory-presser' ),
			'value'  => invp_get_the_transmission( $post_ID ),
		);

		// Exterior Color.
		$table_items[] = array(
			'member' => 'color',
			'label'  => __( 'Color', 'inventory_presser' ),
			'value'  => invp_get_the_color( $post_ID ),
		);

		// Drive Type.
		$table_items[] = array(
			'member' => 'drive_type',
			'label'  => __( 'Drive Type', 'inventory-presser' ),
			'value'  => invp_get_the_drive_type( $post_ID ),
		);

		// Interior Color.
		$table_items[] = array(
			'member' => 'interior_color',
			'label'  => __( 'Interior', 'inventory_presser' ),
			'value'  => invp_get_the_interior_color( $post_ID ),
		);

		// Doors.
		$table_items[] = array(
			'member' => 'doors',
			'label'  => __( 'Doors', 'inventory-presser' ),
			'value'  => invp_get_the_doors( $post_ID ),
		);

		// Stock Number.
		$table_items[] = array(
			'member' => 'stock_number',
			'label'  => __( 'Stock', 'inventory-presser' ),
			'value'  => invp_get_the_stock_number( $post_ID ),
		);

		// Fuel + Engine.
		$table_items[] = array(
			'member' => 'engine',
			'label'  => __( 'Engine', 'inventory-presser' ),
			'value'  => trim( implode( ' ', array( invp_get_the_fuel( $post_ID ), invp_get_the_engine( $post_ID ) ) ) ),
		);

		// Boat-specific engine fields.
		if ( invp_is_boat( $post_ID ) ) {
			// Number of Engines.
			$table_items[] = array(
				'member' => 'engine_count',
				'label'  => __( 'Number of Engines', 'inventory-presser' ),
				'value'  => invp_get_the_engine_count( $post_ID ),
			);

			// Horsepower.
			$table_items[] = array(
				'member' => 'horsepower',
				'label'  => __( 'Horsepower', 'inventory-presser' ),
				'value'  => invp_get_the_horsepower( $post_ID ),
			);

			// Length.
			$table_items[] = array(
				'member' => 'length',
				'label'  => __( 'Length', 'inventory-presser' ),
				'value'  => invp_get_the_length( $post_ID ),
			);

			// Hull material.
			$table_items[] = array(
				'member' => 'hull_material',
				'label'  => __( 'Hull Material', 'inventory-presser' ),
				'value'  => invp_get_the_hull_material( $post_ID ),
			);

			// Is this table displayed on a boat single?
			if ( is_singular( INVP::POST_TYPE ) ) {
				// Beam.
				$table_items[] = array(
					'member' => 'beam',
					'label'  => __( 'Beam', 'inventory-presser' ),
					'value'  => invp_get_the_beam( $post_ID ),
				);

				// Draft.
				$table_items[] = array(
					'member' => 'draft',
					'label'  => __( 'Max Draft', 'inventory-presser' ),
					'value'  => invp_get_the_draft( $post_ID ),
				);

				// Engine Make.
				$table_items[] = array(
					'member' => 'engine_make',
					'label'  => __( 'Engine Make', 'inventory-presser' ),
					'value'  => invp_get_the_engine_make( $post_ID ),
				);

				// Engine Model.
				$table_items[] = array(
					'member' => 'engine_model',
					'label'  => __( 'Engine Model', 'inventory-presser' ),
					'value'  => invp_get_the_engine_model( $post_ID ),
				);

				// Boat Condition.
				$table_items[] = array(
					'member' => 'condition_boat',
					'label'  => __( 'Condition', 'inventory-presser' ),
					'value'  => invp_get_the_condition_boat( $post_ID ),
				);
			}
		}

		// VIN or HIN for boats.
		$table_items[] = array(
			'member' => 'vin',
			'label'  => invp_is_boat( $post_ID ) ? __( 'HIN', 'inventory-presser' ) : __( 'VIN', 'inventory-presser' ),
			'value'  => invp_get_the_vin(),
		);

		$table_items = apply_filters( 'invp_vehicle_attribute_table_items', $table_items );

		$html = '';
		foreach ( $table_items as $item ) {
			// Does the vehicle have a value for this member?
			$member = $item['member'];
			if ( empty( $item['value'] ) && empty( INVP::get_meta( $member, $post_ID ) ) ) {
				// No.
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
}
