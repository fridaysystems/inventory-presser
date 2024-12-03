<?php
/**
 * Inventory Presser
 *
 * @package inventory-presser
 * @author  Corey Salzano <corey@friday.systems>
 */

defined( 'ABSPATH' ) || exit;

/**
 * Plugin Name: Inventory Presser
 * Plugin URI: https://inventorypresser.com
 * Description: Car listings with photo sliders for automobile and powersports dealerships.
 * Version: 14.17.6
 * Author: Friday Systems
 * Author URI: https://inventorypresser.com
 * Text Domain: inventory-presser
 * Domain Path: /languages
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * GitHub Plugin URI: https://github.com/fridaysystems/inventory-presser
 * Primary Branch: main
 * Download URI: https://downloads.wordpress.org/plugin/inventory-presser.14.17.6.zip
 * Download URI: https://inventorypresser.com/wp-content/uploads/inventory-presser-v14.17.6.zip
 */

if ( ! defined( 'INVP_PLUGIN_BASE' ) ) {
	define( 'INVP_PLUGIN_BASE', plugin_basename( __FILE__ ) );
}
if ( ! defined( 'INVP_PLUGIN_FILE_PATH' ) ) {
	define( 'INVP_PLUGIN_FILE_PATH', __FILE__ );
}
if ( ! defined( 'INVP_PLUGIN_VERSION' ) ) {
	define( 'INVP_PLUGIN_VERSION', '14.17.6' );
}

if ( ! class_exists( 'Inventory_Presser_Plugin' ) ) {
	/**
	 * Inventory_Presser_Plugin
	 *
	 * This class includes dependencies, adds hooks, adds rewrite rules, modifies
	 * queries, and registers scripts & styles.
	 */
	class Inventory_Presser_Plugin {

		/**
		 * Filter callback that adds an ORDER BY clause to the main query when a
		 * user requests a list of vehicles.
		 *
		 * @param  object $query An instance of the WP_Query class.
		 * @return void
		 */
		public function add_orderby_to_query( $query ) {
			// Do not mess with the query if it's not the main one and our CPT.
			if ( apply_filters( 'invp_apply_orderby_to_main_query_only', true ) && ! $query->is_main_query() ) {
				return;
			}

			if ( ! is_post_type_archive( INVP::POST_TYPE ) ) {
				return;
			}

			add_filter( 'posts_clauses', array( $this, 'modify_query_orderby' ) );
			$settings = INVP::settings();

			if ( empty( $_GET['orderby'] ) && empty( $settings['sort_vehicles_by'] ) ) {
				return;
			}

			/**
			 * The field we want to order by is either in $_GET['orderby'] when
			 * the user has chosen to reorder posts or saved in the plugin
			 * settings 'default-sort-key.' The sort direction is in
			 * $_GET['order'] or 'sort_vehicles_order.'
			 */
			$direction = $settings['sort_vehicles_order'];
			if ( isset( $_GET['order'] ) ) {
				$direction = sanitize_text_field( wp_unslash( $_GET['order'] ) );
			}

			$key = $settings['sort_vehicles_by'];

			// Backwards compatibility for pre 13.7.1 when there was a bug.
			if ( 'date_entered' === $key ) {
				$key = 'post_date';
			}
			if ( 'last_modified' === $key ) {
				$key = 'post_modified';
			}

			if ( isset( $_GET['orderby'] ) ) {
				$key = sanitize_text_field( wp_unslash( $_GET['orderby'] ) );
			}

			// post_date and post_modified are not meta keys.
			if ( in_array( $key, array( 'post_date', 'post_modified' ), true ) ) {
				$query->set( 'orderby', $key );
				$query->set( 'order', $direction );
				return;
			}

			// Make sure the meta key has the prefix.
			$key = apply_filters( 'invp_prefix_meta_key', $key );
			$query->set( 'meta_key', $key );

			/**
			 * Maybe append to the meta_query if it is already set. If we are
			 * sorting by make, then we want to also add a secondary sort of model
			 * and a tertiary sort of trim. That's what users want. Apply the same
			 * logic to sorts by year and model.
			 */
			$old = $query->get( 'meta_query', array() );
			switch ( apply_filters( 'invp_unprefix_meta_key', $query->get( 'meta_key' ) ) ) {
				case 'make':
					$query->set(
						'meta_query',
						array_merge(
							$old,
							array(
								'relation' => 'AND',
								array(
									'relation' => 'OR',
									array(
										'key'     => apply_filters( 'invp_prefix_meta_key', 'model' ),
										'compare' => 'NOT EXISTS',
									),
									array(
										'key'     => apply_filters( 'invp_prefix_meta_key', 'model' ),
										'compare' => 'EXISTS',
									),
								),
								array(
									'relation' => 'OR',
									array(
										'key'     => apply_filters( 'invp_prefix_meta_key', 'trim' ),
										'compare' => 'NOT EXISTS',
									),
									array(
										'key'     => apply_filters( 'invp_prefix_meta_key', 'trim' ),
										'compare' => 'EXISTS',
									),
								),
							)
						)
					);
					break;

				case 'model':
					$query->set(
						'meta_query',
						array_merge(
							$old,
							array(
								'relation' => 'AND',
								array(
									'relation' => 'OR',
									array(
										'key'     => apply_filters( 'invp_prefix_meta_key', 'model' ),
										'compare' => 'NOT EXISTS',
									),
									array(
										'key'     => apply_filters( 'invp_prefix_meta_key', 'model' ),
										'compare' => 'EXISTS',
									),
								),
								array(
									'relation' => 'OR',
									array(
										'key'     => apply_filters( 'invp_prefix_meta_key', 'trim' ),
										'compare' => 'NOT EXISTS',
									),
									array(
										'key'     => apply_filters( 'invp_prefix_meta_key', 'trim' ),
										'compare' => 'EXISTS',
									),
								),
							)
						)
					);
					break;

				case 'year':
					$query->set(
						'meta_query',
						array_merge(
							$old,
							array(
								'relation' => 'AND',
								array(
									'relation' => 'OR',
									array(
										'key'     => apply_filters( 'invp_prefix_meta_key', 'year' ),
										'compare' => 'NOT EXISTS',
									),
									array(
										'key'     => apply_filters( 'invp_prefix_meta_key', 'year' ),
										'compare' => 'EXISTS',
									),
								),
								array(
									'relation' => 'OR',
									array(
										'key'     => apply_filters( 'invp_prefix_meta_key', 'make' ),
										'compare' => 'NOT EXISTS',
									),
									array(
										'key'     => apply_filters( 'invp_prefix_meta_key', 'make' ),
										'compare' => 'EXISTS',
									),
								),
								array(
									'relation' => 'OR',
									array(
										'key'     => apply_filters( 'invp_prefix_meta_key', 'model' ),
										'compare' => 'NOT EXISTS',
									),
									array(
										'key'     => apply_filters( 'invp_prefix_meta_key', 'model' ),
										'compare' => 'EXISTS',
									),
								),
								array(
									'relation' => 'OR',
									array(
										'key'     => apply_filters( 'invp_prefix_meta_key', 'trim' ),
										'compare' => 'NOT EXISTS',
									),
									array(
										'key'     => apply_filters( 'invp_prefix_meta_key', 'trim' ),
										'compare' => 'EXISTS',
									),
								),
							)
						)
					);
					break;

				// Boat fields might not exist on all vehicles. Do not require them.
				case 'beam':
				case 'condition_boat':
				case 'draft':
				case 'engine_count':
				case 'engine_make':
				case 'engine_model':
				case 'horsepower':
				case 'hull_material':
				case 'length':
					$query->set( 'meta_key', '' );
					$query->set(
						'meta_query',
						array_merge(
							$old,
							array(
								'relation' => 'OR',
								array(
									'key'     => $key,
									'compare' => 'NOT EXISTS',
								),
								array(
									'key'     => $key,
									'compare' => 'EXISTS',
								),
							)
						)
					);
					break;
			}

			$meta_value_or_meta_value_num = 'meta_value';
			$key_is_odometer              = apply_filters( 'invp_prefix_meta_key', 'odometer' ) === $key;

			if ( INVP::meta_value_is_number( $key ) || $key_is_odometer ) {
				$meta_value_or_meta_value_num .= '_num';
			}

			// Customize the ORDER BY to remove non-digits from the odometer.
			if ( $key_is_odometer ) {
				add_filter( 'posts_orderby', array( $this, 'change_order_by_for_odometer' ), 10, 2 );
			}

			// Allow other developers to decide if the post meta values are numbers.
			$query->set( 'orderby', apply_filters( 'invp_meta_value_or_meta_value_num', $meta_value_or_meta_value_num, $key ) );
			$query->set( 'order', $direction );
		}

		/**
		 * Removes commas from the meta value used in the ORDER BY of the query so
		 * that odometer values can be sorted as numbers instead of strings.
		 *
		 * @param  string $orderby The ORDER BY clause of a database query.
		 * @param  object $query   An instance of the WP_Query class.
		 * @return string The changed ORDER BY clause
		 */
		public function change_order_by_for_odometer( $orderby, $query ) {
			/**
			 * Changes
			 * ORDER BY {$wpdb->postmeta}.meta_value+0
			 * to
			 * ORDER BY REPLACE( {$wpdb->postmeta}.meta_value, ',', '' )+0
			 */
			global $wpdb;
			return str_replace( "{$wpdb->postmeta}.meta_value+0", "REPLACE( {$wpdb->postmeta}.meta_value, ',', '' )+0", $orderby );
		}

		/**
		 * Action hook callback that adds rewrite rules to the global $wp_rewrite.
		 * These rewrite rules are what power URLs like /inventory/make/subaru.
		 *
		 * @return void
		 */
		public function add_pretty_search_urls() {
			global $wp_rewrite;
			$wp_rewrite->rules = $this->generate_rewrite_rules( INVP::POST_TYPE ) + $wp_rewrite->rules;
		}

		/**
		 * Change links to terms in our taxonomies to include /inventory before
		 * /tax/term.
		 *
		 * @param  string $termlink URL to modify.
		 * @param  object $term     An instance of the WP_Term class.
		 * @return string A modified term link that has our post type slug prepended.
		 */
		public function change_term_links( $termlink, $term ) {
			// Exit early, this method runs often in the dashboard.
			if ( false !== $termlink && '/category' === substr( $termlink, 0, 9 ) ) {
				return $termlink;
			}
			$taxonomy = get_taxonomy( strtolower( $term->taxonomy ) );
			if ( ! empty( $taxonomy->object_type ) && ! in_array( INVP::POST_TYPE, $taxonomy->object_type, true ) ) {
				return $termlink;
			}

			$post_type = get_post_type_object( INVP::POST_TYPE );
			if ( empty( $post_type ) ) {
				return $termlink;
			}

			$termlink = $post_type->rewrite['slug'] . $termlink;
			return $termlink;
		}

		/**
		 * Registers the post type that holds vehicles.
		 *
		 * @return void
		 */
		public static function create_post_type() {
			// creates a custom post type that will be used by this plugin.
			register_post_type(
				INVP::POST_TYPE,
				apply_filters(
					'invp_post_type_args',
					array(
						'description'   => __( 'Vehicles for sale', 'inventory-presser' ),
						'has_archive'   => true,
						'hierarchical'  => false,
						'labels'        => array(
							'name'                  => _x( 'Vehicles', 'Post type general name', 'inventory-presser' ),
							'singular_name'         => _x( 'Vehicle', 'Post type singular name', 'inventory-presser' ),
							'menu_name'             => _x( 'Vehicles', 'Admin Menu text', 'inventory-presser' ),
							'name_admin_bar'        => _x( 'Vehicle', 'Add New on Toolbar', 'inventory-presser' ),
							'add_new'               => __( 'Add New', 'inventory-presser' ),
							'add_new_item'          => __( 'Add New Vehicle', 'inventory-presser' ),
							'new_item'              => __( 'New Vehicle', 'inventory-presser' ),
							'edit_item'             => __( 'Edit Vehicle', 'inventory-presser' ),
							'view_item'             => __( 'View Vehicle', 'inventory-presser' ),
							'all_items'             => __( 'All Vehicles', 'inventory-presser' ),
							'search_items'          => __( 'Search Vehicles', 'inventory-presser' ),
							'parent_item_colon'     => __( 'Parent Vehicles:', 'inventory-presser' ),
							'not_found'             => __( 'No vehicles found.', 'inventory-presser' ),
							'not_found_in_trash'    => __( 'No vehicles found in Trash.', 'inventory-presser' ),
							'archives'              => _x( 'Inventory', 'The post type archive label used in nav menus. Default “Post Archives”. Added in 4.4', 'inventory-presser' ),
							'insert_into_item'      => _x( 'Insert into vehicle description', 'Overrides the “Insert into post”/”Insert into page” phrase (used when inserting media into a post). Added in 4.4', 'inventory-presser' ),
							'uploaded_to_this_item' => _x( 'Uploaded to this vehicle', 'Overrides the “Uploaded to this post”/”Uploaded to this page” phrase (used when viewing media attached to a post). Added in 4.4', 'inventory-presser' ),
							'filter_items_list'     => _x( 'Filter vehicles list', 'Screen reader text for the filter links heading on the post type listing screen. Default “Filter posts list”/”Filter pages list”. Added in 4.4', 'inventory-presser' ),
							'items_list_navigation' => _x( 'Vehicles list navigation', 'Screen reader text for the pagination heading on the post type listing screen. Default “Posts list navigation”/”Pages list navigation”. Added in 4.4', 'inventory-presser' ),
							'items_list'            => _x( 'Vehicles list', 'Screen reader text for the items list heading on the post type listing screen. Default “Posts list”/”Pages list”. Added in 4.4', 'inventory-presser' ),
						),
						'menu_icon'     => 'dashicons-admin-network',
						'menu_position' => 5, // below Posts.
						'public'        => true,
						'rest_base'     => 'inventory',
						'rewrite'       => array(
							'slug'       => 'inventory',
							'with_front' => false,
						),
						'show_in_rest'  => true,
						'supports'      => array(
							'custom-fields',
							'editor',
							'title',
							'thumbnail',
						),
						'taxonomies'    => Inventory_Presser_Taxonomies::query_vars_array(),
					)
				)
			);
		}

		/**
		 * Filter callback that sets the number of meta keys in the Custom
		 * Fields panel in the editor. Typcially, this value is 30. We register
		 * more than 30 fields!
		 *
		 * @param  int $limit The number of meta keys in the Custom Fields panel in the editor.
		 * @return int
		 */
		public function custom_fields_key_limit( $limit ) {
			if ( INVP::POST_TYPE !== get_post_type() ) {
				return $limit;
			}
			return 9999;
		}

		/**
		 * Deletes the rewrite_rules option so the rewrite rules are generated
		 * on the next page load without ours. Called during deactivation.
		 *
		 * @see https://wordpress.stackexchange.com/a/44337/13090
		 *
		 * @param  bool $network_wide True if this plugin is being Network Activated or Network Deactivated by the multisite admin.
		 * @return void
		 */
		public static function delete_rewrite_rules_option( $network_wide ) {
			if ( ! is_multisite() || ! $network_wide ) {
				delete_option( 'rewrite_rules' );
				return;
			}

			$sites = get_sites(
				array(
					'network' => 1,
					'limit'   => apply_filters( 'invp_query_limit', 1000, __METHOD__ ),
				)
			);
			foreach ( $sites as $site ) {
				switch_to_blog( $site->blog_id );
				delete_option( 'rewrite_rules' );
				restore_current_blog();
			}
		}

		/**
		 * Changes the attachment post type args just before the type is registered.
		 * Changes hierarchical to true so that `parent` is exposed in the REST API.
		 *
		 * @param  array  $args Array of arguments for registering a post type. See the register_post_type() function for accepted arguments.
		 * @param  string $type Post type key.
		 * @return array
		 */
		public function edit_attachment_post_type( $args, $type ) {
			if ( 'attachment' !== $type ) {
				return $args;
			}
			$args['hierarchical'] = true;
			return $args;
		}

		/**
		 * Flushes rewrite rules.
		 *
		 * @param  boolean $network_wide True if this plugin is being Network Activated or Network Deactivated by the multisite admin.
		 * @return void
		 */
		public static function flush_rewrite( $network_wide ) {
			self::create_post_type();

			if ( ! is_multisite() || ! $network_wide ) {
				flush_rewrite_rules();
				return;
			}

			$sites = get_sites(
				array(
					'network' => 1,
					'limit'   => apply_filters( 'invp_query_limit', 1000, __METHOD__ ),
				)
			);
			foreach ( $sites as $site ) {
				switch_to_blog( $site->blog_id );
				global $wp_rewrite;
				$wp_rewrite->init(); // important...
				$wp_rewrite->flush_rules();
				restore_current_blog();
			}
		}

		/**
		 * Generate every possible combination of rewrite rules, including paging, based on post type taxonomy
		 *
		 * @see https://thereforei.am/2011/10/28/advanced-taxonomy-queries-with-pretty-urls/
		 *
		 * @param  string $post_type The name of a post type.
		 * @param  array  $query_vars An array of query variables.
		 * @return array
		 */
		protected function generate_rewrite_rules( $post_type, $query_vars = array() ) {
			global $wp_rewrite;

			if ( ! is_object( $post_type ) ) {
				$post_type = get_post_type_object( $post_type );
			}

			$rewrite_slugs = apply_filters(
				'invp_rewrite_slugs',
				array(
					$post_type->rewrite['slug'],
				)
			);

			$taxonomies        = get_object_taxonomies( $post_type->name, 'objects' );
			$new_rewrite_rules = array();

			// Add taxonomy filters to the query vars array.
			foreach ( $taxonomies as $taxonomy ) {
				$query_vars[] = $taxonomy->query_var;
			}

			// Loop over all the possible combinations of the query vars.
			$query_vars_count = count( $query_vars );
			for ( $i = 1; $i <= $query_vars_count;  $i++ ) {
				foreach ( $rewrite_slugs as $rewrite_slug ) {
					$new_rewrite_rule = $rewrite_slug . '/';
					$new_query_string = 'index.php?post_type=' . $post_type->name;

					// Prepend the rewrites & queries.
					for ( $n = 1; $n <= $i; $n++ ) {
						$new_rewrite_rule .= '(' . implode( '|', $query_vars ) . ')/([^\/]+?)/';
						$new_query_string .= '&' . $wp_rewrite->preg_index( $n * 2 - 1 ) . '[]=' . $wp_rewrite->preg_index( $n * 2 );
					}

					// Allow paging of filtered post type - WordPress expects 'page' in the URL but uses 'paged' in the query string so paging doesn't fit into our regex.
					$new_paged_rewrite_rule = $new_rewrite_rule . 'page/([0-9]{1,})/';
					$new_paged_query_string = $new_query_string . '&paged=' . $wp_rewrite->preg_index( $i * 2 + 1 );

					// Make the trailing backslash optional.
					$new_paged_rewrite_rule = $new_paged_rewrite_rule . '?$';
					$new_rewrite_rule       = $new_rewrite_rule . '?$';

					// Add the new rewrites.
					$new_rewrite_rules[ $new_paged_rewrite_rule ] = $new_paged_query_string;
					$new_rewrite_rules[ $new_rewrite_rule ]       = $new_query_string;
				}
			}
			return apply_filters( 'invp_rewrite_rules', $new_rewrite_rules );
		}

		/**
		 * Given a string, return the last word.
		 *
		 * @param  string $str The string from which to extract the last word.
		 * @return string The last word of the input string
		 */
		private function get_last_word( $str ) {
			$pieces = explode( ' ', rtrim( $str ) );
			return array_pop( $pieces );
		}

		/**
		 * Provides default values for the plugin settings stored in an option
		 * with name INVP::OPTION_NAME.
		 *
		 * @return void
		 */
		public static function set_default_settings() {
			$settings = INVP::settings();
			// Do not overwrite taxonomies setting if it exists.
			if ( ! empty( $settings['taxonomies'] ) ) {
				return;
			}

			// If the old show_all_taxonomies is set, maintain that behavior.
			$show_menu = false;
			if ( isset( $settings['show_all_taxonomies'] ) ) {
				$show_menu = $settings['show_all_taxonomies'];
				unset( $settings['show_all_taxonomies'] );
			}
			if ( ! isset( $settings['taxonomies'] ) ) {
				$settings['taxonomies'] = Inventory_Presser_Admin_Options::taxonomies_setting_default( $settings );
			}
			update_option( INVP::OPTION_NAME, $settings );
		}

		/**
		 * This is the driver function of the entire plugin. Includes dependencies
		 * and adds all hooks.
		 *
		 * @return void
		 */
		public function add_hooks() {
			// Only do these things once.
			if ( has_filter( 'invp_prefix_meta_key', array( 'INVP', 'translate_custom_field_names' ) ) ) {
				return;
			}

			// include all this plugin's classes that live in external files.
			$this->include_dependencies();

			// Translate friendly names to actual custom field keys and the other way.
			add_filter( 'invp_prefix_meta_key', array( 'INVP', 'translate_custom_field_names' ) );
			add_filter( 'invp_unprefix_meta_key', array( 'INVP', 'untranslate_custom_field_names' ) );

			/**
			 * Create our post type and taxonomies
			 */

			// create a custom post type for the vehicles.
			add_action( 'init', array( $this, 'create_post_type' ) );

			// register all postmeta fields the CPT uses.
			add_action( 'init', array( $this, 'register_meta_fields' ), 20 );

			// Filter the attachment post type to make sure `parent` is exposed in the REST API.
			add_filter( 'register_post_type_args', array( $this, 'edit_attachment_post_type' ), 10, 2 );

			/**
			 * Make sure our own classes exist before creating instances in case
			 * a page load happens during a plugin update when some of these
			 * files might actually not exist.
			 */

			// Create custom taxonomies.
			if ( class_exists( 'Inventory_Presser_Taxonomies' ) ) {
				$taxonomies = new Inventory_Presser_Taxonomies();
				$taxonomies->add_hooks();
			}

			// Modify edit-tags.php for our location taxonomy to manage term meta.
			if ( class_exists( 'Inventory_Presser_Admin_Location_Meta' ) ) {
				$location_meta = new Inventory_Presser_Admin_Location_Meta();
				$location_meta->add_hooks();
			}

			/**
			 * Some custom rewrite rules are created and destroyed
			 */

			// Add custom rewrite rules.
			add_action( 'generate_rewrite_rules', array( $this, 'add_pretty_search_urls' ) );

			/**
			 * Activation and deactivation hooks ensure that the rewrite rules are
			 * flushed to add and remove our custom rewrite rules
			 */

			// Flush rewrite rules when the plugin is activated.
			register_activation_hook( INVP_PLUGIN_FILE_PATH, array( __CLASS__, 'flush_rewrite' ) );

			// Set default settings values.
			register_activation_hook( INVP_PLUGIN_FILE_PATH, array( __CLASS__, 'set_default_settings' ) );

			// Delete an option during deactivation.
			register_deactivation_hook( INVP_PLUGIN_FILE_PATH, array( __CLASS__, 'delete_rewrite_rules_option' ) );

			// Register some widgets included with this plugin.
			add_action( 'widgets_init', array( $this, 'register_widgets' ) );

			/**
			 * Deliver our promise to order posts, change the ORDER BY clause of
			 * the query that's fetching post objects.
			 */
			if ( ! is_admin() ) {
				add_action( 'pre_get_posts', array( $this, 'add_orderby_to_query' ) );
				add_action( 'pre_get_posts', array( $this, 'modify_query_for_max_price' ), 99, 1 );
			}

			// Register scripts and styles.
			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'include_scripts_and_styles' ) );
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'include_scripts_and_styles' ) );
			add_action( 'enqueue_block_assets', array( __CLASS__, 'include_scripts_and_styles' ) );

			// Allow custom fields to be searched.
			if ( class_exists( 'Add_Custom_Fields_To_Search' ) ) {
				$add_custom_fields_to_search = new Add_Custom_Fields_To_Search();
				$add_custom_fields_to_search->add_hooks();
			}

			// Redirect URLs by VINs to proper vehicle permalinks.
			if ( class_exists( 'Vehicle_URLs_By_VIN' ) ) {
				$allow_urls_by_vin = new Vehicle_URLs_By_VIN();
				$allow_urls_by_vin->add_hooks();
			}

			// Add buttons near vehicles for Carfax reports or NextGear inspections.
			if ( class_exists( 'Inventory_Presser_Badges' ) ) {
				$badges = new Inventory_Presser_Badges();
				$badges->add_hooks();
			}

			// Redirect 404 vehicles to make archives.
			if ( class_exists( 'Redirect_404_Vehicles' ) ) {
				$redirect_404_vehicles = new Redirect_404_Vehicles();
				$redirect_404_vehicles->add_hooks();
			}

			// Modify the URL of an "Email a Friend" menu item on the "Vehicle Details Buttons" menu.
			if ( class_exists( 'Inventory_Presser_Email_A_Friend' ) ) {
				$email_a_friend = new Inventory_Presser_Email_A_Friend();
				$email_a_friend->add_hooks();
			}

			// Make it possible for a menu item to print the page.
			if ( class_exists( 'Inventory_Presser_Menu_Item_Print' ) ) {
				$print_button = new Inventory_Presser_Menu_Item_Print();
				$print_button->add_hooks();
			}

			/**
			 * When vehicle posts are inserted, make sure they create a relationship
			 * with the "For Sale" term in the Availabilities taxonomy. Some queries
			 * that honor the "Include Sold Vehicles" setting in this plugin will
			 * exclude them without a relationship to a term in that taxonomy.
			 */
			if ( class_exists( 'INVP' ) ) {
				add_action( 'save_post_' . INVP::POST_TYPE, array( $this, 'mark_vehicles_for_sale_during_insertion' ), 10, 3 );
				// Save custom post meta and term relationships when posts are saved.
				add_action( 'save_post_' . INVP::POST_TYPE, array( $this, 'save_vehicle_post_meta' ), 10, 3 );
				add_action( 'save_post_' . INVP::POST_TYPE, array( $this, 'save_vehicle_taxonomy_terms' ), 10, 2 );
			}

			// Maybe skip the trash bin and permanently delete vehicles & photos.
			add_action( 'trashed_post', array( $this, 'maybe_force_delete' ) );

			// When vehicles are deleted, delete their attachments, too.
			add_action( 'before_delete_post', array( 'INVP', 'delete_attachments' ), 10, 1 );

			// Change messages in the dashboard when updating vehicles.
			add_filter( 'post_updated_messages', array( $this, 'change_post_updated_messages' ) );

			// Change links to our taxonomy terms to insert /inventory/.
			add_filter( 'pre_term_link', array( $this, 'change_term_links' ), 10, 2 );

			// Allow users to set the Inventory listing page as the home page.
			if ( class_exists( 'Inventory_Presser_Allow_Inventory_As_Home_Page' ) ) {
				$page = new Inventory_Presser_Allow_Inventory_As_Home_Page();
				$page->add_hooks();
			}

			// Add all our shortcodes.
			if ( class_exists( 'Inventory_Presser_Shortcode_Grid' ) ) {
				$shortcodes = new Inventory_Presser_Shortcode_Grid();
				$shortcodes->add_hooks();
			}
			if ( class_exists( 'Inventory_Presser_Shortcode_Iframe' ) ) {
				$shortcodes = new Inventory_Presser_Shortcode_Iframe();
				$shortcodes->add_hooks();
			}
			if ( class_exists( 'Inventory_Presser_Shortcode_Slider' ) ) {
				$shortcodes = new Inventory_Presser_Shortcode_Slider();
				$shortcodes->add_hooks();
			}
			if ( class_exists( 'Inventory_Presser_Shortcode_Single_Vehicle' ) ) {
				$shortcodes = new Inventory_Presser_Shortcode_Single_Vehicle();
				$shortcodes->add_hooks();
			}
			if ( class_exists( 'Inventory_Presser_Shortcode_Archive' ) ) {
				$shortcodes = new Inventory_Presser_Shortcode_Archive();
				$shortcodes->add_hooks();
			}
			if ( class_exists( 'Inventory_Presser_Shortcode_Archive_Vehicle' ) ) {
				$shortcodes = new Inventory_Presser_Shortcode_Archive_Vehicle();
				$shortcodes->add_hooks();
			}
			if ( class_exists( 'Inventory_Presser_Shortcode_Attribute_Table' ) ) {
				$shortcodes = new Inventory_Presser_Shortcode_Attribute_Table();
				$shortcodes->add_hooks();
			}
			if ( class_exists( 'Inventory_Presser_Shortcode_Hours_Today' ) ) {
				$shortcodes = new Inventory_Presser_Shortcode_Hours_Today();
				$shortcodes->add_hooks();
			}
			if ( class_exists( 'Inventory_Presser_Shortcode_Photo_Slider' ) ) {
				$shortcodes = new Inventory_Presser_Shortcode_Photo_Slider();
				$shortcodes->add_hooks();
			}
			if ( class_exists( 'Inventory_Presser_Shortcode_Vin' ) ) {
				$shortcodes = new Inventory_Presser_Shortcode_Vin();
				$shortcodes->add_hooks();
			}
			if ( class_exists( 'Inventory_Presser_Shortcode_Sort_By' ) ) {
				$shortcodes = new Inventory_Presser_Shortcode_Sort_By();
				$shortcodes->add_hooks();
			}

			/**
			 * When the active theme isn't prepared to display vehicles, insert
			 * our archive and single vehicle shortcodes.
			 */
			if ( class_exists( 'Inventory_Presser_Template_Provider' ) ) {
				$template_provider = new Inventory_Presser_Template_Provider();
				$template_provider->add_hooks();
			}

			// Add blocks.
			if ( class_exists( 'Inventory_Presser_Blocks' ) ) {
				$blocks = new Inventory_Presser_Blocks();
				$blocks->add_hooks();
			}

			/**
			 * Add photo number meta values to vehicle photos uploaded in the
			 * dashboard
			 */
			if ( class_exists( 'Inventory_Presser_Photo_Numberer' ) ) {
				$photo_numberer = new Inventory_Presser_Photo_Numberer();
				$photo_numberer->add_hooks();
			}

			// Allow additional vehicle archives to be created.
			if ( class_exists( 'Inventory_Presser_Additional_Listings_Pages' ) ) {
				$additional_archives = new Inventory_Presser_Additional_Listings_Pages();
				$additional_archives->add_hooks();
			}

			// Add menus to the admin bar.
			if ( class_exists( 'Inventory_Presser_Admin_Bar' ) ) {
				$bar = new Inventory_Presser_Admin_Bar();
				$bar->add_hooks();
			}

			if ( is_admin() ) {
				// Initialize our Settings page in the Dashboard.
				if ( class_exists( 'Inventory_Presser_Admin_Options' ) ) {
					$options = new Inventory_Presser_Admin_Options();
					$options->add_hooks();
				}

				// Add columns to the vehicle posts list.
				if ( class_exists( 'Inventory_Presser_Admin_Posts_List' ) ) {
					$posts_list = new Inventory_Presser_Admin_Posts_List();
					$posts_list->add_hooks();
				}

				// Add a sidebar to the editor when editing vehicles.
				if ( class_exists( 'Inventory_Presser_Admin_Editor_Sidebar' ) ) {
					$sidebar = new Inventory_Presser_Admin_Editor_Sidebar();
					$sidebar->add_hooks();
				}

				// Allow more than 30 meta keys in the Custom Fields editor panel.
				add_filter( 'postmeta_form_limit', array( $this, 'custom_fields_key_limit' ) );
			}

			if ( class_exists( 'Inventory_Presser_Taxonomy_Overlapper' ) ) {
				$overlapper = new Inventory_Presser_Taxonomy_Overlapper();
				$overlapper->add_hooks();
			}

			if ( class_exists( 'Inventory_Presser_Schema_Org_Generator' ) ) {
				$schema_generator = new Inventory_Presser_Schema_Org_Generator();
				$schema_generator->add_hooks();
			}

			// Adds the "View Details" button to each vehicle in archive loops.
			add_action( 'invp_archive_buttons', array( $this, 'add_view_details_button' ) );

			// Embeds a contact form on vehicle singles if one is chosen at Vehicles → Options.
			add_action( 'invp_single_sections', array( $this, 'single_sections_add_form' ) );

			add_action( 'plugins_loaded', array( $this, 'loaded' ) );

			if ( class_exists( 'Inventory_Presser_REST' ) ) {
				$rest = new Inventory_Presser_REST();
				$rest->add_hooks();
			}

			if ( class_exists( 'Inventory_Presser_Admin_Photo_Arranger' ) ) {
				$photo_arranger = new Inventory_Presser_Admin_Photo_Arranger();
				$photo_arranger->add_hooks();
			}

			// Change archive page titles.
			add_filter( 'document_title_parts', array( $this, 'change_archive_title_tags' ) );

			// Add a link to the Settings page on the plugin management page.
			add_filter( 'plugin_action_links_' . INVP_PLUGIN_BASE, array( $this, 'insert_settings_link' ), 2, 2 );

			// Add tests to the Site Health Status page.
			if ( class_exists( 'Inventory_Presser_Site_Health' ) ) {
				$health = new Inventory_Presser_Site_Health();
				$health->add_hooks();
			}

			add_action( 'plugins_loaded', array( $this, 'load_integrations' ) );
		}

		/**
		 * Outputs a View Details link that takes users to a single vehicle page.
		 *
		 * @return void
		 */
		public function add_view_details_button() {
			if ( ! in_the_loop() ) {
				return;
			}

			$css_classes = apply_filters(
				'invp_css_classes_view_details_button',
				array(
					'wp-block-button__link',
					'button',
				)
			);
			?><a class="<?php echo esc_attr( implode( ' ', $css_classes ) ); ?>" href="<?php the_permalink(); ?>" title="<?php the_title(); ?>"><?php esc_html_e( 'View Details', 'inventory-presser' ); ?></a>
			<?php
		}

		/**
		 * Changes the <title> tag on inventory archives.
		 *
		 * @param  array $title_parts An array of strings.
		 * @return array
		 */
		public function change_archive_title_tags( $title_parts ) {

			if ( ! is_post_type_archive( INVP::POST_TYPE ) ) {
				return $title_parts;
			}

			$title_parts['title'] = '';

			// Is it a make search?
			$query_var = get_query_var( 'make' );
			if ( ! empty( $query_var ) ) {
				$term = get_term_by( 'slug', $query_var[0], 'make' );
				if ( is_object( $term ) && 'WP_Term' === get_class( $term ) ) {
					$title_parts['title'] .= $term->name . ' ';
				}
			}

			// Is it a type search?
			$query_var = get_query_var( 'type' );
			if ( ! empty( $query_var ) ) {
				$term = get_term_by( 'slug', $query_var[0], 'type' );
				if ( is_object( $term ) && 'WP_Term' === get_class( $term ) ) {
					$title_parts['title'] .= $term->name . ' ';
				}
			} else {
				$title_parts['title'] .= __( 'Vehicles', 'inventory-presser' ) . ' ';
			}

			$title_parts['title'] .= __( 'For Sale', 'inventory_presser' );

			return $title_parts;
		}

		/**
		 * Changes the messages shown to users in the editor when changes are made
		 * to the post object.
		 *
		 * @param  array $msgs
		 * @return array
		 */
		public function change_post_updated_messages( $msgs ) {
			global $post;

			$view_link = sprintf(
				'<a href="%1$s">%2$s</a>',
				esc_url( get_permalink( $post->ID ) ),
				__( 'View vehicle', 'inventory-presser' )
			);

			$preview_link = sprintf(
				'<a target="_blank" href="%1$s">%2$s</a>',
				esc_url( get_preview_post_link( $post->ID ) ),
				__( 'Preview vehicle', 'inventory-presser' )
			);

			$scheduled_date = date_i18n( __( 'M j, Y @ H:i', 'inventory-presser' ), strtotime( $post->post_date ) );

			$msgs[ INVP::POST_TYPE ] = array(
				0  => '',
				1  => __( 'Vehicle updated. ', 'inventory-presser' ) . $view_link,
				2  => __( 'Custom field updated.', 'inventory-presser' ),
				3  => __( 'Custom field updated.', 'inventory-presser' ),
				4  => __( 'Vehicle updated.', 'inventory-presser' ),
				/* translators: 1. Formatted date timestamp of a post revision. */
				5  => isset( $_GET['revision'] ) ? sprintf( __( 'Vehicle restored to revision from %s.', 'inventory-presser' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
				6  => __( 'Vehicle published. ', 'inventory-presser' ) . $view_link,
				7  => __( 'Vehicle saved.', 'inventory-presser' ),
				8  => __( 'Vehicle submitted. ', 'inventory-presser' ) . $preview_link,
				/* translators: 1. A date. */
				9  => sprintf( __( 'Vehicle scheduled to list on <strong>%s</strong>. ', 'inventory-presser' ), $scheduled_date ) . $preview_link,
				10 => __( 'Vehicle draft updated. ', 'inventory-presser' ) . $preview_link,
			);
			return $msgs;
		}

		/**
		 * Fires on the plugins_loaded hook. Runs the invp_loaded action hook for
		 * all add-ons.
		 *
		 * @return void
		 */
		public function loaded() {
			// Fire an action hook after Inventory Presser is finished loading.
			do_action( 'invp_loaded' );
		}

		/**
		 * Add hooks that power our integrations with other plugins.
		 *
		 * @return void
		 */
		public function load_integrations() {
			static $active_plugins = array();
			if ( empty( $active_plugins ) ) {
				$active_plugins = apply_filters( 'active_plugins', get_option( 'active_plugins' ) );
			}

			if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
				include_once ABSPATH . '/wp-admin/includes/plugin.php';
			}

			// WP All Import.
			$plugin_path = 'wp-all-import-pro/wp-all-import-pro.php';
			if ( in_array( $plugin_path, $active_plugins, true )
				|| is_plugin_active_for_network( $plugin_path ) ) {
				$wp_all_import = new Inventory_Presser_WP_All_Import();
				$wp_all_import->add_hooks();
			}

			// Contact Form 7.
			$plugin_path = 'contact-form-7/wp-contact-form-7.php';
			if ( class_exists( 'Inventory_Presser_Contact_Form_7' )
				&& ( in_array( $plugin_path, $active_plugins, true )
				|| is_plugin_active_for_network( $plugin_path ) ) ) {
				$contact_form_7 = new Inventory_Presser_Contact_Form_7();
				$contact_form_7->add_hooks();
			}

			// WPForms Lite.
			$plugin_path = 'wpforms-lite/wpforms.php';
			if ( in_array( $plugin_path, $active_plugins, true )
				|| is_plugin_active_for_network( $plugin_path ) ) {
				$forms = new Inventory_Presser_WPForms();
				$forms->add_hooks();
			}

			// Classic Editor.
			$plugin_path = 'classic-editor/classic-editor.php';
			if ( in_array( $plugin_path, $active_plugins, true )
				|| is_plugin_active_for_network( $plugin_path ) ) {
				$classic_editor = new Inventory_Presser_Classic_Editor();
				$classic_editor->add_hooks();
			}

			// Avada Builder.
			$plugin_path = 'fusion-builder/fusion-builder.php';
			if ( in_array( $plugin_path, $active_plugins, true )
				|| is_plugin_active_for_network( $plugin_path ) ) {
				$avada = new Inventory_Presser_Avada();
				$avada->add_hooks();
			}

			// Divi.
			$divi = new Inventory_Presser_Divi();
			$divi->add_hooks();

			// Gravity Forms.
			$plugin_path = 'gravityforms/gravityforms.php';
			if ( in_array( $plugin_path, $active_plugins, true )
				|| is_plugin_active_for_network( $plugin_path ) ) {
				GF_Fields::register( new GF_Field_Vehicle() );
			}
		}

		/**
		 * Includes all the includes! This function loads all the other PHP files
		 * that contain this plugin's code.
		 *
		 * @return void
		 */
		protected function include_dependencies() {
			// include composer dependencies.
			include_once plugin_dir_path( INVP_PLUGIN_FILE_PATH ) . 'vendor/autoload.php';

			// Include our object definition dependencies.
			$file_names = array(
				'class-add-custom-fields-to-search.php',
				'addon/class-addon-license-validator.php',
				'addon/class-addon-license.php',
				'addon/class-addon-updater.php',
				'addon/class-addon.php',
				'admin/class-admin-bar.php',
				'admin/class-admin-editor-sidebar.php',
				'admin/class-admin-location-meta.php',
				'admin/class-admin-options.php',
				'admin/class-admin-photo-arranger.php',
				'admin/class-admin-posts-list.php',
				'class-additional-listings-pages.php',
				'class-allow-inventory-as-home-page.php',
				'class-badges.php',
				'class-blocks.php',
				'class-business-day.php',
				'class-invp.php',
				'class-menu-item-email-a-friend.php',
				'class-menu-item-print.php',
				'class-option-manager.php',
				'class-photo-numberer.php',
				'class-range-filters.php',
				'class-redirect-404-vehicles.php',
				'class-rest.php',
				'class-schema-org-generator.php',
				'class-site-health.php',
				'integrations/class-avada.php',
				'integrations/class-classic-editor.php',
				'integrations/class-divi.php',
				'integrations/class-forms-integration.php',
				'integrations/class-contact-form-7.php',
				'integrations/class-gravityforms-field-vehicle.php',
				'integrations/class-wp-all-import.php',
				'integrations/class-wpforms.php',
				'shortcode/class-shortcode-hours-today.php',
				'shortcode/class-shortcode-iframe.php',
				'shortcode/class-shortcode-inventory-grid.php',
				'shortcode/class-shortcode-inventory-slider.php',
				'shortcode/class-shortcode-photo-slider.php',
				'shortcode/class-shortcode-archive.php',
				'shortcode/class-shortcode-archive-vehicle.php',
				'shortcode/class-shortcode-attribute-table.php',
				'shortcode/class-shortcode-single-vehicle.php',
				'shortcode/class-shortcode-sort-by.php',
				'shortcode/class-shortcode-vin.php',
				'class-taxonomies.php',
				'class-taxonomy-overlapper.php',
				'class-template-provider.php',
				'class-vehicle-urls-by-vin.php',
				'widget/class-widget-address.php',
				'widget/class-widget-carfax.php',
				'widget/class-widget-fuel-economy.php',
				'widget/class-widget-google-maps.php',
				'widget/class-widget-google-maps-v3.php',
				'widget/class-widget-hours.php',
				'widget/class-widget-inventory-grid.php',
				'widget/class-widget-inventory-slider.php',
				'widget/class-widget-kbb.php',
				'widget/class-widget-map.php',
				'widget/class-widget-order-by.php',
				'widget/class-widget-phones.php',
				'widget/class-widget-maximum-price-filter.php',
				'template-tags.php',
			);
			foreach ( $file_names as $file_name ) {
				$path = plugin_dir_path( INVP_PLUGIN_FILE_PATH ) . 'includes/' . $file_name;
				if ( file_exists( $path ) ) {
					include $path;
				}
			}
		}

		/**
		 * Registers JavaScripts and stylesheets for front-end users and dashboard
		 * users. Includes some inline styles and scripts depending on the plugin
		 * settings and page request.
		 *
		 * @return void
		 */
		public static function include_scripts_and_styles() {

			// This method might have run already.
			if ( wp_script_is( 'invp-slider', 'registered' ) ) {
				// Abort.
				return;
			}

			// Allow dashicons use on frontend.
			wp_enqueue_style( 'dashicons' );

			/**
			 * Register stylesheets that will only be enqueued when specific
			 * widgets or shortcodes are used.
			 */
			$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
			wp_register_style(
				'invp-grid',
				plugins_url( "css/widget-grid{$min}.css", INVP_PLUGIN_FILE_PATH ),
				array(),
				INVP_PLUGIN_VERSION
			);
			wp_register_style(
				'invp-maximum-price-filters',
				plugins_url( "css/widget-maximum-price-filters{$min}.css", INVP_PLUGIN_FILE_PATH ),
				array(),
				INVP_PLUGIN_VERSION
			);
			wp_register_style(
				'invp-epa-fuel-economy',
				plugins_url( "css/widget-epa-fuel-economy{$min}.css", INVP_PLUGIN_FILE_PATH ),
				array(),
				INVP_PLUGIN_VERSION
			);
			wp_register_style(
				'invp-slider',
				plugins_url( "css/widget-slider{$min}.css", INVP_PLUGIN_FILE_PATH ),
				array(),
				INVP_PLUGIN_VERSION
			);

			// Register a script without a .js URL for our inline invp object.
			if ( ! wp_script_is( 'invp', 'registered' ) ) {
				$settings = INVP::settings();
				wp_register_script( 'invp', '', array(), INVP_PLUGIN_VERSION, true );
				wp_add_inline_script(
					'invp',
					'var invp = ' . wp_json_encode(
						array(
							'hull_materials'      => apply_filters(
								'invp_default_hull_materials',
								array(
									__( 'Aluminum', 'inventory-presser' ),
									__( 'Carbon Fiber', 'inventory-presser' ),
									__( 'Composite', 'inventory-presser' ),
									__( 'Ferro-Cement', 'inventory-presser' ),
									__( 'Fiberglass', 'inventory-presser' ),
									__( 'Hypalon', 'inventory-presser' ),
									__( 'Other', 'inventory-presser' ),
									__( 'PVC', 'inventory-presser' ),
									__( 'Steel', 'inventory-presser' ),
									__( 'Wood', 'inventory-presser' ),
								)
							),
							'is_singular'         => is_singular( INVP::POST_TYPE ), // Used by flexslider.js.
							'meta_prefix'         => INVP::meta_prefix(), // Used by classic-editor.js & editor-sidebar.js.
							'odometer_label'      => apply_filters( 'invp_odometer_word', __( 'Odometer', 'inventory-presser' ) ),
							'odometer_units'      => apply_filters( 'invp_odometer_word', __( 'miles', 'inventory-presser' ) ),
							'payment_frequencies' => apply_filters(
								'invp_default_payment_frequencies',
								array(
									'Monthly'      => 'monthly',
									'Weekly'       => 'weekly',
									'Bi-weekly'    => 'biweekly',
									'Semi-monthly' => 'semimonthly',
								)
							),
							'taxonomies'          => $settings['taxonomies'] ?? array(),
							'title_statuses'      => apply_filters(
								'invp_default_title_statuses',
								array(
									__( 'Unspecified', 'inventory-presser' ),
									__( 'Clear', 'inventory-presser' ),
									__( 'Clean', 'inventory-presser' ),
									__( 'Flood, Water Damage', 'inventory-presser' ),
									__( 'Lemon and Manufacturers Buyback', 'inventory-presser' ),
									__( 'Rebuild, Rebuildable, and Reconstructed', 'inventory-presser' ),
									__( 'Salvage', 'inventory-presser' ),
									__( 'Other', 'inventory-presser' ),
								)
							),
						)
					) . ';'
				);

				// Register a script for our blocks, also without a .js URL.
				wp_register_script(
					'invp-blocks',
					'',
					array(),
					INVP_PLUGIN_VERSION,
					true
				);
				// Provide the vehicle post type meta keys and prefix to JavaScript.
				wp_add_inline_script(
					'invp-blocks',
					'const invp_blocks = ' . wp_json_encode(
						array(
							'currency_symbol'             => INVP::currency_symbol(),
							'keys'                        => INVP::keys_and_types(),
							'meta_prefix'                 => INVP::meta_prefix(),
							'odometer_units'              => apply_filters( 'invp_odometer_word', __( 'Miles', 'inventory-presser' ) ),
							'use_carfax'                  => $settings['use_carfax'],
							'use_carfax_provided_buttons' => $settings['use_carfax_provided_buttons'],
						)
					),
					'before'
				);
				/**
				 * Register a stylesheet for the Block Editor. This makes the
				 * blocks provided by this plugin more pleasant to work with.
				 */
				wp_register_style(
					'invp-block-editor',
					plugins_url( "css/block-editor{$min}.css", INVP_PLUGIN_FILE_PATH ),
					array(),
					INVP_PLUGIN_VERSION
				);
			}

			/**
			 * Register flexslider and provide overrides for scripts and styles
			 */
			$min_dash = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '-min'; // Dash not dot!
			wp_register_script(
				'flexslider',
				plugins_url( "/vendor/woocommerce/FlexSlider/jquery.flexslider{$min_dash}.js", INVP_PLUGIN_FILE_PATH ),
				array( 'invp', 'jquery' ),
				INVP_PLUGIN_VERSION,
				true
			);
			// Our overrides.
			wp_register_script(
				'invp-flexslider',
				plugins_url( "/js/flexslider{$min}.js", INVP_PLUGIN_FILE_PATH ),
				array( 'flexslider' ),
				INVP_PLUGIN_VERSION,
				true
			);
			// Another flexslider spin-up script for the Vehicle Slider widget.
			wp_register_script(
				'invp-slider',
				plugins_url( "/js/widget-slider{$min}.js", INVP_PLUGIN_FILE_PATH ),
				array( 'flexslider' ),
				INVP_PLUGIN_VERSION,
				true
			);

			wp_register_script(
				'invp_sort_by',
				plugins_url( "/js/shortcode-sort-by{$min}.js", INVP_PLUGIN_FILE_PATH ),
				array(),
				INVP_PLUGIN_VERSION,
				true
			);

			// Sort by Vehicle Attributes (Order By) widget.
			wp_register_script(
				'order-by-widget-javascript',
				plugins_url( "js/order-by-post-meta-widget{$min}.js", INVP_PLUGIN_FILE_PATH ),
				array(),
				INVP_PLUGIN_VERSION,
				true
			);

			// Flexslider.
			wp_register_style(
				'flexslider',
				plugins_url( ( ! empty( $min ) ? "/css/woocommerce-flexslider{$min}.css" : '/vendor/woocommerce/FlexSlider/flexslider.css' ), INVP_PLUGIN_FILE_PATH ),
				null,
				INVP_PLUGIN_VERSION
			);
			// Our Flexslider overrides.
			wp_register_style(
				'invp-flexslider',
				plugins_url( "/css/flexslider{$min}.css", INVP_PLUGIN_FILE_PATH ),
				array( 'flexslider' ),
				INVP_PLUGIN_VERSION
			);

			// Register the iFrameResizer.js script for use by our [invp_iframe] shortcode and Iframe block.
			wp_register_script(
				'invp-iframe-resizer',
				plugins_url( "/js/iframe-resizer/iframeResizer{$min}.js", INVP_PLUGIN_FILE_PATH ),
				array(),
				INVP_PLUGIN_VERSION,
				true
			);

			/**
			 * Register a stylesheet that will be used by two shortcodes,
			 * [invp_archive_vehicle] and [invp_single_vehicle]
			 */
			wp_register_style(
				'invp-attribute-table',
				plugins_url( "/css/vehicle-attribute-table{$min}.css", INVP_PLUGIN_FILE_PATH ),
				null,
				INVP_PLUGIN_VERSION
			);

			// Register a stylesheet for the archive vehicle shortcode.
			wp_register_style(
				'invp_archive_vehicle',
				plugins_url( "/css/shortcode-archive-vehicle{$min}.css", INVP_PLUGIN_FILE_PATH ),
				null,
				INVP_PLUGIN_VERSION
			);

			// Register a stylesheet for the single vehicle shortcode.
			wp_register_style(
				'invp_single_vehicle',
				plugins_url( "/css/shortcode-single-vehicle{$min}.css", INVP_PLUGIN_FILE_PATH ),
				null,
				INVP_PLUGIN_VERSION
			);

			// Register leaflet.js script and style files used in Map widget.
			wp_register_script(
				Inventory_Presser_Map_Widget::SCRIPT_HANDLE_LEAFLET,
				plugins_url( 'js/leaflet/leaflet.js', INVP_PLUGIN_FILE_PATH ),
				array(),
				INVP_PLUGIN_VERSION,
				true
			);
			wp_register_script(
				'invp-maps',
				plugins_url( "js/widget-map{$min}.js", INVP_PLUGIN_FILE_PATH ),
				array( Inventory_Presser_Map_Widget::SCRIPT_HANDLE_LEAFLET ),
				INVP_PLUGIN_VERSION,
				true
			);
			wp_register_style(
				Inventory_Presser_Map_Widget::SCRIPT_HANDLE_LEAFLET,
				plugins_url( "js/leaflet/leaflet{$min}.css", INVP_PLUGIN_FILE_PATH ),
				array(),
				INVP_PLUGIN_VERSION
			);
			wp_register_style(
				Inventory_Presser_Map_Widget::ID_BASE,
				plugins_url( "css/widget-map{$min}.css", INVP_PLUGIN_FILE_PATH ),
				array(),
				INVP_PLUGIN_VERSION
			);

			// Admin location meta scripts.
			wp_register_script(
				'inventory-presser-timepicker',
				plugins_url( '/js/jquery.timepicker.min.js', INVP_PLUGIN_FILE_PATH ),
				array( 'jquery' ),
				INVP_PLUGIN_VERSION,
				true
			);
			wp_register_script(
				'inventory-presser-location',
				plugins_url( "/js/tax-location{$min}.js", INVP_PLUGIN_FILE_PATH ),
				array( 'inventory-presser-timepicker', 'jquery-ui-sortable' ),
				INVP_PLUGIN_VERSION,
				true
			);

			// Classic Editor scripts.
			wp_register_script(
				'invp-classic-editor',
				plugins_url( "/js/classic-editor{$min}.js", INVP_PLUGIN_FILE_PATH ),
				array( 'invp' ),
				INVP_PLUGIN_VERSION,
				true
			);

			// Google Maps widget scripts.
			wp_register_script(
				'invp_google_maps_v3',
				plugins_url( "js/widget-google-maps-v3{$min}.js", INVP_PLUGIN_FILE_PATH ),
				array( 'invp_google_maps_v3_goog' ),
				INVP_PLUGIN_VERSION,
				true
			);
		}

		/**
		 * Adds a link to the settings page near the Activate | Delete links on the
		 * list of plugins on the Plugins page.
		 *
		 * @param  array $links
		 * @return array
		 */
		public function insert_settings_link( $links ) {
			$url     = admin_url(
				sprintf(
					'edit.php?post_type=%s&page=%s',
					INVP::POST_TYPE,
					INVP::OPTION_PAGE
				)
			);
			$links[] = sprintf(
				'<a href="%s">%s</a>',
				$url,
				__( 'Settings', 'inventory-presser' )
			);
			return $links;
		}

		/**
		 * Fires once a vehicle post has been saved.
		 *
		 * @param int     $post_ID Post ID.
		 * @param WP_Post $post    Post object.
		 * @param bool    $update  Whether this is an existing post being updated.
		 */
		public function mark_vehicles_for_sale_during_insertion( $post_ID, $post, $update ) {
			if ( $update ) {
				// Abort, we only want to affect insertions.
				return;
			}

			// Does the vehicle already have a term in the Availabilities taxonomy?
			$terms = wp_get_object_terms( $post_ID, 'availability' );
			if ( ! empty( $terms ) && is_array( $terms ) ) {
				// Yes, abort.
				return;
			}

			// No, create a relationship with "For Sale".
			wp_set_object_terms( $post_ID, 'for-sale', 'availability' );
		}

		/**
		 * Filter callback that changes a query's meta_query value if the meta_query
		 * does not already contain the provided $key.
		 *
		 * @param  array  $meta_query The meta_query member of a WP_Query, retrieved with WP_Query->get('meta_query').
		 * @param  string $key
		 * @param  string $value
		 * @param  string $compare
		 * @param  string $type
		 * @return array The modified $meta_query array
		 */
		public static function maybe_add_meta_query( $meta_query, $key, $value, $compare, $type ) {
			// Make sure there is not already $key item in the meta_query.
			if ( self::meta_query_contains_clause( $meta_query, $key, $value, $compare, $type ) ) {
				return $meta_query;
			}

			$meta_query[] = array(
				'key'     => $key,
				'value'   => $value,
				'compare' => $compare,
				'type'    => $type,
			);
			return $meta_query;
		}

		/**
		 * Checks if a meta_query already contains a clause.
		 *
		 * @param  mixed $meta_query
		 * @param  mixed $key
		 * @param  mixed $value
		 * @param  mixed $compare
		 * @param  mixed $type
		 * @return bool
		 */
		public static function meta_query_contains_clause( $meta_query, $key, $value, $compare, $type ) {
			if ( is_array( $meta_query ) ) {
				if ( isset( $meta_query['key'] )
					&& isset( $meta_query['value'] )
					&& isset( $meta_query['compare'] )
					&& isset( $meta_query['type'] )
				) {
					return $meta_query['key'] === $key
					&& $meta_query['value'] === $value
					&& $meta_query['compare'] === $compare
					&& $meta_query['type'] === $type;
				}

				foreach ( $meta_query as $another ) {
					return self::meta_query_contains_clause( $another, $key, $value, $compare, $type );
				}
			}
			return false;
		}

		/**
		 * Modifies the $query to filter vehicles by prices for the Maximum
		 * Price Filter widget.
		 *
		 * @param  object $query An instance of the WP_Query class.
		 * @return void
		 */
		public function modify_query_for_max_price( $query ) {
			// Do not mess with the query if it's not the main one and our CPT.
			if ( ! isset( $_GET['max_price'] )
				|| ! $query->is_main_query()
				|| INVP::POST_TYPE !== $query->get( 'post_type', '' )
			) {
				return;
			}

			// Get original meta query.
			$meta_query = $query->get( 'meta_query' );
			if ( ! is_array( $meta_query ) ) {
				$meta_query = array();
			}

			$meta_query['relation'] = 'AND';
			$meta_query             = self::maybe_add_meta_query(
				$meta_query,
				apply_filters( 'invp_prefix_meta_key', 'price' ),
				(int) $_GET['max_price'],
				'<=',
				'numeric'
			);
			$query->set( 'meta_query', $meta_query );
		}

		/**
		 * Filter callback. Modifies a query's ORDER BY clause to appropriately
		 * sort some meta values as numbers instead of strings while adding fields
		 * to the ORDER BY clause to account for all of the JOINs to the postmeta.
		 *
		 * @param  array $pieces All of a queries syntax organized into an array.
		 * @return array The changed array of database query fragments
		 */
		public function modify_query_orderby( $pieces ) {
			/**
			 * Count the number of meta fields we have added to the query by parsing
			 * the join piece of the query
			 */
			global $wpdb;
			$meta_field_count = count( explode( "INNER JOIN $wpdb->postmeta AS", $pieces['join'] ) ) - 1;

			// Parse out the ASC or DESC sort direction from the end of the ORDER BY clause.
			$direction             = $this->get_last_word( $pieces['orderby'] );
			$acceptable_directions = array( 'ASC', 'DESC' );
			$direction             = ( in_array( $direction, $acceptable_directions, true ) ? ' ' . $direction : '' );

			/**
			 * Build a string to replace the existing ORDER BY field name
			 * Essentially, we are going to turn '{$wpdb->postmeta}.meta_value' into
			 * 'mt1.meta_value ASC, mt2.meta_value ASC, mt3.meta_value ASC'
			 * where the number of meta values is what we calculated in $meta_field_count
			 */
			if ( 0 < $meta_field_count ) {
				$replacement = $pieces['orderby'] . ', ';
				for ( $m = 0; $m < $meta_field_count; $m++ ) {
					$replacement .= 'mt' . ( $m + 1 ) . '.meta_value';
					/**
					 * Determine if this meta field should be sorted as a number
					 * 1. Parse out the meta key name from $pieces['where']
					 * 2. Run it through INVP::meta_value_is_number
					 */
					$field_start = strpos( $pieces['where'] ?? '', 'mt' . ( $m + 1 ) . '.meta_key = \'' ) + 16;
					$field_end   = strpos( $pieces['where'] ?? '', "'", $field_start ) - $field_start;
					if ( is_integer( $field_start ) && is_integer( $field_end ) ) {
						$field_name = substr( $pieces['where'], $field_start, $field_end );
						if ( INVP::meta_value_is_number( $field_name ) ) {
							$replacement .= '+0';
						}
					}

					$replacement .= $direction;
					if ( $m < ( $meta_field_count - 1 ) ) {
						$replacement .= ', ';
					}
				}

				$pieces['orderby'] = $replacement;
			}
			return $pieces;
		}

		/**
		 * Action hook callback. Prevents vehicles from lingering in the Trash after
		 * they've been deleted if a plugin setting dictates such behavior.
		 *
		 * @param  int $post_id
		 * @return void
		 */
		public function maybe_force_delete( $post_id ) {
			// is the post a vehicle?
			if ( INVP::POST_TYPE !== get_post_type( $post_id ) ) {
				return;
			}

			$settings = INVP::settings();
			if ( ! $settings['skip_trash'] ) {
				return;
			}

			// force delete.
			wp_delete_post( $post_id, true );
		}

		/**
		 * Registers all meta fields our custom post type uses to define a vehicle
		 * and its attachments.
		 *
		 * @return void
		 */
		public function register_meta_fields() {
			// Add meta fields to our post type.
			foreach ( INVP::keys_and_types( true ) as $key_arr ) {

				if ( empty( $key_arr['name'] ) ) {
					continue;
				}
				$key = apply_filters( 'invp_prefix_meta_key', $key_arr['name'] );
				register_post_meta(
					INVP::POST_TYPE,
					$key,
					array(
						'show_in_rest' => true,
						'single'       => 'options_array' !== $key_arr['name'],
						'type'         => $key_arr['type'] ?? 'string',
					)
				);
			}

			// Add a couple fields that are used on media attachments.
			$attachment_keys   = array();
			$attachment_keys[] = array(
				'name' => 'hash',
				'type' => 'string',
			);
			$attachment_keys[] = array(
				'name' => 'photo_number',
				'type' => 'integer',
			);
			$attachment_keys[] = array(
				'name' => 'vin',
				'type' => 'string',
			);

			// Add meta fields to attachments.
			foreach ( $attachment_keys as $key_arr ) {
				$key = apply_filters( 'invp_prefix_meta_key', $key_arr['name'] );
				register_post_meta(
					'attachment',
					$key,
					array(
						'sanitize_callback' => 'sanitize_text_field',
						'show_in_rest'      => true,
						'single'            => true,
						'type'              => $key_arr['type'],
					)
				);
			}
		}

		/**
		 * Registers every widget that ships with this plugin.
		 *
		 * @return void
		 */
		public function register_widgets() {
			/**
			 * Make a widget available to sort vehicles by post meta fields.
			 * Or, enable order by year, make, price, odometer, etc.
			 */
			register_widget( 'Inventory_Presser_Order_By_Widget' );

			/**
			 * Make a widget available to show EPA Fuel Economy data
			 */
			register_widget( 'Inventory_Presser_Fuel_Economy_Widget' );

			/**
			 * Make a widget available to embed a Google map pointed at one of
			 * the addresses in our location taxonomy.
			 */
			register_widget( 'Inventory_Presser_Google_Maps_Widget' );
			register_widget( 'Inventory_Presser_Google_Maps_Widget_V3' );
			register_widget( 'Inventory_Presser_Map_Widget' );

			register_widget( 'Inventory_Presser_Carfax_Widget' );
			register_widget( 'Inventory_Presser_Grid' );
			register_widget( 'Inventory_Presser_KBB_Widget' );
			register_widget( 'Inventory_Presser_Location_Address' );
			register_widget( 'Inventory_Presser_Location_Hours' );
			register_widget( 'Inventory_Presser_Location_Phones' );
			register_widget( 'Inventory_Presser_Slider' );
			register_widget( 'Inventory_Presser_Maximum_Price_Filter' );
		}

		/**
		 * Sanitizes every member of an array at every level with
		 * sanitize_text_field()
		 *
		 * @param  array $arr
		 * @return array
		 */
		protected function sanitize_array( $arr ) {
			foreach ( $arr as $key => $value ) {
				if ( is_array( $value ) ) {
					$arr[ $key ] = $this->sanitize_array( $value );
				} else {
					$arr[ $key ] = sanitize_text_field( $value );
				}
			}
			return $arr;
		}

		/**
		 * Saves vehicle attributes into their corresponding post meta fields when
		 * the Save or Update button is clicked in the editor.
		 *
		 * @param  int     $post_id
		 * @param  WP_Post $post
		 * @param  bool    $is_update
		 * @return void
		 */
		public function save_vehicle_post_meta( $post_id, $post, $is_update ) {
			/**
			 * Do not continue if the post is being moved to the trash or if this is
			 * an auto-draft.
			 */
			if ( in_array( $post->post_status, array( 'trash', 'auto-draft' ), true ) ) {
				return;
			}

			// Abort if autosave or AJAX/quick edit.
			if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
				|| ( defined( 'DOING_AJAX' ) && DOING_AJAX )
			) {
				return;
			}

			// is this a vehicle?
			if ( ! empty( $_POST['post_type'] ) && INVP::POST_TYPE !== $_POST['post_type'] ) {
				// no, don't create any meta data for vehicles.
				return;
			}

			/**
			 * Tick the last modified date of this vehicle since we're saving changes.
			 * It looks like this: Tue, 06 Sep 2016 09:26:12 -0400
			 */
			$offset           = sprintf( '%+03d00', intval( get_option( 'gmt_offset' ) ) );
			$timestamp_format = 'D, d M Y h:i:s';
			// use post_modified to set last_modified.
			$post_modified = DateTime::createFromFormat( 'Y-m-d H:i:s', $post->post_modified );
			$timestamp     = $post_modified->format( $timestamp_format ) . ' ' . $offset;
			$key           = apply_filters( 'invp_prefix_meta_key', 'last_modified' );
			delete_post_meta( $post_id, $key );
			update_post_meta( $post_id, $key, $timestamp );

			/**
			 * If this is not an update or there is no date entered post meta value,
			 * set the date_entered meta value using the post_date
			 */
			if ( ! $is_update || empty( INVP::get_meta( 'date_entered', $post_id ) ) ) {
				// use post_date to set date_entered.
				$post_date = DateTime::createFromFormat( 'Y-m-d H:i:s', $post->post_date );
				$timestamp = $post_date->format( $timestamp_format ) . ' ' . $offset;
				$key       = apply_filters( 'invp_prefix_meta_key', 'date_entered' );
				delete_post_meta( $post_id, $key );
				update_post_meta( $post_id, $key, $timestamp );
			}

			if ( empty( $_POST ) ) {
				return;
			}

			// Clear this value that is defined by a checkbox.
			update_post_meta( $post_id, apply_filters( 'invp_prefix_meta_key', 'featured' ), '0' );

			/**
			 * Loop over the post meta keys we manage and save their values
			 * if we find them coming over as part of the post to save.
			 */
			$keys   = INVP::keys();
			$keys[] = 'options_array';

			foreach ( $keys as $key ) {
				$key = apply_filters( 'invp_prefix_meta_key', $key );
				if ( isset( $_POST[ $key ] ) ) {
					if ( is_array( $_POST[ $key ] ) ) {
						// delete all meta, this is essentially for the options.
						delete_post_meta( $post->ID, $key );
						$options = array(); // collect the options to maintain a CSV field for backwards compatibility.

						foreach ( $this->sanitize_array( $_POST[ $key ] ) as $value ) {
							add_post_meta( $post->ID, $key, $value );
							if ( 'inventory_presser_options_array' === $key ) {
								$options[] = $value;
							}
						}
					} else {
						// string data.
						update_post_meta( $post->ID, $key, sanitize_text_field( $_POST[ $key ] ) );
					}
				}
			}
		}

		/**
		 * Saves custom taxonomy terms when vehicles are saved
		 *
		 * @param  int  $post_id   A post ID.
		 * @param  bool $is_update True if this is a post update rather than an insert.
		 * @return void
		 */
		public function save_vehicle_taxonomy_terms( $post_id, $is_update ) {
			foreach ( Inventory_Presser_Taxonomies::slugs_array() as $slug ) {
				$taxonomy_name = $slug;
				switch ( $slug ) {
					case 'style':
						$slug = 'body_style';
						break;
					case 'model_year':
						$slug = 'year';
						break;
				}
				Inventory_Presser_Taxonomies::save_taxonomy_term( $post_id, $taxonomy_name, apply_filters( 'invp_prefix_meta_key', $slug ) );
			}
		}

		/**
		 * Adds a contact form to single vehicle pages if a form is saved in the
		 * setting.
		 *
		 * @param  array $sections
		 * @return array
		 */
		public function single_sections_add_form( $sections ) {
			// Does this setting have a value?
			$settings = INVP::settings();
			if ( ! empty( $settings['singles_contact_form'] ) ) {
				// Value is a form ID prefixed with the form builder. GF_8.
				$form = explode( '_', $settings['singles_contact_form'] );
				if ( ! is_array( $form ) || 2 !== count( $form ) ) {
					return $sections;
				}
				$form_html         = '';
				$shortcode_pattern = '';
				switch ( $form[0] ) {
					case 'CF7':
						$shortcode_pattern = '[contact-form-7 id="%s"]';
						break;

					case 'GF':
						$shortcode_pattern = '[gravityform id="%s" title="false" description="false" ajax="true"]';
						break;

					case 'WPF':
						$shortcode_pattern = '[wpforms id="%s"]';
						break;

					case 'WSF':
						$shortcode_pattern = '[ws_form id="%s"]';
						break;
				}
				$shortcode = sprintf( $shortcode_pattern, $form[1] );
				if ( '' !== $shortcode ) {
					$form_html = apply_shortcodes( apply_filters( 'invp_single_sections_form_shortcode', $shortcode ) );
				}
				if ( '' !== $form_html ) {
					// Add the form to the sections array.
					$sections['form'] = sprintf(
						'<h2 class="vehicle-content-wrap">%s</h2><div class="vehicle-content-wrap">%s</div>',
						esc_html( apply_filters( 'invp_single_sections_form_title', __( 'Check Availability', 'inventory-presser' ) ) ),
						$form_html
					);
				}
			}
			return $sections;
		}
	}

	$inventory_presser = new Inventory_Presser_Plugin();
	$inventory_presser->add_hooks();
}
