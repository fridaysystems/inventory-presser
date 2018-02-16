<?php
defined( 'ABSPATH' ) OR exit;
/**
 * Plugin Name: Inventory Presser
 * Plugin URI: http://inventorypresser.com
 * Description: An inventory management plugin for Car Dealers. Create or import an automobile or powersports dealership inventory.
 * Version: 3.9.0
 * Author: Corey Salzano, John Norton
 * Author URI: https://profiles.wordpress.org/salzano
 * Text Domain: inventory-presser
 * Domain Path: /languages
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

//Include our object definition dependencies
$inventory_presser_include_paths = array(
	'class-add-custom-fields-to-search.php',
	'class-customize-admin-dashboard.php',
	'class-dealership-options.php',
	'class-fuel-economy-widget.php',
	'class-menu-item-email-a-friend.php',
	'class-option-manager.php',
	'class-order-by-post-meta-widget.php',
	'class-redirect-404-vehicles.php',
	'class-reports.php',
	'class-seo.php',
	'class-shortcodes.php',
	'class-taxonomies.php',
	'class-vehicle.php',
	'class-vehicle-urls-by-vin.php',
	'class-widgets.php',
);
foreach( $inventory_presser_include_paths as $path ) {
	$path = plugin_dir_path( __FILE__ ) . 'includes/' . $path;
	if( file_exists( $path ) ) {
		require $path;
	}
}

if ( ! class_exists( 'Inventory_Presser_Plugin' ) ) {
	class Inventory_Presser_Plugin {

		const CUSTOM_POST_TYPE = 'inventory_vehicle';
		var $taxonomies;
		var $settings; //this plugin's options

		//some post meta keys are prefixed with an underscore to hide them
		private $prefixed_meta_keys = array(
			'car_id',
			'dealer_id',
			'edmunds_style_id',
			'epa_fuel_economy',
			'last_modified',
			'photo_number',
		);

		function add_orderby_to_query( $query ) {
			//Do not mess with the query if it's not the main one and our CPT
			if ( ! $query->is_main_query() || ! is_post_type_archive( self::CUSTOM_POST_TYPE ) ) {
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
			if( isset( $_GET['orderby'] ) ) {
				$key = $_GET['orderby'];
				if( isset( $_GET['order'] ) ) {
					$direction = $_GET['order'];
				}
			} else {
				$key = $this->settings['sort_vehicles_by'];
			}
			$query->set( 'meta_key', $key );

			//maybe append to the meta_query if it is already set
			$old = $query->get( 'meta_query', array() );
			switch( $query->query_vars['meta_key'] ) {

				//MAKE
				case 'inventory_presser_make':
					$query->set( 'meta_query', array_merge( $old, array(
							'relation' => 'AND',
							array( 'key' => 'inventory_presser_model', 'compare' => 'EXISTS' ),
							array( 'key' => 'inventory_presser_trim', 'compare' => 'EXISTS' ),
						)
					) );
					break;

				//MODEL
				case 'inventory_presser_model':
					$query->set( 'meta_query', array_merge( $old, array(
							'relation' => 'AND',
							array( 'key' => 'inventory_presser_model', 'compare' => 'EXISTS' ),
							array( 'key' => 'inventory_presser_trim', 'compare' => 'EXISTS' ),
						)
					) );
					break;

				//YEAR
				case 'inventory_presser_year':
					$query->set( 'meta_query', array_merge( $old, array(
							'relation' => 'AND',
							array( 'key' => 'inventory_presser_year', 'compare' => 'EXISTS' ),
							array( 'key' => 'inventory_presser_make', 'compare' => 'EXISTS' ),
							array( 'key' => 'inventory_presser_model', 'compare' => 'EXISTS' ),
							array( 'key' => 'inventory_presser_trim', 'compare' => 'EXISTS' ),
						)
					) );
					break;
			}

			//Allow other developers to decide if the post meta values are numbers
			$vehicle = new Inventory_Presser_Vehicle(); //UGLY: to add a filter before this next line
			$meta_value_or_meta_value_num = apply_filters( 'inventory_presser_meta_value_or_meta_value_num', 'meta_value', $key );
			$query->set( 'orderby', $meta_value_or_meta_value_num );
			$query->set( 'order', $direction );
		}

		function add_pretty_search_urls( ) {
			global $wp_rewrite;
			$wp_rewrite->rules = $this->generate_rewrite_rules( self::CUSTOM_POST_TYPE ) + $wp_rewrite->rules;
		}

		function hooks( ) {

			//Allow translations
			add_action( 'plugins_loaded', function() {
				load_plugin_textdomain( 'inventory-presser', false, __DIR__ );
			} );

			//Modify the administrator dashboard
			$customize_dashboard = new Inventory_Presser_Customize_Admin_Dashboard( self::CUSTOM_POST_TYPE );
			$customize_dashboard->hooks();

			/**
			 * Create our post type and taxonomies
			 */

			//create a custom post type for the vehicles
			add_action( 'init', array( $this, 'create_custom_post_type' ) );

			//Create custom taxonomies
			$this->taxonomies = new Inventory_Presser_Taxonomies( self::CUSTOM_POST_TYPE );
			$this->taxonomies->hooks();

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
			register_activation_hook( __FILE__, array( $this, 'my_rewrite_flush' ) );

			//Do some things during deactivation
			register_deactivation_hook( __FILE__, array( $this, 'delete_rewrite_rules_option' ) );

			//Populate our custom taxonomies with default terms
			register_activation_hook( __FILE__, 'invp_populate_default_terms' );

			/**
			 * These items make it easier to create themes based on our custom post type
			 */

			//Translate friendly names to actual custom field keys and the other way
			add_filter( 'translate_meta_field_key', array( $this, 'translate_custom_field_names' ) );
			add_filter( 'untranslate_meta_field_key', array( $this, 'untranslate_custom_field_names' ) );

			/**
			 * Make a widget available to sort vehicles by post meta fields.
			 * Or, enable order by year, make, price, odometer, etc.
			 */
			$widget_available = new Order_By_Widget();
			//Register the widget
	 		add_action( 'widgets_init', create_function( '', 'return register_widget( "Order_By_Widget" );' ) );

			/**
			 * Make a widget available to show EPA Fuel Economy data
			 */
			$widget_available = new Fuel_Economy_Widget();
			//Register the widget
	 		add_action( 'widgets_init', create_function( '', 'return register_widget( "Fuel_Economy_Widget" );' ) );

			/**
			 * Deliver our promise to order posts, change the ORDER BY clause of
			 * the query that's fetching post objects.
			 */

			$this->settings = $this->settings();
			if( ! is_admin() && ( isset( $_GET['orderby'] ) || isset( $this->settings['sort_vehicles_by'] ) ) ) {
				add_action( 'pre_get_posts', array( $this, 'add_orderby_to_query' ) );
			}

			//Allow custom fields to be searched
			$add_custom_fields_to_search = new Add_Custom_Fields_To_Search();
			$add_custom_fields_to_search->hooks();

			//Redirect URLs by VINs to proper vehicle permalinks
			$allow_urls_by_vin = new Vehicle_URLs_By_VIN();

			//Redirect 404 vehicles to make archives
			$redirect_404_vehicles = new Redirect_404_Vehicles();
			$redirect_404_vehicles->hooks();

			add_action( 'inventory_presser_delete_all_data', array( $this, 'delete_options' ) );
			//deactivate so the next page load doesn't restore the option & terms
			add_action( 'inventory_presser_delete_all_data', array( $this, 'deactivate' ), 99 );

			//Include CSS on the frontend
			add_action( 'wp_enqueue_scripts', array( $this, 'include_styles' ), 11 );

			//Customize the behavior of Yoast SEO, if it is active
			$seo = new Inventory_Presser_SEO();
			$seo->hooks();

			//Modify the URL of an "Email a Friend" menu item on the "Vehicle Details Buttons" menu
			$email_a_friend = new Inventory_Presser_Email_A_Friend();
			$email_a_friend->hooks();

			//Skip the trash bin and always permanently delete vehicles
			add_action( 'trashed_post', array( $this, 'skip_trash' ) );
		}

		function create_custom_post_type( ) {
			//creates a custom post type that will be used by this plugin
			register_post_type(
				self::CUSTOM_POST_TYPE,
				apply_filters(
					'inventory_presser_post_type_args',
					array (
						'description'  => __( 'Vehicles for sale in an automobile or powersports dealership', 'inventory-presser' ),
						/**
						 * Check if the theme (or the parent theme) has a CPT
						 * archive template.  If not, we will assume that the
						 * inventory is going to be displayed via shortcode, and
						 * we won't be using the theme archive
						 */
						'has_archive'  => file_exists( get_template_directory().'/archive-'.self::CUSTOM_POST_TYPE.'.php' )
							|| file_exists( get_stylesheet_directory().'/archive-'.self::CUSTOM_POST_TYPE.'.php' ),

						'hierarchical' => false,
						'labels' => array (
							'name'          => __( 'Vehicles', 'inventory-presser' ),
							'singular_name' => __( 'Vehicle', 'inventory-presser' ),
							'all_items'     => __( 'Inventory', 'inventory-presser' ),
							'add_new_item'  => __( 'Add New Vehicle', 'inventory-presser' ),
							'edit_item'     => __( 'Edit Vehicle', 'inventory-presser' ),
						),
						'menu_icon'    => 'dashicons-admin-network',
						'menu_position'=> 5, //below Posts
						'public'       => true,
						'rest_base'    => 'vehicles',
						'rewrite'      => array ( 'slug' => 'inventory' ),
						'show_in_rest' => true,
						'supports'     => array (
									'editor',
									'title',
									'thumbnail',
								  ),
						'taxonomies'   => $this->taxonomies->query_vars_array(),
					)
				)
			);
		}

		function deactivate() {
			deactivate_plugins( plugin_basename( __FILE__ ) );
		}

		function delete_options() {
			delete_option( '_dealer_settings' );
			delete_option( '_dealer_settings_edmunds' );
		}

		function delete_rewrite_rules_option( ) {
			/**
			 * This is called during plugin deactivation
			 * delete the rewrite_rules option so the rewrite rules
			 * are generated on the next page load without ours.
			 * this is a weird thing and is described here http://wordpress.stackexchange.com/a/44337/13090
			 */
			delete_option('rewrite_rules');
		}

		// generate every possible combination of rewrite rules, including 'page', based on post type taxonomy
		// from http://thereforei.am/2011/10/28/advanced-taxonomy-queries-with-pretty-urls/
		function generate_rewrite_rules( $post_type, $query_vars = array() ) {
		    global $wp_rewrite;

		    if( ! is_object( $post_type ) )
		        $post_type = get_post_type_object( $post_type );

		    $new_rewrite_rules = array();

		    $taxonomies = get_object_taxonomies( $post_type->name, 'objects' );

		    // Add taxonomy filters to the query vars array
		    foreach( $taxonomies as $taxonomy ) {
		        $query_vars[] = $taxonomy->query_var;
			}

		    // Loop over all the possible combinations of the query vars
		    for( $i = 1; $i <= count( $query_vars );  $i++ ) {

		        $new_rewrite_rule =  $post_type->rewrite['slug'] . '/';
		        $new_query_string = 'index.php?post_type=' . $post_type->name;

		        // Prepend the rewrites & queries
		        for( $n = 1; $n <= $i; $n++ ) {
		            $new_rewrite_rule .= '(' . implode( '|', $query_vars ) . ')/(.+?)/';
		            $new_query_string .= '&' . $wp_rewrite->preg_index( $n * 2 - 1 ) . '=' . $wp_rewrite->preg_index( $n * 2 );
		        }

		        // Allow paging of filtered post type - WordPress expects 'page' in the URL but uses 'paged' in the query string so paging doesn't fit into our regex
		        $new_paged_rewrite_rule = $new_rewrite_rule . 'page/([0-9]{1,})/';
		        $new_paged_query_string = $new_query_string . '&paged=' . $wp_rewrite->preg_index( $i * 2 + 1 );

		        // Make the trailing backslash optional
		        $new_paged_rewrite_rule = $new_paged_rewrite_rule . '?$';
		        $new_rewrite_rule = $new_rewrite_rule . '?$';

		        // Add the new rewrites
		        $new_rewrite_rules = array( $new_paged_rewrite_rule => $new_paged_query_string,
		                                    $new_rewrite_rule       => $new_query_string )
		                             + $new_rewrite_rules;
		    }

		    return $new_rewrite_rules;
		}

		/**
		 * Given a string, return the last word.
		 */
		function get_last_word( $str ) {
			$pieces = explode( ' ', rtrim( $str ) );
			return array_pop( $pieces );
		}

		//What is the registered handle of the active theme's stylesheet?
		private function find_theme_stylesheet_handle() {
			global $wp_styles;

			foreach( $wp_styles->registered as $handle => $style_obj ) {
				if( $style_obj->src === get_stylesheet_directory_uri() . '/style.css' ) {
					return $handle;
				}
			}
			return null;
		}

		function include_styles() {
			//If show carfax buttons
			if( isset( $this->settings['use_carfax'] ) && $this->settings['use_carfax'] ) {
				//Add CSS for Carfax button text color, based on a Customizer setting
				//Append an inline style just after the current theme's stylesheet
				$style_handle = $this->find_theme_stylesheet_handle();
				$color = get_theme_mod( 'carfax_text_color', 'black' );
				$css = '.show-me-the{ fill: #' . ( $color == 'black' ? '231F20' : 'FFFFFF' ) . '; }';
				wp_add_inline_style( $style_handle, $css );
			}

			//Allow dashicons use on frontend
			wp_enqueue_style( 'dashicons' );
		}

		function modify_query_orderby( $pieces ) {
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
			if( 0 < $meta_field_count ) {
				$replacement = $pieces['orderby'] . ', ';
				$vehicle = new Inventory_Presser_Vehicle();
				for( $m=0; $m<$meta_field_count; $m++ ) {
					$replacement .= 'mt' . ( $m+1 ) . '.meta_value';
					/**
					 * Determine if this meta field should be sorted as a number
					 * 1. Parse out the meta key name from $pieces['where']
					 * 2. Run it through $vehicle->post_meta_value_is_number
					 */
					$field_start = strpos( $pieces['where'], 'mt' . ( $m+1 ) . '.meta_key = \'')+16;
					$field_end = strpos( $pieces['where'], "'", $field_start )-$field_start;
					$field_name = substr( $pieces['where'], $field_start, $field_end );
					if( $vehicle->post_meta_value_is_number( $field_name ) ) {
						$replacement .= '+0';
					}

					$replacement .= $direction;
					if( $m < ( $meta_field_count-1 ) ) {
						$replacement .= ', ';
					}
				}

				$pieces['orderby'] = $replacement;
			}
			return $pieces;
		}

		function my_rewrite_flush( ) {
			//http://codex.wordpress.org/Function_Reference/register_post_type#Flushing_Rewrite_on_Activation

			// First, we "add" the custom post type via the above written function.
			// Note: "add" is written with quotes, as CPTs don't get added to the DB,
			// They are only referenced in the post_type column with a post entry,
			// when you add a post of this CPT.
			$this->create_custom_post_type( );

			// ATTENTION: This is *only* done during plugin activation hook in this example!
			// You should *NEVER EVER* do this on every page load!!
			flush_rewrite_rules( );
		}

		function translate_custom_field_names( $nice_name ) {
			$nice_name = strtolower( $nice_name );
			return ( in_array( $nice_name, $this->prefixed_meta_keys ) ? '_' : '' ) . 'inventory_presser_' . $nice_name;
		}

		function untranslate_custom_field_names( $meta_key ) {
			if( empty( $meta_key ) ) { return ''; }
			$meta_key = strtolower( $meta_key );
			$prefix = ( '_' == $meta_key[0] ? '_' : '' ) . 'inventory_presser_';
			//remove the prefix
			return substr( $meta_key, strlen( $prefix ) );
		}

		//Get this plugin's Options page settings mingled with default values
		function settings() {
			$defaults = array(
				'sort_vehicles_by' => apply_filters( 'translate_meta_field_key', 'make' ),
				'sort_vehicles_order' => 'ASC',
			);
			return wp_parse_args( get_option( '_dealer_settings' ), $defaults );
		}

		function skip_trash( $post_id ) {
			//is the post a vehicle?
			if( self::CUSTOM_POST_TYPE == get_post_type( $post_id ) ) {
				//force delete
				wp_delete_post( $post_id, true );
			}
		}

	} //end class
	$inventory_presser = new Inventory_Presser_Plugin();
	$inventory_presser->hooks();

	//Populate our taxonomies with terms if they do not already exist
	function invp_populate_default_terms() {

		$taxonomies_obj = new Inventory_Presser_Taxonomies();

		//create the taxonomies or else our wp_insert_term calls will fail
		$taxonomies_obj->create_custom_taxonomies();

		$taxonomy_data = $taxonomies_obj->taxonomy_data();
		for( $i=0; $i<sizeof( $taxonomy_data ); $i++ ) {
			foreach( $taxonomy_data[$i]['term_data'] as $abbr => $desc ) {
				$taxonomy_name = $taxonomies_obj->convert_hyphens_to_underscores( $taxonomy_data[$i]['args']['query_var'] );
				if ( ! is_array( term_exists( $desc, $taxonomy_name ) ) ) {
					$term_exists = wp_insert_term(
						$desc,
						$taxonomy_name,
						array (
							'description' => $desc,
							'slug' => $abbr,
						)
					);
				}
			}
		}
	}
} //end if
