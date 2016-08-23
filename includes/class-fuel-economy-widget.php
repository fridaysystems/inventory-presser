<?php
class Fuel_Economy_Widget extends WP_Widget {

 	const ID_BASE = 'fuel_economy_widget';

	/**
	 * Sets up the widgets name etc
	 */
 	public function __construct() {
 		parent::__construct(
 			self::ID_BASE, // Base ID
 			__( 'EPA Fuel Economy', 'inventory_presser' ), // Name
 			array( 'description' => __( 'A widget that shows EPA fuel economy data for a vehicle', 'inventory_presser' ), ) // Args
 		);
 	}

	/**
	 * Outputs the options form on admin
	 *
	 * @param array $instance The widget options
	 */
	public function form( $instance ) {
		$title = ( isset( $instance[ 'title' ] ) ? $instance[ 'title' ] : '' );
        ?>
         <p>
          <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
          <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
        </p><?php
	}

	/**
	 * Processing widget options on save
	 *
	 * @param array $new_instance The new options
	 * @param array $old_instance The previous options
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = strip_tags( $new_instance['title'] );
		return $instance;
	}

 	/**
 	 * Outputs the content of the widget
 	 *
 	 * @param array $args
 	 * @param array $instance
	 */
 	public function widget( $args, $instance ) {

 		//if we aren't looking at a single vehicle, abort
 		if( ! is_singular( 'inventory_vehicle' ) ) {
 			return;
 		}

 		//if the vehicle doesn't have epa data, abort
		$queried_object = get_queried_object();
		if ( ! $queried_object ) { return; }
		$epa = get_post_meta( $queried_object->ID, '_inventory_presser_epa_fuel_economy', true );

		if( ! isset( $epa['Fuels'] ) || 0 == sizeof( $epa['Fuels'] ) ) {
			return;
		}

		//OK, we have some data and will create output
		extract( $args );

		echo $before_widget;

		$title = apply_filters('widget_title', ( isset( $instance['title'] ) ? $instance['title'] : '' ));
		echo $before_title . $title . $after_title;

		foreach( $epa['Fuels'] as $fuel ) {
			echo '<div class="fuel-economy-fuel">';

			//name
			if( 1 < sizeof( $epa['Fuels'] ) && isset( $fuel['Name'] ) ) {
				echo '<div class="fuel-name">' . $fuel['Name'] . '</div>';
			}

			$have_combined = $have_city = $have_highway = false;

			echo '<table><tr><td class="fuel-economy-combined">';
			if( isset( $fuel['Attributes'] ) && isset( $fuel['Attributes']['Combined MPG'] ) ) {
				$have_combined = true;
				echo '<span class="number">'
					. $fuel['Attributes']['Combined MPG']
					. '</span>';
			}
			echo '</td><td class="mpg" rowspan="2">MPG<div class="fuel-pump"></div></td>'
				. '<td class="fuel-economy-city">';
			if( isset( $fuel['Attributes'] ) && isset( $fuel['Attributes']['City MPG'] ) ) {
				$have_city = true;
				echo '<span class="number">'
					. $fuel['Attributes']['City MPG']
					. '</span>';
			}
			echo '</td><td class="fuel-economy-highway">';
			if( isset( $fuel['Attributes'] ) && isset( $fuel['Attributes']['Highway MPG'] ) ) {
				$have_highway = true;
				echo '<span class="number">'
					. $fuel['Attributes']['Highway MPG']
					. '</span>';
			}
			echo '</td></tr>';
			echo '<tr class="context"><td>'
				. ( $have_combined ? 'combined' : '' ) . '</td><td>'
				. ( $have_city ? 'city' : '' ) . '</td><td>'
				. ( $have_highway ? 'highway' : '' ) . '</td></tr></table>';
			echo '</div>';
		}

		echo $after_widget;
 	}

 	function mpg_number_html( $number, $context ) {
 		return '<div class="fuel-economy-number fuel-economy-'
 			. strtolower( $context ) . '">'
			. '<span class="number">' . $number . '</span>'
			. '<span class="context">' . $context . '</span>'
			. '</div>';
 	}
}
