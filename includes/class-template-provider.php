<?php
defined( 'ABSPATH' ) or exit;

/**
 * Provides single and archive templates for the post type created by this
 * plugin if the active theme does not contain any.
 *
 * @since      10.7.0
 */
if ( ! class_exists( 'Inventory_Presser_Template_Provider' ) )
{
	class Inventory_Presser_Template_Provider
	{
		function hooks()
		{
			add_filter( 'single_template', array( $this, 'maybe_provide_template' ) );
			add_filter( 'archive_template', array( $this, 'maybe_provide_template' ) );
		}

		static function lie_about_post_thumbnails( $has_thumbnail, $post, $thumbnail_id )
		{
			if( ! empty( $post) && Inventory_Presser_Plugin::CUSTOM_POST_TYPE != get_post_type( $post ) )
			{
				return $has_thumbnail;
			}

			$is_vehicle_photo = ! empty( get_post_meta( $thumbnail_id, apply_filters( 'invp_prefix_meta_key', 'photo_number' ), true ) );
			if( ! $is_vehicle_photo )
			{
				return $has_thumbnail;
			}

			return false;
		}

		function maybe_provide_template( $template )
		{
			//is this our vehicle post type?
			global $post;
			if( empty( $post ) )
			{
				return $template;
			}

			if ( Inventory_Presser_Plugin::CUSTOM_POST_TYPE != $post->post_type )
			{
				//no, who cares what happens
				return $template;
			}

			$single_or_archive = str_replace( '_template', '', current_filter() );

			remove_filter( $single_or_archive . '_template', array( $this, 'maybe_provide_template' ) );

			if( ! empty( $template )
				&& (
					( 'archive' == $single_or_archive && 'archive-' . Inventory_Presser_Plugin::CUSTOM_POST_TYPE . '.php' == basename( $template ) )
					|| ( 'single' == $single_or_archive && 'single-' . Inventory_Presser_Plugin::CUSTOM_POST_TYPE . '.php' == basename( $template ) )
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

		function replace_content_with_shortcode_archive( $content )
		{
			return do_shortcode( '[invp-archive-vehicle]' );
		}

		function replace_content_with_shortcode_single( $content )
		{
			// Remove the filter we're in to avoid nested calls.
			remove_filter( 'the_content', array( $this, 'replace_content_with_shortcode_single' ) );
			return do_shortcode( '[invp-single-vehicle]' );
		}
	}
}
