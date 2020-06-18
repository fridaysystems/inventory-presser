<?php
defined( 'ABSPATH' ) or exit;

/**
 * A shortcode that allows themes that do not provide a content-single template
 * to show vehicle archives that are similar to the way themes are properly
 * built out for this plugin.
 */
class Inventory_Presser_Shortcode_Single_Vehicle extends Inventory_Presser_Template_Shortcode
{
	function hooks()
	{
		add_shortcode( 'invp-single-vehicle', array( $this, 'content' ) );
	}

	function content( $atts )
	{
		if( ! is_singular( Inventory_Presser_Plugin::CUSTOM_POST_TYPE ) )
		{
			return;
		}

		$vehicle = new Inventory_Presser_Vehicle( get_the_ID() );
		$image_url_lists = $vehicle->get_images_html_array( array( 'large', 'thumb' ) );

		wp_enqueue_style( 'invp-attribute-table' );
		wp_enqueue_style( 'flexslider' );
		wp_enqueue_style( 'invp-flexslider' );
		wp_enqueue_style( 'invp_single_vehicle' );
		wp_enqueue_script( 'invp-flexslider', '', array(), false, true );

 		ob_start();

		?><div class="vehicle-info">

			<div class="post-inner">

				<div class="post-thumbnail">
					<div class="vehicle-content">
						<h2 class="post-title vehicle-price"><?php
								echo $vehicle->price( __( 'Call For Price', 'inventory-presser' ) );
						?></h2>

						<?php
						// if dealership has multiple locations, display the location of this vehicle
						echo $vehicle->location_sentence();

					?></div>
					<div class="vehicle-images">
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
				</div><!--/.post-thumbnail-->

				<div class="vehicle-columns">
					<div class="vehicle-summary"><?php

						echo $this->vehicle_attribute_table( $vehicle );

					?></div>
					<div class="vehicle-buttons"><?php
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
					?></div>
				</div>

				<div class="vehicle-content">
					<div class="vehicle-content-wrap"><?php echo $vehicle->description; ?></div><?php

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

					?></ul>
				</div>
			</div><!--/.post-inner-->
		</div><script type="text/javascript"><!--
	function adjustSlideHeight(wrapper)
	{
		var ratios = [];
		jQuery(wrapper + ' .slides li img').each(function() {
			ratios.push( jQuery(this).attr('height') / jQuery(this).attr('width'));
		});
		height = Math.ceil( jQuery('#slider-width').width() * Math.min.apply(Math,ratios));
		jQuery(wrapper + ' .slides li img').each(function() {
			jQuery(this).css('height', height + 'px');
			jQuery(this).css('width', 'auto');
		});
	}

	jQuery(window).load(function()
	{
		if (jQuery('body').hasClass('single-inventory_vehicle'))
		{
			adjustSlideHeight('#slider');

			jQuery(window).resize(function() {
				setTimeout(function() {
					adjustSlideHeight('#slider');
				}, 120);
			});

			// The slider being synched must be initialized first
			jQuery('#carousel').flexslider({
				animation: "slide",
				controlNav: false,
				slideshow: false,
				smoothHeight: true,
				itemWidth: 150,
				itemMargin: 10,
				asNavFor: '#slider',
				prevText: '',
				nextText: ''
			});
		}
	});
--></script><?php

		if( method_exists( $vehicle, 'schema_org_json_ld' ) && apply_filters( 'invp_include_schema_org_json_ld', true ) )
		{
			echo $vehicle->schema_org_json_ld();
		}

		return ob_get_clean();
	}
}
