<?php
defined( 'ABSPATH' ) || exit;

/**
 * Inventory_Presser_Google_Maps_Widget_V3
 *
 * Let's users choose an address in the locations taxonomy, and loads a Google
 * Map that points at that address.
 *
 * This class creates the Google Map v3 widget.
 */
class Inventory_Presser_Google_Maps_Widget_V3 extends WP_Widget {


	const ID_BASE = 'invp_google_maps_v3';

	/**
	 * __construct
	 *
	 * Calls the parent class' contructor and adds a hook that will delete the
	 * option that stores this widget's data when the plugin's delete all data
	 * method is run.
	 *
	 * @return void
	 */
	function __construct() {
		parent::__construct(
			self::ID_BASE,
			__( 'Google Map', 'inventory-presser' ),
			array(
				'description'           => __( 'Embeds a Google Map pointed at a dealership address. Requires an API key.', 'inventory-presser' ),
				'show_instance_in_rest' => true,
			)
		);

		add_action( 'invp_delete_all_data', array( $this, 'delete_option' ) );
	}

	/**
	 * delete_option
	 *
	 * Deletes the option that stores this widget's data.
	 *
	 * @return void
	 */
	public function delete_option() {
		delete_option( 'widget_' . self::ID_BASE );
	}

	/**
	 * widget
	 *
	 * Outputs the widget front-end HTML
	 *
	 * @param  array $args
	 * @param  array $instance
	 * @return void
	 */
	public function widget( $args, $instance ) {
		// If there is no API key, we're not showing anything
		if ( empty( $instance['api_key'] ) ) {
			return;
		}

		// Turn an array of location term slugs into an array of address data
		$popups         = array();
		$location_terms = get_terms(
			array(
				'hide_empty' => false,
				'slug'       => $instance['location_slugs'],
				'taxonomy'   => 'location',
			)
		);
		for ( $t = 0; $t < sizeof( $location_terms ); $t++ ) {
			$popup = new stdClass();
			/**
			 * Store the widget ID in case there are two instances of this
			 * widget on the same page.
			 */
			$popup->widget_id = $args['widget_id'] ?? 0;
			// Location title/dealership name
			$popup->name = $location_terms[ $t ]->name;
			// Address
			$popup->address = str_replace( "\r", '', str_replace( PHP_EOL, '<br />', $location_terms[ $t ]->description ) );
			// Get the latitude and longitude coordinates for this address
			$location = INVP::fetch_latitude_and_longitude( $location_terms[ $t ]->term_id );
			if ( false !== $location ) {
				$popup->coords      = new stdClass();
				$popup->coords->lat = $location->lat;
				$popup->coords->lon = $location->lon;
			}

			$meta         = get_term_meta( $location_terms[ $t ]->term_id );
			$popup->city  = $meta['address_city'][0] ?? '';
			$popup->state = $meta['address_state'][0] ?? '';
			$popup->zip   = $meta['address_zip'][0] ?? '';

			$popups[] = $popup;
		}

		// Enqueue JavaScript
		wp_enqueue_script(
			self::ID_BASE . '_goog',
			'https://maps.googleapis.com/maps/api/js?key=' . $instance['api_key']
		);
		$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		wp_enqueue_script(
			self::ID_BASE,
			plugins_url( "js/widget-google-maps-v3{$min}.js", INVP_PLUGIN_FILE_PATH ),
			array( self::ID_BASE . '_goog' )
		);
		wp_add_inline_script(
			self::ID_BASE,
			'const ' . self::ID_BASE . ' = ' . wp_json_encode(
				array(
					'locations' => $popups,
				)
			),
			'before'
		);

		// before and after widget arguments are defined by themes
		echo $args['before_widget'];

		$title = apply_filters( 'widget_title', $instance['title'] );
		if ( ! empty( $title ) ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}

		echo '<div id="map_canvas" style="min-height: 175px;"></div>' . $args['after_widget'];
	}

	/**
	 * form
	 *
	 * Outputs the widget settings form that is shown in the dashboard.
	 *
	 * @param  array $instance
	 * @return void
	 */
	public function form( $instance ) {

		$title   = $instance['title'] ?? '';
		$api_key = $instance['api_key'] ?? '';

		// Title ?><p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'inventory-presser' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<?php

		// API Key
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'api_key' ); ?>"><?php _e( 'API Key:', 'inventory-presser' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'api_key' ); ?>" name="<?php echo $this->get_field_name( 'api_key' ); ?>" type="text" value="<?php echo esc_attr( $api_key ); ?>" />
			<p class="description">
			<?php

				printf(
					'%s <a href="%s">%s</a>',
					__( 'Obtain an API key at', 'inventory-presser' ),
					'https://developers.google.com/maps/documentation/javascript/get-api-key',
					__( 'Google Cloud Console', 'inventory-presser' )
				);

			?>
									</p>
		</p>
		<p><?php _e( 'Choose addresses to mark:', 'inventory-presser' ); ?></p>
												 <?php

													// get all location terms
													$location_terms = get_terms( 'location', array( 'hide_empty' => false ) );

													$location_slugs = isset( $instance['location_slugs'] ) ? $instance['location_slugs'] : array();
													if ( ! is_array( $location_slugs ) ) {
														$location_slugs = array( $location_slugs );
													}

													// loop through each location, set up form
													foreach ( $location_terms as $index => $term_object ) {
														printf(
															'<p><input id="%s" name="%s[]" value="%s" type="checkbox"%s> <label for="%s">%s</label></p>',
															$this->get_field_id( $term_object->slug ),
															$this->get_field_name( 'location_slugs' ),
															$term_object->slug,
															checked( in_array( $term_object->slug, $location_slugs ), true, false ),
															$this->get_field_id( $term_object->slug ),
															$term_object->description
														);
													}
	}

	/**
	 * update
	 *
	 * Saves the widget settings when a dashboard user clicks the Save button.
	 *
	 * @param  array $new_instance
	 * @param  array $old_instance
	 * @return array The updated array full of settings
	 */
	public function update( $new_instance, $old_instance ) {
		return array(
			'title'          => ( ! empty( $new_instance['title'] ) ) ? wp_strip_all_tags( $new_instance['title'] ) : '',
			'api_key'        => ( ! empty( $new_instance['api_key'] ) ) ? wp_strip_all_tags( $new_instance['api_key'] ) : '',
			'location_slugs' => ( ! empty( $new_instance['location_slugs'] ) ) ? $new_instance['location_slugs'] : '',
		);
	}
}
