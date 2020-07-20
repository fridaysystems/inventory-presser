<?php
defined( 'ABSPATH' ) or exit;

/**
 * A shortcode that allows themes that do not provide a content-archive template
 * to show vehicle archives that are similar to the way themes are properly
 * built out for this plugin.
 */
class Inventory_Presser_Shortcode_Archive_Vehicle extends Inventory_Presser_Template_Shortcode
{
	function hooks()
	{
		add_shortcode( 'invp-archive-vehicle', array( $this, 'content' ) );
		add_shortcode( 'invp_archive_vehicle', array( $this, 'content' ) );
	}

	function content( $atts )
	{
		if( ! is_post_type_archive( Inventory_Presser_Plugin::CUSTOM_POST_TYPE ) )
		{
			return;
		}

		wp_enqueue_style( 'invp-attribute-table' );
		wp_enqueue_style( 'invp_archive_vehicle' );

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
									?><img class="no-photo-available" src="<?php echo plugins_url( '/assets/no-photo.png', dirname( dirname( __FILE__ ) ) . '/inventory-presser.php' ); ?>" alt="<?php the_title(); ?>" /><?php
								}
								//Resume lying about thumbnails
								add_filter( 'has_post_thumbnail', array( 'Inventory_Presser_Template_Provider', 'lie_about_post_thumbnails' ), 10, 3 );

							?></a>
						</div><?php

							$photo_count = $vehicle->photo_count();
							if( 0 < $photo_count )
							{
						?><span class="photo-count"><a href="<?php the_permalink(); ?>"><?php

							$multi_icon = '<span class="dashicons dashicons-format-gallery"></span>';
							$single_icon = '<span class="dashicons dashicons-format-image"></span>';
							echo $photo_count . ' ' . ( 1 == $photo_count ? $single_icon : $multi_icon );

						?></a></span><?php

							}

					?></div>

					<div class="vehicle-summary"><?php

						echo $this->vehicle_attribute_table( $vehicle );

					?></div>

					<div class="vehicle-price-and-buttons">
						<h3 class="vehicle-price"><?php
							echo $vehicle->price( __( 'Call For Price', 'inventory-presser' ) );
						?></h3><?php

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

						do_action( 'invp_archive_buttons', $vehicle );

						?><a class="wp-block-button__link" href="<?php the_permalink(); ?>" title="<?php the_title(); ?>"><?php _e( 'View Details', 'inventory-presser' ); ?></a>
					</div>

				</div><!--/.post-inner-->

			</div>
		</article><!--/.post--><?php

		return ob_get_clean();
	}
}
