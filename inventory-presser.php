<?php
defined( 'ABSPATH' ) or exit;

/**
 * Plugin Name: Inventory Presser
 * Plugin URI: https://inventorypresser.com
 * Description: An inventory management plugin for Car Dealers. Create or import an automobile or powersports dealership inventory.
 * Version: 11.8.1
 * Author: Corey Salzano
 * Author URI: https://profiles.wordpress.org/salzano
 * Text Domain: inventory-presser
 * Domain Path: /languages
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

/**
 * Inventory_Presser_Plugin
 * 
 * This class includes dependencies, adds hooks, adds rewrite rules, modifies 
 * queries, and registers scripts & styles.
 */
class Inventory_Presser_Plugin
{
	const CUSTOM_POST_TYPE = 'inventory_vehicle';
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
	 * add_carfax_badge
	 * 
	 * Filter callback that outputs HTML markup that creates a Carfax badge if
	 * $vehicle contains Carfax report data.
	 *
	 * @param  object $vehicle An instance of the Inventory_Presser_Vehicle class.
	 * @return void
	 */
	function add_carfax_badge( $vehicle )
	{
		$carfax_html = $vehicle->carfax_icon_html();
		if( '' != $carfax_html )
		{
			?><div class="carfax-wrapper"><?php
				echo $carfax_html;
			?></div><?php
		}
	}
	
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
			|| ! is_post_type_archive( self::CUSTOM_POST_TYPE )
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
			$direction = $_GET['order'];
		}

		$key = $this->settings['sort_vehicles_by'];
		if( isset( $_GET['orderby'] ) )
		{
			$key = $_GET['orderby'];
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

		if( Inventory_Presser_Vehicle::post_meta_value_is_number( $key ) || $key_is_odometer )
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
		$wp_rewrite->rules = $this->generate_rewrite_rules( self::CUSTOM_POST_TYPE ) + $wp_rewrite->rules;
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

		if( ! in_array( self::CUSTOM_POST_TYPE, $taxonomy->object_type ) )
		{
			return $termlink;
		}

		$post_type = get_post_type_object( self::CUSTOM_POST_TYPE );
		$termlink = $post_type->rewrite['slug'] . $termlink;

		return $termlink;
	}
	
	/**
	 * create_custom_post_type
	 *
	 * Registers the inventory_vehicle post type that holds vehicles.
	 * 
	 * @return void
	 */
	static function create_custom_post_type()
	{
		//creates a custom post type that will be used by this plugin
		register_post_type(
			self::CUSTOM_POST_TYPE,
			apply_filters(
				'invp_post_type_args',
				array (
					'description'  => __( 'Vehicles for sale in an automobile or powersports dealership', 'inventory-presser' ),
					/**
					 * Check if the theme (or the parent theme) has a CPT
					 * archive template.  If not, we will assume that the
					 * inventory is going to be displayed via shortcode, and
					 * we won't be using the theme archive
					 */
					'has_archive'  => true,
					'hierarchical' => false,
					'labels'       => array (
						'name'          => __( 'Vehicles', 'inventory-presser' ),
						'singular_name' => __( 'Vehicle', 'inventory-presser' ),
						'all_items'     => __( 'All Vehicles', 'inventory-presser' ),
						'add_new_item'  => __( 'Add New Vehicle', 'inventory-presser' ),
						'edit_item'     => __( 'Edit Vehicle', 'inventory-presser' ),
						'view_item'     => __( 'View Vehicle', 'inventory-presser' ),
						'archives'      => __( 'Inventory', 'inventory-presser' ), //Public-facing menus label
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
	 * deactivate
	 *
	 * Deactivates this plugin.
	 * 
	 * @return void
	 */
	function deactivate()
	{
		deactivate_plugins( plugin_basename( __FILE__ ) );
	}
	
	/**
	 * delete_options
	 *
	 * Deletes the option that contains this plugins settings.
	 * 
	 * @return void
	 */
	function delete_options()
	{
		delete_option( self::OPTION_NAME );
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
	 * find_theme_stylesheet_handle
	 * 
	 * Finds the registered handle of the active theme's stylesheet.
	 *
	 * @return string|null The stylesheet handle or null if there is no stylesheet
	 */
	private function find_theme_stylesheet_handle()
	{
		global $wp_styles;

		foreach( $wp_styles->registered as $handle => $style_obj )
		{
			if( $style_obj->src === get_stylesheet_directory_uri() . '/style.css' )
			{
				return $handle;
			}
		}
		return null;
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

		//Allow translations
		add_action( 'plugins_loaded', function()
		{
			load_plugin_textdomain( 'inventory-presser', false, __DIR__ );
		} );

		//Modify the administrator dashboard
		$customize_dashboard = new Inventory_Presser_Customize_Dashboard();
		$customize_dashboard->hooks();

		/**
		 * Create our post type and taxonomies
		 */

		//create a custom post type for the vehicles
		add_action( 'init', array( $this, 'create_custom_post_type' ) );

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

		/**
		 * These items make it easier to create themes based on our custom post type
		 */

		//Translate friendly names to actual custom field keys and the other way
		add_filter( 'invp_prefix_meta_key', array( $this, 'translate_custom_field_names' ) );
		add_filter( 'invp_unprefix_meta_key', array( $this, 'untranslate_custom_field_names' ) );

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

		//If Carfax is enabled, add the badge to pages
		if ( isset( $this->settings['use_carfax'] ) && $this->settings['use_carfax'] )
		{
			add_action( 'invp_archive_buttons', array( $this, 'add_carfax_badge' ), 10, 1 );
			add_action( 'invp_single_buttons',  array( $this, 'add_carfax_badge' ), 10, 1 );
		}

		//Allow custom fields to be searched
		$add_custom_fields_to_search = new Add_Custom_Fields_To_Search();
		$add_custom_fields_to_search->hooks();

		//Redirect URLs by VINs to proper vehicle permalinks
		$allow_urls_by_vin = new Vehicle_URLs_By_VIN();
		$allow_urls_by_vin->hooks();

		//Redirect 404 vehicles to make archives
		$redirect_404_vehicles = new Redirect_404_Vehicles();
		$redirect_404_vehicles->hooks();

		add_action( 'invp_delete_all_data', array( $this, 'delete_options' ) );
		//deactivate so the next page load doesn't restore the option & terms
		add_action( 'invp_delete_all_data', array( $this, 'deactivate' ), 99 );

		//Include CSS on the frontend
		add_action( 'wp_enqueue_scripts', array( $this, 'include_scripts_and_styles' ), 11 );

		//Customize the behavior of Yoast SEO, if it is active
		$seo = new Inventory_Presser_SEO();
		$seo->hooks();

		//Modify the URL of an "Email a Friend" menu item on the "Vehicle Details Buttons" menu
		$email_a_friend = new Inventory_Presser_Email_A_Friend();
		$email_a_friend->hooks();

		//Make it possible for a menu item to print the page
		$print_button = new Inventory_Presser_Menu_Item_Print();
		$print_button->hooks();

		//Skip the trash bin and always permanently delete vehicles & photos
		add_action( 'trashed_post', array( $this, 'really_delete' ) );

		//When vehicles are deleted, delete their attachments, too
		add_action( 'before_delete_post', array( $this, 'delete_attachments' ), 10, 1 );

		//Change links to our taxonomy terms to insert /inventory/
		add_filter( 'pre_term_link', array( $this, 'change_term_links' ), 10, 2 );

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
		$shortcodes = new Inventory_Presser_Shortcode_Archive_Vehicle();
		$shortcodes->hooks();
		$shortcodes = new Inventory_Presser_Shortcode_Hours_Today();
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

			//Add a sidebar to the editor when editing vehicles
			$sidebar = new Inventory_Presser_Editor_Sidebar();
			$sidebar->hooks();
		}

		$overlapper = new Inventory_Presser_Taxonomy_Overlapper();
		$overlapper->hooks();
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
		//Include our object definition dependencies
		$file_names = array(
			'class-add-custom-fields-to-search.php',
			'class-additional-listings-pages.php',
			'class-allow-inventory-as-home-page.php',
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
			'class-seo.php',
			'class-shortcode-hours-today.php',
			'class-shortcode-iframe.php',
			'class-shortcode-inventory-grid.php',
			'class-shortcode-inventory-slider.php',
			'class-shortcode-template-shortcode.php',
			'class-shortcode-archive-vehicle.php',
			'class-shortcode-single-vehicle.php',
			'class-taxonomies.php',
			'class-taxonomy-overlapper.php',
			'class-template-provider.php',
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
		//If show carfax buttons
		if( isset( $this->settings['use_carfax'] ) && $this->settings['use_carfax'] )
		{
			//Add CSS for Carfax button text color, based on a Customizer setting
			//Append an inline style just after the current theme's stylesheet
			$style_handle = $this->find_theme_stylesheet_handle();
			$black = '231F20';
			$color = get_theme_mod( 'carfax_text_color', 'black' );
			$color_code = ( $color == 'black' ? $black : 'FFF' );
			$css =
".show-me-the{ fill: #$color_code; }
.carfax-wrapper svg > g:not(#CARFAX_-_Black_Logo):not(#cfx) > path,
.carfax-wrapper #show path{
fill: #$color_code;
stroke: none;
}
g#show path:nth-child(5n),
g#show path:nth-child(8n),
g#show path:nth-child(9n),
g#show path:nth-child(7n),
g#show path:nth-child(6n) {
stroke: none;
}
.carfax-wrapper svg > g#cfx > *:nth-child(13n) {
fill: #$black;
}";
			wp_add_inline_style( $style_handle, $css );
		}

		//Allow dashicons use on frontend
		wp_enqueue_style( 'dashicons' );

		/**
		 * Register stylesheets that will only be enqueued when specific
		 * widgets or shortcodes are used.
		 */
		$plugin_version = get_plugin_data( __FILE__ )['Version'];
		wp_register_style( 'invp-grid', plugins_url( 'css/widget-grid.css', __FILE__ ), [], $plugin_version );
		wp_register_style( 'invp-maximum-price-filters', plugins_url( 'css/widget-maximum-price-filters.css', __FILE__ ), [], $plugin_version );
		wp_register_style( 'invp-epa-fuel-economy', plugins_url( 'css/widget-epa-fuel-economy.css', __FILE__ ), [], $plugin_version );

		/**
		 * Register flexslider and provide overrides for scripts and styles
		 */
		wp_register_script( 'flexslider', plugins_url('/lib/flexslider/jquery.flexslider-min.js', __FILE__ ), array('jquery'), $plugin_version );
		wp_register_script( 'invp-flexslider', plugins_url('/js/flexslider.js', __FILE__ ), array( 'flexslider' ), $plugin_version );

		wp_register_style( 'flexslider', plugins_url( '/lib/flexslider/flexslider.css', __FILE__ ), null, $plugin_version );
		wp_register_style( 'invp-flexslider', plugins_url( '/css/flexslider.css', __FILE__ ), array( 'flexslider' ), $plugin_version );

		/**
		 * Register a stylesheet that will be used by two shortcodes,
		 * [invp-archive-vehicle] and [invp-single-vehicle]
		 */
		wp_register_style(
			'invp-attribute-table',
			plugins_url( '/css/vehicle-attribute-table.css', __FILE__ ),
			null,
			$plugin_version
		);

		//Register a stylesheet for the archive vehicle shortcode
		wp_register_style(
			'invp_archive_vehicle',
			plugins_url( '/css/shortcode-archive-vehicle.css', __FILE__ ),
			null,
			$plugin_version
		);

		//Register a stylesheet for the single vehicle shortcode
		wp_register_style(
			'invp_single_vehicle',
			plugins_url( '/css/shortcode-single-vehicle.css', __FILE__ ),
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
				'meta_prefix' => self::meta_prefix(),
				'is_singular' => is_singular( Inventory_Presser_Plugin::CUSTOM_POST_TYPE ),
			) ); ?>;
		</script><?php
		}
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
			|| $query->query_vars['post_type'] != Inventory_Presser_Plugin::CUSTOM_POST_TYPE )
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
				 * 2. Run it through Inventory_Presser_Vehicle::post_meta_value_is_number
				 */
				$field_start = strpos( $pieces['where'], 'mt' . ( $m+1 ) . '.meta_key = \'')+16;
				$field_end = strpos( $pieces['where'], "'", $field_start )-$field_start;
				$field_name = substr( $pieces['where'], $field_start, $field_end );
				if( Inventory_Presser_Vehicle::post_meta_value_is_number( $field_name ) )
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
	 * really_delete
	 *
	 * Action hook callback. Prevents vehicles from lingering in the Trash after
	 * they've been deleted by always force deleting.
	 * 
	 * @param  int $post_id
	 * @return void
	 */
	function really_delete( $post_id )
	{
		//is the post a vehicle?
		if( self::CUSTOM_POST_TYPE != get_post_type( $post_id ) )
		{
			return;
		}

		//force delete
		wp_delete_post( $post_id, true );
	}
	
	/**
	 * delete_attachments
	 *
	 * Action hook callback. Deletes all a vehicle's attachments when the 
	 * vehicle is deleted.
	 * 
	 * @param  int $post_id
	 * @return void
	 */
	function delete_attachments( $post_id )
	{
		//Is $post_id a vehicle?
		if( self::CUSTOM_POST_TYPE != get_post_type( $post_id ) )
		{
			//No, abort.
			return;
		}

		$vehicle = new Inventory_Presser_Vehicle();
		$vehicle->delete_attachments( $post_id );
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
		foreach( Inventory_Presser_Vehicle::keys_and_types( true ) as $key_arr )
		{
			$key = apply_filters( 'invp_prefix_meta_key', $key_arr['name'] );
			register_post_meta( Inventory_Presser_Plugin::CUSTOM_POST_TYPE, $key, array(
				'show_in_rest' => true,
				'single'       => true,
				'type'         => $key_arr['type'],
			) );
		}

		//Register a meta field for a multi-value options array
		register_post_meta(
			Inventory_Presser_Plugin::CUSTOM_POST_TYPE,
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
			'name' => 'file_date',
			'type' => 'string',
		);
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
	
	/**
	 * translate_custom_field_names
	 *
	 * Prefixes our post meta field keys so 'make' becomes
	 * 'inventory_presser_make'. Careful to not prefix a key that has
	 * already been prefixed.
	 * 
	 * @param  string $nice_name The unprefixed meta key.
	 * @return string The prefixed meta key.
	 */
	function translate_custom_field_names( $nice_name )
	{
		$nice_name = strtolower( $nice_name );
		$prefix = INVP::meta_prefix();

		if( $prefix == substr( $nice_name, 0, strlen( $prefix ) ) )
		{
			return $nice_name;
		}
		return $prefix . $nice_name;
	}

	/**
	 * untranslate_custom_field_names
	 * 
	 * Removes the prefix from our post meta field keys so
	 * 'inventory_presser_make' becomes 'make'. Careful to not damage any
	 * provided key that does not start with our prefix.
	 *
	 * @param  string $meta_key The prefixed meta key.
	 * @return string The un-prefixed meta key.
	 */	
	function untranslate_custom_field_names( $meta_key )
	{
		if( empty( $meta_key ) )
		{
			return '';
		}
		$meta_key = strtolower( $meta_key );
		//prefix may start with an underscore because previous versions hid some meta keys
		$prefix = ( '_' == $meta_key[0] ? '_' : '' ) . INVP::meta_prefix();

		//does $meta_key actually start with the $prefix?
		if( $prefix == substr( $meta_key, 0, strlen( $prefix ) ) )
		{
			//remove the prefix
			return substr( $meta_key, strlen( $prefix ) );
		}

		return $meta_key;
	}
} //end class

$inventory_presser = new Inventory_Presser_Plugin();
$inventory_presser->hooks();
