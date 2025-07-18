<?php
defined( 'ABSPATH' ) || exit;

/**
 * Inventory_Presser_Shortcode_Archive_Vehicle
 *
 * A shortcode that outputs a single archive vehicle in an <article> element.
 * Designed to help any theme show an archive of vehicles. Not intended to be
 * used outright. We use this shortcode on the_content hook to provide better
 * output than a theme that doesn't know anything about our custom post type.
 */
class Inventory_Presser_Shortcode_Archive_Vehicle {

	/**
	 * Adds two shortcodes
	 *
	 * @return void
	 */
	public function add() {
		add_shortcode( 'invp-archive-vehicle', array( $this, 'content' ) );
		add_shortcode( 'invp_archive_vehicle', array( $this, 'content' ) );
	}

	/**
	 * Adds hooks that power the shortcode
	 *
	 * @return void
	 */
	public function add_hooks() {
		add_action( 'init', array( $this, 'add' ) );
	}

	/**
	 * Creates the HTML content of the shortcode
	 *
	 * @param  array $atts
	 * @return string HTML that renders an archive-vehicle template
	 */
	public function content( $atts ) {
		/**
		 * Default show_titles to false because this shortcode is used to
		 * replace the_content when themes handle our custom post type, and
		 * those themes will output a title.
		 */
		$atts = shortcode_atts(
			array(
				/**
				 * Sometimes, it makes sense to let the theme or page builder
				 * layout handle titles.
				 */
				'show_titles' => false,

				/**
				 * Change the look of the listings.
				 *
				 * Values
				 * -----
				 * a. "Flexible" Archive Listing Style from _dealer theme. Condensed.
				 * b. "Default" Archive Listing Style from _dealer theme. Large photo.
				 */
				'style'       => 'a',
			),
			$atts
		);

		// Parse boolean values to make life easy on users.
		$atts['show_titles'] = filter_var( $atts['show_titles'], FILTER_VALIDATE_BOOLEAN );

		if ( ! wp_style_is( 'invp_archive_vehicle', 'registered' ) ) {
			Inventory_Presser_Plugin::include_scripts_and_styles();
		}
		wp_enqueue_style( 'invp_archive_vehicle' );

		// Lie to themes using has_post_thumbnail() statically.
		add_filter( 'has_post_thumbnail', array( 'Inventory_Presser_Template_Provider', 'lie_about_post_thumbnails' ), 10, 3 );

		/**
		 * HTML varies depending on the style setting.
		 *
		 * ASCII art via https://www.patorjk.com/software/taag/#p=display&f=Bloody&t=B
		 */
		ob_start();
		switch ( $atts['style'] ) {
			/**
			 * ▄▄▄
			 * ▒████▄
			 * ▒██  ▀█▄
			 * ░██▄▄▄▄██
			 * ▓█   ▓██▒
			 * ▒▒   ▓▒█░
			 *  ▒   ▒▒ ░
			 *  ░   ▒
			 *      ░  ░
			 */
			case 'a':
				wp_enqueue_style( 'invp_archive_vehicle_a' );
				?>
				<article id="post-<?php the_ID(); ?>" <?php post_class( 'post-vehicle' ); ?>>
					<div class="vehicle-info">
						<div class="entry-header">
					<?php
					if ( $atts['show_titles'] ) {
						?>

							<h2 class="entry-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>                    

						<?php
					}
					?>
							<h3 class="vehicle-price">
							<?php
							/* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */
							echo invp_get_the_price();
							?>
							</h3>
						</div>
						<div class="post-inner">
							<div class="post-thumbnail">
								<div class="vehicle-images">
									<a href="<?php the_permalink(); ?>" title="<?php the_title(); ?>">
										<?php
										// Stop lying about whether vehicles have thumbnails or not.
										remove_filter( 'has_post_thumbnail', array( 'Inventory_Presser_Template_Provider', 'lie_about_post_thumbnails' ), 10, 3 );
										if ( has_post_thumbnail() ) {
											the_post_thumbnail( 'large' );
										} else {
											// This will be no-photo.svg.
											?>
										<img class="no-photo-available" src="<?php echo esc_attr( invp_get_the_photo_url() ); ?>" alt="<?php the_title(); ?>" />
											<?php
										}
										// Resume lying about thumbnails.
										add_filter( 'has_post_thumbnail', array( 'Inventory_Presser_Template_Provider', 'lie_about_post_thumbnails' ), 10, 3 );
										?>
									</a>
								</div>
							<?php
							$photo_count = invp_get_the_photo_count();
							if ( 0 < $photo_count ) {
								?>
								<span class="photo-count"><a href="<?php the_permalink(); ?>">
								<?php
									echo esc_html( $photo_count ) . ' ' . ( 1 === $photo_count ? '<span class="dashicons dashicons-format-image"></span>' : '<span class="dashicons dashicons-format-gallery"></span>' );
								?>
								</a></span>
								<?php
							}
							?>
							</div>
							<?php
							/* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */
							echo apply_shortcodes( '[invp_attribute_table]' );
							?>
							<div class="vehicle-buttons vehicle-price-and-buttons">
								<?php
								do_action( 'invp_archive_buttons' );
								?>
							</div>
						</div><!--/.post-inner-->
					</div>
				</article><!--/.post-->
				<?php
				break;

			/**
			 *  ▄▄▄▄
			 * ▓█████▄
			 * ▒██▒ ▄██
			 * ▒██░█▀
			 * ░▓█  ▀█▓
			 * ░▒▓███▀▒
			 * ▒░▒   ░
			 *  ░    ░
			 *  ░
			 *       ░
			 */
			case 'b':
				wp_enqueue_style( 'invp_archive_vehicle_b' );
				?>
				<article id="post-<?php the_ID(); ?>" <?php post_class( 'post-vehicle' ); ?>>
					<div class="vehicle-info">
						<div class="post-inner">
							<div class="post-thumbnail">
						<?php
						if ( $atts['show_titles'] ) {
							?>
								<h2 class="post-title hpad">
									<a href="<?php the_permalink(); ?>" rel="bookmark" title="<?php the_title(); ?>"><?php the_title(); ?></a>
								</h2>
								<?php
						}
						?>
								<div class="vehicle-images">
									<a href="<?php the_permalink(); ?>" title="<?php the_title(); ?>">
										<?php
										// Stop lying about whether vehicles have thumbnails or not.
										remove_filter( 'has_post_thumbnail', array( 'Inventory_Presser_Template_Provider', 'lie_about_post_thumbnails' ), 10, 3 );
										if ( has_post_thumbnail() ) {
											the_post_thumbnail( 'large' );
										} else {
											?>
										<img class="no-photo-available" src="<?php echo esc_url( invp_get_the_photo_url() ); ?>" alt="<?php esc_attr_e( 'No photo available', '_dealer' ); ?>" />
											<?php
										}
										// Resume lying about thumbnails.
										add_filter( 'has_post_thumbnail', array( 'Inventory_Presser_Template_Provider', 'lie_about_post_thumbnails' ), 10, 3 );
										?>
									</a>
								</div>
							</div><!--/.post-thumbnail-->
							<div class="vehicle-content">
								<h2 class="post-title vehicle-price">
									<?php
										/* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */
										echo invp_get_the_price();
									?>
								</h2>
								<?php
								/* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */
								echo apply_shortcodes( '[invp_attribute_table]' );
								?>
								<div class="vehicle-buttons">
								<?php
								do_action( 'invp_archive_buttons' );
								?>
								</div><!--/.vehicle-buttons-->
							</div><!--/.post-content-->
						</div><!--/.post-inner-->
					</div>
				</article><!--/.post-->
					<?php
				break;
		}
		return ob_get_clean();
	}
}
