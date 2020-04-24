<?php
defined( 'ABSPATH' ) or exit;

class Inventory_Presser_Fuel_Economy_Widget extends WP_Widget
{
	const ID_BASE = '_invp_fuel_economy_widget';

	/**
	 * Sets up the widgets name etc
	 */
	public function __construct()
	{
		parent::__construct(
			self::ID_BASE, // Base ID
			__( 'EPA Fuel Economy', 'inventory-presser' ), // Name
			array( 'description' => __( 'MPG ratings for the current vehicle.', 'inventory-presser' ), ) // Args
		);

		add_action( 'invp_delete_all_data', array( $this, 'delete_option' ) );
	}

	public function delete_option()
	{
		delete_option( 'widget_' . self::ID_BASE );
	}

	/**
	 * Outputs the options form on admin
	 *
	 * @param array $instance The widget options
	 */
	public function form( $instance )
	{
		$title = ( isset( $instance[ 'title' ] ) ? $instance[ 'title' ] : '' );
		?>
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e( 'Title:', 'inventory-presser' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
		</p><?php
	}

	/**
	 * Processing widget options on save
	 *
	 * @param array $new_instance The new options
	 * @param array $old_instance The previous options
	 */
	public function update( $new_instance, $old_instance )
	{
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
 	public function widget( $args, $instance )
 	{
 		//if we aren't looking at a single vehicle, abort
 		if( ! is_singular( Inventory_Presser_Plugin::CUSTOM_POST_TYPE ) )
 		{
 			return;
 		}

		$queried_object = get_queried_object();
		if ( ! $queried_object )
		{
			return;
		}

		//if the vehicle doesn't have EPA data, abort
		$vehicle = new Inventory_Presser_Vehicle( $queried_object->ID );


		if( empty( $vehicle->fuel_economy_city ) || empty( $vehicle->fuel_economy_highway ) )
		{
			return;
		}

		//OK, we have some data and will create output
		extract( $args );

		//Need the stylesheet for this content
		wp_enqueue_style( 'invp-epa-fuel-economy' );

		echo $before_widget
			. $before_title
			. apply_filters( 'widget_title', ( isset( $instance['title'] ) ? $instance['title'] : '' ) )
			. $after_title
			. '<div class="fuel-economy-fuel">';

		//name
		if( ! empty( $vehicle->fuel ) )
		{
			printf( '<div class="fuel-name">%s</div>', $vehicle->fuel );
		}

		$have_combined = $have_city = $have_highway = false;

		echo '<table><tr><td class="fuel-economy-combined">';
		if( ! empty( $vehicle->fuel_economy_combined ) )
		{
			$have_combined = true;
			printf( '<span class="number">%s</span>', $vehicle->fuel_economy_combined );
		}
		echo '</td><td class="mpg" rowspan="2">MPG'
			. '<svg class="fuel-pump" xmlns="http://www.w3.org/2000/svg" width="792" height="720" viewBox="0 0 792 720"><path class="fuel-pump-img" d="M598.1 406c0 74.3-0.1 160.3-0.1 234.6 0 7.3 0.1 14.9 0 22.2 -0.2 29.6-1 29.6-31.6 29.6 -96.7 0.1-193.4 0-290.1-0.1 -19.1 0-38.3-0.1-58.3 0 -23.7 0.1-24.7-0.9-24.7-29.4 0.3-186.6-0.3-373.3-0.3-560 0-47.1 28.1-74.7 76.8-75 79.1-0.5 158.2-0.5 237.3-0.1 15.2 0.1 30.9 1.9 45.6 5.6 26 6.7 45.6 29.1 45.1 54.9 -0.1 3 0.3 26.1 0.3 26.1s10.2 11.7 25.5 24.5c23.7 20.1 44.9 42.9 67.7 64.1 21.8 20.2 31.2 45 31.5 73.3 0.3 35.7 2 71.4-0.5 106.9 -2.6 36.4 8 76.7 28.7 105.9 21.8 30.8 38.2 76.5 25.4 124.7 -15.5 44.3-40.7 63.6-91.2 60.2 -35.5-2.4-63-30.7-63.6-67.8 -0.7-46.1 0.3-92.3-0.1-138.4 -0.1-13.5 0-32.6-0.7-46.1C619.9 403.3 608.9 406 598.1 406zM285.7 73.3c-33.7-0.1-49.4 13.7-49.4 46 0 42.7 0.1 85.6 0.1 128.7 0 27.3 10 40.2 37.5 41.2 79.8 2.7 164.7 4 244.6 3 24.6-0.3 38.9-18.4 38.9-42.2 0.1-44-0.1-90-0.1-131.8 0-32.9-12.6-43-46.4-43.9C474.7 73.1 322.9 73.4 285.7 73.3zM598.5 378.7c53.8 0 51.5 20.6 52.1 67.6 0.6 50.3 1.1 100.5 1.3 150.8 0.2 43.6 26 49.5 46.3 48.4 27.1-1.5 47-22.7 49.1-49.7 2.2-27.9-1.6-54.6-16.8-78.5 -29-45.7-44.7-93.7-38.6-148.8 1.2-10.7-9-25.1-16.5-32.5 -55.2-53.9-45.9-58-46.4-111.9 0-4.1-0.8-41.9-0.8-41.9l-29.6-26.9C598.5 155.5 598.5 312.3 598.5 378.7z"/></svg>'
			. '</td>'
			. '<td class="fuel-economy-city">';

		if( ! empty( $vehicle->fuel_economy_city ) )
		{
			$have_city = true;
			printf( '<span class="number">%s</span>', $vehicle->fuel_economy_city );
		}
		echo '</td><td class="fuel-economy-highway">';
		if( ! empty( $vehicle->fuel_economy_highway ) )
		{
			$have_highway = true;
			printf( '<span class="number">%s</span>', $vehicle->fuel_economy_highway );
		}
		printf(
			'</td></tr><tr class="context"><td>%s</td><td>%s</td><td>%s</td></tr></table></div>',
			( $have_combined ? 'combined' : '' ),
			( $have_city ? 'city' : '' ),
			( $have_highway ? 'highway' : '' )
		);

		echo $after_widget;
	}
}
