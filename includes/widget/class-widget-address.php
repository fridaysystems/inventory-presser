<?php
defined( 'ABSPATH' ) || exit;

/**
 * Inventory_Presser_Location_Address
 *
 * This class creates the Address widget.
 */
class Inventory_Presser_Location_Address extends WP_Widget {


	const ID_BASE = '_invp_address';

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
			__( 'Address', 'inventory-presser' ),
			array(
				'description'           => __( 'Display one or more mailing addresses.', 'inventory-presser' ),
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
	 * Outputs the content of the widget
	 *
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance ) {
		if ( empty( $instance['cb_display'] ) ) {
			return;
		}

		$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'] );
		// before and after widget arguments are defined by themes
		echo $args['before_widget'];
		if ( ! empty( $title ) ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}

		if ( isset( $instance['cb_single_line'] ) && $instance['cb_single_line'] == 'true' ) {
			foreach ( $instance['cb_display'] as $i => $term_id ) {
				$location = get_term( $term_id, 'location' );
				if ( ! is_wp_error( $location ) && null != $location ) {
					printf(
						'<span>%s</span>',
						str_replace( PHP_EOL, ', ', trim( $location->description ) )
					);
				}
			}
		} else {
			foreach ( $instance['cb_display'] as $i => $term_id ) {
				$location = get_term( $term_id, 'location' );
				if ( ! is_wp_error( $location ) && null != $location ) {
					echo '<div>' . nl2br( $location->description ) . '</div>';
				}
			}
		}

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

		// get all locations
		$location_terms = get_terms( 'location', array( 'hide_empty' => false ) );

		// set
		if ( isset( $instance['cb_display'] ) ) {
			$cb_display = $instance['cb_display'];
		} else {
			// majority of dealers will have one address, let's precheck it for them
			if ( count( $location_terms ) == 1 ) {
				$cb_display = array( $location_terms[0]->term_id );
			} else {
				$cb_display = array();
			}
		}

		$address_table = '<table><tbody>'
			. '<tr><td colspan="2">Select Addresses to Display</td></tr>';

		// loop through each location, set up form
		foreach ( $location_terms as $index => $term_object ) {
			$address_checkbox = sprintf(
				'<input id="%s" name="%s" value="%s" type="checkbox"%s>',
				$this->get_field_id( 'cb_title' ),
				$this->get_field_name( 'cb_display[]' ),
				$term_object->term_id,
				checked( ( in_array( $term_object->term_id, $cb_display ) ), true, false )
			);
			$address_table   .= sprintf( '<tr><td>%s</td><td>%s</td></tr>', $address_checkbox, nl2br( $term_object->description ) );
		}

		$address_table .= '</tbody></table>';

		// Widget admin form
		?>
		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'inventory-presser' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<p>
		<input type="checkbox" id="<?php echo $this->get_field_id( 'cb_single_line' ); ?>" name="<?php echo $this->get_field_name( 'cb_single_line' ); ?>" value="true"<?php checked( ( isset( $instance['cb_single_line'] ) && $instance['cb_single_line'] == 'true' ) ); ?>>
		<label for="<?php echo $this->get_field_id( 'cb_single_line' ); ?>"><?php _e( 'Single Line Display', 'inventory-presser' ); ?></label>
		</p>
		<p><?php echo $address_table; ?></p>
		<?php
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
		$instance                   = array();
		$instance['title']          = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['cb_display']     = ( ! empty( $new_instance['cb_display'] ) ) ? $new_instance['cb_display'] : array();
		$instance['cb_single_line'] = ( ! empty( $new_instance['cb_single_line'] ) ) ? $new_instance['cb_single_line'] : '';
		return $instance;
	}

}
