<?php
defined( 'ABSPATH' ) or exit;

/**
 * Plugin Name: Inventory Presser
 * Plugin URI: https://inventorypresser.com
 * Description: An inventory management plugin for Car Dealers. Create or import an automobile or powersports dealership inventory.
 * Version: 13.4.1
 * Author: Friday Systems
 * Author URI: https://inventorypresser.com
 * Text Domain: inventory-presser
 * Domain Path: /languages
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'INVP_PLUGIN_BASE' ) )
{
	define( 'INVP_PLUGIN_BASE', plugin_basename( __FILE__ ) );
}
if ( ! defined( 'INVP_PLUGIN_FILE_PATH' ) )
{
	define( 'INVP_PLUGIN_FILE_PATH', __FILE__ );
}

/**
 * Inventory_Presser_Plugin
 * 
 * This class includes dependencies, adds hooks, adds rewrite rules, modifies 
 * queries, and registers scripts & styles.
 */
class Inventory_Presser_Plugin
{
	/**
	 * @var CUSTOM_POST_TYPE
	 * @deprecated, use INVP::POST_TYPE instead
	 */
	const CUSTOM_POST_TYPE = 'inventory_vehicle';

	/**
	 * @var OPTION_NAME
	 * @deprecated, use INVP::OPTION_NAME instead
	 */
	const OPTION_NAME = 'inventory_presser';
	
	/**
	 * settings
	 * 
	 * A place to store this plugin's option full of settings.
	 *
	 * @var mixed
	 */
	var $settings;
	
	/**
	 * add_orderby_to_query
	 *
	 * Filter callback that adds an ORDER BY clause to the main query when a 
	 * user requests a list of vehicles.
	 * 
	 * @param  object $query An instance of the WP_Query class
	 * @return void
	 */
	function add_orderby_to_query( $query )
	{
		//Do not mess with the query if it's not the main one and our CPT
		if ( ! $query->is_main_query()
			|| ! is_post_type_archive( INVP::POST_TYPE )
			|| ( empty( $_GET['orderby'] ) && empty( $this->settings['sort_vehicles_by'] ) ) )
		{
			return;
		}

		add_filter( 'posts_clauses', array( $this, 'modify_query_orderby' ) );

		/**
		 * The field we want to order by is either in $_GET['orderby'] when
		 * the user has chosen to reorder posts or saved in the plugin
		 * settings 'default-sort-key.' The sort direction is in
		 * $_GET['order'] or 'sort_vehicles_order.'
		 */
		$direction = $this->settings['sort_vehicles_order'];
		if( isset( $_GET['order'] ) )
		{
			$direction = sanitize_text_field( $_GET['order'] );
		}

		$key = $this->settings['sort_vehicles_by'];
		if( isset( $_GET['orderby'] ) )
		{
			$key = sanitize_text_field( $_GET['orderby'] );
		}

		//Make sure the meta key has the prefix
		$key = apply_filters( 'invp_prefix_meta_key', $key );
		$query->set( 'meta_key', $key );

		//maybe append to the meta_query if it is already set
		$old = $query->get( 'meta_query', array() );
		switch( apply_filters( 'invp_unprefix_meta_key', $query->query_vars['meta_key'] ) )
		{
			//make
			case 'make':
				$query->set( 'meta_query', array_merge( $old, array(
					'relation' => 'AND',
					array(
						'relation' => 'OR',
						array(
							'key'     => apply_filters( 'invp_prefix_meta_key', 'model' ),
							'compare' => 'NOT EXISTS'
						),
						array(
							'key'     => apply_filters( 'invp_prefix_meta_key', 'model' ),
							'compare' => 'EXISTS'
						),
					),
					array(
						'relation' => 'OR',
						array(
							'key'     => apply_filters( 'invp_prefix_meta_key', 'trim' ),
							'compare' => 'NOT EXISTS'
						),
						array(
							'key'     => apply_filters( 'invp_prefix_meta_key', 'trim' ),
							'compare' => 'EXISTS'
						),
					),
				) ) );
				break;

			//model
			case 'model':
				$query->set( 'meta_query', array_merge( $old, array(
						'relation' => 'AND',
						array(
							'relation' => 'OR',
							array(
								'key'     => apply_filters( 'invp_prefix_meta_key', 'model' ),
								'compare' => 'NOT EXISTS'
							),
							array(
								'key'     => apply_filters( 'invp_prefix_meta_key', 'model' ),
								'compare' => 'EXISTS'
							),
						),
						array(
							'relation' => 'OR',
							array(
								'key'     => apply_filters( 'invp_prefix_meta_key', 'trim' ),
								'compare' => 'NOT EXISTS'
							),
							array(
								'key'     => apply_filters( 'invp_prefix_meta_key', 'trim' ),
								'compare' => 'EXISTS'
							),
						),
				) ) );
				break;

			//year
			case 'year':
				$query->set( 'meta_query', array_merge( $old, array(
						'relation' => 'AND',
						array(
							'relation' => 'OR',
							array(
								'key'     => apply_filters( 'invp_prefix_meta_key', 'year' ),
								'compare' => 'NOT EXISTS'
							),
							array(
								'key'     => apply_filters( 'invp_prefix_meta_key', 'year' ),
								'compare' => 'EXISTS'
							),
						),
						array(
							'relation' => 'OR',
							array(
								'key'     => apply_filters( 'invp_prefix_meta_key', 'make' ),
								'compare' => 'NOT EXISTS'
							),
							array(
								'key'     => apply_filters( 'invp_prefix_meta_key', 'make' ),
								'compare' => 'EXISTS'
							),
						),
						array(
							'relation' => 'OR',
							array(
								'key'     => apply_filters( 'invp_prefix_meta_key', 'model' ),
								'compare' => 'NOT EXISTS'
							),
							array(
								'key'     => apply_filters( 'invp_prefix_meta_key', 'model' ),
								'compare' => 'EXISTS'
							),
						),
						array(
							'relation' => 'OR',
							array(
								'key'     => apply_filters( 'invp_prefix_meta_key', 'trim' ),
								'compare' => 'NOT EXISTS'
							),
							array(
								'key'     => apply_filters( 'invp_prefix_meta_key', 'trim' ),
								'compare' => 'EXISTS'
							),
						),
					)
				) );
				break;

			//boat fields might not exist on all vehicles, so do not require them
			case 'beam':
			case 'length':
			case 'hull_material':
				unset( $query->query_vars['meta_key'] );
				$query->set( 'meta_query', array_merge( $old, array(
						'relation' => 'OR',
						array(
							'key'     => $key,
							'compare' => 'NOT EXISTS'
						),
						array(
							'key'     => $key,
							'compare' => 'EXISTS'
						),
					)
				) );
				break;
		}

		$meta_value_or_meta_value_num = 'meta_value';
		$key_is_odometer = $key == apply_filters( 'invp_prefix_meta_key', 'odometer' );

		if( INVP::meta_value_is_number( $key ) || $key_is_odometer )
		{
			$meta_value_or_meta_value_num .= '_num';
		}

		//We also want to customize the order by to remove non-digits from the odometer
		if( $key_is_odometer )
		{
			add_filter( 'posts_orderby', array( $this, 'change_order_by_for_odometer' ), 10, 2 );
		}

		//Allow other developers to decide if the post meta values are numbers
		$query->set( 'orderby', apply_filters( 'invp_meta_value_or_meta_value_num', $meta_value_or_meta_value_num, $key ) );
		$query->set( 'order', $direction );
	}


	/**
	 * change_order_by_for_odometer
	 * 
	 * Removes commas from the meta value used in the ORDER BY of the query so
	 * that odometer values can be sorted as numbers instead of strings.
	 *
	 * @param  string $orderby The ORDER BY clause of a database query
	 * @param  object $query An instance of the WP_Query class
	 * @return string The changed ORDER BY clause
	 */
	function change_order_by_for_odometer( $orderby, $query )
	{
		/**
		 * Changes
		 * ORDER BY wp_postmeta.meta_value+0
		 * to
		 * ORDER BY REPLACE( wp_postmeta.meta_value, ',', '' )+0
		 */
		return str_replace( 'wp_postmeta.meta_value+0', "REPLACE( wp_postmeta.meta_value, ',', '' )+0", $orderby );
	}
	
	/**
	 * add_pretty_search_urls
	 * 
	 * Action hook callback that adds rewrite rules to the global $wp_rewrite.
	 * These rewrite rules are what power URLs like /inventory/make/subaru.
	 *
	 * @return void
	 */	
	function add_pretty_search_urls()
	{
		global $wp_rewrite;
		$wp_rewrite->rules = $this->generate_rewrite_rules( INVP::POST_TYPE ) + $wp_rewrite->rules;
	}
	
	/**
	 * change_attachment_urls
	 * 
	 * Adds a querystring to vehicle attachment photo URLs to fight caching.
	 *
	 * @param  string $url
	 * @param  int $post_id
	 * @return string The changed URL
	 */
	public function change_attachment_urls( $url, $post_id = null )
	{
		if( empty( $post_id ) )
		{
			$post_id = attachment_url_to_postid( $url );
		}

		if( INVP::POST_TYPE != get_post_type( wp_get_post_parent_id( $post_id ) ) )
		{
			return $url;
		}

		//This could be controversial
		switch( substr( $url, -4 ) )
		{
			case 'jpeg':
			case '.jpg':
			case '.png':
				break;
			default:
				return $url;
		}

		//Add a querystring that contains this photo's hash 
		$hash = get_post_meta( $post_id, apply_filters( 'invp_prefix_meta_key', 'hash' ), true );
		if( empty( $hash ) )
		{
			return $url;
		}

		return $url . '?' . $hash;
	}

	/**
	 * change_term_links
	 * 
	 * Change links to terms in our taxonomies to include /inventory before 
	 * /tax/term.
	 *
	 * @param  string $termlink
	 * @param  object $term An instance of the WP_Term class
	 * @return string A modified term link that has our post type slug prepended.
	 */
	function change_term_links( $termlink, $term )
	{
		$taxonomy = get_taxonomy( $term->taxonomy );

		if( ! in_array( INVP::POST_TYPE, $taxonomy->object_type ) )
		{
			return $termlink;
		}

		$post_type = get_post_type_object( INVP::POST_TYPE );
		if( empty( $post_type ) )
		{
			return $termlink;
		}

		$termlink = $post_type->rewrite['slug'] . $termlink;
		return $termlink;
	}
	
	/**
	 * create_post_type
	 *
	 * Registers the inventory_vehicle post type that holds vehicles. The post 
	 * type name is available in the API class constant INVP::POST_TYPE. The 
	 * arguments array can be altered using the invp_post_type_args filter hook.
	 * 
	 * @return void
	 */
	static function create_post_type()
	{
		//creates a custom post type that will be used by this plugin
		register_post_type(
			INVP::POST_TYPE,
			apply_filters(
				'invp_post_type_args',
				array (
					'description'  => __( 'Vehicles for sale', 'inventory-presser' ),
					'has_archive'  => true,
					'hierarchical' => false,
					'labels'       => array (
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
					'menu_icon'    => 'dashicons-admin-network',
					'menu_position'=> 5, //below Posts
					'public'       => true,
					'rest_base'    => 'inventory',
					'rewrite'      => array (
						'slug' => 'inventory',
						'with_front'   => false,
					),
					'show_in_rest' => true,
					'supports'     => array (
						'custom-fields',
						'editor',
						'title',
						'thumbnail',
					),
					'taxonomies'   => Inventory_Presser_Taxonomies::query_vars_array(),
				)
			)
		);
	}
	
	/**
	 * delete_rewrite_rules_option
	 *
	 * Deletes the rewrite_rules option so the rewrite rules are generated
	 * on the next page load without ours. Called during deactivation.
	 * @see http://wordpress.stackexchange.com/a/44337/13090
	 * 
	 * @param  bool $network_wide True if this plugin is being Network Activated or Network Deactivated by the multisite admin
	 * @return void
	 */
	static function delete_rewrite_rules_option( $network_wide )
	{
		if( ! is_multisite() || ! $network_wide )
		{
			delete_option('rewrite_rules');
			return;
		}

		$sites = get_sites( array( 'network' => 1, 'limit' => 1000 ) );
		foreach( $sites as $site )
		{
			switch_to_blog( $site->blog_id );
			delete_option( 'rewrite_rules' );
			restore_current_blog();
		}
	}

	/**
	 * flush_rewrite
	 *
	 * @param boolean $network_wide True if this plugin is being Network Activated or Network Deactivated by the multisite admin
	 * @return void
	 */
	static function flush_rewrite( $network_wide )
	{
		self::create_post_type();

		if( ! is_multisite() || ! $network_wide )
		{
			flush_rewrite_rules();
			return;
		}

		$sites = get_sites( array( 'network' => 1, 'limit' => 1000 ) );
		foreach( $sites as $site )
		{
			switch_to_blog( $site->blog_id );
			global $wp_rewrite;
			$wp_rewrite->init(); //important...
			$wp_rewrite->flush_rules();
			restore_current_blog();
		}
	}
	
	/**
	 * generate_rewrite_rules
	 * 
	 * Generate every possible combination of rewrite rules, including paging, based on post type taxonomy
	 * @see http://thereforei.am/2011/10/28/advanced-taxonomy-queries-with-pretty-urls/
	 *
	 * @param  string $post_type
	 * @param  array $query_vars
	 * @return void
	 */
	function generate_rewrite_rules( $post_type, $query_vars = array() )
	{
		global $wp_rewrite;

		if( ! is_object( $post_type ) )
		{
			$post_type = get_post_type_object( $post_type );
		}

		$rewrite_slugs = apply_filters( 'invp_rewrite_slugs', array(
			 $post_type->rewrite['slug'],
		) );

		$taxonomies = get_object_taxonomies( $post_type->name, 'objects' );
		$new_rewrite_rules = array();

		// Add taxonomy filters to the query vars array
		foreach( $taxonomies as $taxonomy )
		{
		    $query_vars[] = $taxonomy->query_var;
		}

		// Loop over all the possible combinations of the query vars
		for( $i = 1; $i <= count( $query_vars );  $i++ )
		{
			foreach( $rewrite_slugs as $rewrite_slug )
			{
				$new_rewrite_rule =  $rewrite_slug  . '/';
				$new_query_string = 'index.php?post_type=' . $post_type->name;

				// Prepend the rewrites & queries
				for( $n = 1; $n <= $i; $n++ )
				{
					$new_rewrite_rule .= '(' . implode( '|', $query_vars ) . ')/([^\/]+?)/';
					$new_query_string .= '&' . $wp_rewrite->preg_index( $n * 2 - 1 ) . '[]=' . $wp_rewrite->preg_index( $n * 2 );
				}

				// Allow paging of filtered post type - WordPress expects 'page' in the URL but uses 'paged' in the query string so paging doesn't fit into our regex
				$new_paged_rewrite_rule = $new_rewrite_rule . 'page/([0-9]{1,})/';
				$new_paged_query_string = $new_query_string . '&paged=' . $wp_rewrite->preg_index( $i * 2 + 1 );

				// Make the trailing backslash optional
				$new_paged_rewrite_rule = $new_paged_rewrite_rule . '?$';
				$new_rewrite_rule = $new_rewrite_rule . '?$';

				// Add the new rewrites
				$new_rewrite_rules[$new_paged_rewrite_rule] = $new_paged_query_string;
				$new_rewrite_rules[$new_rewrite_rule]       = $new_query_string;
			}
		}
		return apply_filters( 'invp_rewrite_rules', $new_rewrite_rules );
	}

	/**
	 * get_last_word
	 * 
	 * Given a string, return the last word.
	 *
	 * @param  string $str The string from which to extract the last word
	 * @return string The last word of the input string
	 */
	private function get_last_word( $str )
	{
		$pieces = explode( ' ', rtrim( $str ) );
		return array_pop( $pieces );
	}
	
	/**
	 * hooks
	 * 
	 * This is the driver function of the entire plugin. Includes dependencies 
	 * and adds all hooks.
	 *
	 * @return void
	 */
	function hooks()
	{
		//include all this plugin's classes that live in external files
		$this->include_dependencies();

		//Translate friendly names to actual custom field keys and the other way
		add_filter( 'invp_prefix_meta_key', array( 'INVP', 'translate_custom_field_names' ) );
		add_filter( 'invp_unprefix_meta_key', array( 'INVP', 'untranslate_custom_field_names' ) );

		//Modify the administrator dashboard
		$customize_dashboard = new Inventory_Presser_Customize_Dashboard();
		$customize_dashboard->hooks();


		/**
		 * Create our post type and taxonomies
		 */

		//create a custom post type for the vehicles
		add_action( 'init', array( $this, 'create_post_type' ) );

		//register all postmeta fields the CPT uses (mostly to expose them in the REST API)
		add_action( 'init', array( $this, 'register_meta_fields' ), 20 );


		//Create custom taxonomies
		$taxonomies = new Inventory_Presser_Taxonomies();
		$taxonomies->hooks();

		/**
		 * Some custom rewrite rules are created and destroyed
		 */

		//Add custom rewrite rules
		add_action('generate_rewrite_rules', array( $this, 'add_pretty_search_urls' ) );

		/**
		 * Activation and deactivation hooks ensure that the rewrite rules are
		 * flushed to add and remove our custom rewrite rules
		 */

		//Flush rewrite rules when the plugin is activated
		register_activation_hook( __FILE__, array( 'Inventory_Presser_Plugin', 'flush_rewrite' ) );

		//Delete an option during deactivation
		register_deactivation_hook( __FILE__, array( 'Inventory_Presser_Plugin', 'delete_rewrite_rules_option' ) );

		//Register some widgets included with this plugin
		add_action( 'widgets_init', array( $this, 'register_widgets' ) );

		/**
		 * Deliver our promise to order posts, change the ORDER BY clause of
		 * the query that's fetching post objects.
		 */

		$this->settings = INVP::settings();
		if( ! is_admin() )
		{
			add_action( 'pre_get_posts', array( $this, 'add_orderby_to_query' ) );
		}

		//Allow custom fields to be searched
		$add_custom_fields_to_search = new Add_Custom_Fields_To_Search();
		$add_custom_fields_to_search->hooks();

		//Redirect URLs by VINs to proper vehicle permalinks
		$allow_urls_by_vin = new Vehicle_URLs_By_VIN();
		$allow_urls_by_vin->hooks();

		//Add buttons near vehicles for Carfax reports or NextGear inspections
		$badges = new Inventory_Presser_Badges();
		$badges->hooks();

		//Redirect 404 vehicles to make archives
		$redirect_404_vehicles = new Redirect_404_Vehicles();
		$redirect_404_vehicles->hooks();

		//Include CSS on the frontend
		add_action( 'wp_enqueue_scripts', array( $this, 'include_scripts_and_styles' ), 11 );

		//Modify the URL of an "Email a Friend" menu item on the "Vehicle Details Buttons" menu
		$email_a_friend = new Inventory_Presser_Email_A_Friend();
		$email_a_friend->hooks();

		//Make it possible for a menu item to print the page
		$print_button = new Inventory_Presser_Menu_Item_Print();
		$print_button->hooks();

		/**
		 * When vehicle posts are inserted, make sure they create a relationship
		 * with the "For Sale" term in the Availabilities taxonomy. Some queries
		 * that honor the "Include Sold Vehicles" setting in this plugin will 
		 * exclude them without a relationship to a term in that taxonomy.
		 */
		add_action( 'save_post_' . INVP::POST_TYPE, array( $this, 'mark_vehicles_for_sale_during_insertion' ), 10, 3 );

		//Maybe skip the trash bin and permanently delete vehicles & photos
		add_action( 'trashed_post', array( $this, 'maybe_force_delete' ) );

		//When vehicles are deleted, delete their attachments, too
		add_action( 'before_delete_post', array( 'INVP', 'delete_attachments' ), 10, 1 );

		//Change links to our taxonomy terms to insert /inventory/
		add_filter( 'pre_term_link', array( $this, 'change_term_links' ), 10, 2 );

		//Change attachment URLs to prevent aggressive caching from showing users updated JPGs
		add_filter( 'wp_get_attachment_url', array( $this, 'change_attachment_urls' ) );

		//Allow users to set the Inventory listing page as the home page
		$page = new Inventory_Presser_Allow_Inventory_As_Home_Page();
		$page->hooks();

		//Add all our shortcodes
		$shortcodes = new Inventory_Presser_Shortcode_Grid();
		$shortcodes->hooks();
		$shortcodes = new Inventory_Presser_Shortcode_Iframe();
		$shortcodes->hooks();
		$shortcodes = new Inventory_Presser_Shortcode_Slider();
		$shortcodes->hooks();
		$shortcodes = new Inventory_Presser_Shortcode_Single_Vehicle();
		$shortcodes->hooks();
		$shortcodes = new Inventory_Presser_Shortcode_Archive();
		$shortcodes->hooks();
		$shortcodes = new Inventory_Presser_Shortcode_Archive_Vehicle();
		$shortcodes->hooks();
		$shortcodes = new Inventory_Presser_Shortcode_Hours_Today();
		$shortcodes->hooks();
		$shortcodes = new  Inventory_Presser_Shortcode_Photo_Slider();
		$shortcodes->hooks();

		/**
		 * When the active theme isn't prepared to display vehicles, insert
		 * our archive and single vehicle shortcodes.
		 */
		$template_provider = new Inventory_Presser_Template_Provider();
		$template_provider->hooks();

		//Add blocks
		$blocks = new Inventory_Presser_Blocks();
		$blocks->hooks();

		/**
		 * Add photo number meta values to vehicle photos uploaded in the
		 * dashboard
		 */
		$photo_numberer = new Inventory_Presser_Photo_Numberer();
		$photo_numberer->hooks();

		//Allow additional vehicle archives to be created
		$additional_archives = new Inventory_Presser_Additional_Listings_Pages();
		$additional_archives->hooks();

		if ( is_admin() )
		{
			//Initialize our Settings page in the Dashboard
			$options = new Inventory_Presser_Options();
			$options->hooks();

			//If the user is looking at our options page, suggest settings tweaks
			$settings_suggester = new Inventory_Presser_Settings_Suggester();
			$settings_suggester->hooks();

			//Add a sidebar to the editor when editing vehicles
			$sidebar = new Inventory_Presser_Editor_Sidebar();
			$sidebar->hooks();
		}

		$overlapper = new Inventory_Presser_Taxonomy_Overlapper();
		$overlapper->hooks();

		$uninstaller = new Inventory_Presser_Uninstaller();
		$uninstaller->hooks();

		$schema_generator = new Inventory_Presser_Schema_Org_Generator();
		$schema_generator->hooks();

		add_action( 'invp_archive_buttons', array( $this, 'add_view_details_button' ) );

		add_action( 'plugins_loaded', array( $this, 'loaded' ) );
	}

	public function add_view_details_button()
	{
		if( ! in_the_loop() )
		{
			return;
		}

		$css_classes = apply_filters( 'invp_css_classes_view_details_button', array(
			'wp-block-button__link',
			'button',
		) );
		?><a class="<?php echo esc_attr( implode( ' ', $css_classes ) ); ?>" href="<?php the_permalink(); ?>" title="<?php the_title(); ?>"><?php _e( 'View Details', 'inventory-presser' ); ?></a><?php
	}

	function loaded()
	{
		//Allow translations
		load_plugin_textdomain( 'inventory-presser', false, __DIR__ );

		//Fire an action hook after Inventory Presser is finished loading
		do_action( 'invp_loaded' );
	}

	/**
	 * include_dependencies
	 * 
	 * Includes all the includes! This function loads all the other PHP files 
	 * that contain this plugin's code.
	 *
	 * @return void
	 */
	function include_dependencies()
	{
		// include composer dependencies
		include_once 'vendor/autoload.php';

		//Include our object definition dependencies
		$file_names = array(
			'class-add-custom-fields-to-search.php',
			'class-addon.php',
			'class-addon-license-validator.php',
			'class-addon-updater.php',
			'class-additional-listings-pages.php',
			'class-allow-inventory-as-home-page.php',
			'class-badges.php',
			'class-blocks.php',
			'class-business-day.php',
			'class-customize-admin-dashboard.php',
			'class-dealership-options.php',
			'class-editor-sidebar.php',
			'class-invp.php',
			'class-license.php',
			'class-menu-item-email-a-friend.php',
			'class-menu-item-print.php',
			'class-option-manager.php',
			'class-order-by-post-meta-widget.php',
			'class-photo-numberer.php',
			'class-redirect-404-vehicles.php',
			'class-schema-org-generator.php',
			'class-settings-suggester.php',
			'class-shortcode-hours-today.php',
			'class-shortcode-iframe.php',
			'class-shortcode-inventory-grid.php',
			'class-shortcode-inventory-slider.php',
			'class-shortcode-photo-slider.php',
			'class-shortcode-template-shortcode.php',
			'class-shortcode-archive.php',
			'class-shortcode-archive-vehicle.php',
			'class-shortcode-single-vehicle.php',
			'class-taxonomies.php',
			'class-taxonomy-overlapper.php',
			'class-template-provider.php',
			'class-uninstaller.php',
			'class-vehicle.php',
			'class-vehicle-urls-by-vin.php',
			'class-widget-address.php',
			'class-widget-carfax.php',
			'class-widget-fuel-economy.php',
			'class-widget-google-maps.php',
			'class-widget-hours.php',
			'class-widget-inventory-grid.php',
			'class-widget-inventory-slider.php',
			'class-widget-kbb.php',
			'class-widget-map.php',
			'class-widget-phones.php',
			'class-widget-maximum-price-filter.php',
			'template-tags.php',
		);
		foreach( $file_names as $file_name )
		{
			$path = plugin_dir_path( __FILE__ ) . 'includes/' . $file_name;
			if( file_exists( $path ) )
			{
				require $path;
			}
		}
	}
	
	/**
	 * include_scripts_and_styles
	 * 
	 * Registers JavaScripts and stylesheets for front-end users and dashboard
	 * users. Includes some inline styles and scripts depending on the plugin
	 * settings and page request.
	 *
	 * @return void
	 */
	function include_scripts_and_styles()
	{
		//Allow dashicons use on frontend
		wp_enqueue_style( 'dashicons' );

		/**
		 * Register stylesheets that will only be enqueued when specific
		 * widgets or shortcodes are used.
		 */
		if( ! function_exists( 'get_plugin_data' ) )
		{
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}
		$plugin_version = get_plugin_data( __FILE__ )['Version'];
		wp_register_style( 'invp-grid', plugins_url( 'css/widget-grid.min.css', __FILE__ ), [], $plugin_version );
		wp_register_style( 'invp-maximum-price-filters', plugins_url( 'css/widget-maximum-price-filters.min.css', __FILE__ ), [], $plugin_version );
		wp_register_style( 'invp-epa-fuel-economy', plugins_url( 'css/widget-epa-fuel-economy.min.css', __FILE__ ), [], $plugin_version );
		wp_register_style( 'invp-slider', plugins_url( 'css/widget-slider.min.css', __FILE__ ), [], $plugin_version );

		/**
		 * Register flexslider and provide overrides for scripts and styles
		 */
		wp_register_script( 'flexslider', plugins_url('/vendor/woocommerce/FlexSlider/jquery.flexslider-min.js', __FILE__ ), array('jquery'), $plugin_version );
		//Our overrides
		wp_register_script( 'invp-flexslider', plugins_url('/js/flexslider.min.js', __FILE__ ), array( 'flexslider' ), $plugin_version );
		//Another flexslider spin-up script for the Vehicle Slider widget
		wp_register_script( 'invp-slider', plugins_url( '/js/widget-slider.min.js', __FILE__ ), array( 'flexslider' ), $plugin_version );

		wp_register_style( 'flexslider', plugins_url( '/vendor/woocommerce/FlexSlider/flexslider.css', __FILE__ ), null, $plugin_version );
		//Our overrides
		wp_register_style( 'invp-flexslider', plugins_url( '/css/flexslider.min.css', __FILE__ ), array( 'flexslider' ), $plugin_version );

		/**
		 * Register a stylesheet that will be used by two shortcodes,
		 * [invp-archive-vehicle] and [invp-single-vehicle]
		 */
		wp_register_style(
			'invp-attribute-table',
			plugins_url( '/css/vehicle-attribute-table.min.css', __FILE__ ),
			null,
			$plugin_version
		);

		//Register a stylesheet for the archive vehicle shortcode
		wp_register_style(
			'invp_archive_vehicle',
			plugins_url( '/css/shortcode-archive-vehicle.min.css', __FILE__ ),
			null,
			$plugin_version
		);

		//Register a stylesheet for the single vehicle shortcode
		wp_register_style(
			'invp_single_vehicle',
			plugins_url( '/css/shortcode-single-vehicle.min.css', __FILE__ ),
			null,
			$plugin_version
		);

		/**
		 * Make the meta prefix to the front-end (the object name invp is
		 * localized for the admin dashboard in
		 * Inventory_Presser_Customize_Dashboard)
		 */
		if( ! is_admin() )
		{ ?><script type="text/javascript">
		    var invp = <?php echo json_encode( array(
				'meta_prefix' => INVP::meta_prefix(),
				'is_singular' => is_singular( INVP::POST_TYPE ),
			) ); ?>;
		</script><?php
		}
	}

	/**
	 * Fires once a vehicle post has been saved.
	 *
	 * @param int     $post_ID Post ID.
	 * @param WP_Post $post    Post object.
	 * @param bool    $update  Whether this is an existing post being updated.
	 */
	function mark_vehicles_for_sale_during_insertion( $post_ID, $post, $update )
	{
		if( $update )
		{
			//Abort, we only want to affect insertions
			return;
		}

		//Does the vehicle already have a term in the Availabilities taxonomy?
		$terms = wp_get_object_terms( $post_ID, 'availability' );
		if( ! empty( $terms ) && is_array( $terms ) )
		{
			//Yes, abort
			return;	
		}

		//No, create a relationship with "For Sale"
		wp_set_object_terms( $post_ID, 'for-sale', 'availability' );
	}

	/**
	 * maybe_add_meta_query
	 * 
	 * Filter callback that changes a query's meta_query value if the meta_query
	 * does not already contain the provided $key.
	 *
	 * @param  array $meta_query The meta_query member of a WP_Query, retrieved with WP_Query->get('meta_query')
	 * @param  string $key
	 * @param  string $value
	 * @param  string $compare
	 * @param  string $type
	 * @return array The modified $meta_query array
	 */
	function maybe_add_meta_query( $meta_query, $key, $value, $compare, $type )
	{
		//Make sure there is not already $key item in the meta_query
		if( $this->meta_query_contains_key( $meta_query, $key ) )
		{
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
	 * meta_prefix
	 * 
	 * @deprecated This method has been moved to the INVP class.
	 * @return void
	 */
	public static function meta_prefix()
	{
		return INVP::meta_prefix();
	}

	/**
	 * meta_query_contains_key
	 * 
	 * Does a query's meta_query contain a specific key?
	 *
	 * @param  array $meta_query The array return value of WP_Query->get('meta_query')
	 * @param  string $key The meta key to search for
	 * @return bool|null
	 */
	function meta_query_contains_key( $meta_query, $key )
	{
		if( is_array( $meta_query ) )
		{
			if( isset( $meta_query['key'] ) )
			{
				return $meta_query['key'] == $key;
			}

			foreach( $meta_query as $another )
			{
				return $this->meta_query_contains_key( $another, $key );
			}
		}
		return null;
	}

	/**
	 * modify_query_for_max_price
	 * 
	 * Modifies the $query to filter vehicles by prices for the Maximum
	 * Price Filter widget.
	 *
	 * @param  object $query An instance of the WP_Query class
	 * @return void
	 */
	function modify_query_for_max_price( $query )
	{
		//Do not mess with the query if it's not the main one and our CPT
		if ( ! $query->is_main_query()
			|| empty( $query->query_vars['post_type'] )
			|| $query->query_vars['post_type'] != INVP::POST_TYPE )
		{
			return;
		}

		//Get original meta query
		$meta_query = $query->get('meta_query');
		if( ! is_array( $meta_query ) )
		{
			$meta_query = array();
		}

		if ( isset( $_GET['max_price'] ) )
		{
			$meta_query['relation'] = 'AND';
			$meta_query = $this->maybe_add_meta_query(
				$meta_query,
				apply_filters( 'invp_prefix_meta_key', 'price' ),
				(int) $_GET['max_price'],
				'<=',
				'numeric'
			);
			$query->set( 'meta_query', $meta_query );
		}
	}
	
	/**
	 * modify_query_orderby
	 * 
	 * Filter callback. Modifies a query's ORDER BY clause to appropriately 
	 * sort some meta values as numbers instead of strings while adding fields
	 * to the ORDER BY clause to account for all of the JOINs to the postmeta.
	 *
	 * @param  array $pieces All of a queries syntax organized into an array
	 * @return array The changed array of database query fragments
	 */
	function modify_query_orderby( $pieces )
	{
		/**
		 * Count the number of meta fields we have added to the query by parsing
		 * the join piece of the query
		 */
		$meta_field_count = sizeof( explode( 'INNER JOIN wp_postmeta AS', $pieces['join'] ) )-1;

		//Parse out the ASC or DESC sort direction from the end of the ORDER BY clause
		$direction = $this->get_last_word( $pieces['orderby'] );
		$acceptable_directions = array( 'ASC', 'DESC' );
		$direction = ( in_array( $direction, $acceptable_directions ) ? ' ' . $direction : '' );

		/**
		 * Build a string to replace the existing ORDER BY field name
		 * Essentially, we are going to turn 'wp_postmeta.meta_value' into
		 * 'mt1.meta_value ASC, mt2.meta_value ASC, mt3.meta_value ASC'
		 * where the number of meta values is what we calculated in $meta_field_count
		 */
		if( 0 < $meta_field_count )
		{
			$replacement = $pieces['orderby'] . ', ';
			for( $m=0; $m<$meta_field_count; $m++ )
			{
				$replacement .= 'mt' . ( $m+1 ) . '.meta_value';
				/**
				 * Determine if this meta field should be sorted as a number
				 * 1. Parse out the meta key name from $pieces['where']
				 * 2. Run it through INVP::meta_value_is_number
				 */
				$field_start = strpos( $pieces['where'], 'mt' . ( $m+1 ) . '.meta_key = \'')+16;
				$field_end = strpos( $pieces['where'], "'", $field_start )-$field_start;
				$field_name = substr( $pieces['where'], $field_start, $field_end );
				if( INVP::meta_value_is_number( $field_name ) )
				{
					$replacement .= '+0';
				}

				$replacement .= $direction;
				if( $m < ( $meta_field_count-1 ) )
				{
					$replacement .= ', ';
				}
			}

			$pieces['orderby'] = $replacement;
		}
		return $pieces;
	}
	
	/**
	 * maybe_force_delete
	 *
	 * Action hook callback. Prevents vehicles from lingering in the Trash after
	 * they've been deleted if a plugin setting dictates such behavior.
	 * 
	 * @param  int $post_id
	 * @return void
	 */
	function maybe_force_delete( $post_id )
	{
		//is the post a vehicle?
		if( INVP::POST_TYPE != get_post_type( $post_id ) )
		{
			return;
		}

		$settings = INVP::settings();
		if( ! $settings['skip_trash'] )
		{
			return;
		}

		//force delete
		wp_delete_post( $post_id, true );
	}
	
	/**
	 * register_meta_fields
	 * 
	 * Registers all meta fields our custom post type uses to define a vehicle
	 * and its attachments.
	 *
	 * @return void
	 */
	function register_meta_fields()
	{
		//Add meta fields to our post type
		foreach( INVP::keys_and_types( true ) as $key_arr )
		{
			$key = apply_filters( 'invp_prefix_meta_key', $key_arr['name'] );
			register_post_meta( INVP::POST_TYPE, $key, array(
				'show_in_rest' => true,
				'single'       => true,
				'type'         => $key_arr['type'],
			) );
		}

		//Register a meta field for a multi-value options array
		register_post_meta(
			INVP::POST_TYPE,
			apply_filters( 'invp_prefix_meta_key', 'options_array' ),
			array(
				'show_in_rest' => true,
				'single'       => false,
				'type'         => 'string',
			)
		);

		//Add a couple fields that are used on media attachments
		$attachment_keys = array();
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


		//Add meta fields to attachments
		foreach( $attachment_keys as $key_arr )
		{
			$key = apply_filters( 'invp_prefix_meta_key', $key_arr['name'] );
			register_post_meta( 'attachment', $key, array(
				'sanitize_callback' => 'sanitize_text_field',
				'show_in_rest'      => true,
				'single'            => true,
				'type'              => $key_arr['type'],
			) );
		}
	}
	
	/**
	 * register_widgets
	 * 
	 * Registers every widget that ships with this plugin.
	 *
	 * @return void
	 */
	function register_widgets()
	{
		/**
		 * Make a widget available to sort vehicles by post meta fields.
		 * Or, enable order by year, make, price, odometer, etc.
		 */
		register_widget( "Order_By_Widget" );

		/**
		 * Make a widget available to show EPA Fuel Economy data
		 */
		register_widget( "Inventory_Presser_Fuel_Economy_Widget" );

		/**
		 * Make a widget available to embed a Google map pointed at one of
		 * the addresses in our location taxonomy.
		 */
		register_widget( 'Inventory_Presser_Google_Maps_Widget' );
		register_widget( 'Inventory_Presser_Map_Widget' );

		register_widget( 'Inventory_Presser_Carfax_Widget' );
		register_widget( 'Inventory_Presser_Grid' );
		register_widget( 'Inventory_Presser_KBB_Widget' );
		register_widget( 'Inventory_Presser_Location_Address' );
		register_widget( 'Inventory_Presser_Location_Hours' );
		register_widget( 'Inventory_Presser_Location_Phones' );
		register_widget( 'Inventory_Presser_Slider' );
		register_widget( 'Inventory_Presser_Maximum_Price_Filter' );
		/**
		 * The query needs to be altered for the Maximum Price Filters widget.
		 */
		if ( ! is_admin() && isset( $_GET['max_price'] ) )
		{
			add_action( 'pre_get_posts', array( $this, 'modify_query_for_max_price' ), 99, 1 );
		}
	}

	/**
	 * settings
	 *
	 * @deprecated	This method has been moved to the INVP class.
	 * @return void
	 */
	public static function settings()
	{
		return INVP::settings();
	}
} //end class

$inventory_presser = new Inventory_Presser_Plugin();
$inventory_presser->hooks();
