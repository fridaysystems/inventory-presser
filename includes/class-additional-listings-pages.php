<?php
defined( 'ABSPATH' ) or exit;

/**
 * This class makes additional listings pages work at URL paths other than
 * /inventory. For example, a dealer might have some vehicles with down payment
 * values while most of their inventory does not have values for down payment,
 * and this class makes it easy to put those specific vehicles on a listings
 * page together.
 */
class Inventory_Presser_Additional_Listings_Pages
{
	function hooks()
	{
		//Do we have additional listings pages enabled?
		$settings = Inventory_Presser_Plugin::settings();
		if( empty( $settings['additional_listings_page'] ) || ! $settings['additional_listings_page'] )
		{
			return;
		}
		add_filter( 'invp_rewrite_slugs', array( $this, 'add_rewrite_slugs' ) );
		add_filter( 'invp_rewrite_rules', array( $this, 'add_rewrite_rules' ) );
		add_action( 'pre_get_posts', array( $this, 'modify_query' ) );
	}

	function add_rewrite_rules( $rules )
	{
		foreach( self::additional_listings_pages_array() as $additional_listing )
		{
			if( empty( $additional_listing['url_path'] ) )
			{
				continue;
			}

			/**
			 * Create a base rule for this slug because this isn't a
			 * post type that will just work.
			 */
			$rules[$additional_listing['url_path'] . '/?$'] = 'index.php?post_type=' . Inventory_Presser_Plugin::CUSTOM_POST_TYPE;
		}
		return $rules;
	}

	function add_rewrite_slugs( $slugs )
	{
		foreach( self::additional_listings_pages_array() as $additional_listing )
		{
			if( empty( $additional_listing['url_path'] ) )
			{
				continue;
			}
			$slugs[] = $additional_listing['url_path'];
		}
		return $slugs;
	}

	public static function additional_listings_pages_array()
	{
		//Are there additional listings pages configured?
		$settings = Inventory_Presser_Plugin::settings();
		if( ! empty( $settings['additional_listings_page'] )
			&& $settings['additional_listings_page']
			&& ! empty( $settings['additional_listings_pages'] ) )
		{
			return $settings['additional_listings_pages'];
		}
		return array();
	}

	function modify_query( $query )
	{
		//Do not mess with the query if it's not the main one and our CPT
		if ( ! $query->is_main_query() || ! is_post_type_archive( Inventory_Presser_Plugin::CUSTOM_POST_TYPE ) )
		{
			return;
		}

		//which rule does the URL path match?
		global $wp;
		foreach( self::additional_listings_pages_array() as $additional_listing )
		{
			if( $additional_listing['url_path'] == substr( $wp->matched_rule, 0, strlen( $additional_listing['url_path'] ) ) )
			{
				//found it, require the key
				$old = $query->get( 'meta_query', array() );
				$query->set( 'meta_query', array_merge( $old, array(
					'relation' => 'AND',
					array(
						'key'     => apply_filters( 'invp_prefix_meta_key', $additional_listing['key'] ),
						'compare' => 'EXISTS'
					),
					array(
						'key'     => apply_filters( 'invp_prefix_meta_key', $additional_listing['key'] ),
						'value'   => array( '', 0 ),
						'compare' => 'NOT IN'
					),
				) ) );
				return $query;
			}
		}
		return $query;
	}
}
