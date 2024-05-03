<?php
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Add_Custom_Fields_To_Search' ) ) {

	/**
	 * Add_Custom_Fields_To_Search
	 *
	 * An object that modifies the WordPress search query to include custom fields
	 *
	 * @see https://adambalee.com/search-wordpress-by-custom-fields-without-a-plugin/
	 *
	 * @since      1.2.1
	 * @package    inventory-presser
	 * @subpackage inventory-presser/includes
	 * @author     Corey Salzano <corey@friday.systems>, Adam Balee
	 */
	class Add_Custom_Fields_To_Search {


		/**
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
		 * Are we looking at the Media Library?
		 *
		 * @return bool
		 */
		protected function is_media_library() {
			return 'upload.php' === basename( $_SERVER['REQUEST_URI'], '?' . $_SERVER['QUERY_STRING'] );
		}

		/**
		 * Prevent duplicate posts from showing up in search results.
		 *
		 * @link https://developer.wordpress.org/reference/hooks/posts_distinct/
		 *
		 * @param  string $distinct The part of the query that contains the word
		 * DISTINCT.
		 * @return string
		 */
		public function cf_search_distinct( $distinct ) {
			if ( is_search() ) {
				return 'DISTINCT';
			}

			return $distinct;
		}

		/**
		 * Join posts and postmeta tables
		 *
		 * @see https://codex.wordpress.org/Plugin_API/Filter_Reference/posts_join
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
			return $join;
		}

		/**
		 * Modify the search query with posts_where
		 *
		 * @see https://codex.wordpress.org/Plugin_API/Filter_Reference/posts_where
		 *
		 * @param  string $where
		 * @return string
		 */
		public function cf_search_where( $where ) {

			if ( ! is_search() || $this->is_media_library() ) {
				return $where;
			}

			global $wpdb;
			$where = preg_replace(
				"/\(\s*$wpdb->posts.post_title\s+LIKE\s*(\'[^\']+\')\s*\)/",
				"($wpdb->posts.post_title LIKE $1) OR ( searchmeta.meta_value LIKE $1 ) ",
				$where
			);

			return $where;
		}
	}
}
