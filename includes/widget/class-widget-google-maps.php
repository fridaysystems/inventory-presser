<?php
defined( 'ABSPATH' ) || exit;

/**
 * Inventory_Presser_Google_Maps_Widget
 *
 * Let's users choose an address in the locations taxonomy, and loads a Google
 * Map that points at that address.
 *
 * This class creates the Google Map widget.
 */
class Inventory_Presser_Google_Maps_Widget extends WP_Widget {


	const ID_BASE = '_invp_google_maps';

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
			__( 'Google Map (legacy)', 'inventory-presser' ),
			array( 'description' => __( 'Embeds a Google Map pointed at a dealership address.', 'inventory-presser' ) )
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
		// abort if we don't have an address to show
		if ( empty( $instance['location_slug'] ) ) {
			return;
		}

		$location = get_term_by( 'slug', $instance['location_slug'], 'location' );
		if ( ! $location ) {
			return;
		}

		// before and after widget arguments are defined by themes
		echo $args['before_widget'];

		$title = apply_filters( 'widget_title', $instance['title'] );
		if ( ! empty( $title ) ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}

		// remove line breaks from the term description
		$address_to_search = preg_replace( '#\R+#', ', ', $location->description );

		printf(
			'<div class="invp-google-maps"><iframe frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="https://maps.google.com/maps?q=%s&t=m&z=%d&output=embed" aria-label="%s"></iframe></div>',
			rawurlencode( $address_to_search ),
			'13',
			esc_attr( $address_to_search )
		);

		echo $args['after_widget'];
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
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<p>
		<?php _e( 'Choose an Address', 'inventory-presser' ); ?>
		</p>
		<?php

		// get all location terms
		$location_terms = get_terms( 'location', array( 'hide_empty' => false ) );

		$location_slug = isset( $instance['location_slug'] ) ? $instance['location_slug'] : '';

		// loop through each location, set up form
		foreach ( $location_terms as $index => $term_object ) {
			printf(
				'<p><input id="%s" name="%s" value="%s" type="radio"%s> <label for="%s">%s</label></p>',
				$this->get_field_id( $term_object->slug ),
				$this->get_field_name( 'location_slug' ),
				$term_object->slug,
				checked( $term_object->slug, $location_slug, false ),
				$this->get_field_id( $term_object->slug ),
				nl2br( $term_object->description )
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
			'title'         => ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '',
			'location_slug' => ( ! empty( $new_instance['location_slug'] ) ) ? $new_instance['location_slug'] : '',
		);
	}
}
