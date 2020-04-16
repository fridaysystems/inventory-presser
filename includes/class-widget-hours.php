<?php
defined( 'ABSPATH' ) or exit;

class Inventory_Presser_Location_Hours extends WP_Widget
{
	const ID_BASE = '_invp_hours';

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
		if (is_array($instance['cb_display']) && count($instance['cb_display']) > 0)
		{
			$title = apply_filters( 'widget_title', $instance['title'] );
			$cb_showclosed = (isset($instance['cb_showclosed']) && $instance['cb_showclosed'] == 'true');

			// before and after widget arguments are defined by themes TODO??
			echo $args['before_widget'];

			if ( ! empty( $title ) )
			{
				echo $args['before_title'] . $title . $args['after_title'];
			}
			echo '<div class="invp-hours">';

			// get all locations
			$location_info = get_terms('location', array('fields'=>'id=>name', 'hide_empty'=>false));

			// loop through each location
			foreach ($location_info as $term_id => $name)
			{
				// get term meta for location
				$location_meta = get_term_meta( $term_id, 'location-phone-hours', true );

				// if any hour sets have been selected for this location
				if (isset($instance['cb_display'][$term_id])
					&& is_array($instance['cb_display'][$term_id])
					&& count($instance['cb_display'][$term_id]) > 0
					&& isset( $location_meta['hours'] )
					&& count($location_meta['hours']) > 0)
				{

					// loop through each hour set from term meta
					foreach ($location_meta['hours'] as $index => $hourset)
					{
						if ( ! in_array( $hourset['uid'], $instance['cb_display'][$term_id] ) )
						{
							continue;
						}

						$hours_title = '';
						if (isset($instance['cb_title'][$term_id])
							&& is_array($instance['cb_title'][$term_id])
							&& in_array($hourset['uid'], $instance['cb_title'][$term_id]))
						{
							$hours_title = sprintf( '<strong>%s</strong>', $hourset['title'] );
						}
						echo apply_filters( 'invp_hours_title', $hours_title, $hourset['uid'] );

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
							if (($hourset[$i]['appt'] == 1) || (!empty($hourset[$i]['open']) && !empty($hourset[$i]['close'])) || $cb_showclosed )
							{
								$echo_row = true;
							}
							elseif ($i < 6)
							{
								// check the remaining days, output current day if there are other displayed days following
								for ($r=($i+1); $r < 7; $r++)
								{
									if (($hourset[$r]['appt'] == 1) || (!empty($hourset[$r]['open']) && !empty($hourset[$r]['close'])))
									{
										$echo_row = true;
									}
								}
								// if there are no remaining days to display, break out of loop
								if (!$echo_row)
								{
									break;
								}
							}

							// output row
							if ($echo_row)
							{
								$current_row_class = ($current_weekday == $i) ? ' class="day-highlight"' : '';
								printf(
									'<tr%s><th>%s</th>',
									$current_row_class,
									$this->weekdays()[$i]
								);

								if ($hourset[$i]['appt'] == 1 && !empty($hourset[$i]['open']) && !empty($hourset[$i]['close']))
								{
									printf(
										'<td colspan="2">%s - %s &amp; %s</td>',
										$hourset[$i]['open'],
										$hourset[$i]['close'],
										__( 'Appointment', 'inventory-presser' )
									);
								}
								elseif ($hourset[$i]['appt'] == 1)
								{
									printf( '<td colspan="2">%s</td>', __( 'Appointment Only', 'inventory-presser' ) );
								}
								elseif (!empty($hourset[$i]['open']) && !empty($hourset[$i]['close']))
								{
									printf(
										'<td>%s</td><td>%s</td>',
										$hourset[$i]['open'],
										$hourset[$i]['close']
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
	}

	// Widget Backend
	public function form( $instance )
	{
		$title = isset($instance[ 'title' ]) ? $instance[ 'title' ] : 'Hours';
		$cb_display = isset($instance['cb_display']) ? $instance['cb_display'] : array();
		$cb_title = isset($instance['cb_title']) ? $instance['cb_title'] : array();

		// get all locations
		$location_info = get_terms('location', array('fields'=>'id=>name', 'hide_empty'=>false));

		$hours_table = '<table><tbody>';

		// loop through each location, set up form
		foreach ( $location_info as $term_id => $name )
		{
			$location_meta = get_term_meta( $term_id, 'location-phone-hours', true );
			if ( ! isset( $location_meta['hours'] ) || 0 == count( $location_meta['hours'] ) )
			{
				continue;
			}

			$hours_table .= sprintf(
				'<tr><td>%s</td><td>%s</td><td>%s</td></tr>',
				$name,
				__( 'Display', 'inventory-presser' ),
				__( 'Title', 'inventory-presser' )
			);

			foreach ( $location_meta['hours'] as $index => $hourset )
			{
				$uid = isset( $hourset['uid'] ) ? $hourset['uid'] : '';

				$hourset_title = ( $hourset['title'] ) ? $hourset['title'] : __( 'No title entered', 'inventory-presser' );

				$cb_display_text = sprintf(
					'<input type="checkbox" id="%s" name="%s" value="%s"%s />',
					$this->get_field_id('cb_display'),
					$this->get_field_name('cb_display['.$term_id.'][]'),
					$uid,
					checked( true, (isset($cb_display[$term_id]) && is_array($cb_display[$term_id]) && in_array($uid, $cb_display[$term_id])), false )
				);

				$cb_title_text = sprintf(
					'<input type="checkbox" id="%s" name="%s" value="%s"%s />',
					$this->get_field_id('cb_title'),
					$this->get_field_name('cb_title['.$term_id.'][]'),
					$uid,
					checked( true, (isset($cb_title[$term_id]) && is_array($cb_title[$term_id]) && in_array($uid, $cb_title[$term_id])), false )
				);

				$hours_table .= sprintf(
					'<tr><td><strong>%s</strong></td><td>%s</td><td>%s</td></tr>',
					$hourset_title,
					$cb_display_text,
					$cb_title_text
				);
			}
		}

		$hours_table .= '</tbody></table>';

		// Widget admin form
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
