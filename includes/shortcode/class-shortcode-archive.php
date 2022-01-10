<?php
defined( 'ABSPATH' ) or exit;

/**
 * Inventory_Presser_Shortcode_Archive
 * 
 * A shortcode that allows themes that do not provide a content-archive template
 * to show a vehicle archive.
 */
class Inventory_Presser_Shortcode_Archive extends Inventory_Presser_Template_Shortcode
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
		add_shortcode( 'invp-archive', array( $this, 'content' ) );
		add_shortcode( 'invp_archive', array( $this, 'content' ) );
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
	 * clean_attributes_for_query
	 * 
	 * Removes shortcode attributes from the attributes array that are not also
	 * query parameters for a posts query.
	 *
	 * @param  array $atts
	 * @return array
	 */
	private function clean_attributes_for_query( $atts )
	{
		unset( $atts['show_titles'] );
		return $atts;
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
		wp_enqueue_style( 'invp-attribute-table' );
		wp_enqueue_style( 'invp_archive_vehicle' );
		$plugin_settings = INVP::settings();

		$atts = shortcode_atts( array(
			'meta_key'       => apply_filters( 'invp_prefix_meta_key', $plugin_settings['sort_vehicles_by'] ),
			'order'          => $plugin_settings['sort_vehicles_order'],
			'orderby'        => apply_filters( 'invp_prefix_meta_key', $plugin_settings['sort_vehicles_by'] ),
			'paged'          => ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1,
			'posts_per_page' => get_option( 'posts_per_page' ),
			'post_status'    => 'publish',
			'show_titles'    => true,
		), $atts );

		//Don't let input change the post type
		$atts['post_type'] = INVP::POST_TYPE;

		//Add all taxonomy query vars to $atts so filters work
		$taxonomies = get_object_taxonomies( $atts['post_type'], 'objects' );
		foreach( $taxonomies as $taxonomy )
		{
			$atts[$taxonomy->query_var] = get_query_var( $taxonomy->query_var );
		}
	 
		query_posts( $this->clean_attributes_for_query( $atts ) );
		$output = '';
		if ( have_posts() )
		{
			while ( have_posts() )
			{
				the_post();
				$shortcode = sprintf( '[invp_archive_vehicle show_titles="%s"]', strval( $atts['show_titles'] ) );
				$output .= apply_shortcodes( $shortcode );
			}
		}

		//Paged navigation
		$output .= $this->paging_html();

		wp_reset_query();
		return $output;
	}

	private function paging_html()
	{
		global $wp_query;
		$pagination_html = '<ul class="group">';
	
		//previous page link
		$previous_link = get_previous_posts_link();
		if( '' != $previous_link )
		{
			$pagination_html .= '<li class="prev left">' . $previous_link . '</li>';
		}
	
		//clickable page numbers
		$pagination_html .= sprintf( 
			'<li>%s</li>',
			get_the_posts_pagination( array(
				'mid_size'  => 2,
				'prev_next' => false,
			) )
		);
	
		//next page link
		$next_link = get_next_posts_link();
		if( '' != $next_link )
		{
			$pagination_html .= '<li class="next right">' . $next_link . '</li>';
		}
	
		//sentence "Showing 1 to 10 of 99 posts"
		$posts_per_page = $wp_query->query_vars['posts_per_page'];
		$page_number = null == $wp_query->query_vars['paged'] ? 1 : $wp_query->query_vars['paged'];
		$start_index = $page_number * $posts_per_page - ( $posts_per_page - 1);
		$end_index = min( array( $start_index + $posts_per_page - 1, $wp_query->found_posts ) );
	
		$object_name = 'posts';
		$post_type_name = isset( $wp_query->query_vars['post_type'] ) ? $wp_query->query_vars['post_type'] : '';
		if( '' != $post_type_name )
		{
			$post_type = get_post_type_object( $post_type_name );
			$object_name = 	strtolower( $post_type->labels->name );
		}
	
		$pagination_html .= '</ul><p>'
			. apply_filters( 'invp_pagination_sentence', sprintf( 'Showing %d to %d of %d %s', $start_index, $end_index, $wp_query->found_posts, $object_name ) )
			. '</p>';		
		
		return '<nav class="pagination group">'
			. apply_filters( 'invp_pagination_html', $pagination_html )
			. '</nav>';
	}
}
