<?php
defined( 'ABSPATH' ) or exit;

/**
 * Inventory_Presser_Customize_Dashboard
 * 
 * An object that customizes the administrator dashboard to make managing an
 * inventory of vehicles easy.
 *
 * @since      1.3.1
 * @package    Inventory_Presser
 * @subpackage Inventory_Presser/includes
 * @author     Corey Salzano <corey@friday.systems>, John Norton <norton@fridaynet.com>
 */
class Inventory_Presser_Customize_Dashboard
{
	const PRODUCT_NAME = 'Inventory Presser';
	const NONCE_DELETE_ALL_MEDIA = 'invp_delete_all_media';
	
	/**
	 * add_columns_to_vehicles_table
	 * 
	 * Adds slugs to the post table columns array so the dashboard list of 
	 * vehicles is more informative than the vanilla list for posts.
	 *
	 * @param  array $column
	 * @return array
	 */
	function add_columns_to_vehicles_table( $column )
	{
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
	
	/**
	 * add_meta_boxes_to_cpt
	 * 
	 * Adds meta boxes to the editor when editing vehicles.
	 *
	 * @return void
	 */
	function add_meta_boxes_to_cpt()
	{
		//Add a meta box to the New/Edit post page
		//add_meta_box('vehicle-meta', 'Attributes', array( $this, 'meta_box_html_vehicle' ), INVP::POST_TYPE, 'normal', 'high' );

		//and another for prices
		//add_meta_box('prices-meta', 'Prices', array( $this, 'meta_box_html_prices' ), INVP::POST_TYPE, 'normal', 'high' );

		//Add another meta box to the New/Edit post page
		add_meta_box('options-meta', 'Optional equipment', array( $this, 'meta_box_html_options' ), INVP::POST_TYPE, 'normal', 'high' );

		//Add a meta box to the side column for a featured vehicle checkbox
		add_meta_box('featured', 'Featured Vehicle', array( $this, 'meta_box_html_featured' ), INVP::POST_TYPE, 'side', 'low' );
	}

	/**
	 * add_settings_to_customizer
	 * 
	 * Add a setting to the customizer's Colors panel for Carfax button text	
	 *
	 * @param  mixed $wp_customize
	 * @return void
	 */
	function add_settings_to_customizer( $wp_customize )
	{
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
			'label'       => __( 'Carfax Button Text', 'inventory-presser' ),
			'description' => __( 'The color of the words "SHOW ME THE" in Carfax buttons.', 'inventory-presser' ),
            'choices'  => array(
                'black' => 'Black',
                'white' => 'White',
             ),
		) );
	}
	
	/**
	 * add_vehicles_to_admin_bar
	 * 
	 * Adds a Vehicle button to the Admin Bar
	 *
	 * @return void
	 */
	function add_vehicles_to_admin_bar()
	{
		//do not do this if we are already looking at the dashboard
		if( is_admin() ) { return; }

		global $wp_admin_bar;
		$wp_admin_bar->add_node( array(
			'id'     => 'wp-admin-bar-vehicles',
			'title'  => 'Vehicles',
			'href'   => admin_url( 'edit.php?post_type=' . INVP::POST_TYPE ),
			'parent' => 'site-name',
		) );
	}
	
	/**
	 * annotate_add_media_button
	 * 
	 * Adds HTML near the Add Media button on the classic editor.
	 *
	 * @param  mixed $editor_id
	 * @return void
	 */
	function annotate_add_media_button( $editor_id )
	{
		if( 'content' != $editor_id ) { return; }

		printf(
			'%s<span id="media-annotation" class="annotation">%s</span>',
			$this->create_delete_all_post_attachments_button(),
			$this->create_add_media_button_annotation()
		);
	}

	/**
	 * array_to_csv
	 * 
	 * Converts a one-dimensional array into an equivalent comma-separated v
	 * values string. Input of array( 1, 2, 3 ) returns "1","2","3"
	 *
	 * @param  array $arr
	 * @return string
	 */
	private function array_to_csv( $arr )
	{
		$csv = '';
		foreach( $arr as $item )
		{
			$csv .= "\"" . str_replace( "\"", "\"\"", $item ) . "\",";
		}
		//ignore last comma
		return substr( $csv, 0, -1 );
	}
	
	/**
	 * change_post_updated_messages
	 * 
	 * Changes the messages shown to users in the editor when changes are made
	 * to the post object.
	 *
	 * @param  array $msgs
	 * @return array
	 */
	function change_post_updated_messages( $msgs )
	{
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

		$msgs[INVP::POST_TYPE] = array(
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
	
	/**
	 * change_taxonomy_show_ui_attributes
	 *
	 * When the user flips the "Show All Taxonomies" setting switch, this 
	 * method changes the taxonomy registration so they are shown.
	 * 
	 * @param  array $taxonomy_data
	 * @return array
	 */
	function change_taxonomy_show_ui_attributes( $taxonomy_data )
	{
		for( $i=0; $i<sizeof( $taxonomy_data ); $i++ )
		{
			if( ! isset( $taxonomy_data[$i]['args']['show_in_menu'] ) )
			{
				continue;
			}

			$taxonomy_data[$i]['args']['show_in_menu'] = true;
			$taxonomy_data[$i]['args']['show_ui'] = true;
		}
		return $taxonomy_data;
	}
	
	/**
	 * create_add_media_button_annotation
	 * 
	 * Computes the number of attachments on vehicles so the number can be
	 * shown in the classic editor near the Add Media button.
	 *
	 * @return string
	 */
	function create_add_media_button_annotation()
	{
		global $post;
		if( !is_object( $post ) && isset( $_POST['post_ID'] ) )
		{
			/**
			 * This function is being called via AJAX and the
			 * post_id is incoming, so get the post
			 */
			$post = get_post( $_POST['post_ID'] );
		}

		if( INVP::POST_TYPE != $post->post_type )
		{
			return '';
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
		foreach( $attachments as $attachment )
		{
			switch( $attachment->post_mime_type )
			{
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
		if( 0 < ( $counts['image'] + $counts['video'] + $counts['text'] + $counts['PDF'] + $counts['other'] ) )
		{
			$note = '';
			foreach( $counts as $key => $count )
			{
				if( 0 < $count )
				{
					if( '' != $note ) { $note .= ', '; }
					$note .= $count . ' ' . $key . ( 1 != $count ? 's' : '' );
				}
			}
			return $note;
		}
		return '0 photos';
	}
	
	/**
	 * create_delete_all_post_attachments_button
	 * 
	 * Creates HTML that renders a button that says "Delete All Media" that will
	 * be placed near the Add Media button.
	 *
	 * @return string
	 */
	function create_delete_all_post_attachments_button()
	{
		global $post;
		if( ! is_object( $post ) || ! isset( $post->ID ) )
		{
			return '';
		}
		//does this post have attachments?
		$post = get_post( $post->ID );
		if( INVP::POST_TYPE != $post->post_type )
		{
			return '';
		}
		$attachments = get_children( array(
			'post_parent'    => $post->ID,
			'post_type'      => 'attachment',
			'posts_per_page' => -1,
		) );
		if( 0 === sizeof( $attachments ) )
		{
			return '';
		}
		return sprintf( 
			'<button type="button" id="delete-media-button" class="button" onclick="delete_all_post_attachments();">'
			. '<span class="wp-media-buttons-icon"></span> %s</button>',
			__( 'Delete All Media', 'inventory-presser' )
		);
	}

	/**
	 * delete_all_inventory_ajax
	 * 
	 * AJAX handler for the 'Delete all Inventory' button on options page. 
	 * Deletes all inventory and outputs a response to the JavaScript caller.
	 *
	 * @return void
	 */
	function delete_all_inventory_ajax()
	{
		$delete_result = INVP::delete_all_inventory();
		if ( is_wp_error( $delete_result ) )
		{
			//output error
			echo "<div id='import-error' class='settings-error'><p><strong>"
				. $delete_result->get_error_message( ) . "</strong></p></div>";
		}
		else
		{
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
	
	/**
	 * delete_all_post_attachments
	 * 
	 * Deletes all a post's attachments. The callback behind the Delete All 
	 * Media button.
	 *
	 * @return void
	 */
	function delete_all_post_attachments()
	{
		if( empty( $_POST['_ajax_nonce'] ) || ! wp_verify_nonce( $_POST['_ajax_nonce'], self::NONCE_DELETE_ALL_MEDIA ) )
		{
			return;
		}

		$post_id = isset( $_POST['post_ID'] ) ? $_POST['post_ID'] : 0;

		if( ! isset( $post_id ) )
		{
			return; // Will die in case you run a function like this: delete_post_media($post_id); if you will remove this line - ALL ATTACHMENTS WHO HAS A PARENT WILL BE DELETED PERMANENTLY!
		}
		elseif ( 0 == $post_id )
		{
			return; // Will die in case you have 0 set. there's no page id called 0 :)
		}
		elseif( is_array( $post_id ) )
		{
			return; // Will die in case you place there an array of pages.
		}
		else
		{
			$vehicle = new Inventory_Presser_Vehicle();
			$vehicle->delete_attachments( $post_id );
		}
	}

	/**
	 * enable_order_by_attachment_count
	 * 
	 * Handle the ORDER BY on the vehicle list (edit.php) when sorting by photo 
	 * count.
	 *
	 * @param  array $pieces
	 * @param  WP_Query $query
	 * @return array
	 */
	function enable_order_by_attachment_count( $pieces, $query )
	{
		if( ! is_admin() ) { return $pieces; }

		/**
		 * We only want our code to run in the main WP query
		 * AND if an orderby query variable is designated.
		 */
		if ( $query->is_main_query() && ( $orderby = $query->get( 'orderby' ) ) )
		{
			// Get the order query variable - ASC or DESC
			$order = strtoupper( $query->get( 'order' ) );

			// Make sure the order setting qualifies. If not, set default as ASC
			if ( ! in_array( $order, array( 'ASC', 'DESC' ) ) )
			{
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
	 * get_admin_notice_html
	 * 
	 * Creates HTML that renders an admin notice.
	 *
	 * @param  string $message
	 * @param  string $color A value of 'red', 'yellow', or 'green' that appears as a left border on the notice box
	 * @return string HTML that renders an admin notice
	 */
	function get_admin_notice_html( $message, $color )
	{
		switch( $color )
		{
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
	
	/**
	 * hooks
	 * 
	 * Adds hooks
	 *
	 * @return void
	 */
	function hooks()
	{
		add_filter( 'posts_clauses', array( $this, 'enable_order_by_attachment_count' ), 1, 2 );

		//Save custom post data when posts are saved
		add_action( 'save_post_' . INVP::POST_TYPE, array( $this, 'save_vehicle_post_meta' ), 10, 3 );

		//Add columns to the table that lists all the Vehicles on edit.php
		add_filter( 'manage_' . INVP::POST_TYPE . '_posts_columns', array( $this, 'add_columns_to_vehicles_table' ) );

		//Populate the columns we added to the Vehicles table
		add_action( 'manage_' . INVP::POST_TYPE . '_posts_custom_column', array( $this, 'populate_columns_we_added_to_vehicles_table' ), 10, 2 );

		//Make our added columns to the Vehicles table sortable
		add_filter( 'manage_edit-' . INVP::POST_TYPE . '_sortable_columns', array( $this, 'make_vehicles_table_columns_sortable' ) );

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

		//Make our Add Media button annotation available from an AJAX call
		add_action( 'wp_ajax_output_add_media_button_annotation', array( $this, 'output_add_media_button_annotation' ) );

		//Add a link to the Settings page on the plugin management page
		add_filter( 'plugin_action_links_' . INVP_PLUGIN_BASE, array( $this, 'insert_settings_link' ), 2, 2 );

		//Define an AJAX handler for the 'Delete all inventory' button
		add_action( 'wp_ajax_delete_all_inventory', array( $this, 'delete_all_inventory_ajax' ) );

		//Add a link to the main menu of the Admin bar
		add_action( 'admin_bar_menu', array( $this, 'add_vehicles_to_admin_bar' ), 100 );

		$options = INVP::settings();
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
	
	/**
	 * insert_settings_link
	 *
	 * Adds a link to the settings page near the Activate | Delete links on the
	 * list of plugins on the Plugins page.
	 * 
	 * @param  array $links
	 * @return array
	 */
	function insert_settings_link( $links )
	{
		$url = admin_url( sprintf( 
			'edit.php?post_type=%s&page=dealership-options',
			INVP::POST_TYPE
		) );
		$links[] = sprintf(
			'<a href="%s">%s</a>',
			$url,
			__( 'Settings', 'inventory-presser' )
		);
		return $links;
	}
	
	/**
	 * load_scripts
	 * 
	 * Includes JavaScripts and stylesheets that power our changes to the 
	 * dashboard. 
	 *
	 * @param  string $hook
	 * @return void
	 */
	function load_scripts( $hook )
	{
		wp_enqueue_style( 'my-admin-theme', plugins_url( '/css/wp-admin.min.css', INVP_PLUGIN_FILE_PATH ) );
		wp_register_script( 'inventory-presser-javascript', plugins_url( '/js/admin.min.js', INVP_PLUGIN_FILE_PATH ) );
		wp_enqueue_script( 'inventory-presser-javascript' );

		//localize an odometer units word for the edit vehicle page
		wp_localize_script( 'inventory-presser-javascript', 'invp', array(
			'hull_materials'      => apply_filters( 'invp_default_hull_materials', array(
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
			) ),
			'miles_word'          => apply_filters( 'invp_odometer_word', 'miles' ),
			'meta_prefix'         => INVP::meta_prefix(),
			'payment_frequencies' => apply_filters( 'invp_default_payment_frequencies', array(
				'Monthly'      => 'monthly',
				'Weekly'       => 'weekly',
				'Bi-weekly'    => 'biweekly',
				'Semi-monthly' => 'semimonthly',
			) ),
			'delete_all_media_nonce' => wp_create_nonce( self::NONCE_DELETE_ALL_MEDIA ),
		) );
	}
	
	/**
	 * make_vehicles_table_columns_sortable
	 * 
	 * Declares which of our custom columns on the list of posts are sortable.
	 *
	 * @param  array $columns
	 * @return array
	 */
	function make_vehicles_table_columns_sortable( $columns )
	{
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
	
	/**
	 * meta_box_html_featured
	 * 
	 * Creates an editor meta box to help users mark vehicles as featured.
	 *
	 * @param  WP_Post $post
	 * @return void
	 */
	function meta_box_html_featured( $post )
	{
		$meta_key = apply_filters( 'invp_prefix_meta_key', 'featured' );
		printf( '<input type="checkbox" id="%s" name="%s" value="1"%s><label for="%s">%s</label>',
			$meta_key,
			$meta_key,
			checked( '1', get_post_meta( $post->ID, $meta_key, true ), false ),
			$meta_key,
			__( 'Featured in Slideshows', 'inventory-presser' )
		);
	}
	
	/**
	 * meta_box_html_options
	 * 
	 * Creates a meta box to help the user see and manage vehicle options while
	 * editing a vehicle post.
	 *
	 * @param  WP_Post $post
	 * @return void
	 */
	function meta_box_html_options( $post )
	{
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

		$vehicle = new Inventory_Presser_Vehicle( $post->ID );
		$options_array = $vehicle->options_array();
		if( is_array( $options_array ) )
		{
			foreach( $options_array as $option )
			{
				$options[$option] = true;
			}
		}
		//sort the array by key
		ksort( $options );
		//output a bunch of checkboxes
		$HTML = '<div class="list-with-columns"><ul class="optional-equipment">';
		foreach( $options as $key => $value )
		{
			//element IDs cannot contain slashes, spaces or parentheses
			$id = 'option-' . preg_replace( '/\/\(\)/i', '', str_replace( ' ', '_', $key ) );
			$HTML .= sprintf(
				'<li><input type="checkbox" id="%s" name="%s" value="%s"%s><label for="%s">%s</label></li>',
				$id,
				'inventory_presser_options_array[]',
				$key,
				checked( true, $value, false ),
				$id,
				$key
			);
		}
		echo $HTML . '</ul></div>';
	}
	
	/**
	 * meta_box_html_prices
	 *
	 * Creates a meta box to help users manage a vehicles prices in the editor.
	 * 
	 * @param  WP_Post $post
	 * @param  mixed $meta_box
	 * @return void
	 */
	function meta_box_html_prices( $post, $meta_box )
	{
		$prices = array(
			'price'        => 'Price',
			'msrp'         => 'MSRP',
			'down_payment' => 'Down payment',
			'payment'      => 'Payment',
		);

		echo '<table class="form-table"><tbody>';
		foreach( $prices as $key => $label )
		{
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
		foreach( $frequencies as $key => $value )
		{
			printf(
				'<option value="%s"%s>%s</option>',
				$value,
				selected( $payment_frequency, $value, false ),
				$key
			);
		}
		echo '</select></td></tr>';
		echo '</tbody></table>';
	}
	
	/**
	 * meta_box_html_vehicle
	 *
	 * Creates a meta box to help the user manage the bulk of the meta fields
	 * that define a vehicle.
	 * 
	 * @param  WP_Post $post
	 * @param  mixed $meta_box
	 * @return void
	 */
	function meta_box_html_vehicle( $post, $meta_box )
	{
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
		$youtube = ( isset( $custom[apply_filters( 'invp_prefix_meta_key', 'youtube' )] ) ? $custom[apply_filters( 'invp_prefix_meta_key', 'youtube' )][0] : '' );

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

		for( $y=date('Y')+2; $y>=1920; $y-- )
		{
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
		foreach( $boat_styles as $s )
		{
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

		//YouTube
			. '<tr><th scope="row"><label for="%s">%s</label></th>'
			. '<td><input type="text" name="%s" value="%s"></td></tr>'

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

			apply_filters( 'invp_prefix_meta_key', 'youtube' ),
			__( 'YouTube video ID', 'inventory-presser' ),
			apply_filters( 'invp_prefix_meta_key', 'youtube' ),
			$youtube,

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
		foreach( $hull_materials as $m )
		{
			printf(
				'<option%s>%s</option>',
				selected( $m, $hull_material, false ),
				$m
			);
		}
		echo '</select></tbody></table>';
	}
	
	/**
	 * move_advanced_meta_boxes
	 * 
	 * Rearranges meta boxes on the editor when editing vehicles
	 *
	 * @return void
	 */
	function move_advanced_meta_boxes()
	{
		global $post, $wp_meta_boxes;
		$post_type = get_post_type( $post );

		if( INVP::POST_TYPE != $post_type )
		{
			return;
		}

		do_meta_boxes( get_current_screen( ), 'advanced', $post );
		unset( $wp_meta_boxes[get_post_type( $post )]['advanced'] );
	}
	
	/**
	 * move_tags_meta_box
	 * 
	 * Remove and re-add the "Tags" meta box so it ends up at the bottom for our CPT
	 *
	 * @return void
	 */
	function move_tags_meta_box()
	{
		global $wp_meta_boxes;
		unset( $wp_meta_boxes[INVP::POST_TYPE]['side']['core']['tagsdiv-post_tag'] );
		add_meta_box(
			'tagsdiv-post_tag',
			'Tags',
			'post_tags_meta_box',
			INVP::POST_TYPE,
			'side',
			'core',
			array( 'taxonomy' => 'post_tag' )
		);
	}
	
	/**
	 * output_add_media_button_annotation
	 * 
	 * AJAX callback to output the content we put near the Add Media button in
	 * the classic editor.
	 *
	 * @return void
	 */
	function output_add_media_button_annotation()
	{
		//because AJAX
		echo $this->create_add_media_button_annotation( );
		wp_die();
	}
	
	/**
	 * output_thumbnail_size_error_html
	 * 
	 * Outputs an admin notice to warn a user that they have attachment multiple
	 * aspect ratios of vehicle photos to a single vehicle.
	 *
	 * @return void
	 */
	function output_thumbnail_size_error_html()
	{
		echo $this->get_admin_notice_html(
			'At least one of your thumbnail sizes does not have an aspect ratio of 4:3, which is the most common smartphone and digital camera aspect ratio. You can change thumbnail sizes <a href="options-media.php">here</a>.',
			'yellow'
		);
	}
	
	/**
	 * output_upload_folder_error_html
	 * 
	 * Outputs an admin notice to warn the user if uploads are saved in month-
	 * and year-based folders.
	 *
	 * @return void
	 */
	function output_upload_folder_error_html()
	{
		echo $this->get_admin_notice_html(
			'Your media settings are configured to organize uploads into month- and year-based folders. This is not optimal for '.self::PRODUCT_NAME.', and you can turn this setting off on <a href="options-media.php">this page</a>.',
			'yellow'
		);
	}
	
	/**
	 * populate_columns_we_added_to_vehicles_table
	 * 
	 * Populates the custom columns we added to the posts table in the 
	 * dashboard.
	 *
	 * @param  string $column_name
	 * @param  int $post_id
	 * @return void
	 */
	function populate_columns_we_added_to_vehicles_table( $column_name, $post_id )
	{
		$custom_fields = get_post_custom( $post_id );
		$val = ( isset( $custom_fields[$column_name] ) ? $custom_fields[$column_name][0] : '' );
		switch( true )
		{
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
	
	/**
	 * save_vehicle_post_meta
	 * 
	 * Saves vehicle attributes into their corresponding post meta fields when
	 * the Save or Update button is clicked in the editor.
	 *
	 * @param  int $post_id
	 * @param  WP_Post $post
	 * @param  bool $is_update
	 * @return void
	 */
	function save_vehicle_post_meta( $post_id, $post, $is_update )
	{
		/**
		 * Do not continue if the post is being moved to the trash or if this is
		 * an auto-draft.
		 */
		if( in_array( $post->post_status, array( 'trash', 'auto-draft' ) ) )
		{
			return;
		}

		//Abort if autosave or AJAX/quick edit
		if( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			|| ( defined('DOING_AJAX') && DOING_AJAX ) )
		{
			return;
		}

		//is this a vehicle?
		if( ! empty( $_POST['post_type'] ) && INVP::POST_TYPE != $_POST['post_type'] )
		{
			//no, don't create any meta data for vehicles
			return;
		}

		if( empty( $_POST ) )
		{
			return;
		}

		/**
		 * Tick the last modified date of this vehicle since we're saving changes.
		 * It looks like this: Tue, 06 Sep 2016 09:26:12 -0400
		 */
		$offset = sprintf( '%+03d00', intval( get_option('gmt_offset') ) );
		update_post_meta( $post_id, apply_filters( 'invp_prefix_meta_key', 'last_modified' ), current_time( 'D, d M Y h:i:s' ) . ' ' . $offset );

		//Clear this value that is defined by a checkbox
		update_post_meta( $post_id, apply_filters( 'invp_prefix_meta_key', 'featured' ), '0' );

		/**
		 * Loop over the post meta keys we manage and save their values
		 * if we find them coming over as part of the post to save.
		 */
		$vehicle = new Inventory_Presser_Vehicle( $post_id );
		$keys = $vehicle->keys();
		$keys[] = 'options_array';

		foreach( $keys as $key )
		{
			$key = apply_filters( 'invp_prefix_meta_key', $key );
			if ( isset( $_POST[$key] ) )
			{
				if( is_array( $_POST[$key] ) )
				{
					//delete all meta, this is essentially for the options
					delete_post_meta( $post->ID, $key );
					$options = array(); //collect the options to maintain a CSV field for backwards compatibility

					foreach( $this->sanitize_array( $_POST[$key] ) as $value )
					{
						add_post_meta( $post->ID, $key, $value );
						if( 'inventory_presser_options_array' == $key )
						{
							$options[] = $value;
						}
					}
				}
				else
				{
					//string data
					update_post_meta( $post->ID, $key, sanitize_text_field( $_POST[$key] ) );
				}
				
			}
		}
	}
	
	/**
	 * sanitize_array
	 * 
	 * Sanitizes every member of an array at every level with 
	 * sanitize_text_field()
	 *
	 * @param  array $arr
	 * @return array
	 */
	function sanitize_array( $arr )
	{
		foreach( $arr as $key => $value )
		{
			if( is_array( $value ) )
			{
				$arr[$key] = $this->sanitize_array( $value );
			}
			else
			{
				$arr[$key] = sanitize_text_field( $value );
			}
		}
		return $arr;
	}
	
	/**
	 * scan_for_recommended_settings_and_create_warnings
	 *
	 * Suggest values for WordPress internal settings if the user has values
	 * we do not prefer
	 * 
	 * @return void
	 */
	function scan_for_recommended_settings_and_create_warnings()
	{
		if( '1' == get_option('uploads_use_yearmonth_folders') )
		{
			//Organize uploads into yearly and monthly folders is turned on. Recommend otherwise.
			add_action( 'admin_notices', array( $this, 'output_upload_folder_error_html' ) );
		}

		//Are thumbnail sizes not 4:3 aspect ratios?
		if(
			( ( 4/3 ) != ( get_option('thumbnail_size_w')/get_option('thumbnail_size_h') ) )
			|| ( ( 4/3 ) != ( get_option('medium_size_w')/get_option('medium_size_h') ) )
			|| ( ( 4/3 ) != ( get_option('large_size_w')/get_option('large_size_h') ) )
		){
			//At least one thumbnail size is not 4:3
			add_action( 'admin_notices', array( $this, 'output_thumbnail_size_error_html' ) );
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
	function vehicles_table_columns_orderbys( $query )
	{
		if( ! is_admin() || ! $query->is_main_query() ) { return; }

		$columns = array(
			'color',
			'odometer',
			'price',
			'stock_number',
		);
		$orderby = $query->get( 'orderby' );
		foreach( $columns as $column )
		{
			$meta_key = apply_filters( 'invp_prefix_meta_key', $column );
			if ( $orderby == $meta_key )
			{
	            $query->set( 'meta_key', $meta_key );
	            $query->set( 'orderby', 'meta_value' . ( Inventory_Presser_Vehicle::post_meta_value_is_number( $meta_key ) ? '_num' : '' ) );
	            return;
			}
		}
	}
}
