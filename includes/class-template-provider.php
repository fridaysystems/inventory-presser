<?php
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Inventory_Presser_Template_Provider' ) ) {
	/**
	 * Inventory_Presser_Template_Provider
	 *
	 * This class provides single and archive templates for the post type
	 * created by this plugin if the active theme does not contain any.
	 *
	 * @since 10.7.0
	 */
	class Inventory_Presser_Template_Provider {

		/**
		 * Adds hooks to catch requests for vehicle singles and archives.
		 *
		 * @return void
		 */
		public function add_hooks() {
			add_filter( 'single_template', array( $this, 'maybe_provide_template' ) );
			add_filter( 'archive_template', array( $this, 'maybe_provide_template' ) );
		}

		/**
		 * This method lies about whether vehicles have thumbnails so that all
		 * template content can be handled by the shortcode.
		 *
		 * @param  bool             $has_thumbnail true if the post has a post thumbnail, otherwise false.
		 * @param  int|WP_Post|null $post Post ID or WP_Post object. Default is global $post.
		 * @param  int|false        $thumbnail_id Post thumbnail ID or false if the post does not exist.
		 * @return bool
		 */
		public static function lie_about_post_thumbnails( $has_thumbnail, $post, $thumbnail_id ) {
			if ( ! empty( $post ) && INVP::POST_TYPE !== get_post_type( $post ) ) {
				return $has_thumbnail;
			}

			$is_vehicle_photo = 'attachment' === get_post_type( $thumbnail_id )
				&& ! empty( INVP::get_meta( 'photo_number', $thumbnail_id ) );

			if ( ! $is_vehicle_photo ) {
				return $has_thumbnail;
			}

			// if it's a vehicle with a vehicle photo, lie and say no.
			return false;
		}

		/**
		 * This method decides whether or not to add filters to the_content and
		 * has_post_thumbnail by examining the template file. If the theme does
		 * not have templates for vehicle singles and archives, the filters are
		 * added and shortcodes provide the templates instead. I stole this
		 * technique from WooCommerce, and it's kind of beautiful.
		 *
		 * @param  string $template The template file to load.
		 * @return string The same template file that was passed in
		 */
		public function maybe_provide_template( $template ) {

			// Is this setting enabled?
			$settings = INVP::settings();
			if ( ! isset( $settings['provide_templates'] ) || ! $settings['provide_templates'] ) {
				// No.
				return $template;
			}

			// is this our vehicle post type?
			global $post;
			if ( empty( $post ) ) {
				return $template;
			}

			if ( INVP::POST_TYPE !== $post->post_type ) {
				// no, who cares.
				return $template;
			}

			$single_or_archive = str_replace( '_template', '', current_filter() ?? '' );

			remove_filter( $single_or_archive . '_template', array( $this, 'maybe_provide_template' ) );

			if ( ! empty( $template )
				&& ( ( 'archive' === $single_or_archive && 'archive-' . INVP::POST_TYPE . '.php' === basename( $template ) )
				|| ( 'single' === $single_or_archive && 'single-' . INVP::POST_TYPE . '.php' === basename( $template ) ) )
			) {
				// the current theme already has a template.
				return $template;
			}

			// filter the post content to use a shortcode instead.
			add_filter( 'the_content', array( $this, 'replace_content_with_shortcode_' . $single_or_archive ) );

			// Lie to themes using has_post_thumbnail() statically.
			add_filter( 'has_post_thumbnail', array( __CLASS__, 'lie_about_post_thumbnails' ), 10, 3 );
			// Except when posts are being output by the Divi Blog Module.
			add_filter( 'et_builder_blog_query', array( $this, 'dont_lie_to_divi_blog_module' ) );

			// Still return the template.
			return $template;
		}

		/**
		 * Divi Blog Module never runs our shortcodes, so do not lie about
		 * thumbnails or the module won't show any. Callback on
		 * et_builder_blog_query, but does not modify the query. This filter is
		 * used to detect the Divi Blog Module.
		 *
		 * @param WP_Query $wp_query The query object created by Divi Blog Module.
		 * @return WP_Query The unchanged query object.
		 */
		public function dont_lie_to_divi_blog_module( $wp_query ) {
			// Stop lying about whether vehicles have thumbnails or not.
			remove_filter( 'has_post_thumbnail', array( __CLASS__, 'lie_about_post_thumbnails' ), 10, 3 );
			return $wp_query;
		}

		/**
		 * Returns the output of the [invp_archive_vehicle] shortcode.
		 *
		 * @param  string $content The post content as provided by the the_content filter.
		 * @return string The output of the [invp_archive_vehicle] shortcode
		 */
		public function replace_content_with_shortcode_archive( $content ) {
			return do_shortcode( '[invp_archive_vehicle]' );
		}

		/**
		 * Filter callback. Returns the output of the [invp_single_vehicle]
		 * shortcode regardless of what is passed in.
		 *
		 * @param  string $content The post content as provided by the the_content filter.
		 * @return string The output of the [invp_single_vehicle] shortcode
		 */
		public function replace_content_with_shortcode_single( $content ) {
			/**
			 * Avoid running the shortcode more than necessary by checking if
			 * this stylesheet is already enqueued. Some themes, like
			 * GeneratePress, apply filters to the_content a few times.
			 */
			if ( wp_style_is( 'invp_single_vehicle' ) ) {
				// Remove the filter we're in to avoid nested calls.
				remove_filter( 'the_content', array( $this, 'replace_content_with_shortcode_single' ) );
			}
			return do_shortcode( '[invp_single_vehicle]' );
		}
	}
}
