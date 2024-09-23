<?php
defined( 'ABSPATH' ) || exit;

/**
 * Inventory_Presser_Shortcode_Sort_By
 *
 * A shortcode that creates a dropdown that lets users reorder listings.
 */
class Inventory_Presser_Shortcode_Sort_By {

	/**
	 * Adds a shortcode
	 *
	 * @return void
	 */
	public function add() {
		add_shortcode( 'invp_sort_by', array( $this, 'content' ) );
	}

	/**
	 * Adds hooks that power the shortcode
	 *
	 * @return void
	 */
	public function add_hooks() {
		add_action( 'init', array( $this, 'add' ) );
		add_action( 'init', array( $this, 'change_sorter_based_on_price_display' ) );
	}

	/**
	 * If a user wants to order vehicles by price, and the site is showing MSRP
	 * instead of price, sort by that.
	 *
	 * @param  WP_Query $query Query object.
	 * @return void
	 */
	public function change_price_field_when_sorting( $query ) {
		$settings = INVP::settings();
		if ( ! isset( $settings['price_display'] ) ) {
			return;
		}
		switch ( $settings['price_display'] ) {

			case 'genes':
				// msrp.
				$query->set( 'meta_key', apply_filters( 'invp_prefix_meta_key', 'msrp' ) );
				break;

			case 'down_only':
				// down_payment.
				$query->set( 'meta_key', apply_filters( 'invp_prefix_meta_key', 'down_payment' ) );
				break;
		}
	}

	/**
	 * Creates the HTML output that replaces the shortcode.
	 *
	 * @param  array $atts Shortcode attributes.
	 * @return string
	 */
	public function content( $atts ) {
		$atts = shortcode_atts(
			array(
				'label' => __( 'SORT', 'inventory_presser' ),
			),
			$atts,
			'invp_sort_by'
		);

		global $wp_query;
		// If there are no posts, abort.
		if ( 0 === $wp_query->found_posts ) {
			return '';
		}

		if ( ! wp_script_is( 'invp_sort_by', 'registered' ) ) {
			Inventory_Presser_Plugin::include_scripts_and_styles();
		}
		wp_enqueue_script( 'invp_sort_by' );

		$html = '';
		if ( ! empty( $atts['label'] ) ) {
			$html .= sprintf( '<label for="sort_by">%s</label> ', esc_html( $atts['label'] ) );
		}
		$html .= '<select class="inventory_sort" id="sort_by">';

		$options_data = apply_filters(
			'invp_sort_dropdown_options',
			array(
				'make'     => array(
					'ASC'  => __( 'Make A-Z', '_dealer' ),
					'DESC' => __( 'Make Z-A', '_dealer' ),
				),

				'price'    => array(
					'ASC'  => __( 'Price Low', '_dealer' ),
					'DESC' => __( 'Price High', '_dealer' ),
				),

				'odometer' => array(
					'ASC'  => sprintf(
						'%s %s',
						apply_filters( 'invp_odometer_word', 'Mileage' ),
						__( 'Low', '_dealer' )
					),
					'DESC' => sprintf(
						'%s %s',
						apply_filters( 'invp_odometer_word', 'Mileage' ),
						__( 'High', '_dealer' )
					),
				),

				'year'     => array(
					'ASC'  => __( 'Year Oldest', '_dealer' ),
					'DESC' => __( 'Year Newest', '_dealer' ),
				),
			)
		);

		$plugin_settings  = INVP::settings();
		$current_sort_key = isset( $_GET['orderby'] ) ? $_GET['orderby'] : ( isset( $plugin_settings['sort_vehicles_by'] ) ? $plugin_settings['sort_vehicles_by'] : '' );
		$current_sort_dir = isset( $_GET['order'] ) ? $_GET['order'] : ( isset( $plugin_settings['sort_vehicles_order'] ) ? $plugin_settings['sort_vehicles_order'] : '' );

		if ( ! empty( $current_sort_key ) ) {
			$without_prefix = apply_filters( 'invp_unprefix_meta_key', $current_sort_key );
			if ( ! in_array( $without_prefix, array_keys( $options_data ), true ) ) {
				// The current sort option isn't in the list, so add it.
				$label = ucfirst( $without_prefix );
				switch ( $without_prefix ) {
					case 'post_date':
						$label = __( 'Date entered', 'inventory-presser' );
						break;

					case 'post_modified':
						$label = __( 'Last modified', 'inventory-presser' );
						break;
				}

				$options_data[ $without_prefix ] = array(
					'ASC'  => $label . ' 🔼',
					'DESC' => $label . ' 🔽',
				);
			}
		}

		foreach ( $options_data as $key => $options ) {
			foreach ( $options as $dir => $label ) {
				$html .= sprintf(
					'<option data-order="%s" value="%s"%s>%s</option>',
					$dir,
					$key,
					selected( $key . $dir, $current_sort_key . $current_sort_dir, false ),
					$label
				);
			}
		}
		return $html . '</select>';
	}

	/**
	 * change_sorter_based_on_price_display
	 *
	 * @return void
	 */
	public function change_sorter_based_on_price_display() {
		if ( is_admin() ) {
			return;
		}

		$settings = INVP::settings();
		if ( ! isset( $settings['price_display'] ) ) {
			return;
		}

		if ( isset( $_GET['orderby'] ) && apply_filters( 'invp_prefix_meta_key', 'price' ) == $_GET['orderby'] ) {
			add_action( 'pre_get_posts', array( $this, 'change_price_field_when_sorting' ) );
		}

		switch ( $settings['price_display'] ) {

			case 'down_only':
				// add down payment to the sort drop down.
				add_filter( 'invp_sort_dropdown_options', array( $this, 'add_down_payment_to_sort_dropdown' ) );
				// and remove price.
				add_filter( 'invp_sort_dropdown_options', array( $this, 'remove_price_from_sort_dropdown' ) );
				break;

			case 'call_for_price':
				// no prices available, remove price from the drop down.
				add_filter( 'invp_sort_dropdown_options', array( $this, 'remove_price_from_sort_dropdown' ) );
				break;
		}
	}

	/**
	 * Removes price from the dropdown options on sites that do not use the
	 * price field.
	 *
	 * @param  array $options An array of sort fields and directions.
	 * @return array
	 */
	public function remove_price_from_sort_dropdown( $options ) {
		unset( $options['price'] );
		return $options;
	}

	/**
	 * Adds a down payment option to the dropdown on sites that show down
	 * payments instead of prices.
	 *
	 * @param  array $options An array of sort fields and directions.
	 * @return array
	 */
	public function add_down_payment_to_sort_dropdown( $options ) {
		$options['down_payment'] = array(
			'ASC'  => __( 'Down Payment Low', '_dealer' ),
			'DESC' => __( 'Down Payment High', '_dealer' ),
		);
		return $options;
	}
}
