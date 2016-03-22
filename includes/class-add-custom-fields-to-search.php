<?php
/**
 * An object that modifies the WordPress search query to include custom fields
 *
 * This code was found at
 * http://adambalee.com/search-wordpress-by-custom-fields-without-a-plugin/
 * and was organized into this object by Corey Salzano.
 *
 * @since      1.2.1
 * @package    Inventory_Presser
 * @subpackage Inventory_Presser/includes
 * @author     Corey Salzano <corey.salzano@gmail.com>, Adam Balee
 */

if ( ! class_exists( 'Add_Custom_Fields_To_Search' ) ) {
	class Add_Custom_Fields_To_Search {

		function __construct() {
			add_filter( 'posts_distinct', array( &$this, 'cf_search_distinct' ) );
			add_filter( 'posts_join', array( &$this, 'cf_search_join' ) );
			add_filter( 'posts_where', array( &$this, 'cf_search_where' ) );
		}

		/**
		 * Join posts and postmeta tables
		 *
		 * http://codex.wordpress.org/Plugin_API/Filter_Reference/posts_join
		 */
		function cf_search_join( $join ) {
		    global $wpdb;

		    if ( is_search() ) {
		        $join .=' LEFT JOIN '.$wpdb->postmeta. ' ON '. $wpdb->posts . '.ID = ' . $wpdb->postmeta . '.post_id ';
		    }

		    return $join;
		}

		/**
		 * Modify the search query with posts_where
		 *
		 * http://codex.wordpress.org/Plugin_API/Filter_Reference/posts_where
		 */
		function cf_search_where( $where ) {
		    global $wpdb;

		    if ( is_search() ) {
		        $where = preg_replace(
		            "/\(\s*".$wpdb->posts.".post_title\s+LIKE\s*(\'[^\']+\')\s*\)/",
		            "(".$wpdb->posts.".post_title LIKE $1) OR (".$wpdb->postmeta.".meta_value LIKE $1)", $where );
		    }

		    return $where;
		}

		/**
		 * Prevent duplicates
		 *
		 * http://codex.wordpress.org/Plugin_API/Filter_Reference/posts_distinct
		 */
		function cf_search_distinct( $where ) {
		    if ( is_search() ) {
		        return "DISTINCT";
		    }

		    return $where;
		}
	}
}
