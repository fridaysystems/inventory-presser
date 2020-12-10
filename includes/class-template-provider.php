<?php
defined( 'ABSPATH' ) or exit;

if ( ! class_exists( 'Inventory_Presser_Template_Provider' ) )
{	
	/**
	 * Inventory_Presser_Template_Provider
	 * 
	 * This class provides single and archive templates for the post type 
	 * created by this plugin if the active theme does not contain any.
	 * 
	 * @since      10.7.0
	 */
	class Inventory_Presser_Template_Provider
	{		
		/**
		 * hooks
		 * 
		 * Adds hooks to catch requests for vehicle singles and archives.
		 *
		 * @return void
		 */
		function hooks()
		{
			add_filter( 'single_template', array( $this, 'maybe_provide_template' ) );
			add_filter( 'archive_template', array( $this, 'maybe_provide_template' ) );
		}
		
		/**
		 * lie_about_post_thumbnails
		 * 
		 * This method lies about whether vehicles have thumbnails so that all
		 * template content can be handled by the shortcode.
		 *
		 * @param  bool $has_thumbnail
		 * @param  mixed $post
		 * @param  int $thumbnail_id
		 * @return void
		 */
		static function lie_about_post_thumbnails( $has_thumbnail, $post, $thumbnail_id )
		{
			if( ! empty( $post ) && INVP::POST_TYPE != get_post_type( $post ) )
			{
				return $has_thumbnail;
			}

			$is_vehicle_photo = 'attachment' == get_post_type( $thumbnail_id )
				&& ! empty( get_post_meta( $thumbnail_id, apply_filters( 'invp_prefix_meta_key', 'photo_number' ), true ) );

			if( ! $is_vehicle_photo )
			{
				return $has_thumbnail;
			}

			//if it's a vehicle with a vehicle photo, lie and say no
			return false;
		}
		
		/**
		 * maybe_provide_template
		 * 
		 * This method decides whether or not to add filters to the_content and
		 * has_post_thumbnail by examining the template file. If the theme does
		 * not have templates for vehicle singles and archives, the filters are
		 * added and shortcodes provide the templates instead. I stole this 
		 * technique from WooCommerce, and it's kind of beautiful.
		 *
		 * @param  string $template The template file to load
		 * @return string The same template file that was passed in
		 */
		function maybe_provide_template( $template )
		{
			//is this our vehicle post type?
			global $post;
			if( empty( $post ) )
			{
				return $template;
			}

			if ( INVP::POST_TYPE != $post->post_type )
			{
				//no, who cares what happens
				return $template;
			}

			$single_or_archive = str_replace( '_template', '', current_filter() );

			remove_filter( $single_or_archive . '_template', array( $this, 'maybe_provide_template' ) );

			if( ! empty( $template )
				&& (
					( 'archive' == $single_or_archive && 'archive-' . INVP::POST_TYPE . '.php' == basename( $template ) )
					|| ( 'single' == $single_or_archive && 'single-' . INVP::POST_TYPE . '.php' == basename( $template ) )
				) )
			{
				//the current theme already has a template
				return $template;
			}

			//lie to themes using has_post_thumbnail() statically
			add_filter( 'has_post_thumbnail', array( __CLASS__, 'lie_about_post_thumbnails' ), 10, 3 );

			//filter the post content to use a shortcode instead
			add_filter( 'the_content', array( $this, 'replace_content_with_shortcode_' . $single_or_archive ) );

			//Still return the empty template
			return $template;
		}
		
		/**
		 * replace_content_with_shortcode_archive
		 * 
		 * Returns the output of the [invp-archive-vehicle] shortcode.
		 *
		 * @param  string $content The post content as provided by the the_content filter
		 * @return string The output of the [invp-archive-vehicle] shortcode.
		 */
		function replace_content_with_shortcode_archive( $content )
		{
			return do_shortcode( '[invp-archive-vehicle]' );
		}
		
		/**
		 * replace_content_with_shortcode_single
		 * 
		 * Filter callback. Returns the output of the [invp-single-vehicle] 
		 * shortcode regardless of what is passed in.
		 *
		 * @param  string $content The post content as provided by the the_content filter
		 * @return string The output of the [invp-single-vehicle] shortcode
		 */
		function replace_content_with_shortcode_single( $content )
		{
			// Remove the filter we're in to avoid nested calls.
			remove_filter( 'the_content', array( $this, 'replace_content_with_shortcode_single' ) );
			return do_shortcode( '[invp-single-vehicle]' );
		}
	}
}
