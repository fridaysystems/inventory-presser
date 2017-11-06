<?php
/**
 * An object that customizes the administrator dashboard to make managing an
 * inventory of vehicles easy.
 *
 *
 * @since      1.3.1
 * @package    Inventory_Presser
 * @subpackage Inventory_Presser/includes
 * @author     Corey Salzano <corey@fridaynet.com>, John Norton <norton@fridaynet.com>
 */
class Inventory_Presser_Customize_Admin_Dashboard {

	const PRODUCT_NAME = 'Inventory Presser';
	var $post_type;

	function add_columns_to_vehicles_table( $column ) {
		//add our columns
		$column['inventory_presser_stock_number'] = 'Stock #';
		$column['inventory_presser_color'] = 'Color';
		$column['inventory_presser_odometer'] = 'Odometer';
		$column['inventory_presser_price'] = 'Price';
		$column['inventory_presser_photo_count'] = 'Photos';
		$column['inventory_presser_thumbnail'] = 'Thumbnail';
		//remove the date and tags columns
		unset( $column['date'] );
		unset( $column['tags'] );
		return $column;
	}


	function add_meta_boxes_to_cpt( ) {
		//Add a meta box to the New/Edit post page
		add_meta_box('vehicle-meta', 'Vehicle attributes', array( &$this, 'meta_box_html_vehicle' ), $this->post_type(), 'advanced', 'high' );

		//Add another meta box to the New/Edit post page
		add_meta_box('options-meta', 'Optional equipment', array( &$this, 'meta_box_html_options' ), $this->post_type(), 'normal', 'low' );

		//Add a meta box to the side column for a featured vehicle checkbox
		add_meta_box('featured', 'Featured Vehicle', array( &$this, 'meta_box_html_featured' ), $this->post_type(), 'side', 'low' );
	}

	function add_vehicles_to_admin_bar() {

		//do not do this if we are already looking at the dashboard
		if( is_admin() ) { return; }

		global $wp_admin_bar;

		$wp_admin_bar->add_node( array(
			'id'     => 'wp-admin-bar-vehicles',
			'title'  => 'Vehicles',
			'href'   => admin_url( 'edit.php?post_type=' . $this->post_type ),
			'parent' => 'site-name',
		) );
	}

	function annotate_add_media_button( $context ) {
		return $context .
			$this->create_delete_all_post_attachments_button() .
			'<span id="media-annotation" class="annotation">' .
			$this->create_add_media_button_annotation( ) . '</span>';
	}

	function __construct( $post_type='inventory_vehicle' ) {

		$this->post_type = $post_type;

		add_filter( 'posts_clauses', array( &$this, 'enable_order_by_attachment_count' ), 1, 2 );

		//Save custom post data when posts are saved
		add_action( 'save_post_' . $this->post_type(), array( &$this, 'save_vehicle_post_meta' ), 10, 3 );

		//Add columns to the table that lists all the Vehicles on edit.php
		add_filter( 'manage_' . $this->post_type() . '_posts_columns', array( &$this, 'add_columns_to_vehicles_table' ) );

		//Populate the columns we added to the Vehicles table
		add_action( 'manage_' . $this->post_type() . '_posts_custom_column', array( &$this, 'populate_columns_we_added_to_vehicles_table' ), 10, 2 );

		//Make our added columns to the Vehicles table sortable
		add_filter( 'manage_edit-' . $this->post_type() . '_sortable_columns', array( &$this, 'make_vehicles_table_columns_sortable' ) );

		//Implement the orderby for each of these added columns
		add_filter( 'pre_get_posts', array( &$this, 'vehicles_table_columns_orderbys' ) );

		add_action( 'add_meta_boxes', array( &$this, 'add_meta_boxes_to_cpt' ) );

		// Move all "advanced" meta boxes above the default editor
		// http://wordpress.stackexchange.com/a/88103
		add_action( 'edit_form_after_title', function( ) {
			global $post, $wp_meta_boxes;
			do_meta_boxes( get_current_screen( ), 'advanced', $post );
			unset( $wp_meta_boxes[get_post_type( $post )]['advanced'] );
		});

		//Move the "Tags" metabox below the meta boxes for vehicle custom taxonomies
		add_action( 'add_meta_boxes', array( &$this, 'move_tags_meta_box' ), 0 );

		//Load our scripts
		add_action( 'admin_enqueue_scripts', array( &$this, 'load_scripts' ) );

		//Add some content next to the "Add Media" button
		add_action( 'media_buttons_context', array( &$this, 'annotate_add_media_button' ) );

		//Define an AJAX handler for the 'Delete All Media' button
		add_filter( 'wp_ajax_delete_all_post_attachments', array( &$this, 'delete_all_post_attachments' ) );

		//'add_attachment'
		//'delete_attachment'
		//Make our Add Media button annotation available from an AJAX call
		add_action( 'wp_ajax_output_add_media_button_annotation', array( &$this, 'output_add_media_button_annotation' ) );

		//Add a link to the Settings page on the plugin management page
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( &$this, 'insert_settings_link' ), 2, 2 );

		//Define an AJAX handler for the 'Delete all inventory' button
		add_action( 'wp_ajax_delete_all_inventory', array( &$this, 'delete_all_inventory_ajax' ) );

		//Add a link to the main menu of the Admin bar
		add_action( 'admin_bar_menu', array( &$this, 'add_vehicles_to_admin_bar' ), 100 );
	}

	function create_add_media_button_annotation( ) {
		global $post;
		if( !is_object( $post ) && isset( $_POST['post_ID'] ) ) {
			/**
			 * This function is being called via AJAX and the
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
				'PDF'   => 0,
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
						$counts['PDF']++;
						break;
					default:
						$counts['other']++;
						break;
				}
			}
			if( 0 < ( $counts['image'] + $counts['video'] + $counts['text'] + $counts['PDF'] + $counts['other'] ) ) {
				$note = '';
				foreach( $counts as $key => $count ) {
					if( 0 < $count ) {
						if( '' != $note ) { $note .= ', '; }
						$note .= $count . ' ' . $key . ( 1 != $count ? 's' : '' );
					}
				}
				return $note;
			}
			return '0 photos';
		}
	}

	function create_delete_all_post_attachments_button( ) {
		global $post;
		if( ! is_object( $post ) || ! isset( $post->ID ) ) {
			return '';
		}
		//does this post have attachments?
		$post = get_post( $post->ID );
		if( $this->post_type() != $post->post_type ) {
			return '';
		}
		$attachments = get_children( array(
			'post_parent'    => $post->ID,
			'post_type'      => 'attachment',
			'posts_per_page' => -1,
		) );
		if( 0 === sizeof( $attachments ) ) {
			return '';
		}
		return '<button type="button" id="delete-media-button" class="button" onclick="delete_all_post_attachments( );">' .
			'<span class="wp-media-buttons-icon"></span> Delete All Media</button>';
	}

	function delete_all_data_and_deactivate( ) {
		/**
		 * This function will operate as an uninstall utility. Removes all the
		 * data we have added to the database.
		 */
		set_time_limit( 0 );

		//delete all the vehicles
		$deleted_count = $this->delete_all_inventory();

		//delete all terms
		$taxonomies = new Inventory_Presser_Taxonomies( $this->post_type );
		foreach( $taxonomies->query_vars_array() as $taxonomy ) {
			$terms = get_terms( array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
			) );
			foreach( $terms as $term ) {
				wp_delete_term( $term->term_id, $taxonomy );
			}
		}

		do_action( 'inventory_presser_delete_all_data' );
	}

	function delete_all_inventory( ) {
		/**
		 * This function deletes all posts that exist of our custom post type
		 * and their associated meta data. Returns the number of vehicles
		 * deleted.
		 */
		set_time_limit( 0 );

		$args = array(
			'post_status'    => 'any',
			'post_type'      => $this->post_type(),
			'posts_per_page' => -1,
		);
		$posts = get_posts( $args );
		$deleted_count = 0;
		if ( $posts ){
			$upload_dir = wp_upload_dir();
			foreach( $posts as $post ){
				//delete post attachments
				$attachment = array(
					'posts_per_page' => -1,
					'post_type'      => 'attachment',
					'post_parent'    => $post->ID,
				);

				$attachment_dir = '';

				foreach( get_posts( $attachment ) as $attached ){
					$attachment_dir = get_attached_file( $attached->ID );
					//delete the attachment
					wp_delete_attachment( $attached->ID, true );
				}

				//delete the parent post or vehicle
				wp_delete_post( $post->ID, true );
				$deleted_count++;

				//delete the photo folder if it exists (and is empty)
				if( '' != $attachment_dir ) {
					$dir_path = dirname( $attachment_dir );
					if( is_dir( $dir_path ) && $dir_path != $upload_dir['basedir'] ) {
						@rmdir( $dir_path );
					}
				}
			}
		}
		/**
		 * Delete media that is managed by this plugin but may not be attached
		 * to a vehicle at this time.
		 */
		$orphan_media = get_posts( array(
			'posts_per_page' => -1,
			'post_status'    => 'any',
			'post_type'      => 'attachment',
			'meta_query'     => array(
				array(
					'key'     => '_inventory_presser_photo_number',
					'compare' => 'EXISTS'
				)
			),
		) );
		foreach( $orphan_media as $post ) {
			wp_delete_post( $post->ID );
		}

		do_action( 'inventory_presser_delete_all_inventory' );

		return $deleted_count;
	}

	/**
	 * AJAX handler for the 'Delete all Inventory' button on options page.
	 *
	 * Deletes all inventory and echoes a response to the JavaScript caller.
	 */
	function delete_all_inventory_ajax( ) {
		$delete_result = $this->delete_all_inventory();
		if ( is_wp_error( $delete_result ) ) {
			//output error
			echo "<div id='import-error' class='settings-error'><p><strong>" . $delete_result->get_error_message( ) . "</strong></p></div>";
		} else {
			//output the success result, it's a string of html
			echo '
				<div id="setting-error-settings_updated" class="updated settings-error notice is-dismissible">
					<p><strong>' .
					( 0 == $delete_result ? 'There are no vehicles to delete.' : 'Deleted all vehicles.' )
					. '</strong></p>
					<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>
				</div>';
		}
		wp_die();
	}

	function delete_all_post_attachments( ) {

		$post_id = isset( $_POST['post_ID'] ) ? $_POST['post_ID'] : 0;

		if( ! isset( $post_id ) ) {

			return; // Will die in case you run a function like this: delete_post_media($post_id); if you will remove this line - ALL ATTACHMENTS WHO HAS A PARENT WILL BE DELETED PERMANENTLY!

		} elseif ( 0 == $post_id ) {

			return; // Will die in case you have 0 set. there's no page id called 0 :)

		} elseif( is_array( $post_id ) ) {

			return; // Will die in case you place there an array of pages.

		} else {

		    $attachments = get_posts( array(
		        'post_type'      => 'attachment',
		        'posts_per_page' => -1,
		        'post_status'    => 'any',
		        'post_parent'    => $post_id
		    ) );

		    foreach ( $attachments as $attachment ) {
		        if ( false === wp_delete_attachment( $attachment->ID ) ) {
		            // Log failure to delete attachment.
		           	error_log( 'Failed to delete attachment ' . $attachment->ID . ' in ' . __FILE__ );
		        }
		    }
		}
	}

	//Handle the ORDER BY on the vehicle list (edit.php) when sorting by photo count
	function enable_order_by_attachment_count( $pieces, $query ) {
		if( ! is_admin() ) { return $pieces; }

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

			if( 'inventory_presser_photo_count' == $orderby || 'inventory_presser_thumbnail' == $orderby ) {
				global $wpdb;
				$pieces[ 'orderby' ] = "( SELECT COUNT( ID ) FROM {$wpdb->posts} forget WHERE post_parent = {$wpdb->posts}.ID ) $order, " . $pieces[ 'orderby' ];
			}
	   }
	   return $pieces;
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

	function insert_settings_link( $links ) {
		$links[] = '<a href="options-general.php?page=' . $this->product_name_slug() . '_settings">Settings</a>';
		return $links;
	}

	function load_scripts($hook) {
		wp_enqueue_style( 'my-admin-theme', plugins_url( '/css/wp-admin.css', dirname( __FILE__ ) ) );
		wp_register_script( 'inventory-presser-javascript', plugins_url( '/js/admin.js', dirname( __FILE__ ) ) );
		wp_enqueue_script( 'inventory-presser-javascript' );
	}

	function make_vehicles_table_columns_sortable( $columns ) {
		$custom = array(
			// meta column id => sortby value used in query
			'inventory_presser_color'        => 'inventory_presser_color',
			'inventory_presser_odometer'     => 'inventory_presser_odometer',
			'inventory_presser_price'        => 'inventory_presser_price',
			'inventory_presser_stock_number' => 'inventory_presser_stock_number',
			'inventory_presser_photo_count'  => 'inventory_presser_photo_count',
			'inventory_presser_thumbnail'    => 'inventory_presser_thumbnail',
		);
		return wp_parse_args( $custom, $columns );
	}

	function meta_box_html_featured( $post ) {
		echo '<input type="checkbox" id="inventory_presser_featured" name="inventory_presser_featured" ' .
			checked( '1', get_post_meta( $post->ID, apply_filters( 'translate_meta_field_key', 'featured' ), true ), false ) .
			' value="1"><label for="inventory_presser_featured">Featured in slideshows</label>';
	}

	function meta_box_html_options( $post ) {
		$options = apply_filters( 'inventory_presser_default_options', array(
			'3rd Row Seats' => false,
			'Air Bags' => false,
			'Air Conditioning' => false,
			'Alloy Wheels' => false,
			'Aluminum Wheels' => false,
			'AM/FM Stereo' => false,
			'Anti-lock Brakes' => false,
			'Backup Camera' => false,
			'Bed Cap' => false,
			'Bluetooth, Hands Free' => false,
			'Cassette' => false,
			'CD Player' => false,
			'Cell or Intergrated Cell Phone' => false,
			'Cloth Seats' => false,
			'Conversion Package' => false,
			'Convertible' => false,
			'Cooled Seats' => false,
			'Cruise Control' => false,
			'Custom Paint' => false,
			'Disability Equipped' => false,
			'Dual Sliding Doors' => false,
			'DVD Player' => false,
			'Extended Cab' => false,
			'Fog Lights' => false,
			'Heated Seats' => false,
			'Keyless Entry' => false,
			'Leather Seats' => false,
			'Lift Kit' => false,
			'Long Bed' => false,
			'Memory Seat(s)' => false,
			'Moon Roof' => false,
			'Multi-zone Climate Control' => false,
			'Navigation System' => false,
			'Oversize Off Road Tires' => false,
			'Portable Audio Connection' => false,
			'Power Brakes' => false,
			'Power Lift Gate' => false,
			'Power Locks' => false,
			'Power Seats' => false,
			'Power Steering' => false,
			'Power Windows' => false,
			'Premium Audio' => false,
			'Premium Wheels' => false,
			'Privacy Glass' => false,
			'Quad Seating' => false,
			'Rear Air Bags' => false,
			'Rear Air Conditioning' => false,
			'Rear Defroster' => false,
			'Rear Heat' => false,
			'Refrigerator' => false,
			'Roof Rack' => false,
			'Running Boards' => false,
			'Satellite Radio' => false,
			'Security System' => false,
			'Short Bed' => false,
			'Side Air Bags' => false,
			'Skid Plate(s)' => false,
			'Snow Plow' => false,
			'Spoiler' => false,
			'Sport Package' => false,
			'Step Side Bed' => false,
			'Steering Wheel Controls' => false,
			'Styled Steel Wheels' => false,
			'Sunroof' => false,
			'Supercharger' => false,
			'Tilt Steering Wheel' => false,
			'Tonneau Cover' => false,
			'Topper' => false,
			'Tow Package' => false,
			'Traction Control' => false,
			'Trailer Hitch' => false,
			'Turbo' => false,
			'Two Tone Paint' => false,
			'Wide Tires' => false,
			'Winch' => false,
			'Wire Wheels' => false,
			'Xenon Headlights' => false,
		) );
		$options_arr = get_post_meta( $post->ID, apply_filters( 'translate_meta_field_key', 'option_array' ), true );
		if( is_array( $options_arr ) ) {
			foreach( $options_arr as $option ) {
				$options[$option] = true;
			}
		}
		//sort the array by key
		ksort( $options );
		//output a bunch of checkboxes
		$HTML = '<div class="list-with-columns"><ul class="optional-equipment">';
		foreach( $options as $key => $value ) {
			//element IDs cannot contain slashes, spaces or parentheses
			$id = 'option-' . preg_replace( '/\/\(\)/i', '', str_replace( ' ', '_', $key ) );
			$HTML .= '<li><input type="checkbox" id="'. $id .'" name="'. $id .'" value="'. $key .'"'
				. checked( true, $value, false )
				. '>'
				. '<label for="'. $id .'">' . $key . '</label></li>';
		}
		$HTML .= '</ul></div>';

		echo $HTML;
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

		//boat items
		$beam = ( isset( $custom['inventory_presser_beam'] ) ? $custom['inventory_presser_beam'][0] : '' );
		$length = ( isset( $custom['inventory_presser_length'] ) ? $custom['inventory_presser_length'][0] : '' );
		$hull_material = ( isset( $custom['inventory_presser_hull_material'] ) ? $custom['inventory_presser_hull_material'][0] : '' );

		echo '<table class="form-table"><tbody>'

		//VIN
			. '<tr><th scope="row"><label for="inventory_presser_vin">VIN</label></th>'
			. '<td>'
			. apply_filters( 'inventory_presser_edit_control_vin', '<input type="text" name="inventory_presser_vin" maxlength="17" value="'. $VIN .'">' )
			. '</td>'

		//Stock number
			. '<tr><th scope="row"><label for="inventory_presser_stock_number">Stock number</label></th>'
			. '<td><input type="text" name="inventory_presser_stock_number" value="'. $stock_number .'"></td>'

		//Year
			. '<tr><th scope="row"><label for="inventory_presser_year">Year</label></th>'
			. '<td><select name="inventory_presser_year"><option></option>';

		for( $y=date('Y')+2; $y>=1920; $y-- ) {
			echo '<option';
			if ( $y == $year ) {
				echo ' selected="selected"';
			}
			echo '>' .$y. '</option> ';
		}

		echo '</select></td></tr>'

		//Make
			. '<tr><th scope="row"><label for="inventory_presser_make">Make</label></th>'
			. '<td><input type="text" name="inventory_presser_make" value="' .$make. '"></td></tr>'

		//Model
			. '<tr><th scope="row"><label for="inventory_presser_model">Model</label></th>'
			. '<td><input type="text" name="inventory_presser_model" value="' .$model. '"></td></tr>'

		//Trim level
			. '<tr><th scope="row"><label for="inventory_presser_trim">Trim</label></th>'
			. '<td><input type="text" name="inventory_presser_trim" value="' .$trim. '"></td></tr>'

		//Engine
			. '<tr><th scope="row"><label for="inventory_presser_engine">Engine</label></th>'
			. '<td><input type="text" name="inventory_presser_engine" value="' .$engine. '"></td></tr>'

		//Body style
			. '<tr><th scope="row"><label for="inventory_presser_body_style">Body style</label></th>'
			. '<td><input type="text" name="inventory_presser_body_style" id="inventory_presser_body_style" value="' .$body_style. '">'

			. '<select name="inventory_presser_body_style_hidden" id="inventory_presser_body_style_hidden">';

			$boat_styles = array(
				'Bass boat',
				'Bow Rider',
				'Cabin Cruiser',
				'Center Console',
				'Cuddy Cabin',
				'Deck boat',
				'Performance',
				'Pontoon',
			);
			foreach( $boat_styles as $s ) {
				echo '<option' . selected( $s, $body_style ) . '>' .$s. '</option>';
			}

			echo '</select></td></tr>'

		//Color
			. '<tr><th scope="row"><label for="inventory_presser_color">Color</label></th>'
			. '<td><input type="text" name="inventory_presser_color" value="' .$color. '"></td></tr>'

		//Interior color
			. '<tr><th scope="row"><label for="inventory_presser_interior_color">Interior color</label></th>'
			. '<td><input type="text" name="inventory_presser_interior_color" value="' .$interior_color. '"></td></tr>'

		//Odometer
			. '<tr><th scope="row"><label for="inventory_presser_odometer">Odometer</label></th>'
			. '<td><input type="text" name="inventory_presser_odometer" value="' .$odometer. '"> <span class="invp_odometer_units">miles</span></td></tr>'

		//Price
			. '<tr><th scope="row"><label for="inventory_presser_price">Price</label></th>'
			. '<td><input type="text" name="inventory_presser_price" value="' .$price. '"></td></tr>'

		//Beam (boats)
			. '<tr class="boat-postmeta"><th scope="row"><label for="inventory_presser_beam">Beam</label></th>'
			. '<td><input type="text" name="inventory_presser_beam" value="' .$beam. '"></td></tr>'

		//Length (boats)
			. '<tr class="boat-postmeta"><th scope="row"><label for="inventory_presser_length">Length</label></th>'
			. '<td><input type="text" name="inventory_presser_length" value="' .$length. '"></td></tr>'

		//Hull material
			. '<tr class="boat-postmeta"><th scope="row"><label for="inventory_presser_hull_material">Hull material</label></th>'
			. '<td><select name="inventory_presser_hull_material"><option></option>';

			$hull_materials = array(
				'Aluminum',
				'Carbon Fiber',
				'Composite',
				'Ferro-Cement',
				'Fiberglass',
				'Hypalon',
				'Other',
				'PVC',
				'Steel',
				'Wood',
			);
			foreach( $hull_materials as $m ) {
				echo '<option' . selected( $m, $hull_material ) . '>' .$m. '</option>';
			}
			echo '</select>'

			. '</tbody></table>';
	}

	function move_tags_meta_box( ) {
		//Remove and re-add the "Tags" meta box so it ends up at the bottom for our CPT
		global $wp_meta_boxes;
		unset( $wp_meta_boxes[$this->post_type()]['side']['core']['tagsdiv-post_tag'] );
		add_meta_box( 'tagsdiv-post_tag', 'Tags', 'post_tags_meta_box', $this->post_type(), 'side', 'core', array( 'taxonomy' => 'post_tag' ));
	}

	function output_add_media_button_annotation( ) { //because AJAX
		echo $this->create_add_media_button_annotation( );
		wp_die();
	}

	function output_thumbnail_size_error_html() {
		echo $this->get_admin_notice_html(
			'At least one of your thumbnail sizes does not have an aspect ratio of 4:3, which is the most common smartphone and digital camera aspect ratio. You can change thumbnail sizes <a href="options-media.php">here</a>.',
			'yellow'
		);
	}

	function output_upload_folder_error_html() {
		echo $this->get_admin_notice_html(
			'Your media settings are configured to organize uploads into month- and year-based folders. This is not optimal for '.self::PRODUCT_NAME.', and you can turn this setting off on <a href="options-media.php">this page</a>.',
			'yellow'
		);
	}

	function populate_columns_we_added_to_vehicles_table( $column_name, $post_id ) {
		$custom_fields = get_post_custom( $post_id );
		$val = ( isset( $custom_fields[$column_name] ) ? $custom_fields[$column_name][0] : '' );
		switch( $column_name ) {

			case 'inventory_presser_thumbnail':
				echo get_the_post_thumbnail( $post_id, 'thumbnail' );
				break;

			case 'inventory_presser_odometer':
				$vehicle = new Inventory_Presser_Vehicle();
				$vehicle->odometer = $val;
				echo $vehicle->odometer();
				break;

			case 'inventory_presser_photo_count':
				echo count( get_children( array( 'post_parent' => $post_id ) ) );
				break;

			case 'inventory_presser_price':
				$vehicle = new Inventory_Presser_Vehicle();
				$vehicle->price = $val;
				echo $vehicle->price( '-' );
				break;

			default:
				echo $val;
		}
	}

	function post_type() {
		return $this->post_type;
	}

	function product_name_slug( $suffix = '' ) {
		return strtolower( str_replace( ' ', '_', self::PRODUCT_NAME ) . $suffix );
	}

	function save_vehicle_post_meta( $post_id, $post, $is_update ) {

		//Do not continue if the post is being moved to the trash
        if( 'trash' == $post->post_status ) {
        	return;
        }

		//if we are not coming from the new/edit post page, we want to abort
		if( ! isset( $_POST['post_title'] ) ) {
			return;
		}

		/**
		 * Tick the last modified date of this vehicle since we're saving changes.
		 * It looks like this: Tue, 06 Sep 2016 09:26:12 -0400
		 */
		$offset = sprintf( '%+03d00', intval( get_option('gmt_offset') ) );
		update_post_meta( $post->ID, apply_filters( 'translate_meta_field_key', 'last_modified' ), current_time( 'D, d M Y h:i:s' ) . ' ' . $offset );

		//Clear this value that is defined by a checkbox
		update_post_meta( $post->ID, apply_filters( 'translate_meta_field_key', 'featured' ), '0' );

		/**
		 * Loop over the post meta keys we manage and save their values
		 * if we find them coming over as part of the post to save.
		 */
		$vehicle = new Inventory_Presser_Vehicle( $post_id );

		foreach( $vehicle->keys() as $key ) {
			$key = apply_filters( 'translate_meta_field_key', $key );
			if ( isset( $_POST[$key] ) ) {
				update_post_meta( $post->ID, $key, sanitize_text_field( $_POST[$key] ) );

				//if price
				if( apply_filters( 'translate_meta_field_key', 'price' ) == $key ) {
					//value also saved in another meta key. update that, too
					$price_arr_key = apply_filters( 'translate_meta_field_key', 'prices' );
					$price_arr = get_post_meta( $post->ID, $price_arr_key, true );
					$price_arr['price'] = sanitize_text_field( $_POST[$key] );
					update_post_meta( $post->ID, $price_arr_key, $price_arr );
				}
			}
		}

		/**
		 * Loop over the keys in the $_POST object to find all the options
		 * check boxes
		 */
		$options = array();
		foreach( $_POST as $key => $val ) {
			if( 'option-' == substr( $key, 0, 7 ) ) {
				array_push( $options, $val );
			}
		}
		update_post_meta( $post->ID, apply_filters( 'translate_meta_field_key', 'option_array' ), $options );
	}

	function scan_for_recommended_settings_and_create_warnings() {
		/**
		 * Suggest values for WordPress internal settings if the user has values
		 * we do not prefer
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

	function vehicles_table_columns_orderbys( $query ) {

		if( ! is_admin() || ! $query->is_main_query() ) { return; }

		$columns = array(
			'color',
			'odometer',
			'price',
			'stock_number',
		);
		$vehicle = new Inventory_Presser_Vehicle();
		$orderby = $query->get( 'orderby' );
		foreach( $columns as $column ) {
			$meta_key = apply_filters( 'translate_meta_field_key', $column );
			if ( $orderby == $meta_key ) {
	            $query->set( 'meta_key', $meta_key );
	            $query->set( 'orderby', 'meta_value' . ( $vehicle->post_meta_value_is_number( $meta_key ) ? '_num' : '' ) );
	            return;
			}
		}
	}
}
