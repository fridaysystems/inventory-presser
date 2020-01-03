<?php

class Inventory_Presser_Allow_Inventory_As_Home_Page
{
	const PAGE_META_KEY = '_inventory_presser_hidden_page';

	private static function create_page()
	{
		//Does the page already exist?
		if( -1 != self::find_page_id() )
		{
			//Yes, abort
			return;
		}

		$id = wp_insert_post( array(
			'comment_status' => 'closed',
			'meta_input'     => array(
				self::PAGE_META_KEY => '1',
			),
			'post_content'   => __( 'This is not the real Inventory page. This page exists so that you can set the site\'s home page to the Inventory list in the Customizer or the Settings > Reading admin page. The Inventory "page" is actually a custom post type archive, and typically will not show up in those settings drop downs. All requests for this page will be redirected to the Inventory archive.', 'inventory-presser' ),
			'post_status'    => 'publish',
			'post_title'     => __( 'Inventory', 'inventory-presser' ),
			'post_type'      => 'page',
		) );
	}

	static function create_pages()
	{
		/**
		 * Are we on multi-site? If so, we need to create a page on every blog
		 * in the multisite network
		 */

		if( ! is_multisite() )
		{
			self::create_page();
			return;
		}

		//We are on multisite, create a page for every site on the network
		$sites = get_sites( array( 'network' => 1, 'limit' => 1000 ) );
		foreach( $sites as $site )
		{
			switch_to_blog( $site->blog_id );
			self::create_page();
			restore_current_blog();
		}
	}

	static function delete_pages()
	{
		/**
		 * Are we on multi-site? If so, we need to delete the page from every
		 * blog in the multisite network
		 */
		if( ! is_multisite() )
		{
			wp_delete_post( self::find_page_id(), true );
			return;
		}

		//We are on multisite, create a page for every site on the network
		$sites = get_sites( array( 'network' => 1, 'limit' => 1000 ) );
		foreach( $sites as $site )
		{
			switch_to_blog( $site->blog_id );
			wp_delete_post( self::find_page_id(), true );
			restore_current_blog();
		}
	}

	/**
	 * Finds the ID of the page this plugin creates to allow the user to pick
	 * the inventory listing as a home page option.
	 *
	 * @return int The ID of the page or -1 if the page is not found.
	 */
	static function find_page_id()
	{
		$pages = get_pages( array(
			'meta_key'   => self::PAGE_META_KEY,
			'meta_value' => '1',
		) );

		if( empty( $pages ) )
		{
			return -1;
		}

		return $pages[0]->ID;
	}

	/**
	 * Prevents the page this plugin creates from showing up in the list of
	 * pages while editing in the dashboard. We need a page to exist in order to
	 * allow users to choose it as their home page, but we don't want them to
	 * see an actual page in the list, because the Inventory listing is not a
	 * page, it's a custom post type archive.
	 *
	 * @param WP_Query $query An instance of the WP_Query class
	 * @return void
	 */
	function hide_page_from_edit_list( $query )
	{
		global $pagenow, $post_type;
		if( is_admin() && 'edit.php' == $pagenow && 'page' == $post_type )
		{
			$query->query_vars['post__not_in'] = array( self::find_page_id() );
		}
	}

	function hooks()
	{
		$file_path = dirname( __FILE__, 2 ) . DIRECTORY_SEPARATOR . 'inventory-presser.php';
		register_activation_hook(   $file_path, array( 'Inventory_Presser_Allow_Inventory_As_Home_Page', 'create_pages' ) );
		register_deactivation_hook( $file_path, array( 'Inventory_Presser_Allow_Inventory_As_Home_Page', 'delete_pages' ) );

		add_filter( 'parse_query', array( $this, 'hide_page_from_edit_list' ) );
		add_action( 'pre_get_posts', array( $this, 'redirect_the_page' ) );
	}

	function redirect_the_page( $wp_query )
	{
		if( is_admin() )
		{
			return;
		}

		if( $wp_query->get( 'page_id' ) == get_option( 'page_on_front' ) && $wp_query->get( 'page_id' ) == self::find_page_id() )
		{
			wp_redirect( get_post_type_archive_link( Inventory_Presser_Plugin::CUSTOM_POST_TYPE ) );
			exit;
		}
	}
}
