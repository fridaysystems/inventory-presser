<?php
defined( 'ABSPATH' ) or exit;

/**
 * Inventory_Presser_Map_Widget
 * 
 * Let's users choose an address in the locations taxonomy, and loads a map that
 * points at that address.
 * 
 * This class creates the Map widget.
 */
class Inventory_Presser_Map_Widget extends WP_Widget {

	//const ID_BASE = '_invp_google_maps';
	const ID_BASE = '_invp_map';
	const SCRIPT_HANDLE_LEAFLET = 'invp-leaflet';

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
			__( 'Map', 'inventory-presser' ),
			array( 'description' => __( 'Embeds a map pointed at a dealership address.', 'inventory-presser' ), )
		);

		add_action( 'invp_delete_all_data', array( $this, 'delete_option' ) );

		//Register script and style files for leaflet.js
		wp_register_script( self::SCRIPT_HANDLE_LEAFLET, plugins_url( 'js/leaflet/leaflet.js', INVP_PLUGIN_FILE_PATH ) );
		wp_register_style( self::SCRIPT_HANDLE_LEAFLET, plugins_url( 'js/leaflet/leaflet.css', INVP_PLUGIN_FILE_PATH ) );
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

	private function escape_single_quotes( $string )
	{
		return str_replace( "'", "\'", $string );
	}

	private function get_latitude_and_longitude( $address_str )
	{
		$url = 'https://nominatim.openstreetmap.org/search?format=json&q=' . rawurlencode( $address_str );
		$result = wp_remote_get( $url );
		if( is_wp_error( $result ) )
		{
			return false;
		}
		$body = json_decode( wp_remote_retrieve_body( $result ) );
		return $body[0];
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
	public function widget( $args, $instance )
	{
		//abort if we don't have an address to show
		if( empty( $instance['location_slug'] ) )
		{
			return;
		}

		//abort if we do not have a mapbox.com API token
		$settings = INVP::settings();
		if( empty( $settings['mapbox_public_token'] ) )
		{
			return;
		}

		$location_terms = get_terms( array(
			'hide_empty' => false,
			'slug'       => $instance['location_slug'],
			'taxonomy'   => 'location',
		) );
		if( ! $location_terms )
		{
			//there are no dealership addresses stored in this site, abort
			return;
		}

		/**
		 * Create an array that contains the data needed to create the markers 
		 * and popups: location names, addresses, and lat lon coords
		 */
		$popups = array();
		for( $t=0; $t<sizeof( $location_terms ); $t++ )
		{
			$popup = new stdClass();
			//Location title/dealership name
			$popup->name = $this->escape_single_quotes( $location_terms[$t]->name );
			//Address
			$popup->address = str_replace( PHP_EOL, '<br />', $this->escape_single_quotes( $location_terms[$t]->description ) );
			//Get latitude and longitude
			$location = $this->get_latitude_and_longitude( str_replace( PHP_EOL, ', ', $location_terms[$t]->description ) );
			if( false !== $location )
			{
				$popup->coords = new stdClass();
				$popup->coords->lat = $location->lat;
				$popup->coords->lon = $location->lon;
			}
			$popups[] = $popup;
		}
		
		//Enqueue leaflet.js scripts and styles
		wp_enqueue_script( self::SCRIPT_HANDLE_LEAFLET );
		wp_enqueue_style( self::SCRIPT_HANDLE_LEAFLET );

		//Include the JavaScript file that powers the map
		$handle = 'invp-maps';
		wp_enqueue_script( $handle, plugins_url( 'js/widget-map.min.js', INVP_PLUGIN_FILE_PATH ) );
		//Localize an API key and the popups array we built data for JavaScript
		wp_add_inline_script( $handle, 'const invp_maps = ' . json_encode( array(
			'mapbox_public_token' => $settings['mapbox_public_token'],
			'popups'              => $popups,
		) ), 'before' );

		// before and after widget arguments are defined by themes
		echo $args['before_widget'];

		$title = apply_filters( 'widget_title', $instance['title'] );
		if( ! empty( $title ) ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}

		echo '<div class="invp-google-maps" id="map" style="height: 28em;"></div>'
			. $args['after_widget'];
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

		$title = isset( $instance['title'] ) ? $instance['title'] : '';

		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'inventory-presser' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<p>
			<?php _e( 'Choose addresses to include:', 'inventory-presser' ); ?>
		</p><?php

		// get all location terms
		$location_terms = get_terms( 'location', array( 'hide_empty' => false ) );

		$location_slug = isset( $instance['location_slug'] ) ? $instance['location_slug'] : array();
		if( ! is_array( $location_slug ) )
		{
			$location_slug = array( $location_slug );
		}

	    // loop through each location, set up form
	   	foreach( $location_terms as $index => $term_object ) {
	   		printf(
	   			'<p><input id="%s" name="%s[]" value="%s" type="checkbox"%s> <label for="%s">%s</label></p>',
	   			$this->get_field_id( $term_object->slug ),
	   			$this->get_field_name('location_slug'),
	   			$term_object->slug,
	   			checked( true, in_array( $term_object->slug, $location_slug ), false ),
	   			$this->get_field_id( $term_object->slug ),
	   			str_replace( PHP_EOL, ', ', $term_object->description )
	   		);
	    }

		//Only show this if the API key is missing
		$settings = INVP::settings();
		if( empty( $settings['mapbox_public_token'] ) )
		{
			printf( '<p>%s</p>', __( 'An API token from mapbox.com is required for this widget to work. Obtain a key at mapbox.com and save it on the Inventory Presser Options page.', 'inventory-presser' ) );
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
			'title'         => ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '',
			'location_slug' => ( ! empty( $new_instance['location_slug'] ) ) ? $new_instance['location_slug'] : '',
		);
	}
}
