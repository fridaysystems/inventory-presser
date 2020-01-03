<?php
defined( 'ABSPATH' ) or exit;

/**
 * A shortcode that allows themes that do not provide a content-archive template
 * to show vehicle archives that are similar to the way themes are properly
 * built out for this plugin.
 */
class Inventory_Presser_Shortcode_Archive_Vehicle
{
	function hooks()
	{
		add_shortcode( 'invp-archive-vehicle', array( $this, 'content' ) );
	}

	function content( $atts )
	{
		if( ! is_post_type_archive( Inventory_Presser_Plugin::CUSTOM_POST_TYPE ) )
		{
			return;
		}

		wp_enqueue_style(
			'inv_archive_vehicle',
			plugins_url( '/css/shortcode-archive-vehicle.css', dirname( __FILE__, 2 ) . '/inventory-presser.php' ),
			null,
			234243
		);

		$vehicle = new Inventory_Presser_Vehicle( get_the_ID() );

 		ob_start();
		?>

		<article id="post-<?php the_ID(); ?>" <?php post_class('post-vehicle'); ?>>
			<div class="vehicle-info">
				<div class="post-inner">
					<div class="post-thumbnail">
						<div class="vehicle-images">
							<a href="<?php the_permalink(); ?>" title="<?php the_title(); ?>"><?php

								//Stop lying about whether vehicles have thumbnails or not
								remove_filter( 'has_post_thumbnail', array( 'Inventory_Presser_Template_Provider', 'lie_about_post_thumbnails' ), 10, 3 );
								if ( has_post_thumbnail() )
								{
									the_post_thumbnail( 'large' );
								}
								else
								{
									?><img class="no-photo-available" src="<?php echo plugins_url( '/assets/no-photo.png', dirname( __FILE__, 2 ) . '/inventory-presser.php' ); ?>" alt="<?php the_title(); ?>" /><?php
								}
								//Resume lying about thumbnails
								add_filter( 'has_post_thumbnail', array( 'Inventory_Presser_Template_Provider', 'lie_about_post_thumbnails' ), 10, 3 );

							?></a>
						</div>
					</div><!--/.post-thumbnail-->

					<div class="vehicle-price-and-buttons">
						<h2 class="vehicle-price"><?php
							echo $vehicle->price( __( 'Call For Price', '_dealer' ) );
						?></h2><?php

						//carfax
						if ( isset( $invp_settings['use_carfax'] ) && $invp_settings['use_carfax'] )
						{
							$carfax_html = $vehicle->carfax_icon_html();
							if( '' != $carfax_html )
							{
								?><div class="carfax-wrapper"><?php
									echo $carfax_html;
								?></div><?php
							}
						}

						// autocheck icon
						if( shortcode_exists( 'autocheck_button' ) )
						{
							$autocheck_html = do_shortcode( sprintf( '[autocheck_button vin="%s"]', $vehicle->vin ) );
							if( '' != $autocheck_html )
							{
								?><div class="autocheck-wrapper"><?php
									echo $autocheck_html;
								?></div><?php
							}
						}

						//cargurus
						if ( isset( $GLOBALS['_dealer_settings']['cargurus_badge_archive'] )
							&& $GLOBALS['_dealer_settings']['cargurus_badge_archive']
							&& shortcode_exists( 'invp_cargurus_badge' ) )
						{
							echo do_shortcode( '[invp_cargurus_badge height="78"]' );
						}

						do_action( 'invp_listing_buttons', $vehicle );

						?><a class="wp-block-button__link" href="<?php the_permalink(); ?>" title="<?php the_title(); ?>"><?php _e( 'View Details', 'inventory-presser' ); ?></a>
					</div>
						<div class="vehicle-summary"><?php

							$invp_settings = Inventory_Presser_Plugin::settings();

							/**
							 * Build an array of items that will make up a table
							 * of vehicle attributes. If a value key is not
							 * provided, the member will be used directly on the
							 * vehicle object to find the value.
							 */
							$table_items = array();

							//Book Value
							if( ! isset( $invp_settings['price_display'] ) || 'genes' != $invp_settings['price_display'] )
							{
								$book_value = $vehicle->get_book_value();
								error_log( '$book_value = ' . $book_value );
								if( $book_value > 0  && $book_value > intval( $vehicle->price ) )
								{
									$table_items[] = array(
										'member' => 'book_value',
										'label'  => __( 'Book Value', 'inventory-presser' ),
										'value'  => '$' . number_format( $book_value, 0, '.', ',' ),
									);
								}
							}

							//Odometer
							if( 'boat' != $vehicle->type )
							{
								$table_items[] = array(
									'member' => 'odometer',
									'label'  => apply_filters( '_dealer_label-odometer', apply_filters( '_dealer_odometer_word', __( 'Mileage', 'inventory-presser' ) ) ),
									'value'  => $vehicle->odometer( ' ' . apply_filters( '_dealer_odometer_word', 'Miles' ) ),
								);
							}

							$table_items = array_merge( $table_items, array(

								//Exterior Color
								array(
									'member' => 'color',
									'label'  => __( 'Exterior', 'inventory_presser' ),
								),

								//Interior Color
								array(
									'member' => 'interior_color',
									'label'  => __( 'Interior', 'inventory_presser' ),
								),

								//Fuel + Engine
								array(
									'member' => 'engine',
									'label'  => __( 'Engine', 'inventory-presser' ),
									'value'  => implode( ' ', array( $vehicle->fuel, $vehicle->engine ) ),
								),

								//Transmission
								array(
									'member' => 'transmission',
									'label'  => __( 'Transmission', 'inventory-presser' ),
								),

								//Drive Type
								array(
									'member' => 'drivetype',
									'label'  => __( 'Drive Type', 'inventory-presser' ),
								),

								//Stock Number
								array(
									'member' => 'stock_number',
									'label'  => __( 'Stock', 'inventory-presser' ),
								),

								//VIN
								array(
									'member' => 'vin',
									'label'  => 'boat' == $vehicle->type ? __( 'HIN', 'inventory-presser' ) : __( 'VIN', 'inventory-presser' ),
									'value'  => $vehicle->vin,
								),
							) );

							//Boat-specific fields
							if( 'boat' == $vehicle->type )
							{
								//Beam
								$table_items[] = array(
									'member' => 'beam',
									'label'  => __( 'Beam', 'inventory-presser' ),
								);

								//Length
								$table_items[] = array(
									'member' => 'length',
									'label'  => __( 'Length', 'inventory-presser' ),
								);

								//Hull material
								$table_items[] = array(
									'member' => 'hull_material',
									'label'  => __( 'Hull Material', 'inventory-presser' ),
								);
							}

							foreach( $table_items as $item )
							{
								//does the vehicle have a value for this member?
								$member = $item['member'];
								if( empty( $item['value'] ) && empty( $vehicle->$member ) )
								{
									//no
									continue;
								}

								printf(
									'<div class="vehicle-summary-item"><div class="vehicle-summary-item-label">%s</div><div class="vehicle-summary-item-value vehicle-content-initcaps">%s</div></div>',
									apply_filters( '_dealer_label-' . $member, $item['label'] ),
									empty( $item['value'] ) ? strtolower( $vehicle->$member ) : $item['value']
								);
							}

						?></div>

				</div><!--/.post-inner-->

			</div>
		</article><!--/.post--><?php

		return ob_get_clean();
	}
}

