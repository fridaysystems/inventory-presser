<?php
defined( 'ABSPATH' ) or exit;

class Inventory_Presser_Location_Hours extends WP_Widget
{
	const ID_BASE = '_invp_hours';
	const MAX_HOURS_SETS = 5; //the maximum number of sets of hours a single address holds

	function __construct()
	{
		parent::__construct(
			self::ID_BASE,
			'Hours',
			array(
				'description' => __( 'Display hours of operation.', 'inventory-presser' ),
			)
		);

		add_action( 'invp_delete_all_data', array( $this, 'delete_option' ) );
	}

	public function delete_option()
	{
		delete_option( 'widget_' . self::ID_BASE );
	}

	// widget front-end
	public function widget( $args, $instance )
	{
		if( empty( $instance['cb_display'] ) || ! is_array( $instance['cb_display'] ) )
		{
			return;
		}

		$cb_showclosed = (isset($instance['cb_showclosed']) && $instance['cb_showclosed'] == 'true');

		// before and after widget arguments are defined by themes TODO??
		echo $args['before_widget'];

		$title = apply_filters( 'widget_title', $instance['title'] );
		if ( ! empty( $title ) )
		{
			echo $args['before_title'] . $title . $args['after_title'];
		}
		echo '<div class="invp-hours">';

		// get all locations
		$location_info = get_terms( 'location', array( 'fields' => 'id=>name', 'hide_empty' => false ) );

		// loop through each location
		foreach ( $location_info as $term_id => $name )
		{
			//Does this address even have hours displayed by this instance of this widget?
			if( empty( $instance['cb_display'][$term_id] ) )
			{
				//No
				continue;
			}

			for( $h=1; $h<=self::MAX_HOURS_SETS; $h++ )
			{
				$hours_uid = get_term_meta( $term_id, 'hours_' . $h . '_uid', true );
				if( ! $hours_uid )
				{
					break;
				}

				//There are hours in is slot $h, has the user configured this widget to display it?
				if( in_array( $hours_uid, $instance['cb_display'][$term_id] ) )
				{
					$hours_title = '';
					if( ! empty( $instance['cb_title'][$term_id] )
						&& is_array( $instance['cb_title'][$term_id] )
						&& in_array( $hours_uid, $instance['cb_title'][$term_id] ) )
					{
						$hours_title = sprintf( '<strong>%s</strong>', get_term_meta( $term_id, 'hours_' . $h . '_title', true ) );
					}
					echo apply_filters( 'invp_hours_title', $hours_title, $hours_uid );

					// get current day number, starting on a monday
					$current_weekday = date('w') - 1;
					$current_weekday = ($current_weekday == -1) ? 6 : $current_weekday;

					$start_of_week = get_option('start_of_week') -1;

					echo '<table>';

					// output a row for each day
					for ($z = $start_of_week; $z < ($start_of_week + 7); $z++)
					{
						$i = ($z > 6) ? $z - 7 : $z;

						// do a check to make sure we want to output this row
						$echo_row = false;

						$open_by_appt = get_term_meta( $term_id, 'hours_' . $h . '_' . $this->weekday( $i ) . '_appt', true );
						$open = get_term_meta( $term_id, 'hours_' . $h . '_' . $this->weekday( $i ) . '_open', true );
						$close = get_term_meta( $term_id, 'hours_' . $h . '_' . $this->weekday( $i ) . '_close', true );

						if( ( 1 == $open_by_appt || ! empty( $open ) && ! empty( $close ) ) || $cb_showclosed )
						{
							$echo_row = true;
						}
						elseif( $i < 6 )
						{
							// check the remaining days, output current day if there are other displayed days following
							for( $r=( $i+1 ); $r<7; $r++ )
							{
								$future_open_by_appt = get_term_meta( $term_id, 'hours_' . $h . '_' . $this->weekday( $r ) . '_appt', true );
								$future_open = get_term_meta( $term_id, 'hours_' . $h . '_' . $this->weekday( $r ) . '_open', true );
								$future_close = get_term_meta( $term_id, 'hours_' . $h . '_' . $this->weekday( $r ) . '_close', true );
								if( 1 == $future_open_by_appt || ( ! empty( $future_open ) && ! empty( $future_close ) ) )
								{
									$echo_row = true;
									break;
								}
							}
							// if there are no remaining days to display, break out of loop
							if( ! $echo_row )
							{
								break;
							}
						}

						// output row
						if( $echo_row )
						{
							$current_row_class = ( $current_weekday == $i ) ? ' class="day-highlight"' : '';
							printf(
								'<tr%s><th>%s</th>',
								$current_row_class,
								$this->weekdays()[$i]
							);

							if( 1 == $open_by_appt && ! empty( $open ) && ! empty( $close ) )
							{
								printf(
									'<td colspan="2">%s - %s &amp; %s</td>',
									$open,
									$close,
									__( 'Appointment', 'inventory-presser' )
								);
							}
							elseif( 1 == $open_by_appt )
							{
								printf( '<td colspan="2">%s</td>', __( 'Appointment Only', 'inventory-presser' ) );
							}
							elseif( ! empty( $open ) && ! empty( $close ) )
							{
								printf(
									'<td>%s</td><td>%s</td>',
									$open,
									$close
								);
							}
							else
							{
								echo '<td colspan="2">Closed</td>';
							}
						    echo '</tr>';
						}
					}
					echo '</table>';
				}
			}
		}
		echo '</div>'. $args['after_widget'];
	}

	// Widget Backend
	public function form( $instance )
	{
		$cb_display = isset($instance['cb_display']) ? $instance['cb_display'] : array();
		$cb_title = isset($instance['cb_title']) ? $instance['cb_title'] : array();

		// get all locations
		$location_info = get_terms('location', array('fields'=>'id=>name', 'hide_empty'=>false));

		$hours_table = '<table><tbody>';

		// loop through each location, set up form
		foreach ( $location_info as $term_id => $name )
		{
			//Output a checkbox for every set of hours in this location
			for( $h=1; $h<=self::MAX_HOURS_SETS; $h++ )
			{
				//Are there hours in this slot?
				$hours_uid = get_term_meta( $term_id, 'hours_' . $h . '_uid', true );
				if( ! $hours_uid )
				{
					//No, we're done with this location
					break;
				}

				//Only do this once per location
				if( 1 == $h )
				{
					$hours_table .= sprintf(
						'<tr><td>%s</td><td>%s</td><td>%s</td></tr>',
						$name,
						__( 'Display', 'inventory-presser' ),
						__( 'Title', 'inventory-presser' )
					);
				}

				$title = get_term_meta( $term_id, 'hours_' . $h . '_title', true );
				if( empty( $title ) )
				{
					$title =  __( 'No title entered', 'inventory-presser' );
				}

				$cb_display_text = sprintf(
					'<input type="checkbox" id="%s" name="%s" value="%s"%s />',
					$this->get_field_id('cb_display_' . $hours_id ),
					$this->get_field_name('cb_display['.$term_id.'][]'),
					$hours_uid,
					checked( true, (isset($cb_display[$term_id]) && is_array($cb_display[$term_id]) && in_array($hours_uid, $cb_display[$term_id])), false )
				);

				$cb_title_text = sprintf(
					'<input type="checkbox" id="%s" name="%s" value="%s"%s />',
					$this->get_field_id('cb_title_' . $hours_id ),
					$this->get_field_name('cb_title['.$term_id.'][]'),
					$hours_uid,
					checked( true, (isset($cb_title[$term_id]) && is_array($cb_title[$term_id]) && in_array($hours_uid, $cb_title[$term_id])), false )
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

		// Widget admin form
		$title = isset($instance[ 'title' ]) ? $instance[ 'title' ] : _( 'Hours', 'inventory-presser' );
		?>
		<p>
		<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e( 'Main Title', 'inventory-presser' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
		</p>

		<p>
		<label for="<?php echo $this->get_field_id('cb_showclosed'); ?>"><?php _e( 'Show All Closed Days', 'inventory-presser' ); ?></label>
		<input type="checkbox" id="<?php echo $this->get_field_id('cb_showclosed'); ?>" name="<?php echo $this->get_field_name('cb_showclosed'); ?>" value="true"<?php checked( (isset($instance[ 'cb_showclosed' ]) && $instance[ 'cb_showclosed' ] == 'true') ); ?>>
		</p>
		<p><?php echo $hours_table; ?></p>
		<?php
	}

	// Updating widget replacing old instances with new
	public function update( $new_instance, $old_instance )
	{
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['cb_display'] = ( !empty( $new_instance['cb_display'] ) ) ? $new_instance['cb_display'] : array();
		$instance['cb_title'] = ( !empty( $new_instance['cb_title'] ) ) ? $new_instance['cb_title'] : array();
		$instance['cb_showclosed'] = ( !empty( $new_instance['cb_showclosed'] ) ) ? $new_instance['cb_showclosed'] : '';
		return $instance;
	}

	private function weekday( $zero_through_six )
	{
		$days = array(
			'monday',
			'tuesday',
			'wednesday',
			'thursday',
			'friday',
			'saturday',
			'sunday',
		);
		return empty( $days[$zero_through_six] ) ? false : $days[$zero_through_six];
	}

	private function weekdays()
	{
		return array(
			__( 'Mon', 'inventory-presser' ),
			__( 'Tue', 'inventory-presser' ),
			__( 'Wed', 'inventory-presser' ),
			__( 'Thu', 'inventory-presser' ),
			__( 'Fri', 'inventory-presser' ),
			__( 'Sat', 'inventory-presser' ),
			__( 'Sun', 'inventory-presser' ),
		);
	}
}
