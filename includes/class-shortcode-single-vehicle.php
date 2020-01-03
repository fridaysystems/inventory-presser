<?php
defined( 'ABSPATH' ) or exit;

/**
 * A shortcode that allows themes that do not provide a content-single template
 * to show vehicle archives that are similar to the way themes are properly
 * built out for this plugin.
 */
class Inventory_Presser_Shortcode_Single_Vehicle
{
	function hooks()
	{
		add_shortcode( 'invp-single-vehicle', array( $this, 'content' ) );
	}

	function return_content()
	{

	}

	function content( $atts )
	{
		if( ! is_singular( Inventory_Presser_Plugin::CUSTOM_POST_TYPE ) )
		{
			return;
		}

		$vehicle = new Inventory_Presser_Vehicle( get_the_ID() );

		wp_enqueue_style( 'flexslider' );
		wp_enqueue_style( 'invp-flexslider' );
		wp_enqueue_script( 'invp-flexslider', '', array(), false, true );

 		ob_start();

		?><div class="vehicle-info">

			<div class="post-inner">

				<div class="post-thumbnail">
					<header class="entry-header">
						<?php the_title( '<h1 class="post-title">', '</h1>' ); ?>
					</header><!-- .entry-header -->

					<?php
					// widget area, main column below vehicle
					if( is_active_sidebar( 'sidebar-above-single-vehicle' ) )
					{
						dynamic_sidebar( 'sidebar-above-single-vehicle' );
					}
					?>
					<div class="vehicle-images">
						<?php
						if ( isset( $image_url_lists['large'] ) && count( $image_url_lists['large'] ) )
						{
							echo sprintf( '<div id="print-image">%s</div>', $image_url_lists['large'][0] );
						}
						?>

						<div id="slider-width"></div>

						<div id="slider" class="flexslider">
							<ul class="slides"><?php

								if ( isset( $image_url_lists['large'] ) )
								{
									for( $p=0; $p<sizeof( $image_url_lists['large'] ); $p++ )
									{
										//Inventory Presser versions 8.1.0 and above provide the 'urls'
										if( isset( $image_url_lists['urls'][$p] ) )
										{
											printf(
												'<li><a href="%s">%s</a></li>',
												$image_url_lists['urls'][$p],
												$image_url_lists['large'][$p]
											);
										}
										else
										{
										 	printf(
												'<li>%s</li>',
												$image_url_lists['large'][$p]
											);
										}
									}
								}
							?></ul>
						</div><?php

						if ( isset( $image_url_lists['thumb'] ) && count($image_url_lists['thumb']) > 1)
						{
							?><div id="carousel" class="flexslider no-preview">
							<ul class="slides"><?php

								foreach( $image_url_lists['thumb'] as $image )
								{
									printf( '<li>%s</li>', $image );
								}

							?></ul>
						</div><?php

						}
					?></div>

					<div class="vehicle-content-wrap"><?php the_content(); ?></div><?php

					// if there's a youtube video associated with this vehicle, embed it
					if ( $vehicle->youtube )
					{
						echo wp_oembed_get( 'https://www.youtube.com/watch?v=' . $vehicle->youtube );
					}

					?><ul class="vehicle-features"><?php

					// loop through list of vehicle options
					if( isset( $vehicle->option_array ) && is_array( $vehicle->option_array ) )
					{
						foreach($vehicle->option_array as $option)
						{
							printf( '<li>%s</li>', $option );
						}
					}

					?></ul><?php

					/**
					 * Maybe show an accordian of content if some
					 * filters add content to this empty array.
					 */
					$acc_arr = apply_filters( '_dealer_accordian_content', array(), $vehicle->post_ID );
					if( 0 < sizeof( $acc_arr ) )
					{
						echo '<div class="accordion cf"><ul class="accordion__wrapper">';
						foreach( $acc_arr as $title => $content )
						{
							echo '<li class="accordion__item">'
								. '<input type="checkbox" checked>'
								. '<span class="dashicons dashicons-arrow-up-alt2"></span>'
								. '<h2>' . $title . '</h2>'
								. '<div class="panel">'
								. '<div class="panel-inner">'
								. $content
								. '</div>'
								. '</div>'
								. '</li>';
						}
						echo '</ul></div>';

						//A disclaimer about third party databases
						printf(
							'<p><small>%s ',
							__( 'While every effort has been made to ensure the accuracy of this listing, some of the information this page was sourced from a third party rather than being entered by us as the sellers of this vehicle.', 'inventory-presser' )
						);

						if( $vehicle->is_used )
						{
							_e( 'Especially since this vehicle is used and could have been modified by a previous owner, it may not include the features or components listed on this page. ', 'inventory-presser' );
						}
						printf(
							'%s</small></p>',
							__( 'Please verify all statements and features with your sales representative before buying this vehicle.', 'inventory-presser' )
						);
					}

				?></div><!--/.post-thumbnail-->

				<div class="vehicle-content">

					<h2 class="post-title vehicle-price"><?php
							echo $vehicle->price( __( 'Call For Price', '_dealer' ) );
					?></h2>

					<?php
					// if dealership has multiple locations, display the location of this vehicle
					echo $vehicle->location_sentence();

					$invp_settings = Inventory_Presser_Plugin::settings();

					?><div class="vehicle-summary"><?php

						//Populate this array with attributes
						$labels_and_values = array();

						// Book Value
						if ( method_exists( $vehicle, 'get_book_value' ) && ( ! isset( $invp_settings['price_display'] ) || $invp_settings['price_display'] != 'genes' ) )
						{
							$book_value = $vehicle->get_book_value();
							if( $book_value > 0  && $book_value > intval( $vehicle->price ) )
							{
								$labels_and_values[apply_filters( '_dealer_label-book_value', 'Book Value' )] = '$' . number_format( $book_value, 0, '.', ',' );
							}
						}

						// MSRP
						if ( isset( $GLOBALS['_dealer_settings']['msrp_label'] ) && $GLOBALS['_dealer_settings']['msrp_label'] && isset( $vehicle->msrp ) && $vehicle->msrp )
						{
							$msrp = is_numeric( $vehicle->msrp ) ? number_format( $vehicle->msrp, 0, '.', ',' ) : $vehicle->msrp;
							$labels_and_values[$GLOBALS['_dealer_settings']['msrp_label']] = $msrp;
						}

						// Odometer
						if ( $vehicle->odometer( ' ' . apply_filters( '_dealer_odometer_word', 'Miles' ) ) && $vehicle->type != 'boat' )
						{
							$labels_and_values[apply_filters( '_dealer_label-odometer', apply_filters( '_dealer_odometer_word', 'Mileage' ) )] = $vehicle->odometer( ' ' . apply_filters( '_dealer_odometer_word', 'Miles' ) );
						}

						// Type
						if ( $vehicle->type )
						{
							$labels_and_values[apply_filters( '_dealer_label-type', __( 'Type', 'inventory-presser' ) )] = $vehicle->type;
						}

						// Body style
						if ( $vehicle->body_style )
						{
							$labels_and_values[apply_filters( '_dealer_label-body_style', __( 'Body style', '_dealer' ) )] = sprintf( '<span class="vehicle-content-initcaps">%s</span>', strtolower( $vehicle->body_style ) );
						}

						// Exterior Color
						if ( $vehicle->color )
						{
							$labels_and_values[apply_filters( '_dealer_label-color', 'Exterior' )] = sprintf( '<span class="vehicle-content-initcaps">%s</span>', strtolower( $vehicle->color ) );
						}

						// Interior Color
						if ( $vehicle->interior_color )
						{
							$labels_and_values[apply_filters( '_dealer_label-interior_color', 'Interior' )] = sprintf( '<span class="vehicle-content-initcaps">%s</span>', strtolower( $vehicle->interior_color ) );
						}

						//Engine
						if ( $vehicle->fuel || $vehicle->engine )
						{
							$labels_and_values[apply_filters( '_dealer_label-engine', 'Engine' )] =	implode( ' ', array( $vehicle->fuel, $vehicle->engine ) );
						}

						//Transmission
						if ( $vehicle->transmission )
						{
							$labels_and_values[apply_filters( '_dealer_label-transmission', 'Transmission' )] = sprintf( '<span class="vehicle-content-initcaps">%s</span>', strtolower( $vehicle->transmission ) );
						}

						//Drive Type
						if ( $vehicle->drivetype )
						{
							$labels_and_values[apply_filters( '_dealer_label-drivetype', 'Drive Type' )] = $vehicle->drivetype;
						}

						// stock #
						if ( $vehicle->stock_number )
						{
							$labels_and_values[apply_filters( '_dealer_label-stock_number', 'Stock' )] = $vehicle->stock_number;
						}

						// vin
						if ( $vehicle->vin )
						{
							$labels_and_values[apply_filters( '_dealer_label-vin', 'boat' == $vehicle->type ? 'HIN' : 'VIN' )] = $vehicle->vin;
						}

						//Boat-specific fields
						if( 'boat' == $vehicle->type )
						{
							//Beam
							if ( $vehicle->beam )
							{
								$labels_and_values[apply_filters( '_dealer_label-beam', 'Beam' )] = $vehicle->beam;
							}

							//Length
							if ( $vehicle->length )
							{
								$labels_and_values[apply_filters( '_dealer_label-length', 'Length' )] = $vehicle->length;
							}

							//Hull material
							if ( $vehicle->hull_material )
							{
								$labels_and_values[apply_filters( '_dealer_label-hull_material', 'Hull Material' )] = $vehicle->hull_material;
							}
						}

						//Output all the labels and values
						foreach( apply_filters( '_dealer_details_table_labels_and_values', $labels_and_values, $vehicle ) as $label => $value )
						{
							printf(
								'<div class="vehicle-summary-item"><div class="vehicle-summary-item-label">%s:</div><div class="vehicle-summary-item-value">%s</div></div>',
								$label,
								$value
							);
						}

					?></div>

					<?php
					// carfax
					$carfax_html = $vehicle->carfax_icon_html();
					if ( isset( $invp_settings['use_carfax'] ) && $invp_settings['use_carfax'] && '' != $carfax_html )
					{
						printf( '<div class="carfax-wrapper">%s</div>', $carfax_html );
					}

					// autocheck icon
					if( shortcode_exists( 'autocheck_button' ) )
					{
						$autocheck_html = do_shortcode( sprintf( '[autocheck_button vin="%s"]', $vehicle->vin ) );
						if( '' != $autocheck_html )
						{
							printf( '<div class="autocheck-wrapper">%s</div>', $autocheck_html );
						}
					}

					//include our vehicle details page menu if we have one set
					if ( has_nav_menu( 'vehicle-details' ) )
					{
						wp_nav_menu( array( 'theme_location' => 'vehicle-details' ) );
					}

				?></div><!--/.post-content--><?php

				// widget area, main column below vehicle
				if( is_active_sidebar( 'sidebar-below-single-vehicle' ) )
				{
					dynamic_sidebar( 'sidebar-below-single-vehicle' );
				}
				?>

			</div><!--/.post-inner-->

		</div><?php

		if( method_exists( $vehicle, 'schema_org_json_ld' ) && apply_filters( '_dealer_include_schema_org_json_ld', true ) )
		{
			echo $vehicle->schema_org_json_ld();
		}

		return ob_get_clean();
	}
}

