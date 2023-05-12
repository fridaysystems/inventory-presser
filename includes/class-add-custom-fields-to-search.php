<?php
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Add_Custom_Fields_To_Search' ) ) {

	/**
	 * Add_Custom_Fields_To_Search
	 *
	 * An object that modifies the WordPress search query to include custom fields
	 *
	 * @see http://adambalee.com/search-wordpress-by-custom-fields-without-a-plugin/
	 *
	 * @since      1.2.1
	 * @package    Inventory_Presser
	 * @subpackage Inventory_Presser/includes
	 * @author     Corey Salzano <corey@friday.systems>, Adam Balee
	 */
	class Add_Custom_Fields_To_Search {


		/**
		 * hooks
		 *
		 * Adds hooks
		 *
		 * @return void
		 */
		public function add_hooks() {
			add_filter( 'posts_distinct', array( $this, 'cf_search_distinct' ) );
			add_filter( 'posts_join', array( $this, 'cf_search_join' ) );
			add_filter( 'posts_where', array( $this, 'cf_search_where' ) );
		}

		/**
		 * is_media_library
		 *
		 * Are we looking at the Media Library?
		 *
		 * @return bool
		 */
		function is_media_library() {
			return 'upload.php' == basename( $_SERVER['REQUEST_URI'], '?' . $_SERVER['QUERY_STRING'] );
		}

		/**
		 * Join posts and postmeta tables
		 *
		 * @see http://codex.wordpress.org/Plugin_API/Filter_Reference/posts_join
		 *
		 * @param  string $join The JOIN clause of the query.
		 * @return string
		 */
		public function cf_search_join( $join ) {
			global $wpdb;

			if ( ! is_search() || $this->is_media_library() ) {
				return $join;
			}

			// join to search post meta values like year, make, model, trim, etc.
			$join .= " LEFT JOIN $wpdb->postmeta searchmeta ON $wpdb->posts.ID = searchmeta.post_id ";

			// these joins are so taxonomy terms like Sport Utility Vehicle => suv are included.
			$join .= "LEFT JOIN $wpdb->term_relationships tr ON $wpdb->posts.ID = tr.object_id "
				. "INNER JOIN $wpdb->term_taxonomy tt ON tt.term_taxonomy_id = tr.term_taxonomy_id "
				. "INNER JOIN $wpdb->terms t ON t.term_id = tt.term_id ";

			return $join;
		}

		/**
		 * cf_search_where
		 *
		 * Modify the search query with posts_where
		 *
		 * @see http://codex.wordpress.org/Plugin_API/Filter_Reference/posts_where
		 *
		 * @param  string $where
		 * @return void
		 */
		function cf_search_where( $where ) {

			if ( ! is_search() || $this->is_media_library() ) {
				return $where;
			}

			global $wpdb;
			$where = preg_replace(
				"/\(\s*$wpdb->posts.post_title\s+LIKE\s*(\'[^\']+\')\s*\)/",
				"($wpdb->posts.post_title LIKE $1) OR ( searchmeta.meta_value LIKE $1) OR (t.name LIKE $1) OR (t.slug LIKE $1)",
				$where
			);

			return $where;
		}

		/**
		 * cf_search_distinct
		 *
		 * Prevent duplicates
		 *
		 * @see http://codex.wordpress.org/Plugin_API/Filter_Reference/posts_distinct
		 *
		 * @param  string $where
		 * @return void
		 */
		function cf_search_distinct( $where ) {
			if ( is_search() ) {
				return 'DISTINCT';
			}

			return $where;
		}
	}
}
