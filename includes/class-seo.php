<?php
/**
 * A class that implements theme-agnostic search engine optimization via the
 * Yoast SEO plugin, if it is active.
 *
 *
 * @since      3.4.0
 * @package    Inventory_Presser
 * @subpackage Inventory_Presser/includes
 * @author     Corey Salzano <corey@friday.systems>
 */
class Inventory_Presser_SEO {

	//Adds a sitemap directive to robots.txt for a Yoast SEO XML sitemap
	function append_sitemap_to_robots_txt( $robots, $public ) {
    	return $robots . 'Crawl-delay: 10
Sitemap: ' . home_url( '/sitemap_index.xml', 'https' );
	}

	function hooks() {
		if( $this->yoast_sitemap_enabled() ) {
			//Do not include our taxonomies in Yoast SEO XML sitemaps
			add_filter( 'wpseo_sitemap_exclude_taxonomy', array( &$this, 'yoast_sitemap_exclude_taxonomies' ), 10, 2 );
		}

		if( is_multisite() ) {
			add_filter( 'robots_txt', array( &$this, 'append_sitemap_to_robots_txt'), 10, 2 );
		}
	}

	function yoast_sitemap_enabled() {
		//is yoast activated?
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		if( ! is_plugin_active( 'wordpress-seo/wp-seo.php' ) ) {
			return false;
		}

		//is the XML sitemap feature of yoast enabled?
		$yoast_option_name = 'wpseo_xml';
		$yoast_options = get_option( $yoast_option_name );
		return isset( $yoast_options['enablexmlsitemap'] ) && $yoast_options['enablexmlsitemap'];
	}

	//Do not include our taxonomies in Yoast SEO XML sitemaps
	function yoast_sitemap_exclude_taxonomies( $value, $taxonomy ) {
		$invp_taxonomies = new Inventory_Presser_Taxonomies();
		return in_array( $taxonomy, $invp_taxonomies->slugs_array() );
	}
}
