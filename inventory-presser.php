<?php
defined( 'ABSPATH' ) OR exit;
/**
 * Plugin Name: Inventory Presser
 * Plugin URI: http://inventorypresser.com
 * Description: An inventory management plugin for Car Dealers. Import or create an automobile or powersports dealership inventory.
 * Version: 1.3.0
 * Author: Corey Salzano
 * Author URI: https://profiles.wordpress.org/salzano
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! class_exists( 'Inventory_Presser_Option_Manager' ) ) {
	$class_option_manager = plugin_dir_path( __FILE__ ) . 'includes/class-option-manager.php';
	if ( file_exists( $class_option_manager ) ) {
		require $class_option_manager;
	}
}

if ( ! class_exists( 'Inventory_Presser_Vehicle' ) ) {
	$class_vehicle = plugin_dir_path( __FILE__ ) . 'includes/class-inventory-presser-vehicle.php';
	if ( file_exists( $class_vehicle ) ) {
		require $class_vehicle;
	}
}

if ( ! class_exists( 'Inventory_Presser_Vehicle_Shortcodes' ) ) {
	$class_shortcode = plugin_dir_path( __FILE__ ) . 'includes/class-shortcodes.php';
	if ( file_exists( $class_shortcode ) ) {
		require $class_shortcode;
	}
}

if ( ! class_exists( 'Inventory_Presser_Plugin' ) ) {
	class Inventory_Presser_Plugin {

		const CUSTOM_POST_TYPE = 'inventory_vehicle';

		function add_orderby_to_query( $query ) {
			//Do not mess with the query if it's not the main one and our CPT
			if ( ! $query->is_main_query() || ! is_post_type_archive( self::CUSTOM_POST_TYPE ) ) {
				return;
			}

			add_filter( 'posts_clauses', array( &$this, 'modify_query_orderby' ) );

			/**
			 * The field we want to order by is either in $_GET['orderby'] when
			 * the user has chosen to reorder posts or saved in the plugin
			 * settings 'default-sort-key.' The sort direction is in
			 * $_GET['order'] or 'default-sort-order.'
			 */
			if( isset( $_GET['orderby'] ) ) {
				$key = $_GET['orderby'];
				$direction = $_GET['order'];
			} else {
				$option_manager = new Inventory_Presser_Option_Manager();
				$options = $option_manager->get_options();
				$key = $options['default-sort-key'];
				$direction = $options['default-sort-order'];
			}

			$query->set( 'meta_key', $key );
			switch( $query->query_vars['meta_key'] ) {

				//MAKE
				case 'inventory_presser_make':
					$query->set( 'meta_query', array(
							'relation' => 'AND',
							array( 'key' => 'inventory_presser_model', 'compare' => 'EXISTS' ),
							array( 'key' => 'inventory_presser_trim', 'compare' => 'EXISTS' ),
						)
					);
					break;

				//MODEL
				case 'inventory_presser_model':
					$query->set( 'meta_query', array(
							'relation' => 'AND',
							array( 'key' => 'inventory_presser_model', 'compare' => 'EXISTS' ),
							array( 'key' => 'inventory_presser_trim', 'compare' => 'EXISTS' ),
						)
					);
					break;

				//YEAR
				case 'inventory_presser_year':
					$query->set( 'meta_query', array(
							'relation' => 'AND',
							array( 'key' => 'inventory_presser_year', 'compare' => 'EXISTS' ),
							array( 'key' => 'inventory_presser_make', 'compare' => 'EXISTS' ),
							array( 'key' => 'inventory_presser_model', 'compare' => 'EXISTS' ),
							array( 'key' => 'inventory_presser_trim', 'compare' => 'EXISTS' ),
						)
					);
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
			$wp_rewrite->rules = $this->generate_rewrite_rules(self::CUSTOM_POST_TYPE) + $wp_rewrite->rules;
		}

		function __construct( ) {

			/**
			 * Modify the administrator dashboard
			 */
			$class_customize_dashboard = plugin_dir_path( __FILE__ ) . 'includes/class-customize-admin-dashboard.php';
			if( file_exists( $class_customize_dashboard ) ) {
				require $class_customize_dashboard;
				$customize_dashboard = new Inventory_Presser_Customize_Admin_Dashboard( $this->post_type() );
			}


			/**
			 * Create our post type and taxonomies
			 */

			//create a custom post type for the vehicles
			add_action( 'init', array( &$this, 'create_custom_post_type' ) );

			//create custom taxonomies for vehicles
			add_action( 'init', array( &$this, 'create_custom_taxonomies' ) );


			/**
			 * Some custom rewrite rules are created and destroyed
			 */

			//Add custom rewrite rules
			add_action('generate_rewrite_rules', array( &$this, 'add_pretty_search_urls' ) );

			/**
			 * Activation and deactivation hooks ensure that the rewrite rules are
			 * flushed to add and remove our custom rewrite rules
			 */

			//Flush rewrite rules when the plugin is activated
			register_activation_hook( __FILE__, array( &$this, 'my_rewrite_flush' ) );

			//Do some things during deactivation
			register_deactivation_hook( __FILE__, array( &$this, 'delete_rewrite_rules_option' ) );

			/**
			 * These items make it easier to create themes based on our custom post type
			 */

			//Translate friendly names to actual custom field keys
			add_filter( 'translate_meta_field_key', array( &$this, 'translate_custom_field_names' ) );

			/**
			 * Affect the way some importers work
			 */
			require plugin_dir_path( __FILE__ ) . 'includes/class-modify-imports.php';
			$option_manager = new Inventory_Presser_Option_Manager();
			$options = $option_manager->get_options( );
			if( ! isset( $options['delete-vehicles-not-in-new-feeds'] ) ) {
				$default_options = $option_manager->get_default_options();
				$options['delete-vehicles-not-in-new-feeds'] = $default_options['delete-vehicles-not-in-new-feeds'];
				$option_manager->save_options( $options );
			}
			$modify_imports = new Inventory_Presser_Modify_Imports( $this->post_type(), $options['delete-vehicles-not-in-new-feeds'] );

			/**
			 * Make a widget available to sort vehicles by post meta fields.
			 * Or, enable order by year, make, price, odometer, etc.
			 */
			$class_order_by_widget = plugin_dir_path( __FILE__ ) . 'includes/class-order-by-post-meta-widget.php';
			if( file_exists( $class_order_by_widget ) ) {
				require $class_order_by_widget;
				$widget_available = new Order_By_Widget();
				//Register the widget
		 		add_action( 'widgets_init', create_function( '', 'return register_widget( "Order_By_Widget" );' ) );
			}

			/**
			 * Deliver our promise to order posts, change the ORDER BY clause of
			 * the query that's fetching post objects.
			 */
			if( ! is_admin() && ( isset( $_GET['orderby'] ) || isset( $options['default-sort-key'] ) ) ) {
				add_action( 'pre_get_posts', array( &$this, 'add_orderby_to_query' ) );
			}

			//Allow custom fields to be searched
			if ( ! class_exists( 'Add_Custom_Fields_To_Search' ) ) {
				$class_search = plugin_dir_path( __FILE__ ) . 'includes/class-add-custom-fields-to-search.php';
				if ( file_exists( $class_search ) ) {
					require $class_search;
					$add_custom_fields_to_search = new Add_Custom_Fields_To_Search();
				}
			}

			//Redirect URLs by VINs to proper vehicle permalinks
			$class_vin_urls = plugin_dir_path( __FILE__ ) . 'includes/class-vehicle-urls-by-vin.php';
			if( file_exists( $class_vin_urls ) ) {
				require $class_vin_urls;
				$allow_urls_by_vin = new Vehicle_URLs_By_VIN();
			}

			// location taxonomy admin actions
			add_action( 'location_add_form_fields', array( &$this, 'add_location_fields'), 10, 2 );
			add_action( 'created_location', array( &$this, 'save_location_meta'), 10, 2 );
			add_action( 'location_edit_form_fields', array( &$this, 'edit_location_field'), 10, 2 );
			add_action( 'edited_location', array( &$this, 'update_location_meta'), 10, 2 );

			add_action( 'inventory_presser_delete_all_data', array( &$this, 'delete_term_data' ) );
			add_action( 'inventory_presser_delete_all_data', array( &$this, 'delete_options' ) );
			//deactivate so the next page load doesn't restore the option & terms
			add_action( 'inventory_presser_delete_all_data', array( &$this, 'deactivate' ) );

		}

		function create_custom_post_type( ) {

			//creates a custom post type that will be used by this plugin
			register_post_type(
				$this->post_type(),
				apply_filters(
					'inventory_presser_post_type_args',
					array (
						'description'  => __('Vehicles for sale in an automobile or powersports dealership'),
						// check if the theme has a CPT archive template.  If not, we will assume that the inventory is going to
						// be displayed via shortcode, and we won't be using the theme archive
						'has_archive'  => file_exists(get_stylesheet_directory().'/archive-'.self::CUSTOM_POST_TYPE.'.php'),
						'hierarchical' => false,
						'labels' => array (
							'name'          => __( 'Vehicles' ),
							'singular_name' => __( 'Vehicle' ),
							'all_items'     => __( 'All Vehicles' ),
							'add_new_item'  => __( 'Add New Vehicle' ),
							'edit_item'     => __( 'Edit Vehicle' ),
						),
						'menu_icon'    => 'dashicons-admin-network',
						'menu_position'=> 5, //below Posts
						'public'       => true,
						'rewrite'      => array ( 'slug' => 'inventory' ),
						'supports'     => array (
									'editor',
									'title',
									'thumbnail',
								  ),
						'taxonomies'   => array (
									//'post_tag', //Allow tags
									'Model year',
									'Make',
									'Model',
									'Availability',
									'Condition',
									'Drive type',
									'Fuel',
									'Transmission',
									'Type',
								  ),

					)
				)
			);
		}

		function create_custom_taxonomies( ) {
			//loop over this data, register the taxonomies, and populate the terms if needed
			$taxonomy_data = $this->taxonomy_data();
			for( $i=0; $i<sizeof( $taxonomy_data ); $i++ ) {
				//create the taxonomy
				$taxonomy_name = str_replace( '-', '_', $taxonomy_data[$i]['args']['query_var'] );
				register_taxonomy( $taxonomy_name, $this->post_type(), $taxonomy_data[$i]['args'] );

				/* populate the taxonomy we just created with terms if they do not
				 * already exist
				 */
				foreach( $taxonomy_data[$i]['term_data'] as $abbr => $desc ) {
					if ( !is_array( term_exists( $desc, $taxonomy_name ) ) ) {
						wp_insert_term( $desc, $taxonomy_name,
							array (
								'description' => $desc,
								'slug' => $abbr,
							)
						);
					}
				}
			}
		}

		function deactivate() {
			deactivate_plugins( plugin_basename( __FILE__ ) );
		}

		function delete_options() {
			$option_manager = new Inventory_Presser_Option_Manager();
			$option_manager->delete_options();
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

		function delete_term_data() {
			//remove the terms in taxonomies
			$taxonomy_data = $this->taxonomy_data();
			for( $i=0; $i<sizeof( $taxonomy_data ); $i++ ) {
				$tax = $taxonomy_data[$i]['args']['label'];
				$terms = get_terms( $tax, array( 'fields' => 'ids', 'hide_empty' => false ) );
				foreach ( $terms as $value ) {
					wp_delete_term( $value, $tax );
				}
			}
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

		function get_term_slug( $taxonomy_name, $post_id ) {
			$terms = wp_get_object_terms( $post_id, $taxonomy_name, array( 'orderby' => 'term_id', 'order' => 'ASC' ) );
			if ( ! is_wp_error( $terms ) ) {
				if ( isset( $terms[0] ) && isset( $terms[0]->name ) ) {
					return $terms[0]->slug;
				}
			}
			return '';
		}

		function meta_box_html_condition( $post ) {
			echo $this->taxonomy_meta_box_html( 'condition', 'inventory_presser_condition', $post );
		}

		function meta_box_html_cylinders( $post ) {
			echo $this->taxonomy_meta_box_html( 'cylinders', 'inventory_presser_cylinders', $post );
		}

		function meta_box_html_availability( $post ) {
			echo $this->taxonomy_meta_box_html( 'availability', 'inventory_presser_availability', $post );
		}

 		function meta_box_html_drive_type( $post ) {
 			echo $this->taxonomy_meta_box_html( 'drive_type', 'inventory_presser_drive_type', $post );
		}

 		function meta_box_html_fuel( $post ) {
 			echo $this->taxonomy_meta_box_html( 'fuel', 'inventory_presser_fuel', $post );
		}

 		function meta_box_html_transmission( $post ) {
 			echo $this->taxonomy_meta_box_html( 'transmission', apply_filters( 'translate_meta_field_key', 'transmission' ), $post );
		}

		function meta_box_html_type( $post ) {
			echo $this->taxonomy_meta_box_html( 'type', apply_filters( 'translate_meta_field_key', 'type' ), $post );
		}

		function meta_box_html_locations( $post ) {
			echo $this->taxonomy_meta_box_html( 'location', 'inventory_presser_location', $post );
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

		function post_type() {
			return apply_filters( 'inventory_presser_post_type', self::CUSTOM_POST_TYPE );
		}

		//this is an array of taxonomy names and the corresponding arrays of term data
		function taxonomy_data( ) {
			return apply_filters(
				'inventory_presser_taxonomy_data',
				array (
					array (
						'args' => array (
							'hierarchical'   => true,
							'label'          => 'Model year',
							'labels'         => array (
							       'name'          => 'Model years',
							       'singular_name' => 'Model year',
							       'search_items'  => 'Search years',
							       'popular_items' => 'Popular years',
							       'all_items'     => 'All years',
							),
							'meta_box_cb'    => null,
							'query_var'      => 'model-year',
							'singular_label' => 'Model year',
							'show_in_menu'   => false,
							'show_ui'        => false,
						),
						'term_data' => array (	),
					),
					array (
						'args' => array (
							'hierarchical'   => true,
							'label'          => 'Make',
							'labels'         => array (
							       'name'          => 'Makes',
							       'singular_name' => 'Make',
							       'search_items'  => 'Search makes',
							       'popular_items' => 'Popular makes',
							       'all_items'     => 'All makes',
							),
							'meta_box_cb'    => null,
							'query_var'      => 'make',
							'singular_label' => 'Make',
							'show_in_menu'   => false,
							'show_ui'        => false,
						),
						'term_data' => array (	),
					),
					array (
						'args' => array (
							'hierarchical'   => true,
							'label'          => 'Model',
							'labels'         => array (
							       'name'          => 'Models',
							       'singular_name' => 'Model',
							       'search_items'  => 'Search models',
							       'popular_items' => 'Popular models',
							       'all_items'     => 'All models',
							),
							'meta_box_cb'    => null,
							'query_var'      => 'model',
							'singular_label' => 'Model',
							'show_in_menu'   => false,
							'show_ui'        => false,
						),
						'term_data' => array (	),
					),
					array (
						'args' => array (
							'hierarchical'   => true,
							'label'          => 'Condition',
							'labels'         => array (
							       'name'          => 'Conditions',
							       'singular_name' => 'Condition',
							       'search_items'  => 'Search new and used',
							       'popular_items' => 'Popular conditions',
							       'all_items'     => 'All new and used',
							),
							'meta_box_cb'    => array( $this, 'meta_box_html_condition' ),
							'query_var'      => 'condition',
							'singular_label' => 'Condition',
							'show_in_menu'   => false,
						),
						'term_data' =>	array (
									'New'  => 'New',
									'Used' => 'Used',
								),
					),
					array (
						'args' => array (
							'hierarchical'   => true,
							'label'          => 'Type',
							'labels'         => array (
							       'name'          => 'Types',
							       'singular_name' => 'Type',
							       'search_items'  => 'Search types',
							       'popular_items' => 'Popular types',
							       'all_items'     => 'All types',
							),
							'meta_box_cb'    => array( $this, 'meta_box_html_type' ),
							'query_var'      => 'type',
							'singular_label' => 'Type',
							'show_in_menu'   => false,
						),
						'term_data' =>	array (
									'ATV' => 'All Terrain Vehicle',
									'CAR' => 'Passenger Car',
									'MOT' => 'Motorcycle',
									'OTH' => 'Other',
									'RV'  => 'Recreational Vehicle',
									'SUV' => 'Sport Utility Vehicle',
									'TRLR'=> 'Trailer',
									'TRU' => 'Truck',
									'VAN' => 'Van',
								),
					),
					array (
						'args' => array (
							'hierarchical'   => true,
							'label'          => 'Availability',
							'labels'         => array (
							       'name'          => 'Availabilities',
							       'singular_name' => 'Availability',
							       'search_items'  => 'Search availabilities',
							       'popular_items' => 'Popular availabilities',
							       'all_items'     => 'All sold and for sale',
							),
							'meta_box_cb'    => array( $this, 'meta_box_html_availability' ),
							'query_var'      => 'availability',
							'singular_label' => 'Availability',
							'show_in_menu'   => false,
						),
						'term_data' =>	array (
									'For sale' => 'For sale',
									'Sold'     => 'Sold',
								),
					),
					array (
						'args' => array (
							'hierarchical'   => true,
							'label'          => 'Drive type',
							'labels'         => array (
							       'name'          => 'Drive types',
							       'singular_name' => 'Drive type',
							       'search_items'  => 'Search drive types',
							       'popular_items' => 'Popular drive types',
							       'all_items'     => 'All drive types',
							),
							'meta_box_cb'    => array( $this, 'meta_box_html_drive_type' ),
							'query_var'      => 'drive-type',
							'singular_label' => 'Drive type',
							'show_in_menu'   => false,
						),
						'term_data' =>	array (
									'4FD' => 'Front Wheel Drive w/4x4',
									'4RD' => 'Rear Wheel Drive w/4x4',
									'2WD' => 'Two Wheel Drive',
									'4WD' => 'Four Wheel Drive',
									'AWD' => 'All Wheel Drive',
									'FWD' => 'Front Wheel Drive',
									'RWD' => 'Rear Wheel Drive',
								),
					),
					array (
						'args' => array (
							'hierarchical'   => true,
							'label'          => 'Fuel',
							'labels'         => array (
							       'name'          => 'Fuel types',
							       'singular_name' => 'Fuel type',
							       'search_items'  => 'Search fuel types',
							       'popular_items' => 'Popular fuel types',
							       'all_items'     => 'All fuel types',
							),
							'meta_box_cb'    => array( $this, 'meta_box_html_fuel' ),
							'query_var'      => 'fuel',
							'singular_label' => 'Fuel',
							'show_in_menu'   => false,
						),
						'term_data' =>	array (
									'B' => 'Electric and Gas Hybrid',
									'D' => 'Diesel',
									'E' => 'Electric',
									'F' => 'Flexible',
									'C' => 'Gas',
									'N' => 'Compressed Natural Gas',
									'P' => 'Propane',
									'R' => 'Hydrogen Fuel Cell',
									'U' => 'Unknown',
									'Y' => 'Electric and Diesel Hybrid',
								),
					),
					array (
						'args' => array (
							'hierarchical'   => true,
							'label'          => 'Transmission',
							'labels'         => array (
							       'name'          => 'Transmissions',
							       'singular_name' => 'Transmission',
							       'search_items'  => 'Search transmissions',
							       'popular_items' => 'Popular transmissions',
							       'all_items'     => 'All transmissions',
							),
							'meta_box_cb'    => array( $this, 'meta_box_html_transmission' ),
							'query_var'      => 'transmission',
							'singular_label' => 'Transmission',
							'show_in_menu'   => false,
						),
						'term_data' =>	array (
									'A' => 'Automatic',
									'E' => 'ECVT',
									'M' => 'Manual',
									'U' => 'Unknown',
								),
					),
					array (
						'args' => array (
							'hierarchical'   => true,
							'label'          => 'Cylinders',
							'labels'         => array (
							       'name'          => 'Cylinders',
							       'singular_name' => 'Cylinder count',
							       'search_items'  => 'Search cylinder counts',
							       'popular_items' => 'Popular cylinder counts',
							       'all_items'     => 'All cylinder counts',
							),
							'meta_box_cb'    => array( $this, 'meta_box_html_cylinders' ),
							'query_var'      => 'cylinders',
							'singular_label' => 'Cylinders',
							'show_in_menu'   => false,
						),
						'term_data' => array (
							'1'  => '1',
							'2'  => '2',
							'3'  => '3',
							'4'  => '4',
							'5'  => '5',
							'6'  => '6',
							'8'  => '8',
							'10' => '10',
							'12' => '12',
						),
					),
					array (
						'args' => array (
							'hierarchical'   => false,
							'label'          => 'Locations',
							'labels'         => array (
								'name'          => 'Location',
								'singular_name' => 'Location',
								'search_items'  => 'Search Locations',
								'popular_items' => 'Popular Locations',
								'all_items'     => 'All Locations',
								'edit_item'     => __( 'Edit Location' ),
								'update_item'   => __( 'Update Location' ),
								'add_new_item'  => __( 'Add New Location' ),
								'new_item_name' => __( 'New Location Name' ),
								'menu_name'     => __( 'Locations' ),
							),
							'meta_box_cb'    => array( $this, 'meta_box_html_locations' ),
							'query_var'      => 'location',
							'show_ui'			=> true,
							'singular_label' => 'Location',
							'show_in_menu'   => true,
						),
						'term_data' => array(),
					),
				)
			);
		}

		function taxonomy_meta_box_html( $taxonomy_name, $element_name, $post ) {
			/**
			 *  Creates HTML output for a meta box that turns a taxonomy into
			 * a select drop-down list instead of the typical checkboxes
			 */
			//get the saved term for this taxonomy
			$saved_term_slug = $this->get_term_slug( $taxonomy_name, $post->ID );
			$HTML  = '<select name="' . $element_name . '">';
			$HTML .= '<option></option>'; //offering a blank value is the only way a user can remove the value
			//get all the term names and slugs for $taxonomy_name
			$terms = get_terms( $taxonomy_name,  array( 'hide_empty' => false ) );
			if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
				foreach( $terms as $term ) {
					$HTML .= '<option value="' . $term->slug . '"'
						. selected( strtolower( $term->slug ), strtolower( $saved_term_slug ), false )
						. '>' . $term->name . '</option>';
				}
			}
			return $HTML . '</select>';
		}

		function translate_custom_field_names( $nice_name ) {
			$nice_name = strtolower( $nice_name );
			$prefixed_fields = array(
				'car_id',
				'dealer_id'
			);
			return ( in_array( $nice_name, $prefixed_fields ) ? '_' : '' ) . 'inventory_presser_' . $nice_name;
		}

		/* location taxonomy */
		function add_location_fields($taxonomy) {
		    ?>

		    <div class="form-field term-group location-tax">
			    <div class="form-wrap form-field">
			        <label>Phone Numbers</label>
			        <div class="repeat-group">
			        	<div class="repeat-container"></div>
			        	<div class="repeat-this">
			        		<div class="repeat-form">
						        <input type="text" name="phone_description[]" placeholder="Description" />
						        <input type="text" name="phone_number[]" placeholder="Number" required />
					        </div>
					        <div class="repeat-buttons">
					        	<span class="dashicons dashicons-menu repeat-move"></span>
					        	<span class="dashicons dashicons-trash repeat-delete"></span>
					        </div>
				        </div>
				        <button type="button" class="repeat-add">Add Phone</button>
			        </div>
			    </div>
			    <div class="form-wrap form-field">
			        <label>Hours</label>
			        <div class="repeat-group">
			        	<div class="repeat-container"></div>
			        	<div class="repeat-this">
			        		<div class="repeat-form">

					        	<input type="text" name="hours_title[]" placeholder="Title" />

					        	<table>
					        		<thead>
					        			<th></th>
					        			<th>Open</th>
					        			<th></th>
					        			<th>Close</th>
					        			<th>Appt Only</th>
					        		</thead>
					        		<tbody>
						        		<tr>
						        			<th>MON</th>
						        			<td><input name="hours[mon][open][]" class="timepick" type="text"></td>
						        			<td>to</td>
						        			<td><input name="hours[mon][close][]" class="timepick" type="text"></td>
						        			<td>
												<select name="hours[mon][appt][]">
													<option value="0">No</option>
													<option value="1">Yes</option>
												</select>
						        			</td>
						        		</tr>
						        		<tr>
						        			<th>TUE</th>
						        			<td><input name="hours[tue][open][]" class="timepick" type="text"></td>
						        			<td>to</td>
						        			<td><input name="hours[tue][close][]" class="timepick" type="text"></td>
						        			<td>
												<select name="hours[tue][appt][]">
													<option value="0">No</option>
													<option value="1">Yes</option>
												</select>
						        			</td>
						        		</tr>
						        		<tr>
						        			<th>WED</th>
						        			<td><input name="hours[wed][open][]" class="timepick" type="text"></td>
						        			<td>to</td>
						        			<td><input name="hours[wed][close][]" class="timepick" type="text"></td>
						        			<td>
												<select name="hours[wed][appt][]">
													<option value="0">No</option>
													<option value="1">Yes</option>
												</select>
						        			</td>
						        		</tr>
						        		<tr>
						        			<th>THU</th>
						        			<td><input name="hours[thu][open][]" class="timepick" type="text"></td>
						        			<td>to</td>
						        			<td><input name="hours[thu][close][]" class="timepick" type="text"></td>
						        			<td>
												<select name="hours[thu][appt][]">
													<option value="0">No</option>
													<option value="1">Yes</option>
												</select>
						        			</td>
						        		</tr>
						        		<tr>
						        			<th>FRI</th>
						        			<td><input name="hours[fri][open][]" class="timepick" type="text"></td>
						        			<td>to</td>
						        			<td><input name="hours[fri][close][]" class="timepick" type="text"></td>
						        			<td>
												<select name="hours[fri][appt][]">
													<option value="0">No</option>
													<option value="1">Yes</option>
												</select>
						        			</td>
						        		</tr>
						        		<tr>
						        			<th>SAT</th>
						        			<td><input name="hours[sat][open][]" class="timepick" type="text"></td>
						        			<td>to</td>
						        			<td><input name="hours[sat][close][]" class="timepick" type="text"></td>
						        			<td>
												<select name="hours[sat][appt][]">
													<option value="0">No</option>
													<option value="1">Yes</option>
												</select>
						        			</td>
						        		</tr>
						        		<tr>
						        			<th>SUN</th>
						        			<td><input name="hours[sun][open][]" class="timepick" type="text"></td>
						        			<td>to</td>
						        			<td><input name="hours[sun][close][]" class="timepick" type="text"></td>
						        			<td>
												<select name="hours[sun][appt][]">
													<option value="0">No</option>
													<option value="1">Yes</option>
												</select>
						        			</td>
						        		</tr>

						        	</tbody>
					        	</table>

					        </div>
					        <div class="repeat-buttons">
					        	<span class="dashicons dashicons-menu repeat-move"></span>
					        	<span class="dashicons dashicons-trash repeat-delete"></span>
					        </div>
				        </div>
				        <button type="button" class="repeat-add">Add Hours</button>
			        </div>
		        </div>
		    </div>

		    <?php
		}

		function save_location_meta( $term_id, $tt_id ){

			if (isset($_POST['tag-name'])) {

				$meta_final = array('phones' => array(), 'hours' => array());

				$count = count($_POST['hours_title']) - 2;

				for ($i = 0; $i <= $count; $i++) {

					$has_data = false;

					$this_hours = array();
					$this_hours['title'] = sanitize_text_field($_POST['hours_title'][$i]);

					foreach ($_POST['hours'] as $day => $harray) {
						$open = sanitize_text_field($harray['open'][$i]);
						$close = sanitize_text_field($harray['close'][$i]);
						$appt = sanitize_text_field($harray['appt'][$i]);
						if (!$has_data && ($open || $close || $appt == '1')) {
							$has_data = true;
						}
						$this_hours[$day] = array('open' => $open, 'close' => $close, 'appt'=> $appt);
					}

					if ($has_data) {
						$meta_final['hours'][] = $this_hours;
					}

				}

				foreach ($_POST['phone_number'] as $index => $phone_number) {

					$phone_description = sanitize_text_field($_POST['phone_description'][$index]);
					$phone_number = sanitize_text_field($phone_number);

		    		if ($phone_number) {
		    			$meta_final['phones'][] = array(
		    				'phone_description' => $phone_description,
		    				'phone_number' => $phone_number
		    			);
		    		}
		    	}

		    	add_term_meta( $term_id, 'location-phone-hours', $meta_final, true );

			}

		}

		function edit_location_field( $term, $taxonomy ){

		    // get current term meta
		    $location_meta = get_term_meta( $term->term_id, 'location-phone-hours', true );

		    ?>
		    <tr class="form-field term-group-wrap">
		        <th scope="row"><label>Phone Numbers</label></th>
		        <td>
			        <div class="repeat-group">
			        	<div class="repeat-container">
<?php
foreach ($location_meta['phones'] as $index => $phone) {
?>

				        	<div class="repeated">
				        		<div class="repeat-form">

<?php
echo sprintf('<input type="text" name="phone_description[]" value="%s" placeholder="Description" />', $phone['phone_description']);
echo sprintf('<input type="text" name="phone_number[]" value="%s" placeholder="Number" />', $phone['phone_number']);
?>
						        </div>
						        <div class="repeat-buttons">
						        	<span class="dashicons dashicons-menu repeat-move"></span>
						        	<span class="dashicons dashicons-trash repeat-delete"></span>
						        </div>
					        </div>
<?php
}
?>
						</div>
						<div class="repeat-this">
			        		<div class="repeat-form">
						        <input type="text" name="phone_description[]" placeholder="Description" />
						        <input type="text" name="phone_number[]" placeholder="Number" />
					        </div>
					        <div class="repeat-buttons">
					        	<span class="dashicons dashicons-menu repeat-move"></span>
					        	<span class="dashicons dashicons-trash repeat-delete"></span>
					        </div>
				        </div>
				        <button type="button" class="repeat-add">Add Phone</button>
			        </div>
				</td>
		    </tr>
		    <tr class="form-field term-group-wrap">
		        <th scope="row"><label>Hours</label></th>
		        <td>
			        <div class="repeat-group">
			        	<div class="repeat-container">
<?php
foreach ($location_meta['hours'] as $index => $hours) {
?>
							<div class="repeated">
				        		<div class="repeat-form">

				       				<input type="text" name="hours_title[]" placeholder="Title" value="<?php echo $hours['title'] ?>" />

						        	<table class="repeater-table">
						        		<thead>
						        			<td></td>
						        			<td>Open</td>
						        			<td></td>
						        			<td>Close</td>
						        			<td>Appt Only</td>
						        		</thead>
						        		<tbody>
							        		<tr>
							        			<td>MON</td>
							        			<td><input name="hours[mon][open][]" class="timepick" type="text" value="<?php echo $hours['mon']['open'] ?>"></td>
							        			<td>to</td>
							        			<td><input name="hours[mon][close][]" class="timepick" type="text" value="<?php echo $hours['mon']['close'] ?>"></td>
							        			<td>
													<select name="hours[mon][appt][]" autocomplete="off">
														<option value="0"<?php echo ($hours['mon']['appt'] == '0') ? ' selected' : ''; ?>>No</option>
														<option value="1"<?php echo ($hours['mon']['appt'] == '1') ? ' selected' : ''; ?>>Yes</option>
													</select>
							        			</td>
							        		</tr>
							        		<tr>
							        			<td>TUE</td>
							        			<td><input name="hours[tue][open][]" class="timepick" type="text" value="<?php echo $hours['tue']['open'] ?>"></td>
							        			<td>to</td>
							        			<td><input name="hours[tue][close][]" class="timepick" type="text" value="<?php echo $hours['tue']['close'] ?>"></td>
							        			<td>
													<select name="hours[tue][appt][]" autocomplete="off">
														<option value="0"<?php echo ($hours['tue']['appt'] == '0') ? ' selected' : ''; ?>>No</option>
														<option value="1"<?php echo ($hours['tue']['appt'] == '1') ? ' selected' : ''; ?>>Yes</option>
													</select>
							        			</td>
							        		</tr>
							        		<tr>
							        			<td>WED</td>
							        			<td><input name="hours[wed][open][]" class="timepick" type="text" value="<?php echo $hours['wed']['open'] ?>"></td>
							        			<td>to</td>
							        			<td><input name="hours[wed][close][]" class="timepick" type="text" value="<?php echo $hours['wed']['close'] ?>"></td>
							        			<td>
													<select name="hours[wed][appt][]" autocomplete="off">
														<option value="0"<?php echo ($hours['wed']['appt'] == '0') ? ' selected' : ''; ?>>No</option>
														<option value="1"<?php echo ($hours['wed']['appt'] == '1') ? ' selected' : ''; ?>>Yes</option>
													</select>
							        			</td>
							        		</tr>
							        		<tr>
							        			<td>THU</td>
							        			<td><input name="hours[thu][open][]" class="timepick" type="text" value="<?php echo $hours['thu']['open'] ?>"></td>
							        			<td>to</td>
							        			<td><input name="hours[thu][close][]" class="timepick" type="text" value="<?php echo $hours['thu']['close'] ?>"></td>
							        			<td>
													<select name="hours[thu][appt][]" autocomplete="off">
														<option value="0"<?php echo ($hours['thu']['appt'] == '0') ? ' selected' : ''; ?>>No</option>
														<option value="1"<?php echo ($hours['thu']['appt'] == '1') ? ' selected' : ''; ?>>Yes</option>
													</select>
							        			</td>
							        		</tr>
							        		<tr>
							        			<td>FRI</td>
							        			<td><input name="hours[fri][open][]" class="timepick" type="text" value="<?php echo $hours['fri']['open'] ?>"></td>
							        			<td>to</td>
							        			<td><input name="hours[fri][close][]" class="timepick" type="text" value="<?php echo $hours['fri']['close'] ?>"></td>
							        			<td>
													<select name="hours[fri][appt][]" autocomplete="off">
														<option value="0"<?php echo ($hours['fri']['appt'] == '0') ? ' selected' : ''; ?>>No</option>
														<option value="1"<?php echo ($hours['fri']['appt'] == '1') ? ' selected' : ''; ?>>Yes</option>
													</select>
							        			</td>
							        		</tr>
							        		<tr>
							        			<td>SAT</td>
							        			<td><input name="hours[sat][open][]" class="timepick" type="text" value="<?php echo $hours['sat']['open'] ?>"></td>
							        			<td>to</td>
							        			<td><input name="hours[sat][close][]" class="timepick" type="text" value="<?php echo $hours['sat']['close'] ?>"></td>
							        			<td>
													<select name="hours[sat][appt][]" autocomplete="off">
														<option value="0"<?php echo ($hours['sat']['appt'] == '0') ? ' selected' : ''; ?>>No</option>
														<option value="1"<?php echo ($hours['sat']['appt'] == '1') ? ' selected' : ''; ?>>Yes</option>
													</select>
							        			</td>
							        		</tr>
							        		<tr>
							        			<td>SUN</td>
							        			<td><input name="hours[sun][open][]" class="timepick" type="text" value="<?php echo $hours['sun']['open'] ?>"></td>
							        			<td>to</td>
							        			<td><input name="hours[sun][close][]" class="timepick" type="text" value="<?php echo $hours['sun']['close'] ?>"></td>
							        			<td>
													<select name="hours[sun][appt][]" autocomplete="off">
														<option value="0"<?php echo ($hours['sun']['appt'] == '0') ? ' selected' : ''; ?>>No</option>
														<option value="1"<?php echo ($hours['sun']['appt'] == '1') ? ' selected' : ''; ?>>Yes</option>
													</select>
							        			</td>
							        		</tr>
							        	</tbody>
						        	</table>

						        </div>
						        <div class="repeat-buttons">
						        	<span class="dashicons dashicons-menu repeat-move"></span>
						        	<span class="dashicons dashicons-trash repeat-delete"></span>
						        </div>
					        </div>
<?php
}
?>
			        	</div>
			        	<div class="repeat-this">
			        		<div class="repeat-form">

			       				<input type="text" name="hours_title[]" placeholder="Title" />

					        	<table class="repeater-table">
					        		<thead>
					        			<td></td>
					        			<td>Open</td>
					        			<td></td>
					        			<td>Close</td>
					        			<td>Appt Only</td>
					        		</thead>
					        		<tbody>
						        		<tr>
						        			<td>MON</td>
						        			<td><input name="hours[mon][open][]" class="timepick" type="text"></td>
						        			<td>to</td>
						        			<td><input name="hours[mon][close][]" class="timepick" type="text"></td>
						        			<td>
												<select name="hours[mon][appt][]">
													<option value="0">No</option>
													<option value="1">Yes</option>
												</select>
						        			</td>
						        		</tr>
						        		<tr>
						        			<td>TUE</td>
						        			<td><input name="hours[tue][open][]" class="timepick" type="text"></td>
						        			<td>to</td>
						        			<td><input name="hours[tue][close][]" class="timepick" type="text"></td>
						        			<td>
												<select name="hours[tue][appt][]">
													<option value="0">No</option>
													<option value="1">Yes</option>
												</select>
						        			</td>
						        		</tr>
						        		<tr>
						        			<td>WED</td>
						        			<td><input name="hours[wed][open][]" class="timepick" type="text"></td>
						        			<td>to</td>
						        			<td><input name="hours[wed][close][]" class="timepick" type="text"></td>
						        			<td>
												<select name="hours[wed][appt][]">
													<option value="0">No</option>
													<option value="1">Yes</option>
												</select>
						        			</td>
						        		</tr>
						        		<tr>
						        			<td>THU</td>
						        			<td><input name="hours[thu][open][]" class="timepick" type="text"></td>
						        			<td>to</td>
						        			<td><input name="hours[thu][close][]" class="timepick" type="text"></td>
						        			<td>
												<select name="hours[thu][appt][]">
													<option value="0">No</option>
													<option value="1">Yes</option>
												</select>
						        			</td>
						        		</tr>
						        		<tr>
						        			<td>FRI</td>
						        			<td><input name="hours[fri][open][]" class="timepick" type="text"></td>
						        			<td>to</td>
						        			<td><input name="hours[fri][close][]" class="timepick" type="text"></td>
						        			<td>
												<select name="hours[fri][appt][]">
													<option value="0">No</option>
													<option value="1">Yes</option>
												</select>
						        			</td>
						        		</tr>
						        		<tr>
						        			<td>SAT</td>
						        			<td><input name="hours[sat][open][]" class="timepick" type="text"></td>
						        			<td>to</td>
						        			<td><input name="hours[sat][close][]" class="timepick" type="text"></td>
						        			<td>
												<select name="hours[sat][appt][]">
													<option value="0">No</option>
													<option value="1">Yes</option>
												</select>
						        			</td>
						        		</tr>
						        		<tr>
						        			<td>SUN</td>
						        			<td><input name="hours[sun][open][]" class="timepick" type="text"></td>
						        			<td>to</td>
						        			<td><input name="hours[sun][close][]" class="timepick" type="text"></td>
						        			<td>
												<select name="hours[sun][appt][]">
													<option value="0">No</option>
													<option value="1">Yes</option>
												</select>
						        			</td>
						        		</tr>
						        	</tbody>
					        	</table>

					        </div>
					        <div class="repeat-buttons">
					        	<span class="dashicons dashicons-menu repeat-move"></span>
					        	<span class="dashicons dashicons-trash repeat-delete"></span>
					        </div>
				        </div>
				        <button type="button" class="repeat-add">Add Hours</button>
			        </div>
		        </td>
		    </tr><?php
		}

		function update_location_meta( $term_id, $tt_id ){

			if (isset($_POST['name'])) {

				$meta_final = array('phones' => array(), 'hours' => array());

				$count = count($_POST['hours_title']) - 2;

				for ($i = 0; $i <= $count; $i++) {

					$has_data = false;

					$this_hours = array();
					$this_hours['title'] = sanitize_text_field($_POST['hours_title'][$i]);

					foreach ($_POST['hours'] as $day => $harray) {
						$open = sanitize_text_field($harray['open'][$i]);
						$close = sanitize_text_field($harray['close'][$i]);
						$appt = sanitize_text_field($harray['appt'][$i]);
						if (!$has_data && ($open || $close || $appt == '1')) {
							$has_data = true;
						}
						$this_hours[$day] = array('open' => $open, 'close' => $close, 'appt'=> $appt);
					}

					if ($has_data) {
						$meta_final['hours'][] = $this_hours;
					}

				}

				foreach ($_POST['phone_number'] as $index => $phone_number) {

					$phone_description = sanitize_text_field($_POST['phone_description'][$index]);
					$phone_number = sanitize_text_field($phone_number);

		    		if ($phone_number) {
		    			$meta_final['phones'][] = array(
		    				'phone_description' => $phone_description,
		    				'phone_number' => $phone_number
		    			);
		    		}
		    	}

		    	update_term_meta( $term_id, 'location-phone-hours', $meta_final);

			}

		    //die(print_r($meta_final, true));

		}

	} //end class
	$inventory_presser = new Inventory_Presser_Plugin;
} //end if