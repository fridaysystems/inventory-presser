<?php
defined( 'ABSPATH' ) || exit;

/**
 * Inventory_Presser_Admin_Posts_List
 *
 * Enhances the list of vehicle posts in the dashboard.
 *
 * @since      14.10.0
 * @package    inventory-presser
 * @subpackage inventory-presser/includes/admin
 * @author     Corey Salzano <corey@friday.systems>
 */
class Inventory_Presser_Admin_Posts_List {

	/**
	 * add_columns_to_vehicles_table
	 *
	 * Adds slugs to the post table columns array so the dashboard list of
	 * vehicles is more informative than the vanilla list for posts.
	 *
	 * @param  array $column
	 * @return array
	 */
	function add_columns_to_vehicles_table( $column ) {
		// add our columns
		$column[ apply_filters( 'invp_prefix_meta_key', 'stock_number' ) ] = __( 'Stock #', 'inventory-presser' );
		$column[ apply_filters( 'invp_prefix_meta_key', 'color' ) ]        = __( 'Color', 'inventory-presser' );
		$column[ apply_filters( 'invp_prefix_meta_key', 'odometer' ) ]     = apply_filters( 'invp_odometer_word', __( 'Odometer', 'inventory-presser' ) );
		$column[ apply_filters( 'invp_prefix_meta_key', 'price' ) ]        = __( 'Price', 'inventory-presser' );
		$column[ apply_filters( 'invp_prefix_meta_key', 'photo_count' ) ]  = __( 'Photos', 'inventory-presser' );
		$column[ apply_filters( 'invp_prefix_meta_key', 'thumbnail' ) ]    = __( 'Thumbnail', 'inventory-presser' );
		// remove the date and tags columns
		unset( $column['date'] );
		unset( $column['tags'] );
		return $column;
	}

	/**
	 * enable_order_by_attachment_count
	 *
	 * Handle the ORDER BY on the vehicle list (edit.php) when sorting by photo
	 * count.
	 *
	 * @param  array    $pieces
	 * @param  WP_Query $query
	 * @return array
	 */
	function enable_order_by_attachment_count( $pieces, $query ) {
		if ( ! is_admin() ) {
			return $pieces;
		}

		/**
		 * We only want our code to run in the main WP query
		 * AND if an orderby query variable is designated.
		 */
		if ( $query->is_main_query() && ( $orderby = $query->get( 'orderby' ) ) ) {
			// Get the order query variable - ASC or DESC
			$order = strtoupper( $query->get( 'order' ) );

			// Make sure the order setting qualifies. If not, set default as ASC
			if ( ! in_array( $order, array( 'ASC', 'DESC' ) ) ) {
				$order = 'ASC';
			}

			if ( apply_filters( 'invp_prefix_meta_key', 'photo_count' ) == $orderby
				|| apply_filters( 'invp_prefix_meta_key', 'thumbnail' ) == $orderby
			) {
				global $wpdb;
				$pieces['orderby'] = "( SELECT COUNT( ID ) FROM {$wpdb->posts} forget WHERE post_parent = {$wpdb->posts}.ID ) $order, " . $pieces['orderby'];
			}
		}
		return $pieces;
	}

	/**
	 * hooks
	 *
	 * Adds hooks
	 *
	 * @return void
	 */
	public function add_hooks() {
		add_filter( 'posts_clauses', array( $this, 'enable_order_by_attachment_count' ), 1, 2 );

		// Add columns to the table that lists all the Vehicles on edit.php
		add_filter( 'manage_' . INVP::POST_TYPE . '_posts_columns', array( $this, 'add_columns_to_vehicles_table' ) );

		// Populate the columns we added to the Vehicles table
		add_action( 'manage_' . INVP::POST_TYPE . '_posts_custom_column', array( $this, 'populate_columns_we_added_to_vehicles_table' ), 10, 2 );

		// Make our added columns to the Vehicles table sortable
		add_filter( 'manage_edit-' . INVP::POST_TYPE . '_sortable_columns', array( $this, 'make_vehicles_table_columns_sortable' ) );

		// Implement the orderby for each of these added columns
		add_filter( 'pre_get_posts', array( $this, 'vehicles_table_columns_orderbys' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'scripts_and_styles' ) );
	}

	public function scripts_and_styles() {
		global $pagenow, $post_type;
		if ( is_admin() && 'edit.php' == $pagenow && INVP::POST_TYPE == $post_type ) {
			$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
			wp_enqueue_style(
				'invp-posts-list',
				plugins_url( "/css/posts-list{$min}.css", INVP_PLUGIN_FILE_PATH ),
				array(),
				INVP_PLUGIN_VERSION
			);
		}
	}

	/**
	 * make_vehicles_table_columns_sortable
	 *
	 * Declares which of our custom columns on the list of posts are sortable.
	 *
	 * @param  array $columns
	 * @return array
	 */
	function make_vehicles_table_columns_sortable( $columns ) {
		$custom = array(
			// meta column id => sortby value used in query
			apply_filters( 'invp_prefix_meta_key', 'color' ) => apply_filters( 'invp_prefix_meta_key', 'color' ),
			apply_filters( 'invp_prefix_meta_key', 'odometer' ) => apply_filters( 'invp_prefix_meta_key', 'odometer' ),
			apply_filters( 'invp_prefix_meta_key', 'price' ) => apply_filters( 'invp_prefix_meta_key', 'price' ),
			apply_filters( 'invp_prefix_meta_key', 'stock_number' ) => apply_filters( 'invp_prefix_meta_key', 'stock_number' ),
			apply_filters( 'invp_prefix_meta_key', 'photo_count' ) => apply_filters( 'invp_prefix_meta_key', 'photo_count' ),
			apply_filters( 'invp_prefix_meta_key', 'thumbnail' ) => apply_filters( 'invp_prefix_meta_key', 'thumbnail' ),
		);
		return wp_parse_args( $custom, $columns );
	}

	/**
	 * populate_columns_we_added_to_vehicles_table
	 *
	 * Populates the custom columns we added to the posts table in the
	 * dashboard.
	 *
	 * @param  string $column_name
	 * @param  int    $post_id
	 * @return void
	 */
	function populate_columns_we_added_to_vehicles_table( $column_name, $post_id ) {
		$custom_fields = get_post_custom( $post_id );
		$val           = ( isset( $custom_fields[ $column_name ] ) ? $custom_fields[ $column_name ][0] : '' );
		switch ( true ) {
			case $column_name == apply_filters( 'invp_prefix_meta_key', 'thumbnail' ):
				echo edit_post_link( get_the_post_thumbnail( $post_id, 'thumbnail' ) );
				break;

			case $column_name == apply_filters( 'invp_prefix_meta_key', 'odometer' ):
				echo invp_get_the_odometer( '', $post_id );
				break;

			case $column_name == apply_filters( 'invp_prefix_meta_key', 'photo_count' ):
				echo invp_get_the_photo_count( $post_id );
				break;

			case $column_name == apply_filters( 'invp_prefix_meta_key', 'price' ):
				echo invp_get_the_price( '-', $post_id );
				break;

			default:
				echo $val;
		}
	}

	/**
	 * vehicles_table_columns_orderbys
	 *
	 * Change the dashboard post query to sort based on a custom column we
	 * added.
	 *
	 * @param  WP_Query $query
	 * @return void
	 */
	function vehicles_table_columns_orderbys( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		$columns = array(
			'color',
			'odometer',
			'price',
			'stock_number',
		);
		$orderby = $query->get( 'orderby' );
		foreach ( $columns as $column ) {
			$meta_key = apply_filters( 'invp_prefix_meta_key', $column );
			if ( $orderby == $meta_key ) {
				$query->set( 'meta_key', $meta_key );
				$query->set( 'orderby', 'meta_value' . ( INVP::meta_value_is_number( $meta_key ) ? '_num' : '' ) );
				return;
			}
		}
	}
}
