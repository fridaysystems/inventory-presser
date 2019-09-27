<?php
defined( 'ABSPATH' ) or exit;

/**
 * An object that customizes the administrator dashboard to make managing an
 * inventory of vehicles easy.
 *
 *
 * @since      1.3.1
 * @package    Inventory_Presser
 * @subpackage Inventory_Presser/includes
 * @author     Corey Salzano <corey@friday.systems>, John Norton <norton@fridaynet.com>
 */
class Inventory_Presser_Customize_Dashboard {

	const PRODUCT_NAME = 'Inventory Presser';

	function add_columns_to_vehicles_table( $column ) {
		//add our columns
		$column[apply_filters( 'invp_prefix_meta_key', 'stock_number' )] = 'Stock #';
		$column[apply_filters( 'invp_prefix_meta_key', 'color' )] = 'Color';
		$column[apply_filters( 'invp_prefix_meta_key', 'odometer' )] = 'Odometer';
		$column[apply_filters( 'invp_prefix_meta_key', 'price' )] = 'Price';
		$column[apply_filters( 'invp_prefix_meta_key', 'photo_count' )] = 'Photos';
		$column[apply_filters( 'invp_prefix_meta_key', 'thumbnail' )] = 'Thumbnail';
		//remove the date and tags columns
		unset( $column['date'] );
		unset( $column['tags'] );
		return $column;
	}


	function add_meta_boxes_to_cpt( ) {
		//Add a meta box to the New/Edit post page
		add_meta_box('vehicle-meta', 'Attributes', array( $this, 'meta_box_html_vehicle' ), Inventory_Presser_Plugin::CUSTOM_POST_TYPE, 'advanced', 'high' );

		//and another for prices
		add_meta_box('prices-meta', 'Prices', array( $this, 'meta_box_html_prices' ), Inventory_Presser_Plugin::CUSTOM_POST_TYPE, 'advanced', 'high' );

		//Add another meta box to the New/Edit post page
		add_meta_box('options-meta', 'Optional equipment', array( $this, 'meta_box_html_options' ), Inventory_Presser_Plugin::CUSTOM_POST_TYPE, 'normal', 'high' );

		//Add a meta box to the side column for a featured vehicle checkbox
		add_meta_box('featured', 'Featured Vehicle', array( $this, 'meta_box_html_featured' ), Inventory_Presser_Plugin::CUSTOM_POST_TYPE, 'side', 'low' );
	}

	//Add a setting to the customizer's Colors panel for Carfax button text
	function add_settings_to_customizer( $wp_customize ) {
		$wp_customize->add_setting( 'carfax_text_color', array(
			'type'       => 'theme_mod',
			'capability' => 'edit_theme_options',
			'default'    => 'black',
			'transport'  => 'refresh',
		) );

		$wp_customize->add_control( 'carfax_text_color', array(
			'type'        => 'select',
			'settings'    => 'carfax_text_color',
			'priority'    => 40,
			'section'     => 'colors',
			'label'       => __( 'CARFAX Button Text', 'inventory-presser' ),
			'description' => __( 'The color of the words "SHOW ME THE" in CARFAX buttons.', 'inventory-presser' ),
            'choices'  => array(
                'black' => 'Black',
                'white' => 'White',
             ),
		) );
	}

	function add_vehicles_to_admin_bar() {

		//do not do this if we are already looking at the dashboard
		if( is_admin() ) { return; }

		global $wp_admin_bar;

		$wp_admin_bar->add_node( array(
			'id'     => 'wp-admin-bar-vehicles',
			'title'  => 'Vehicles',
			'href'   => admin_url( 'edit.php?post_type=' . Inventory_Presser_Plugin::CUSTOM_POST_TYPE ),
			'parent' => 'site-name',
		) );
	}

	function annotate_add_media_button( $editor_id ) {

		if( 'content' != $editor_id ) { return; }

		printf(
			'%s<span id="media-annotation" class="annotation">%s</span>',
			$this->create_delete_all_post_attachments_button(),
			$this->create_add_media_button_annotation()
		);
	}

	function change_post_updated_messages( $msgs ) {
		global $post;

		$view_link = sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( get_permalink ( $post->ID ) ),
			__( 'View vehicle', 'inventory-presser' )
    	);

    	$preview_link = sprintf(
    		'<a target="_blank" href="%1$s">%2$s</a>',
			esc_url( get_preview_post_link( $post->ID ) ),
			__( 'Preview vehicle', 'inventory-presser' )
    	);

    	$scheduled_date = date_i18n( __( 'M j, Y @ H:i', 'inventory-presser' ), strtotime( $post->post_date ) );

		$msgs[Inventory_Presser_Plugin::CUSTOM_POST_TYPE] = array(
			0  => '',
			1  => __( 'Vehicle updated. ', 'inventory-presser' ) . $view_link,
			2  => __( 'Custom field updated.', 'inventory-presser' ),
			3  => __( 'Custom field updated.', 'inventory-presser' ),
			4  => __( 'Vehicle updated.', 'inventory-presser' ),
			5  => isset( $_GET['revision'] ) ? sprintf( __( 'Vehicle restored to revision from %s.', 'inventory-presser' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
    		6  => __( 'Vehicle published. ', 'inventory-presser' ) . $view_link,
			7  => __( 'Vehicle saved.', 'inventory-presser' ),
    		8  => __( 'Vehicle submitted. ', 'inventory-presser' ) . $preview_link,
    		9  => sprintf( __( 'Vehicle scheduled to list on <strong>%s</strong>. ', 'inventory-presser' ), $scheduled_date ) . $preview_link,
    		10 => __( 'Vehicle draft updated. ', 'inventory-presser' ) . $preview_link,
		);
		return $msgs;
	}

	function change_taxonomy_show_ui_attributes( $taxonomy_data ) {
		for( $i=0; $i<sizeof( $taxonomy_data ); $i++ ) {
			if( ! isset( $taxonomy_data[$i]['args']['show_in_menu'] ) ) {
				continue;
			}

			$taxonomy_data[$i]['args']['show_in_menu'] = true;
			$taxonomy_data[$i]['args']['show_ui'] = true;
		}
		return $taxonomy_data;
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

		if( Inventory_Presser_Plugin::CUSTOM_POST_TYPE != $post->post_type ) {
			return;
		}

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

	function create_delete_all_post_attachments_button( ) {
		global $post;
		if( ! is_object( $post ) || ! isset( $post->ID ) ) {
			return '';
		}
		//does this post have attachments?
		$post = get_post( $post->ID );
		if( Inventory_Presser_Plugin::CUSTOM_POST_TYPE != $post->post_type ) {
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
		return '<button type="button" id="delete-media-button" class="button" onclick="delete_all_post_attachments( );">'
			. '<span class="wp-media-buttons-icon"></span> Delete All Media</button>';
	}

	function default_price_array_keys() {
		return array(
			'down_payment',
			'msrp',
			'payment',
			'payment_frequency',
			'price',
		);
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
		$taxonomies = new Inventory_Presser_Taxonomies();
		foreach( $taxonomies->query_vars_array() as $taxonomy ) {
			$terms = get_terms( array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
			) );
			foreach( $terms as $term ) {
				wp_delete_term( $term->term_id, $taxonomy );
			}
		}

		do_action( 'invp_delete_all_data' );
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
			'post_type'      => Inventory_Presser_Plugin::CUSTOM_POST_TYPE,
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
					'key'     => apply_filters( 'invp_prefix_meta_key', 'photo_number' ),
					'compare' => 'EXISTS'
				)
			),
		) );
		foreach( $orphan_media as $post ) {
			wp_delete_post( $post->ID );
		}

		do_action( 'invp_delete_all_inventory' );

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

			if( apply_filters( 'invp_prefix_meta_key', 'photo_count' ) == $orderby
				|| apply_filters( 'invp_prefix_meta_key', 'thumbnail' ) == $orderby )
			{
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
		return sprintf(
			'<div class="%s notice"><p><strong>%s</strong></p></div>',
			$type,
			__( $message, 'inventory-presser' )
		);
	}

	function hooks() {

		add_filter( 'posts_clauses', array( $this, 'enable_order_by_attachment_count' ), 1, 2 );

		//Save custom post data when posts are saved
		add_action( 'save_post_' . Inventory_Presser_Plugin::CUSTOM_POST_TYPE, array( $this, 'save_vehicle_post_meta' ), 10, 3 );

		//Add columns to the table that lists all the Vehicles on edit.php
		add_filter( 'manage_' . Inventory_Presser_Plugin::CUSTOM_POST_TYPE . '_posts_columns', array( $this, 'add_columns_to_vehicles_table' ) );

		//Populate the columns we added to the Vehicles table
		add_action( 'manage_' . Inventory_Presser_Plugin::CUSTOM_POST_TYPE . '_posts_custom_column', array( $this, 'populate_columns_we_added_to_vehicles_table' ), 10, 2 );

		//Make our added columns to the Vehicles table sortable
		add_filter( 'manage_edit-' . Inventory_Presser_Plugin::CUSTOM_POST_TYPE . '_sortable_columns', array( $this, 'make_vehicles_table_columns_sortable' ) );

		//Implement the orderby for each of these added columns
		add_filter( 'pre_get_posts', array( $this, 'vehicles_table_columns_orderbys' ) );

		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes_to_cpt' ) );

		// Move all "advanced" meta boxes above the default editor
		// http://wordpress.stackexchange.com/a/88103
		add_action( 'edit_form_after_title', array( $this, 'move_advanced_meta_boxes' ) );

		//Move the "Tags" metabox below the meta boxes for vehicle custom taxonomies
		add_action( 'add_meta_boxes', array( $this, 'move_tags_meta_box' ), 0 );

		//Load our scripts
		add_action( 'admin_enqueue_scripts', array( $this, 'load_scripts' ) );

		//Add some content next to the "Add Media" button
		add_action( 'media_buttons', array( $this, 'annotate_add_media_button' ) );

		//Define an AJAX handler for the 'Delete All Media' button
		add_filter( 'wp_ajax_delete_all_post_attachments', array( $this, 'delete_all_post_attachments' ) );

		//'add_attachment'
		//'delete_attachment'
		//Make our Add Media button annotation available from an AJAX call
		add_action( 'wp_ajax_output_add_media_button_annotation', array( $this, 'output_add_media_button_annotation' ) );

		//Add a link to the Settings page on the plugin management page
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'insert_settings_link' ), 2, 2 );

		//Define an AJAX handler for the 'Delete all inventory' button
		add_action( 'wp_ajax_delete_all_inventory', array( $this, 'delete_all_inventory_ajax' ) );

		//Add a link to the main menu of the Admin bar
		add_action( 'admin_bar_menu', array( $this, 'add_vehicles_to_admin_bar' ), 100 );

		$options = Inventory_Presser_Plugin::settings();
		if( isset( $options['use_carfax'] ) && $options['use_carfax'] ) {
			add_action( 'customize_register', array( $this, 'add_settings_to_customizer' ) );
		}

		//If the Show All Taxonomies setting is checked, change the way we register taxonomies
		if( isset( $options['show_all_taxonomies'] ) && $options['show_all_taxonomies'] ) {
			add_filter( 'invp_taxonomy_data', array( $this, 'change_taxonomy_show_ui_attributes' ) );
		}

		//Change some messages in the dashboard the user sees when updating vehicles
		add_filter( 'post_updated_messages', array( $this, 'change_post_updated_messages' ) );
	}

	function insert_settings_link( $links ) {
		$links[] = sprintf(
			'<a href="options-general.php?page=%s_settings">Settings</a>',
			$this->product_name_slug()
		);
		return $links;
	}

	function load_scripts($hook) {
		wp_enqueue_style( 'my-admin-theme', plugins_url( '/css/wp-admin.css', dirname( __FILE__ ) ) );
		wp_register_script( 'inventory-presser-javascript', plugins_url( '/js/admin.js', dirname( __FILE__ ) ) );
		wp_enqueue_script( 'inventory-presser-javascript' );

		//localize an odometer units word for the edit vehicle page
		wp_localize_script( 'inventory-presser-javascript', 'invp', array(
			'miles_word'  => apply_filters( 'invp_odometer_word', 'miles' ),
			'meta_prefix' => Inventory_Presser_Plugin::meta_prefix(),
		) );
	}

	function make_vehicles_table_columns_sortable( $columns ) {
		$custom = array(
			// meta column id => sortby value used in query
			apply_filters( 'invp_prefix_meta_key', 'color' )        => apply_filters( 'invp_prefix_meta_key', 'color' ),
			apply_filters( 'invp_prefix_meta_key', 'odometer' )     => apply_filters( 'invp_prefix_meta_key', 'odometer' ),
			apply_filters( 'invp_prefix_meta_key', 'price' )        => apply_filters( 'invp_prefix_meta_key', 'price' ),
			apply_filters( 'invp_prefix_meta_key', 'stock_number' ) => apply_filters( 'invp_prefix_meta_key', 'stock_number' ),
			apply_filters( 'invp_prefix_meta_key', 'photo_count' )  => apply_filters( 'invp_prefix_meta_key', 'photo_count' ),
			apply_filters( 'invp_prefix_meta_key', 'thumbnail' )    => apply_filters( 'invp_prefix_meta_key', 'thumbnail' ),
		);
		return wp_parse_args( $custom, $columns );
	}

	function meta_box_html_featured( $post ) {
		$meta_key = apply_filters( 'invp_prefix_meta_key', 'featured' );
		printf( '<input type="checkbox" id="%s" name="%s" value="1"%s><label for="%s">%s</label>',
			$meta_key,
			$meta_key,
			checked( '1', get_post_meta( $post->ID, $meta_key, true ), false ),
			$meta_key,
			__( 'Featured in Slideshows', 'inventory-presser' )
		);
	}

	function meta_box_html_options( $post ) {
		$options = apply_filters( 'invp_default_options', array(
			__( '3rd Row Seats', 'inventory-presser' ),
			__( 'Air Bags', 'inventory-presser' ),
			__( 'Air Conditioning', 'inventory-presser' ),
			__( 'Alloy Wheels', 'inventory-presser' ),
			__( 'Aluminum Wheels', 'inventory-presser' ),
			__( 'AM/FM Stereo', 'inventory-presser' ),
			__( 'Anti-lock Brakes', 'inventory-presser' ),
			__( 'Backup Camera', 'inventory-presser' ),
			__( 'Bed Cap', 'inventory-presser' ),
			__( 'Bluetooth, Hands Free', 'inventory-presser' ),
			__( 'Cassette', 'inventory-presser' ),
			__( 'CD Player', 'inventory-presser' ),
			__( 'Cell or Intergrated Cell Phone', 'inventory-presser' ),
			__( 'Cloth Seats', 'inventory-presser' ),
			__( 'Conversion Package', 'inventory-presser' ),
			__( 'Convertible', 'inventory-presser' ),
			__( 'Cooled Seats', 'inventory-presser' ),
			__( 'Cruise Control', 'inventory-presser' ),
			__( 'Custom Paint', 'inventory-presser' ),
			__( 'Disability Equipped', 'inventory-presser' ),
			__( 'Dual Sliding Doors', 'inventory-presser' ),
			__( 'DVD Player', 'inventory-presser' ),
			__( 'Extended Cab', 'inventory-presser' ),
			__( 'Fog Lights', 'inventory-presser' ),
			__( 'Heated Seats', 'inventory-presser' ),
			__( 'Keyless Entry', 'inventory-presser' ),
			__( 'Leather Seats', 'inventory-presser' ),
			__( 'Lift Kit', 'inventory-presser' ),
			__( 'Long Bed', 'inventory-presser' ),
			__( 'Memory Seat(s)', 'inventory-presser' ),
			__( 'Moon Roof', 'inventory-presser' ),
			__( 'Multi-zone Climate Control', 'inventory-presser' ),
			__( 'Navigation System', 'inventory-presser' ),
			__( 'Oversize Off Road Tires', 'inventory-presser' ),
			__( 'Portable Audio Connection', 'inventory-presser' ),
			__( 'Power Brakes', 'inventory-presser' ),
			__( 'Power Lift Gate', 'inventory-presser' ),
			__( 'Power Locks', 'inventory-presser' ),
			__( 'Power Seats', 'inventory-presser' ),
			__( 'Power Steering', 'inventory-presser' ),
			__( 'Power Windows', 'inventory-presser' ),
			__( 'Premium Audio', 'inventory-presser' ),
			__( 'Premium Wheels', 'inventory-presser' ),
			__( 'Privacy Glass', 'inventory-presser' ),
			__( 'Quad Seating', 'inventory-presser' ),
			__( 'Rear Air Bags', 'inventory-presser' ),
			__( 'Rear Air Conditioning', 'inventory-presser' ),
			__( 'Rear Defroster', 'inventory-presser' ),
			__( 'Rear Heat', 'inventory-presser' ),
			__( 'Refrigerator', 'inventory-presser' ),
			__( 'Roof Rack', 'inventory-presser' ),
			__( 'Running Boards', 'inventory-presser' ),
			__( 'Satellite Radio', 'inventory-presser' ),
			__( 'Security System', 'inventory-presser' ),
			__( 'Short Bed', 'inventory-presser' ),
			__( 'Side Air Bags', 'inventory-presser' ),
			__( 'Skid Plate(s)', 'inventory-presser' ),
			__( 'Snow Plow', 'inventory-presser' ),
			__( 'Spoiler', 'inventory-presser' ),
			__( 'Sport Package', 'inventory-presser' ),
			__( 'Step Side Bed', 'inventory-presser' ),
			__( 'Steering Wheel Controls', 'inventory-presser' ),
			__( 'Styled Steel Wheels', 'inventory-presser' ),
			__( 'Sunroof', 'inventory-presser' ),
			__( 'Supercharger', 'inventory-presser' ),
			__( 'Tilt Steering Wheel', 'inventory-presser' ),
			__( 'Tonneau Cover', 'inventory-presser' ),
			__( 'Topper', 'inventory-presser' ),
			__( 'Tow Package', 'inventory-presser' ),
			__( 'Traction Control', 'inventory-presser' ),
			__( 'Trailer Hitch', 'inventory-presser' ),
			__( 'Turbo', 'inventory-presser' ),
			__( 'Two Tone Paint', 'inventory-presser' ),
			__( 'Wheelchair Access', 'inventory-presser' ),
			__( 'Wide Tires', 'inventory-presser' ),
			__( 'Winch', 'inventory-presser' ),
			__( 'Wire Wheels', 'inventory-presser' ),
			__( 'Xenon Headlights', 'inventory-presser' ),
		) );

		//turn the array into an associative array with value false for all
		$options = array_fill_keys( $options, false );

		$options_arr = get_post_meta( $post->ID, apply_filters( 'invp_prefix_meta_key', 'option_array' ), true );
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
			$HTML .= sprintf(
				'<li><input type="checkbox" id="%s" name="%s" value="%s"%s><label for="%s">%s</label></li>',
				$id,
				$id,
				$key,
				checked( true, $value, false ),
				$id,
				$key
			);
		}
		echo $HTML . '</ul></div>';
	}

	function meta_box_html_prices( $post, $meta_box ) {

		$prices = array(
			'price'        => 'Price',
			'msrp'         => 'MSRP',
			'down_payment' => 'Down payment',
			'payment'      => 'Payment',
		);

		echo '<table class="form-table"><tbody>';
		foreach( $prices as $key => $label ) {
			$meta_key = apply_filters( 'invp_prefix_meta_key', $key );
			$value = get_post_meta( $post->ID, $meta_key, true );

			printf(
				'<tr><th scope="row"><label for="%s">%s</label></th>'
				. '<td><input type="text" name="%s" value="%s" onkeypress="return is_number(event)"></td></tr>',
				$meta_key,
				$label,
				$meta_key,
				$value
			);
		}

		//Payment frequency is a drop-down
		$meta_key = apply_filters( 'invp_prefix_meta_key', 'payment_frequency' );
		$payment_frequency = get_post_meta( $post->ID, $meta_key, true );

		printf(
			'<tr><th scope="row"><label for="%s">Payment frequency</label></th>'
			. '<td><select name="%s"><option></option>',
			$meta_key,
			$meta_key
		);

		$frequencies = apply_filters( 'invp_default_payment_frequencies', array(
			'Monthly'      => 'monthly',
			'Weekly'       => 'weekly',
			'Bi-weekly'    => 'biweekly',
			'Semi-monthly' => 'semimonthly',
		) );
		foreach( $frequencies as $key => $value ) {
			printf(
				'<option value="%s"%s>%s</option>',
				$value,
				selected( $payment_frequency, $value, false ),
				$key
			);
		}
		echo '</select></td></tr>';

		//handle all other keys in the prices array, could be any keys
		$prices_meta_key = apply_filters( 'invp_prefix_meta_key', 'prices' );
		$prices = get_post_meta( $post->ID, $prices_meta_key, true );
		if( is_array( $prices ) ) {
			foreach( $prices as $key => $value ) {
				if( ! in_array( $key, $this->default_price_array_keys() ) ) {
					//this is a price we need to display
					printf(
						'<tr><th scope="row"><label for="%s[%s]">%s</label></th>'
						. '<td><input type="text" name="%s[%s]" value="%s"></td></tr>',
						$prices_meta_key,
						$key,
						apply_filters( 'invp_custom_price_label', $key ),
						$prices_meta_key,
						$key,
						$value
					);
				}
			}
		}
		echo '</tbody></table>';
	}

	function meta_box_html_vehicle( $post, $meta_box ) {
		//HTML output for vehicle data meta box
		$custom = get_post_custom( $post->ID );

		$body_style = ( isset( $custom[apply_filters( 'invp_prefix_meta_key', 'body_style' )] ) ? $custom[apply_filters( 'invp_prefix_meta_key', 'body_style' )][0] : '' );
		$color = ( isset( $custom[apply_filters( 'invp_prefix_meta_key', 'color' )] ) ? $custom[apply_filters( 'invp_prefix_meta_key', 'color' )][0] : '' );
		$engine = ( isset( $custom[apply_filters( 'invp_prefix_meta_key', 'engine' )] ) ? $custom[apply_filters( 'invp_prefix_meta_key', 'engine' )][0] : '' );
		$interior_color = ( isset( $custom[apply_filters( 'invp_prefix_meta_key', 'interior_color' )] ) ? $custom[apply_filters( 'invp_prefix_meta_key', 'interior_color' )][0] : '' );
		$make = ( isset( $custom[apply_filters( 'invp_prefix_meta_key', 'make' )] ) ? $custom[apply_filters( 'invp_prefix_meta_key', 'make' )][0] : '' );
		$model = ( isset( $custom[apply_filters( 'invp_prefix_meta_key', 'model' )] ) ? $custom[apply_filters( 'invp_prefix_meta_key', 'model' )][0] : '' );
		$odometer = ( isset( $custom[apply_filters( 'invp_prefix_meta_key', 'odometer' )] ) ? $custom[apply_filters( 'invp_prefix_meta_key', 'odometer' )][0] : '' );
		$stock_number = ( isset( $custom[apply_filters( 'invp_prefix_meta_key', 'stock_number' )] ) ? $custom[apply_filters( 'invp_prefix_meta_key', 'stock_number' )][0] : '' );
		$trim = ( isset( $custom[apply_filters( 'invp_prefix_meta_key', 'trim' )] ) ? $custom[apply_filters( 'invp_prefix_meta_key', 'trim' )][0] : '' );
		$VIN = ( isset( $custom[apply_filters( 'invp_prefix_meta_key', 'vin' )] ) ? $custom[apply_filters( 'invp_prefix_meta_key', 'vin' )][0] : '' );
		$year = ( isset( $custom[apply_filters( 'invp_prefix_meta_key', 'year' )] ) ? $custom[apply_filters( 'invp_prefix_meta_key', 'year' )][0] : '' );

		//boat items
		$beam = ( isset( $custom[apply_filters( 'invp_prefix_meta_key', 'beam' )] ) ? $custom[apply_filters( 'invp_prefix_meta_key', 'beam' )][0] : '' );
		$length = ( isset( $custom[apply_filters( 'invp_prefix_meta_key', 'length' )] ) ? $custom[apply_filters( 'invp_prefix_meta_key', 'length' )][0] : '' );
		$hull_material = ( isset( $custom[apply_filters( 'invp_prefix_meta_key', 'hull_material' )] ) ? $custom[apply_filters( 'invp_prefix_meta_key', 'hull_material' )][0] : '' );

		printf(
			'<table class="form-table"><tbody>'

		//VIN
			. '<tr><th scope="row"><label for="%s">VIN</label></th>'
			. '<td>%s</td>'

		//Stock number
			. '<tr><th scope="row"><label for="%s">Stock number</label></th>'
			. '<td><input type="text" name="%s" value="%s"></td>'

		//Year
			. '<tr><th scope="row"><label for="%s">Year</label></th>'
			. '<td><select name="%s"><option></option>',

			apply_filters( 'invp_prefix_meta_key', 'vin' ),
			apply_filters( 'invp_edit_control_vin', sprintf(
				'<input type="text" name="%s" maxlength="17" value="%s">',
				apply_filters( 'invp_prefix_meta_key', 'vin' ),
				$VIN
			) ),

			apply_filters( 'invp_prefix_meta_key', 'stock_number' ),
			apply_filters( 'invp_prefix_meta_key', 'stock_number' ),
			$stock_number,

			apply_filters( 'invp_prefix_meta_key', 'year' ),
			apply_filters( 'invp_prefix_meta_key', 'year' )
		);

		for( $y=date('Y')+2; $y>=1920; $y-- ) {
			printf(
				'<option%s>%s</option>',
				selected( $y, $year, false ),
				$y
			);
		}

		printf(
			'</select></td></tr>'

		//Make
			. '<tr><th scope="row"><label for="%s">Make</label></th>'
			. '<td><input type="text" name="%s" value="%s"></td></tr>'

		//Model
			. '<tr><th scope="row"><label for="%s">Model</label></th>'
			. '<td><input type="text" name="%s" value="%s"></td></tr>'

		//Trim level
			. '<tr><th scope="row"><label for="%s">Trim</label></th>'
			. '<td><input type="text" name="%s" value="%s"></td></tr>'

		//Engine
			. '<tr><th scope="row"><label for="%s">Engine</label></th>'
			. '<td><input type="text" name="%s" value="%s"></td></tr>'

		//Body style
			. '<tr><th scope="row"><label for="%s">Body style</label></th>'
			. '<td><input type="text" name="%s" id="%s" value="%s">'

			. '<select name="%s_hidden" id="%s_hidden">',

			apply_filters( 'invp_prefix_meta_key', 'make' ),
			apply_filters( 'invp_prefix_meta_key', 'make' ),
			$make,

			apply_filters( 'invp_prefix_meta_key', 'model' ),
			apply_filters( 'invp_prefix_meta_key', 'model' ),
			$model,

			apply_filters( 'invp_prefix_meta_key', 'trim' ),
			apply_filters( 'invp_prefix_meta_key', 'trim' ),
			$trim,

			apply_filters( 'invp_prefix_meta_key', 'engine' ),
			apply_filters( 'invp_prefix_meta_key', 'engine' ),
			$engine,

			apply_filters( 'invp_prefix_meta_key', 'body_style' ),
			apply_filters( 'invp_prefix_meta_key', 'body_style' ),
			apply_filters( 'invp_prefix_meta_key', 'body_style' ),
			$body_style,
			apply_filters( 'invp_prefix_meta_key', 'body_style' ),
			apply_filters( 'invp_prefix_meta_key', 'body_style' )
		);

		$boat_styles = apply_filters( 'invp_default_boat_styles', array(
			'Bass boat',
			'Bow Rider',
			'Cabin Cruiser',
			'Center Console',
			'Cuddy Cabin',
			'Deck boat',
			'Performance',
			'Pontoon',
		) );
		foreach( $boat_styles as $s ) {
			printf(
				'<option%s>%s</option>',
				selected( $s, $body_style ),
				$s
			);
		}

		printf( '</select></td></tr>'

		//Color
			. '<tr><th scope="row"><label for="%s">Color</label></th>'
			. '<td><input type="text" name="%s" value="%s"></td></tr>'

		//Interior color
			. '<tr><th scope="row"><label for="%s">Interior color</label></th>'
			. '<td><input type="text" name="%s" value="%s"></td></tr>'

		//Odometer
			. '<tr><th scope="row"><label for="%s">Odometer</label></th>'
			. '<td><input type="text" name="%s" value="%s">'
			. ' <span class="invp_odometer_units">%s</span></td></tr>'

		//Beam (boats)
			. '<tr class="boat-postmeta"><th scope="row"><label for="%s">Beam</label></th>'
			. '<td><input type="text" name="%s" value="%s"></td></tr>'

		//Length (boats)
			. '<tr class="boat-postmeta"><th scope="row"><label for="%s">Length</label></th>'
			. '<td><input type="text" name="%s" value="%s"></td></tr>'

		//Hull material
			. '<tr class="boat-postmeta"><th scope="row"><label for="%s">Hull material</label></th>'
			. '<td><select name="%s"><option></option>',

			apply_filters( 'invp_prefix_meta_key', 'color' ),
			apply_filters( 'invp_prefix_meta_key', 'color' ),
			$color,

			apply_filters( 'invp_prefix_meta_key', 'interior_color' ),
			apply_filters( 'invp_prefix_meta_key', 'interior_color' ),
			$interior_color,

			apply_filters( 'invp_prefix_meta_key', 'odometer' ),
			apply_filters( 'invp_prefix_meta_key', 'odometer' ),
			$odometer,
			apply_filters( 'invp_odometer_word', 'miles' ),

			apply_filters( 'invp_prefix_meta_key', 'beam' ),
			apply_filters( 'invp_prefix_meta_key', 'beam' ),
			$beam,

			apply_filters( 'invp_prefix_meta_key', 'length' ),
			apply_filters( 'invp_prefix_meta_key', 'length' ),
			$length,

			apply_filters( 'invp_prefix_meta_key', 'hull_material' ),
			apply_filters( 'invp_prefix_meta_key', 'hull_material' )
		);

		$hull_materials = apply_filters( 'invp_default_hull_materials', array(
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
		) );
		foreach( $hull_materials as $m ) {
			printf(
				'<option%s>%s</option>',
				selected( $m, $hull_material, false ),
				$m
			);
		}
		echo '</select></tbody></table>';
	}

	function move_advanced_meta_boxes() {
		global $post, $wp_meta_boxes;
		$post_type = get_post_type( $post );

		if( Inventory_Presser_Plugin::CUSTOM_POST_TYPE != $post_type ) {
			return;
		}

		do_meta_boxes( get_current_screen( ), 'advanced', $post );
		unset( $wp_meta_boxes[get_post_type( $post )]['advanced'] );
	}

	function move_tags_meta_box( ) {
		//Remove and re-add the "Tags" meta box so it ends up at the bottom for our CPT
		global $wp_meta_boxes;
		unset( $wp_meta_boxes[Inventory_Presser_Plugin::CUSTOM_POST_TYPE]['side']['core']['tagsdiv-post_tag'] );
		add_meta_box(
			'tagsdiv-post_tag',
			'Tags',
			'post_tags_meta_box',
			Inventory_Presser_Plugin::CUSTOM_POST_TYPE,
			'side',
			'core',
			array( 'taxonomy' => 'post_tag' )
		);
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
		switch( true ) {

			case $column_name == apply_filters( 'invp_prefix_meta_key', 'thumbnail' ):
				echo edit_post_link( get_the_post_thumbnail( $post_id, 'thumbnail' ) );
				break;

			case $column_name == apply_filters( 'invp_prefix_meta_key', 'odometer' ):
				$vehicle = new Inventory_Presser_Vehicle();
				$vehicle->odometer = $val;
				echo $vehicle->odometer();
				break;

			case $column_name == apply_filters( 'invp_prefix_meta_key', 'photo_count' ):
				echo count( get_children( array( 'post_parent' => $post_id ) ) );
				break;

			case $column_name == apply_filters( 'invp_prefix_meta_key', 'price' ):
				$vehicle = new Inventory_Presser_Vehicle();
				$vehicle->price = $val;
				echo $vehicle->price( '-' );
				break;

			default:
				echo $val;
		}
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
		update_post_meta( $post->ID, apply_filters( 'invp_prefix_meta_key', 'last_modified' ), current_time( 'D, d M Y h:i:s' ) . ' ' . $offset );

		//Clear this value that is defined by a checkbox
		update_post_meta( $post->ID, apply_filters( 'invp_prefix_meta_key', 'featured' ), '0' );

		/**
		 * Loop over the post meta keys we manage and save their values
		 * if we find them coming over as part of the post to save.
		 */
		$vehicle = new Inventory_Presser_Vehicle( $post_id );
		$price = 0;

		foreach( $vehicle->keys() as $key ) {
			$key = apply_filters( 'invp_prefix_meta_key', $key );
			if ( isset( $_POST[$key] ) ) {
				if( is_array( $_POST[$key] ) ) {
					$_POST[$key] = $this->sanitize_array( $_POST[$key] );
				} else {
					$_POST[$key] = sanitize_text_field( $_POST[$key] );
				}
				update_post_meta( $post->ID, $key, $_POST[$key] );
			}
		}

		/**
		 * Update the prices array
		 */
		$price_arr_key = apply_filters( 'invp_prefix_meta_key', 'prices' );
		$price_arr = get_post_meta( $post->ID, $price_arr_key, true );
		if( '' == $price_arr ) { $price_arr = []; }

		//our built-in prices should also live in the prices array
		foreach( $this->default_price_array_keys() as $key ) {
			if( isset( $_POST[ apply_filters( 'invp_prefix_meta_key', $key ) ] ) ) {
				$price_arr[$key] = $_POST[ apply_filters( 'invp_prefix_meta_key', $key ) ];
			}
		}

		//other custom prices
		if( isset( $_POST[ $price_arr_key ] ) ) {
			foreach( $_POST[ $price_arr_key ] as $key => $value ) {
				$price_arr[$key] = $value;
			}
		}

		update_post_meta( $post->ID, $price_arr_key, $price_arr );

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
		update_post_meta( $post->ID, apply_filters( 'invp_prefix_meta_key', 'option_array' ), $options );
	}

	function sanitize_array( $arr ) {
		foreach( $arr as $key => $value ) {
			if( is_array( $value ) ) {
				$arr[$key] = $this->sanitize_array( $value );
			} else {
				$arr[$key] = sanitize_text_field( $value );
			}
		}
		return $arr;
	}

	function scan_for_recommended_settings_and_create_warnings() {
		/**
		 * Suggest values for WordPress internal settings if the user has values
		 * we do not prefer
		 */

		if( '1' == get_option('uploads_use_yearmonth_folders') ) {
			//Organize uploads into yearly and monthly folders is turned on. Recommend otherwise.
			add_action( 'admin_notices', array( $this, 'output_upload_folder_error_html' ) );
		}

		//Are thumbnail sizes not 4:3 aspect ratios?
		if(
			( ( 4/3 ) != ( get_option('thumbnail_size_w')/get_option('thumbnail_size_h') ) ) ||
			( ( 4/3 ) != ( get_option('medium_size_w')/get_option('medium_size_h') ) ) ||
			( ( 4/3 ) != ( get_option('large_size_w')/get_option('large_size_h') ) )
		){
			//At least one thumbnail size is not 4:3
			add_action( 'admin_notices', array( $this, 'output_thumbnail_size_error_html' ) );
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
			$meta_key = apply_filters( 'invp_prefix_meta_key', $column );
			if ( $orderby == $meta_key ) {
	            $query->set( 'meta_key', $meta_key );
	            $query->set( 'orderby', 'meta_value' . ( $vehicle->post_meta_value_is_number( $meta_key ) ? '_num' : '' ) );
	            return;
			}
		}
	}
}
