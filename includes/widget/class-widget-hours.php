<?php
defined( 'ABSPATH' ) || exit;

/**
 * Inventory_Presser_Location_Hours
 *
 * This class creates the Hours widget.
 */
class Inventory_Presser_Location_Hours extends WP_Widget {

	const ID_BASE = '_invp_hours';

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
			'Hours',
			array(
				'description'           => __( 'Display hours of operation.', 'inventory-presser' ),
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
		if ( empty( $instance['cb_display'] ) || ! is_array( $instance['cb_display'] ) ) {
			return;
		}

		$cb_showclosed = ( isset( $instance['cb_showclosed'] ) && 'true' === $instance['cb_showclosed'] );

		/**
		 * Hold the HTML that begins the widget until we're sure we're going to
		 * output something.
		 */
		$html = '';

		// before and after widget arguments are defined by themes.
		$html .= $args['before_widget'];

		$title = apply_filters( 'widget_title', $instance['title'] );
		if ( ! empty( $title ) ) {
			$html .= $args['before_title'] . $title . $args['after_title'];
		}
		$html .= '<div class="invp-hours">';

		// Get all location taxonomy terms.
		$location_info = get_terms(
			array(
				'taxonomy'   => 'location',
				'fields'     => 'id=>name',
				'hide_empty' => false,
			)
		);

		// loop through each location.
		foreach ( $location_info as $term_id => $name ) {
			// Does this address even have hours displayed by this instance of this widget?
			if ( empty( $instance['cb_display'][ $term_id ] ) ) {
				// No.
				continue;
			}

			for ( $h = 1; $h <= INVP::LOCATION_MAX_HOURS; $h++ ) {
				$hours_uid = get_term_meta( $term_id, 'hours_' . $h . '_uid', true );
				if ( ! $hours_uid ) {
					break;
				}

				// There are hours in is slot $h, has the user configured this widget to display it?
				if ( ! in_array( $hours_uid, $instance['cb_display'][ $term_id ], true ) ) {
					// No.
					continue;
				}

				/**
				 * The first time we reach this block, we can be sure we have
				 * some hours content to output in the widget, so dump the
				 * preamble $html.
				 */
				if ( ! empty( $html ) ) {
					echo $html;
					$html = '';
				}

				$hours_title = '';
				if ( ! empty( $instance['cb_title'][ $term_id ] )
					&& is_array( $instance['cb_title'][ $term_id ] )
					&& in_array( $hours_uid, $instance['cb_title'][ $term_id ], true )
				) {
					$hours_title = sprintf( '<strong>%s</strong>', get_term_meta( $term_id, 'hours_' . $h . '_title', true ) );
				}
				echo apply_filters( 'invp_hours_title', $hours_title, $hours_uid );

				// get current day number, starting on a monday.
				$current_weekday = gmdate( 'w' ) - 1;
				$current_weekday = ( -1 === $current_weekday ) ? 6 : $current_weekday;

				$start_of_week = get_option( 'start_of_week' ) - 1;

				$hours_sets = INVP::get_hours( $term_id );
				$hours_set  = array();
				foreach ( $hours_sets as $set ) {
					if ( $hours_uid === $set['uid'] ) {
						$hours_set = $set;
						break;
					}
				}
				$days          = Inventory_Presser_Shortcode_Hours_Today::create_days_array_from_hours_array( $hours_set );
				$next_open_day = Inventory_Presser_Shortcode_Hours_Today::find_next_open_day( $days );

				echo '<table class="table"><tbody>';

				// output a row for each day.
				$highlighted_a_row = false;
				for ( $z = $start_of_week; $z < ( $start_of_week + 7 ); $z++ ) {
					$i = ( $z > 6 ) ? $z - 7 : $z;

					// do a check to make sure we want to output this row.
					$echo_row = false;

					$open_by_appt = (int) get_term_meta( $term_id, 'hours_' . $h . '_' . INVP::weekdays( $i ) . '_appt', true );
					$open         = get_term_meta( $term_id, 'hours_' . $h . '_' . INVP::weekdays( $i ) . '_open', true );
					$close        = get_term_meta( $term_id, 'hours_' . $h . '_' . INVP::weekdays( $i ) . '_close', true );

					if ( ( 1 === $open_by_appt || ! empty( $open ) && ! empty( $close ) ) || $cb_showclosed ) {
						$echo_row = true;
					} elseif ( $i < 6 ) {
						// check the remaining days, output current day if there are other displayed days following.
						for ( $r = ( $i + 1 ); $r < 7; $r++ ) {
							$future_open_by_appt = get_term_meta( $term_id, 'hours_' . $h . '_' . INVP::weekdays( $r ) . '_appt', true );
							$future_open         = get_term_meta( $term_id, 'hours_' . $h . '_' . INVP::weekdays( $r ) . '_open', true );
							$future_close        = get_term_meta( $term_id, 'hours_' . $h . '_' . INVP::weekdays( $r ) . '_close', true );
							if ( 1 === (int) $future_open_by_appt || ( ! empty( $future_open ) && ! empty( $future_close ) ) ) {
								$echo_row = true;
								break;
							}
						}
						// if there are no remaining days to display, break out of loop.
						if ( ! $echo_row ) {
							break;
						}
					}

					// output row.
					if ( $echo_row ) {
						/**
						 * Highlight this row if it's today and we have not
						 * passed closing time, or highlight it if we're
						 * not open today or closed for the day and $i is
						 * $next_open_day
						 */
						$current_row_class = '';
						if ( ! $highlighted_a_row
							&& ( ( ! empty( $days[ $i ] )
							&& $days[ $i ]->open_right_now()
							&& $current_weekday === $i ) // if it's today and we're open, highlight the row.
							|| ( $current_weekday !== $i
							&& ! empty( $next_open_day )
							&& $next_open_day->weekday - 1 === $i ) // it's not today, it's the next open day though.
							)
						) {
							$current_row_class = ' class="day-highlight"';
							$highlighted_a_row = true;
						}

						printf(
							'<tr%s><th scope="row">%s</th>',
							$current_row_class,
							esc_html( array_values( INVP::weekdays() )[ $i ] )
						);

						if ( 1 === $open_by_appt && ! empty( $open ) && ! empty( $close ) ) {
							printf(
								'<td colspan="2">%s - %s &amp; %s</td>',
								esc_html( $open ),
								esc_html( $close ),
								esc_html__( 'Appointment', 'inventory-presser' )
							);
						} elseif ( 1 === $open_by_appt ) {
							printf( '<td colspan="2">%s</td>', esc_html__( 'Appointment Only', 'inventory-presser' ) );
						} elseif ( ! empty( $open ) && ! empty( $close ) ) {
							printf(
								'<td>%s</td><td>%s</td>',
								esc_html( $open ),
								esc_html( $close )
							);
						} else {
							printf( '<td colspan="2">%s</td>', esc_html__( 'Closed', 'inventory-presser' ) );
						}
						echo '</tr>';
					}
				}
				echo '</tbody></table>';
			}
		}
		// Only output HTML here if $html has been emptied, that means it was echoed.
		if ( empty( $html ) ) {
			echo '</div>' . $args['after_widget'];
		}
	}

	/**
	 * Outputs the widget settings form that is shown in the dashboard.
	 *
	 * @param  array $instance
	 * @return void
	 */
	public function form( $instance ) {
		$cb_display = isset( $instance['cb_display'] ) ? $instance['cb_display'] : array();
		$cb_title   = isset( $instance['cb_title'] ) ? $instance['cb_title'] : array();

		// get all locations.
		$location_info = get_terms(
			array(
				'taxonomy'   => 'location',
				'fields'     => 'id=>name',
				'hide_empty' => false,
			)
		);

		$hours_table = '<table><tbody>';

		// loop through each location, set up form.
		foreach ( $location_info as $term_id => $name ) {
			// Output a checkbox for every set of hours in this location.
			for ( $h = 1; $h <= INVP::LOCATION_MAX_HOURS; $h++ ) {
				// Are there hours in this slot?
				$hours_uid = get_term_meta( $term_id, 'hours_' . $h . '_uid', true );
				if ( ! $hours_uid ) {
					// No, we're done with this location.
					break;
				}

				// Only do this once per location.
				if ( 1 === $h ) {
					$hours_table .= sprintf(
						'<tr><td>%s</td><td>%s</td><td>%s</td></tr>',
						$name,
						__( 'Display', 'inventory-presser' ),
						__( 'Title', 'inventory-presser' )
					);
				}

				$title = get_term_meta( $term_id, 'hours_' . $h . '_title', true );
				if ( empty( $title ) ) {
					$title = __( 'No title entered', 'inventory-presser' );
				}

				$cb_display_text = sprintf(
					'<input type="checkbox" id="%s" name="%s" value="%s"%s />',
					$this->get_field_id( 'cb_display_' . $hours_uid ),
					$this->get_field_name( 'cb_display[' . $term_id . '][]' ),
					$hours_uid,
					checked( true, ( isset( $cb_display[ $term_id ] ) && is_array( $cb_display[ $term_id ] ) && in_array( $hours_uid, $cb_display[ $term_id ], true ) ), false )
				);

				$cb_title_text = sprintf(
					'<input type="checkbox" id="%s" name="%s" value="%s"%s />',
					$this->get_field_id( 'cb_title_' . $hours_uid ),
					$this->get_field_name( 'cb_title[' . $term_id . '][]' ),
					$hours_uid,
					checked( true, ( isset( $cb_title[ $term_id ] ) && is_array( $cb_title[ $term_id ] ) && in_array( $hours_uid, $cb_title[ $term_id ], true ) ), false )
				);

				$hours_table .= sprintf(
					'<tr><td><strong>%s</strong></td><td>%s</td><td>%s</td></tr>',
					$title,
					$cb_display_text,
					$cb_title_text
				);
			}
		}

		$hours_table .= '</tbody></table>';

		// Widget admin form.
		$title = isset( $instance['title'] ) ? $instance['title'] : __( 'Hours', 'inventory-presser' );
		?>
		<p>
		<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Main Title', 'inventory-presser' ); ?></label>
		<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>

		<p>
		<label for="<?php echo esc_attr( $this->get_field_id( 'cb_showclosed' ) ); ?>"><?php esc_html_e( 'Show All Closed Days', 'inventory-presser' ); ?></label>
		<input type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'cb_showclosed' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'cb_showclosed' ) ); ?>" value="true"<?php checked( ( isset( $instance['cb_showclosed'] ) && 'true' === $instance['cb_showclosed'] ) ); ?>>
		</p>
		<p><?php echo $hours_table; ?></p>
		<?php
	}

	/**
	 * Saves the widget settings when a dashboard user clicks the Save button.
	 *
	 * @param  array $new_instance
	 * @param  array $old_instance
	 * @return array The updated array full of settings
	 */
	public function update( $new_instance, $old_instance ) {
		$instance                  = array();
		$instance['title']         = ( ! empty( $new_instance['title'] ) ) ? wp_strip_all_tags( $new_instance['title'] ) : '';
		$instance['cb_display']    = ( ! empty( $new_instance['cb_display'] ) ) ? $new_instance['cb_display'] : array();
		$instance['cb_title']      = ( ! empty( $new_instance['cb_title'] ) ) ? $new_instance['cb_title'] : array();
		$instance['cb_showclosed'] = ( ! empty( $new_instance['cb_showclosed'] ) ) ? $new_instance['cb_showclosed'] : '';
		return $instance;
	}
}
