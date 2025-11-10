<?php
defined( 'ABSPATH' ) || exit;

/**
 * Inventory_Presser_Map_Widget
 *
 * Let's users choose an address in the locations taxonomy, and loads a map that
 * points at that address.
 *
 * This class creates the Map widget.
 */
class Inventory_Presser_Map_Widget extends WP_Widget {


	const ID_BASE               = '_invp_map';
	const SCRIPT_HANDLE_LEAFLET = 'invp-leaflet-widget';

	/**
	 * Calls the parent class' contructor and adds a hook that will delete the
	 * option that stores this widget's data when the plugin's delete all data
	 * method is run.
	 *
	 * @return void
	 */
	public function __construct() {
		parent::__construct(
			self::ID_BASE,
			__( 'Map', 'inventory-presser' ),
			array(
				'description'           => __( 'Embeds a map pointed at a dealership address.', 'inventory-presser' ),
				'show_instance_in_rest' => true,
			)
		);

		add_action( 'invp_delete_all_data', array( $this, 'delete_option' ) );
	}

	/**
	 * Deletes the option that stores this widget's data.
	 *
	 * @return void
	 */
	public function delete_option() {
		delete_option( 'widget_' . self::ID_BASE );
	}

	/**
	 * Outputs the widget front-end HTML
	 *
	 * @param  array $args
	 * @param  array $instance
	 * @return void
	 */
	public function widget( $args, $instance ) {
		// abort if we don't have an address to show.
		if ( empty( $instance['location_slug'] ) ) {
			return;
		}

		// abort if we do not have a mapbox.com API token.
		$settings = INVP::settings();
		if ( empty( $settings['mapbox_public_token'] ) ) {
			return;
		}

		$location_terms = get_terms(
			array(
				'hide_empty' => false,
				'slug'       => $instance['location_slug'],
				'taxonomy'   => 'location',
			)
		);
		if ( ! $location_terms ) {
			// there are no dealership addresses stored in this site, abort.
			return;
		}

		if ( empty( $args['widget_id'] ) ) {
			// Sometimes the widget ID is unavailable in stuff like Elementor.
			$args['widget_id'] = '9999';
		}

		/**
		 * Create an array that contains the data needed to create the markers
		 * and popups: location names, addresses, and lat lon coords
		 */
		$popups         = array();
		$location_count = count( $location_terms );
		for ( $t = 0; $t < $location_count; $t++ ) {
			$popup = new stdClass();
			/**
			 * Store the widget ID in case there are two instances of this
			 * widget on the same page.
			 */
			$popup->widget_id = $args['widget_id'];
			// Location title/dealership name - escape to prevent XSS.
			$popup->name = esc_html( $location_terms[ $t ]->name );
			// Address - allow <br /> tags but escape other HTML to prevent XSS.
			$popup->address = wp_kses_post( str_replace( "\r", '', str_replace( PHP_EOL, '<br />', $location_terms[ $t ]->description ) ) );
			// Get the latitude and longitude coordinates for this address.
			$location = INVP::fetch_latitude_and_longitude( $location_terms[ $t ]->term_id );
			if ( false !== $location ) {
				$popup->coords      = new stdClass();
				$popup->coords->lat = $location->lat;
				$popup->coords->lon = $location->lon;
				$popups[]           = $popup;
			}
		}

		if ( empty( $popups ) ) {
			/**
			 * We didn't find any latitude & longitude coordinates using the
			 * addresses on openstreetmap.org. It is likely that the addresses
			 * need to be added to the buildings for this dealer's locations.
			 */
			return;
		}

		// Enqueue leaflet.js scripts and styles.
		if ( ! wp_script_is( self::SCRIPT_HANDLE_LEAFLET ) ) {
			wp_enqueue_script( self::SCRIPT_HANDLE_LEAFLET );
			wp_enqueue_style( self::SCRIPT_HANDLE_LEAFLET );
			wp_enqueue_style( self::ID_BASE );
		}

		// Include the JavaScript file that powers the map.
		$handle = 'invp-maps';
		/**
		 * If there are two Map widgets on the same page, we need to avoid the
		 * second one redefining the same invp_maps constant because that will
		 * produce a JavaScript error.
		 */
		if ( ! wp_script_is( $handle ) ) {
			// First instance of this widget on the page.
			wp_enqueue_script( $handle );
			// Localize an API key and the popups array for JavaScript.
			wp_add_inline_script(
				$handle,
				'const invp_maps = ' . wp_json_encode(
					array(
						'mapbox_public_token' => $settings['mapbox_public_token'],
						'popups'              => $popups,
					)
				),
				'before'
			);
		} else {
			// There is another Map widget on this page already.
			foreach ( $popups as $popup ) {
				wp_add_inline_script( $handle, 'invp_maps.popups.push( ' . wp_json_encode( $popup ) . ' );', 'before' );
			}
		}

		// before and after widget arguments are defined by themes.
		echo wp_kses_post( $args['before_widget'] );

		$title = apply_filters( 'widget_title', $instance['title'] );
		if ( ! empty( $title ) ) {
			echo wp_kses_post( $args['before_title'] ) . esc_html( $title ) . wp_kses_post( $args['after_title'] );
		}

		printf( '<div class="invp-map %1$s" id="%1$s-inner"></div>', esc_attr( $args['widget_id'] ) );
		echo wp_kses_post( $args['after_widget'] );
	}

	/**
	 * Outputs the widget settings form that is shown in the dashboard.
	 *
	 * @param  array $instance
	 * @return void
	 */
	public function form( $instance ) {

		$title = isset( $instance['title'] ) ? $instance['title'] : '';

		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'inventory-presser' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<p>
		<?php esc_html_e( 'Choose addresses to include:', 'inventory-presser' ); ?>
		</p>
		<?php

		// get all location terms.
		$location_terms = get_terms(
			array(
				'taxonomy'   => 'location',
				'hide_empty' => false,
			)
		);

		$location_slug = isset( $instance['location_slug'] ) ? $instance['location_slug'] : array();
		if ( ! is_array( $location_slug ) ) {
			$location_slug = array( $location_slug );
		}

		// loop through each location, set up form.
		foreach ( $location_terms as $index => $term_object ) {
			printf(
				'<p><input id="%1$s" name="%2$s[]" value="%3$s" type="checkbox"%4$s> <label for="%1$s">%5$s</label></p>',
				esc_attr( $this->get_field_id( $term_object->slug ) ),
				esc_attr( $this->get_field_name( 'location_slug' ) ),
				esc_attr( $term_object->slug ),
				checked( true, in_array( $term_object->slug, $location_slug, true ), false ),
				esc_html( str_replace( "\r", '', str_replace( PHP_EOL, ', ', $term_object->description ) ) )
			);
		}

		// Only show this if the API key is missing.
		$settings = INVP::settings();
		if ( empty( $settings['mapbox_public_token'] ) ) {
			printf( '<p>%s</p>', esc_html__( 'An API token from mapbox.com is required for this widget to work. Obtain a key at mapbox.com and save it on the Inventory Presser Options page.', 'inventory-presser' ) );
		}
	}

	/**
	 * Saves the widget settings when a dashboard user clicks the Save button.
	 *
	 * @param  array $new_instance
	 * @param  array $old_instance
	 * @return array The updated array full of settings
	 */
	public function update( $new_instance, $old_instance ) {
		return array(
			'title'         => ( ! empty( $new_instance['title'] ) ) ? wp_strip_all_tags( $new_instance['title'] ) : '',
			'location_slug' => ( ! empty( $new_instance['location_slug'] ) ) ? $new_instance['location_slug'] : '',
		);
	}
}
