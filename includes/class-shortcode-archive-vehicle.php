<?php
defined( 'ABSPATH' ) or exit;

/**
 * Inventory_Presser_Shortcode_Archive_Vehicle
 * 
 * A shortcode that outputs a single archive vehicle in an <article> element.
 * Designed to help any theme show an archive of vehicles. Not intended to be 
 * used outright. We use this shortcode on the_content hook to provide better
 * output than a theme that doesn't know anything about our custom post type.
 */
class Inventory_Presser_Shortcode_Archive_Vehicle extends Inventory_Presser_Template_Shortcode
{		
	/**
	 * add
	 * 
	 * Adds two shortcodes
	 *
	 * @return void
	 */
	function add()
	{
		add_shortcode( 'invp-archive-vehicle', array( $this, 'content' ) );
		add_shortcode( 'invp_archive_vehicle', array( $this, 'content' ) );
	}
	
	/**
	 * hooks
	 * 
	 * Adds hooks that power the shortcode
	 *
	 * @return void
	 */
	function hooks()
	{
		add_action( 'init', array( $this, 'add' ) );
	}
	
	/**
	 * content
	 * 
	 * Creates the HTML content of the shortcode
	 *
	 * @param  array $atts
	 * @return string HTML that renders an archive-vehicle template
	 */
	function content( $atts )
	{
		/**
		 * Default show_titles to false because this shortcode is used to 
		 * replace the_content when themes handle our custom post type, and 
		 * those themes will output a title.
		 */
		$atts = shortcode_atts( array(
 			'show_titles'   => false,
		), $atts );

		//We want real booleans, please
		if( is_string( $atts['show_titles'] ) )
		{
			$atts['show_titles'] = filter_var( $atts['show_titles'], FILTER_VALIDATE_BOOLEAN );
		}

		wp_enqueue_style( 'invp-attribute-table' );
		wp_enqueue_style( 'invp_archive_vehicle' );

 		ob_start();
		?>

		<article id="post-<?php the_ID(); ?>" <?php post_class('post-vehicle'); ?>>
			<div class="vehicle-info"><?php

				if( $atts['show_titles'] )
				{
					?><div class="entry-header">
							<h2 class="entry-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>							
						</div><?php
				}

				?><div class="post-inner">
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
									?><img class="no-photo-available" src="<?php echo plugins_url( '/images/no-photo.png', INVP_PLUGIN_FILE_PATH ); ?>" alt="<?php the_title(); ?>" /><?php
								}
								//Resume lying about thumbnails
								add_filter( 'has_post_thumbnail', array( 'Inventory_Presser_Template_Provider', 'lie_about_post_thumbnails' ), 10, 3 );

							?></a>
						</div><?php

							$photo_count = invp_get_the_photo_count();
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

						echo $this->vehicle_attribute_table();

					?></div>

					<div class="vehicle-price-and-buttons">
						<h3 class="vehicle-price"><?php
							echo invp_get_the_price();
						?></h3><?php

						//carfax
						if ( isset( $invp_settings['use_carfax'] ) && $invp_settings['use_carfax'] )
						{
							$carfax_html = invp_get_the_carfax_icon_html();
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
							$autocheck_html = do_shortcode( sprintf( '[autocheck_button vin="%s"]', invp_get_the_VIN() ) );
							if( '' != $autocheck_html )
							{
								?><div class="autocheck-wrapper"><?php
									echo $autocheck_html;
								?></div><?php
							}
						}

						do_action( 'invp_archive_buttons' );

						?><a class="wp-block-button__link button" href="<?php the_permalink(); ?>" title="<?php the_title(); ?>"><?php _e( 'View Details', 'inventory-presser' ); ?></a>
					</div>

				</div><!--/.post-inner-->

			</div>
		</article><!--/.post--><?php

		return ob_get_clean();
	}
}
