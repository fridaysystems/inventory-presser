<?php
defined( 'ABSPATH' ) OR exit;
/**
 * Plugin Name: Inventory Presser
 * Plugin URI: http://inventorypresser.com
 * Description: An inventory management plugin for Car Dealers. Import or create an automobile or powersports dealership inventory.
 * Version: 1.1.0
 * Author: Corey Salzano
 * Author URI: https://profiles.wordpress.org/salzano
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! class_exists( 'Inventory_Presser_Vehicle' ) ) {
	$class_vehicle = plugin_dir_path( __FILE__ ) . 'includes/class-inventory-presser-vehicle.php';
	if ( file_exists( $class_vehicle ) ) {
		require $class_vehicle;
	}
}

if ( ! class_exists( 'Inventory_Presser_Plugin' ) ) {
	class Inventory_Presser_Plugin {
		const PRODUCT_NAME = 'Inventory Presser';
		const OPTION_NAME = 'inventory_presser_options';
		
		const CUSTOM_POST_TYPE = 'inventory_vehicle';
		function post_type() {
			return apply_filters( 'inventory_presser_post_type', self::CUSTOM_POST_TYPE );
		}		
		
		function add_columns_to_vehicles_table( $column ) {
			//add our columns
			$column['inventory_presser_stock_number'] = 'Stock #';
			$column['inventory_presser_color'] = 'Color';
			$column['inventory_presser_odometer'] = 'Odometer';
			$column['inventory_presser_price'] = 'Price';
			$column['inventory_presser_photo_count'] = 'Photo count';
			//remove the date and tags columns
			unset( $column['date'] );
			unset( $column['tags'] );
			return $column;
		}
		
		function add_our_menu_to_settings( ) {
			$menu_slug = $this->product_name_slug( '_settings' );
			$my_admin_page = add_options_page( self::PRODUCT_NAME . ' settings', self::PRODUCT_NAME, 'manage_options', $menu_slug, array( &$this, 'get_settings_page_html'));
			//when our options page is loaded, run a scan that checks settings values that we recommend
			add_action('load-'.$my_admin_page, array( &$this, 'scan_for_recommended_settings_and_create_warnings' ) );
		}
		
		function annotate_add_media_button( $context ) {
			return $context . '<span id="media-annotation" class="annotation">' . $this->create_add_media_button_annotation( ) . '</span>';
		}

		function __construct( ) {
		
			/**
			 * Modify the administrator dashboard
			 */

			//add a menu to the admin settings menu
			add_action( 'admin_menu', array( &$this, 'add_our_menu_to_settings' ) );
			
			//Add functionality to the admin dashboard so managing our data is easier
			add_action( 'admin_init', array( &$this, 'enhance_administrator_dashboard' ) );
			
			
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

			//Flush rewrite rules when the plugin is activated
			register_activation_hook( __FILE__, array( &$this, 'my_rewrite_flush' ) );

			//Do some things during deactivation
			register_deactivation_hook( __FILE__, array( &$this, 'deactivation' ) );


			/**
			 * These items make it easier to create themes based on our custom post type
			 */

			//Translate friendly names to actual custom field keys
			add_filter( 'translate_meta_field_key', array( &$this, 'translate_custom_field_names' ) );
			
			/**
			 * Affect the way some importers work
			 */
			require plugin_dir_path( __FILE__ ) . 'includes/class-modify-imports.php';
			$options = $this->get_options( );
			if( ! isset( $options['delete-vehicles-not-in-new-feeds'] ) ) {
				$default_options = $this->get_default_options();
				$options['delete-vehicles-not-in-new-feeds'] = $default_options['delete-vehicles-not-in-new-feeds'];
				$this->save_options( $options );
			}
			$modify_imports = new Inventory_Presser_Modify_Imports( $this->post_type(), $options['delete-vehicles-not-in-new-feeds'] );
		}

		function create_add_media_button_annotation( ) {
			global $post;
			if( !is_object( $post ) && isset( $_POST['post_ID'] ) ) {
				/* this function is being called via AJAX and the 
				 * post_id is incoming, so get the post
				 */
				$post = get_post( $_POST['post_ID'] );
			}
			if( $this->post_type() == $post->post_type ) {
				$attachments = get_children( array( 
					'post_parent'    => $post->ID,
					'post_type'      => 'attachment',
					'posts_per_page' => -1,
				) );
				$counts = array(
					'image' => 0,
					'video' => 0,
					'text'  => 0,
					'pdf'   => 0,
					'other' => 0,
				);
				foreach( $attachments as $attachment ) {
					switch( $attachment->post_mime_type ) {
						case 'image/jpeg':
						case 'image/png':
						case 'image/gif':
							$counts['image']++;
							break;
						case 'video/mpeg':
						case 'video/mp4': 
						case 'video/quicktime':
							$counts['video']++;
							break;
						case 'text/csv':
						case 'text/plain': 
						case 'text/xml':
							$counts['text']++;
							break;
						case 'application/pdf':
							$counts['pdf']++;
							break;
						default:
							$counts['other']++;
							break;
					}
				}				
				if( 0 < ( $counts['image'] + $counts['video'] + $counts['text'] + $counts['pdf'] + $counts['other'] ) ) {
					$note = '';
					if( 0 < $counts['image'] ) { 
						$note .= $counts['image'] . ' photo' . ( 1 != $counts['image'] ? 's' : '' );
					}
					if( 0 < $counts['video'] ) {
						if( '' != $note ) { 
							$note .= ', ';
						}
						$note .= $counts['video'] . ' video' . ( 1 != $counts['video'] ? 's' : '' );
					}
					if( 0 < $counts['text'] ) { 
						if( '' != $note ) { 
							$note .= ', ';
						}
						$note .= $counts['text'] . ' text' . ( 1 != $counts['text'] ? 's' : '' );
					}
					if( 0 < $counts['pdf'] ) { 
						if( '' != $note ) { 
							$note .= ', ';
						}
						$note .= $counts['pdf'] . ' PDF' . ( 1 != $counts['pdf'] ? 's' : '' );
					}
					if( 0 < $counts['other'] ) { 
						if( '' != $note ) { 
							$note .= ', ';
						}
						$note .= $counts['other'] . ' file' . ( 1 != $counts['other'] ? 's' : '' );
					}
					return $note;
				} else {
					return '0 photos';
				}				
			}		
		}
				
		function create_custom_post_type( ) {
			//creates a custom post type that will be used by this plugin
			register_post_type( 
				$this->post_type(),
				apply_filters( 
					'inventory_presser_post_type_args', 
					array (
						'description'  => __('Vehicles for sale in an automobile or powersports dealership'),
						'has_archive'  => true,
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
									'post_tag', //Allow tags
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
				register_taxonomy( $taxonomy_data[$i]['args']['label'], $this->post_type(), $taxonomy_data[$i]['args'] );

				/* populate the taxonomy we just created with terms if they do not
				 * already exist
				 */
				foreach( $taxonomy_data[$i]['term_data'] as $abbr => $desc ) {
					if ( !is_array( term_exists( $desc, $taxonomy_data[$i]['args']['label'] ) ) ) {
						wp_insert_term( $desc, $taxonomy_data[$i]['args']['label'], 
							array ( 
								'description' => $desc,
								'slug' => $abbr,
							)
						);
					}
				}
			}
		}
		
		function deactivation( ) {
			/* this is called during plugin deactivation
			 * delete the rewrite_rules option so the rewrite rules
			 * are generated on the next page load without ours. 
			 * this is a weird thing and is described here http://wordpress.stackexchange.com/a/44337/13090
			 */
			delete_option('rewrite_rules');
		}
		
		function delete_all_data_and_deactivate( ) {			
			//this function will operate as an uninstall utility
			//removes all the data we have added to the database

			//delete all the vehicles
			$deleted_count = $this->delete_all_inventory( );
				
			//remove the terms in taxonomies
			$taxonomy_data = $this->taxonomy_data();
			for( $i=0; $i<sizeof( $taxonomy_data ); $i++ ) {				
				$tax = $taxonomy_data[$i]['args']['label'];
				$terms = get_terms( $tax, array( 'fields' => 'ids', 'hide_empty' => false ) );
				foreach ( $terms as $value ) {
					wp_delete_term( $value, $tax );
				}
			}
			
			do_action( 'inventory_presser_delete_all_data' );

			//kill the option
			delete_option( self::OPTION_NAME );
					
			//deactivate so the next page load doesn't restore the option & terms
			deactivate_plugins( plugin_basename( __FILE__ ) );
		}
		
		function delete_all_inventory( ) {
			//this function deletes all posts that exist of our custom post type
			//and their associated meta data
			//returns the number of vehicles deleted
			$args = array(
				'posts_per_page' => -1,
				'post_type'      => $this->post_type()
			);
			$posts = get_posts( $args );
			$deleted_count = 0;
			if ( $posts ){
				foreach( $posts as $post ){
					//delete post attachments
					$attachment = array(
						'posts_per_page' => -1,
						'post_type'      => 'attachment',
						'post_parent'    => $post->ID
					);

					foreach( get_posts( $attachment ) as $attached ){
						//delete the attachment
						wp_delete_attachment( $attached->ID, true );
					}

					//delete the parent post or vehicle
					wp_delete_post( $post->ID, true );
					$deleted_count++;
				}
			}
			return $deleted_count;
		}
		
		function enhance_administrator_dashboard( ) {
			//This function is added to the 'admin_init' hook
			
			//Save custom post data when posts are saved
			add_action( 'save_post', array( &$this, 'save_vehicle_post_meta' ) );
			
			//Add columns to the table that lists all the Vehicles on edit.php
			add_filter( 'manage_' . $this->post_type() . '_posts_columns', array( &$this, 'add_columns_to_vehicles_table' ) );
			
			//Populate the columns we added to the Vehicles table
			add_action( 'manage_' . $this->post_type() . '_posts_custom_column', array( &$this, 'populate_columns_we_added_to_vehicles_table' ), 10, 2 );
		
			//Make our added columns to the Vehicles table sortable
			add_filter( 'manage_edit-' . $this->post_type() . '_sortable_columns', array( &$this, 'make_vehicles_table_columns_sortable' ) );			
			
			//Implement the orderby for each of these added columns
			add_filter( 'request', array( &$this, 'vehicles_table_columns_orderbys' ) );
			
			//Add a meta box to the New/Edit post page
			add_meta_box('vehicle-meta', 'Vehicle attributes', array( &$this, 'meta_box_html_vehicle' ), $this->post_type(), 'advanced', 'high' );
			
			// Move all "advanced" meta boxes above the default editor
			// http://wordpress.stackexchange.com/a/88103
			add_action( 'edit_form_after_title', function( ) {
				global $post, $wp_meta_boxes;
				do_meta_boxes( get_current_screen( ), 'advanced', $post );
				unset( $wp_meta_boxes[get_post_type( $post )]['advanced'] );
			});
			
			//Move the "Tags" metabox below the meta boxes for vehicle custom taxonomies
			add_action( 'add_meta_boxes', array( &$this, 'move_tags_meta_box' ), 0 );

			//Load our stylesheet that modifies the dashboard
			add_action( 'admin_enqueue_scripts', array( &$this, 'my_admin_style' ) );
			
			//Load our javascript that modifies the dashboard	 
			add_action( 'admin_enqueue_scripts', array( &$this, 'my_admin_javascript' ) );
			
			//Add some content next to the "Add Media" button
			add_action( 'media_buttons_context', array( &$this, 'annotate_add_media_button' ) );
			
			//'add_attachment'
			//'delete_attachment'
			//Make our Add Media button annotation available from an AJAX call
			add_action( 'wp_ajax_output_add_media_button_annotation', array( &$this, 'output_add_media_button_annotation' ) );
			
			//Add a link to the Settings page on the plugin management page
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( &$this, 'insert_settings_link' ), 2, 2 );
		}
		
		/**
		 * $color is a string value of 'red', 'yellow', or 'green' that appears as a left border on the notice box
		 */
		function get_admin_notice_html( $message, $color ) {
			switch( $color ){
				case 'red': 
					$type = 'error';
					break;
				case 'yellow':
					$type = 'update-nag no-pad';
					break;
				case 'green':
					$type = 'updated';
					break;
			}
			return '<div class="'. $type .' notice"><p><strong>'. __( $message, 'inventory_presser' ) . '</strong></p></div>';
		}
		
		function get_default_options( ) {
			$options = array(
				'delete-vehicles-not-in-new-feeds' => true,
			);
			return apply_filters( 'inventory_presser_default_options', $options );
		}
		
		function get_options( ) {
			$options = get_option( self::OPTION_NAME );
			if( !$options ) {
				$options = $this->get_default_options( );
				$this->save_options( $options );
				return $options;
			}
			return $options;
		}
		
		function get_settings_page_html( ) {
			
			$options = $this->get_options( );
				
			//only take action if we find the nonce
			if( isset( $_POST['save-options'] ) && check_admin_referer( $this->product_name_slug() . '-nonce' ) ) {
				//save changes to the plugin's options				
				$new_options = $options;
				$new_options['delete-vehicles-not-in-new-feeds'] = isset( $_POST['delete-vehicles-not-in-new-feeds'] );
				if( $options != $new_options ) {
					//save changes
					$this->save_options( $new_options );
					//use the changed set
					$options = $new_options;
				}			
			}
			
			//check if user clicked the delete inventory button
			if ( isset( $_POST['delete-vehicles'] ) && 'yes' == sanitize_text_field( $_POST['delete-vehicles'] ) && check_admin_referer( 'delete-vehicles-nonce' ) ) {
				$delete_result = $this->delete_all_inventory( );
				if ( is_wp_error( $delete_result ) ) {
					//output error
					echo "<div id='import-error' class='settings-error'><p><strong>" . $delete_result->get_error_message( ) . "</strong></p></div>";
				} else {
					//output the success result, it's a string of html
					echo '<div id="setting-error-settings_updated" class="updated settings-error notice is-dismissible"><p><strong>';
					if( 0 == $delete_result ) {
						echo 'There are no vehicles to delete.';
					} else {
						echo 'Deleted '.$delete_result.' vehicles.';
					}
					echo '</strong></p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>';
				}
			}
			
			//did the user click the Delete all plugin data button?
			if ( isset( $_POST['delete'] ) && 'yes' == sanitize_text_field( $_POST['delete'] ) && check_admin_referer( 'delete-nonce' ) ) {
				$delete_result = $this->delete_all_data_and_deactivate( );
				if ( is_wp_error( $delete_result ) ) {
					//output error
					echo "<div id='import-error' class='settings-error'><p><strong>" . $delete_result->get_error_message( ) . "</strong></p></div>";
				} else {
					//redirect to plugins page to show that we've deactivated
					?><script type="text/javascript"> location.href='plugins.php'; </script><?php
				}
			}
			
			//output the admin settings page
			?><div class="wrap">
				<h2><?php echo self::PRODUCT_NAME ?> Settings</h2>				
				<form method="post" action="options-general.php?page=<?php echo $this->product_name_slug() ?>_settings"><?php 
					wp_nonce_field( $this->product_name_slug() . '-nonce'); ?>
					<table class="form-table">
					<tbody>
					<tr>
						<th scope="row"><label for="Imports">Imports</label></th>
						<td>
							<label for="delete-vehicles-not-in-new-feeds">
								<input type="checkbox" name="delete-vehicles-not-in-new-feeds" id="delete-vehicles-not-in-new-feeds" <?php if( isset( $options['delete-vehicles-not-in-new-feeds'] ) && $options['delete-vehicles-not-in-new-feeds'] ) { echo 'checked="checked"'; } ?>/> <?php _e( 'When importing a feed, delete units not contained in the new feed' ) ?>
							</label>
						</td>
					</tr>
					</table>
					
					<input type="submit" id="save-options" name="save-options" class="button-primary" value="<?php _e( 'Save Changes' ) ?>" />
				</form>

				<h3><?php _e( 'Delete data' ) ?></h3>					
					
				<p><?php _e( 'Remove all vehicle data and photos.' ) ?></p>
				<form method="post" action="options-general.php?page=<?php echo $this->product_name_slug() ?>_settings">
					<?php wp_nonce_field('delete-vehicles-nonce'); ?>
					<input type="hidden" name="delete-vehicles" value="yes">
					<input type="submit" class="button-primary" value="<?php _e('Delete all Vehicles') ?>" />
				</form>
				
				<p><?php _e( 'Remove all ' . self::PRODUCT_NAME . ' plugin data (including vehicles and photos) and deactivate.' ) ?></p>
				<form method="post" action="options-general.php?page=<?php echo $this->product_name_slug() ?>_settings">
					<?php wp_nonce_field('delete-nonce'); ?>
					<input type="hidden" name="delete" value="yes">
					<input type="submit" class="button-primary" value="<?php _e('Delete all Plugin Data') ?>" />
				</form>
			</div><?php
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
		
		function insert_settings_link( $links ) {
			$links[] = '<a href="options-general.php?page=' . $this->product_name_slug() . '_settings">Settings</a>';
			return $links; 
		}
		
		function make_vehicles_table_columns_sortable( $columns ) {
			$custom = array(
				// meta column id => sortby value used in query
				'inventory_presser_color'        => 'inventory_presser_color',
				'inventory_presser_odometer'     => 'inventory_presser_odometer',
				'inventory_presser_price'        => 'inventory_presser_price',
				'inventory_presser_stock_number' => 'inventory_presser_stock_number',
			);
			return wp_parse_args( $custom, $columns );
		}
		
		function meta_box_html_condition( $post ) {
			echo $this->taxonomy_meta_box_html( 'Condition', 'inventory_presser_condition', $post );
		}
		
		function meta_box_html_availability( $post ) {
			echo $this->taxonomy_meta_box_html( 'Availability', 'inventory_presser_availability', $post );
		}
		
 		function meta_box_html_drive_type( $post ) {
 			echo $this->taxonomy_meta_box_html( 'Drive type', 'inventory_presser_drive_type', $post );
		}
		
 		function meta_box_html_fuel( $post ) {
 			echo $this->taxonomy_meta_box_html( 'Fuel', 'inventory_presser_fuel', $post );
		}
		
 		function meta_box_html_transmission( $post ) {
 			echo $this->taxonomy_meta_box_html( 'Transmission', 'inventory_presser_transmission', $post );
		}
		
		function meta_box_html_type( $post ) {
			echo $this->taxonomy_meta_box_html( 'Type', 'inventory_presser_type', $post );
		}

		function meta_box_html_vehicle( $post, $meta_box ) {
			//HTML output for vehicle data meta box
			$custom = get_post_custom( $post->ID );	

			$body_style = ( isset( $custom['inventory_presser_body_style'] ) ? $custom['inventory_presser_body_style'][0] : '' );
			$color = ( isset( $custom['inventory_presser_color'] ) ? $custom['inventory_presser_color'][0] : '' );
			$engine = ( isset( $custom['inventory_presser_engine'] ) ? $custom['inventory_presser_engine'][0] : '' );
			$interior_color = ( isset( $custom['inventory_presser_interior_color'] ) ? $custom['inventory_presser_interior_color'][0] : '' );
			$make = ( isset( $custom['inventory_presser_make'] ) ? $custom['inventory_presser_make'][0] : '' );
			$model = ( isset( $custom['inventory_presser_model'] ) ? $custom['inventory_presser_model'][0] : '' );
			$odometer = ( isset( $custom['inventory_presser_odometer'] ) ? $custom['inventory_presser_odometer'][0] : '' );
			$price = ( isset( $custom['inventory_presser_price'] ) ? $custom['inventory_presser_price'][0] : '' );
			$stock_number = ( isset( $custom['inventory_presser_stock_number'] ) ? $custom['inventory_presser_stock_number'][0] : '' );
			$trim = ( isset( $custom['inventory_presser_trim'] ) ? $custom['inventory_presser_trim'][0] : '' );	
			$VIN = ( isset( $custom['inventory_presser_vin'] ) ? $custom['inventory_presser_vin'][0] : '' );
			$year = ( isset( $custom['inventory_presser_year'] ) ? $custom['inventory_presser_year'][0] : '' );
			
			echo '<table class="form-table"><tbody>';
			
			//VIN
			echo '<tr><th scope="row"><label for="inventory_presser_vin">VIN</label></th>';
			echo '<td><input type="text" name="inventory_presser_vin" maxlength="17" value="'. $VIN .'"></td>';
			
			//Stock number
			echo '<tr><th scope="row"><label for="inventory_presser_stock_number">Stock number</label></th>';
			echo '<td><input type="text" name="inventory_presser_stock_number" value="'. $stock_number .'"></td>';
			
			//Year
			echo '<tr><th scope="row"><label for="inventory_presser_year">Year</label></th>';
			echo '<td><select name="inventory_presser_year">';
			for( $y=date('Y')+2; $y>=1920; $y-- ) {
				echo '<option';
				if ( $y == $year ) {
					echo ' selected="selected"';
				}
				echo '>' .$y. '</option> ';
			}
			echo '</select></td></tr>';
			
			//Make
			echo '<tr><th scope="row"><label for="inventory_presser_make">Make</label></th>';
			echo '<td><input type="text" name="inventory_presser_make" value="' .$make. '"></td></tr>';
			
			//Model
			echo '<tr><th scope="row"><label for="inventory_presser_model">Model</label></th>';
			echo '<td><input type="text" name="inventory_presser_model" value="' .$model. '"></td></tr>';
			
			//Trim level
			echo '<tr><th scope="row"><label for="inventory_presser_trim">Trim</label></th>';
			echo '<td><input type="text" name="inventory_presser_trim" value="' .$trim. '"></td></tr>';
			
			//Engine
			echo '<tr><th scope="row"><label for="inventory_presser_engine">Engine</label></th>';
			echo '<td><input type="text" name="inventory_presser_engine" value="' .$engine. '"></td></tr>';

			//Body style			
			echo '<tr><th scope="row"><label for="inventory_presser_body_style">Body style</label></th>';
			echo '<td><input type="text" name="inventory_presser_body_style" value="' .$body_style. '"></td></tr>';
			
			//Color
			echo '<tr><th scope="row"><label for="inventory_presser_color">Color</label></th>';
			echo '<td><input type="text" name="inventory_presser_color" value="' .$color. '"></td></tr>';
			
			//Interior color
			echo '<tr><th scope="row"><label for="inventory_presser_interior_color">Interior color</label></th>';
			echo '<td><input type="text" name="inventory_presser_interior_color" value="' .$interior_color. '"></td></tr>';
			
			//Odometer
			echo '<tr><th scope="row"><label for="inventory_presser_odometer">Odometer</label></th>';
			echo '<td><input type="text" name="inventory_presser_odometer" value="' .$odometer. '"></td></tr>';
			
			//Price
			echo '<tr><th scope="row"><label for="inventory_presser_price">Price</label></th>';
			echo '<td><input type="text" name="inventory_presser_price" value="' .$price. '"></td></tr>';
			
			echo '</tbody></table>';
		}
		
		function move_tags_meta_box( ) {
			//Remove and re-add the "Tags" meta box so it ends up at the bottom for our CPT
			global $wp_meta_boxes;
			unset( $wp_meta_boxes[$this->post_type()]['side']['core']['tagsdiv-post_tag'] );
			add_meta_box( 'tagsdiv-post_tag', 'Tags', 'post_tags_meta_box', $this->post_type(), 'side', 'core', array( 'taxonomy' => 'post_tag' ));
		}
		
		function my_admin_javascript( ) {
			wp_register_script( 'custom-javascript', plugins_url( '/js/inventory.js', __FILE__ ) );
			wp_enqueue_script( 'custom-javascript' );
		}
		
		function my_admin_style( ) {
			wp_enqueue_style( 'my-admin-theme', plugins_url( 'wp-admin.css', __FILE__ ) );
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
		
		function output_add_media_button_annotation( ) { //because AJAX
			echo $this->create_add_media_button_annotation( );
			wp_die();
		}		
		
		function populate_columns_we_added_to_vehicles_table( $column_name, $post_id ) {
 			$custom_fields = get_post_custom( $post_id );
 			$val = ( isset( $custom_fields[$column_name] ) ? $custom_fields[$column_name][0] : '' );
			switch( $column_name ) { 
				case 'inventory_presser_odometer':
					if( class_exists( 'Vehicle_Data_Formatter' ) ) {
						$formatter = new Vehicle_Data_Formatter;
						echo $formatter->odometer( $val );
					} else {
						echo $val;
					}
					break;
				case 'inventory_presser_photo_count':
					echo count( get_children( array( 'post_parent' => $post_id ) ) );
					break;
				case 'inventory_presser_price':
					if( class_exists( 'Vehicle_Data_Formatter' ) ) {
						$formatter = new Vehicle_Data_Formatter;
						echo $formatter->price( $val );
					} else {
						echo $val;
					}
					break;
				default:
					echo $val;
			}			
		}
		
		function product_name_slug( $suffix = '' ) { 
			return strtolower( str_replace( ' ', '_', self::PRODUCT_NAME ) . $suffix );
		}
		
		function save_options( $arr ) { 
			update_option( self::OPTION_NAME, $arr );
		}
		
		function save_taxonomy_term( $post_id, $taxonomy_name, $element_name ) {
			if ( isset( $_POST[$element_name] ) ) {
				$term_slug = sanitize_text_field( $_POST[$element_name] );
				if ( '' == $term_slug ) {
					// the user is setting the vehicle type to empty string so remove the term
					wp_remove_object_terms( $post_id, $this->get_term_slug( $taxonomy_name, $post_id ), $taxonomy_name );
					return;
				}
				$term = get_term_by( 'slug', $term_slug, $taxonomy_name );
				if ( ! empty( $term ) && ! is_wp_error( $term ) ) {
					if ( is_wp_error( wp_set_object_terms( $post_id, $term->term_id, $taxonomy_name, false ) ) ) {
						//There was an error setting the term
					}
				}
			}
		}

		function save_vehicle_post_meta( $post_id ) {
			//only do this if the post is our custom type
			$post = get_post( $post_id );
			if ( null == $post || $post->post_type != $this->post_type() ) {
				return;
			}
			// save post_meta values and custom taxonomy terms when vehicles are saved
						
			//Condition custom taxonomy
			$this->save_taxonomy_term( $post_id, 'Condition', 'inventory_presser_condition' );
			
			//Availability custom taxonomy
			$this->save_taxonomy_term( $post_id, 'Availability', 'inventory_presser_availability' );
			
			//Drive type custom taxonomy
			$this->save_taxonomy_term( $post_id, 'Drive type', 'inventory_presser_drive_type' );
			
			//Fuel custom taxonomy
			$this->save_taxonomy_term( $post_id, 'Fuel', 'inventory_presser_fuel' );
			
			//Transmission custom taxonomy
			$this->save_taxonomy_term( $post_id, 'Transmission', 'inventory_presser_transmission' );
			
			//Type custom taxonomy
			$this->save_taxonomy_term( $post_id, 'Type', 'inventory_presser_type' );
			
			/** 
			 * Loop over the post meta keys we manage and save their values 
			 * if we find them coming over as part of the post to save.
			 */

			$vehicle = new Inventory_Presser_Vehicle($post_id);
			foreach( $vehicle->keys() as $key ) {
				$key = apply_filters( 'translate_meta_field_key', $key );
				if ( isset( $_POST[$key] ) ) {
					update_post_meta( $post->ID, $key, sanitize_text_field( $_POST[$key] ) );
				}
			}
		}
		
		function scan_for_recommended_settings_and_create_warnings() {	
			/** Suggest values for WordPress internal settings if the user has the values we do not prefer
			 */
			 
			if( '1' == get_option('uploads_use_yearmonth_folders') ) {
				//Organize uploads into yearly and monthly folders is turned on. Recommend otherwise.
				add_action( 'admin_notices', array( &$this, 'output_upload_folder_error_html' ) );
			}
			
			//Are thumbnail sizes not 4:3 aspect ratios?
			if( 
				( ( 4/3 ) != ( get_option('thumbnail_size_w')/get_option('thumbnail_size_h') ) ) ||
				( ( 4/3 ) != ( get_option('medium_size_w')/get_option('medium_size_h') ) ) ||
				( ( 4/3 ) != ( get_option('large_size_w')/get_option('large_size_h') ) ) 
			){
				//At least one thumbnail size is not 4:3
				add_action( 'admin_notices', array( &$this, 'output_thumbnail_size_error_html' ) );
			}
		}
		
		function output_upload_folder_error_html() {
			echo $this->get_admin_notice_html( 
				'Your media settings are configured to organize uploads into month- and year-based folders. This is not optimal for '.self::PRODUCT_NAME.', and you can turn this setting off on <a href="options-media.php">this page</a>.', 
				'yellow' 
			);
		}
		
		function output_thumbnail_size_error_html() {
			echo $this->get_admin_notice_html(
				'At least one of your thumbnail sizes does not have an aspect ratio of 4:3, which is the most common smartphone and digital camera aspect ratio. You can change thumbnail sizes <a href="options-media.php">here</a>.',
				'yellow'
			);
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
							'singular_label' => 'Fuel',
							'show_in_menu'   => false,
						),
						'term_data' =>	array (
									'B' => 'Electric and Gas Hybrid',
									'D' => 'Diesel',
									'E' => 'Electric',
									'F' => 'Flexible',
									'G' => 'Gas',
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
							'meta_box_cb'    => null,
							'singular_label' => 'Cylinders',
							'show_in_menu'   => false,
						),
						'term_data' => array (	),
					),
				)
			);
		}
		
		function taxonomy_meta_box_html( $taxonomy_name, $element_name, $post ) {
			/* creates HTML output for a meta box that turns a taxonomy into
			 * a select drop-down list instead of the typical checkboxes
			 */
			$HTML = '';
			//get the saved custom taxonomy value
			$saved = $this->get_term_slug( $taxonomy_name, $post->ID );
			$HTML .= '<select name="' . $element_name . '">';
			$HTML .= '<option></option>'; //offering a blank value is the only way a user can remove the value
			//get all the term names and slugs for $taxonomy_name
			$terms = get_terms( $taxonomy_name,  array( 'hide_empty' => false ) );
			if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
				foreach( $terms as $term ) {
					$HTML .= '<option value="' . $term->slug . '"';
					if ( strtolower( $term->slug ) == strtolower( $saved ) ) {
						$HTML .= ' selected="selected"';
					}
					$HTML .= '>' . $term->name . '</option>';
				}
			}
			$HTML .= '</select>';
			return $HTML;
		}
		
		function translate_custom_field_names( $nice_name ) {
			$nice_name = strtolower( $nice_name );
			$prefixed_fields = array(
				'car_id',
				'dealer_id'
			);
			if( in_array( $nice_name, $prefixed_fields ) ) {
				return '_inventory_presser_' . $nice_name;
			}
			return 'inventory_presser_' . $nice_name;
		}
		
		function vehicles_table_columns_orderbys( $vars ) {
			if ( isset( $vars['orderby'] ) && 'inventory_presser_color' == $vars['orderby'] ) {
				return array_merge( $vars, array(
					'meta_key' => 'inventory_presser_color',
					'orderby' => 'meta_value'
				) );
			}
			if ( isset( $vars['orderby'] ) && 'inventory_presser_odometer' == $vars['orderby'] ) {
				return array_merge( $vars, array(
					'meta_key' => 'inventory_presser_odometer',
					'orderby' => 'meta_value'
				) );
			}
			if ( isset( $vars['orderby'] ) && 'inventory_presser_price' == $vars['orderby'] ) {
				return array_merge( $vars, array(
					'meta_key' => 'inventory_presser_price',
					'orderby' => 'meta_value_num'
				) );
			}
			if ( isset( $vars['orderby'] ) && 'inventory_presser_stock_number' == $vars['orderby'] ) {
				return array_merge( $vars, array(
					'meta_key' => 'inventory_presser_stock_number',
					'orderby' => 'meta_value'
				) );
			}
			return $vars;
		}

	} //end class
	$inventory_presser = new Inventory_Presser_Plugin;
} //end if