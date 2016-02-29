<?php
/**
 * An object that modifies the behavior of WordPress Importers
 *
 * This file defines hooks that are called by WXR importers like
 * the WordPress Importer and WordPress Importer for Cron.
 *
 * @since      0.2.0
 * @package    Inventory_Presser
 * @subpackage Inventory_Presser/includes
 * @author     Corey Salzano <corey.salzano@gmail.com>
 */
class Inventory_Presser_Modify_Imports {

	const PENDING_DIR_NAME = '_pending_import';

	var $existing_posts_before_an_import;
	var $post_type;

	function allow_fetch_attachments( $value /* boolean */, $URL ) {
		/**
		 * Avoid downloading files that are 1) already in the uploads folder
		 * and 2) already in a subfolder of the uploads folder.
		 */
		$upload_dir = wp_upload_dir( );
		$attachment_URL_parts = parse_url( $URL );
		$this_site_URL_parts = parse_url( $upload_dir['url'] );
		$same_host = $this_site_URL_parts['host'] == $attachment_URL_parts['host'];
		// They live on different servers, go fetch it
		if( !$same_host ){ return true; }
		/**
		 * Change the attachment path to match the format of the upload_dir path
		 * by removing the file name and the slash right before it
		 */
		$attachment_path = substr( $attachment_URL_parts['path'], 0, strlen( $attachment_URL_parts['path'] ) - ( 1 + strlen( basename( $attachment_URL_parts['path'] ) ) ) );
		$same_path = $this_site_URL_parts['path'] == $attachment_path;
		//both paths point to the same location, do not fetch
		if( $same_path ){ return false; }
		/**
		 * Perhaps the file is in a subfolder of the uploads folder.
		 * Does the path to the attachment start with the path to the uploads folder?
		 */
		if( substr( $attachment_URL_parts['path'], 0, strlen( $this_site_URL_parts['path'] ) ) == $this_site_URL_parts['path'] ) {
			/**
			 * Yes, the file lives in a sub-folder of the uploads folder. Such as...
			 * http://localhost/test-site/wp-content/uploads/{PENDING_DIR_NAME}/H517718-2.jpg
			 * Copy the file to the uploads folder.
			 */
			$source = $upload_dir['basedir'] . substr( $attachment_URL_parts['path'], strlen( $this_site_URL_parts['path'] ) );
			$destination = $upload_dir['basedir'] . '/' . basename( $attachment_URL_parts['path'] );
			if( is_file( $source ) ) {
				copy( $source, $destination );
				//do not delete the file beginning december 30, 2015, we're going to maintain the pending folder differently
				//unlink( $source );
			}
			return false;
		}
		return true;
	}

	function associate_parentless_attachments_with_parents( ) {
		/**
		 * Loop over post_type == 'attachment' where no post_parent, and
		 * require that attachments found contain our meta key.
		 * Order by GUID so we can use a control break loop.
		 */
		$attachments = get_posts( array(
			'meta_query'     => array(
				array(
					'key'     => '_inventory_presser_photo_number',
					'value'   => '',
					'compare' => '!='
				)
	        ),
	        'orderby'        => 'guid',
			'post_parent'    => '0',
			'post_type'      => 'attachment',
			'posts_per_page' => -1,
		));
		$last_file_name_base = $last_post_ID = '';
		foreach( $attachments as $attachment ) {
			//the post guid is a URL to the attachment, we need the file name without extension
			$file_name = basename( $attachment->guid );
			$info = pathinfo( $file_name );
			$file_name_base = apply_filters( '_inventory_presser_create_photo_file_name_base', basename( $file_name, '.' . $info['extension'] ) );
			if( $file_name_base == $last_file_name_base && '' != $file_name_base ) {
				//do not need to query, we are looking for the same parent as last
				$attachment->post_parent = $last_post_ID;
			} else {
				/**
				 * Do we have a post that uses our custom post type and has a meta
				 * key named `inventory_presser_photo_file_name_base` that contains
				 * the value $file_name_base? If so, that's this attachment's parent.
				 */
				$find_parent_args = array(
					'post_type'  => $this->post_type,
					'meta_query' => array(
								array(
									'key'   => '_inventory_presser_photo_file_name_base',
									'value' => $file_name_base,
								)
							),
				);
				$parent_query = new WP_Query( $find_parent_args );
				if( $parent_query->have_posts() && 1 == count( $parent_query->posts ) ) {
					//only one post was found, great, use it's ID as our parent
					$attachment->post_parent = $last_post_ID = $parent_query->posts[0]->ID;
				}
			}
			/**
			 * A post_meta key called `_inventory_presser_photo_number` specifies the photo number.
			 * This is useful here, where we want to make photo number one the thumbnail for the parent post.
			 */
			if( '1' === get_post_meta( $attachment->ID, '_inventory_presser_photo_number', true ) ) {
				set_post_thumbnail( $attachment->post_parent, $attachment->ID );
			}
			//save the post with the updated post_parent value
			wp_update_post( $attachment );
			$last_file_name_base = $file_name_base;
		 }
	}

	function __construct( $post_type, $delete_vehicles_not_in_new_feeds ) {
		$this->post_type = $post_type;

		//don't actually download attachments during imports if they are already on this server
		add_filter( 'import_allow_fetch_file', array( &$this, 'allow_fetch_attachments' ), 10, 2 ) ;

		//after an import is completed, try to match attachments with their parent posts
		add_action( 'import_end', array( &$this, 'associate_parentless_attachments_with_parents' ) );

		//After an import is completed, prune the pending folder for attachments we no longer need
		add_action( 'import_end', array( &$this, 'prune_pending_attachments' ) );

		if( $delete_vehicles_not_in_new_feeds ) {
			/* build a list of posts in a class member variable that identifies
			 * posts that exist before an import. the next action hook will remove some items
			 * from said list (that are in the import), and the third action hook will delete
			 * the remaining members because they are units that were not found in the new feed
			 */
			add_filter( 'wp_import_posts', array( &$this, 'remember_posts_before_an_import' ) );

			//remove the posts from the list of posts to delete as they come through the import
			add_action( 'wp_import_post_and_type_exist', array( &$this, 'remove_existing_post_from_import_purge_list' ) );

			//list left over should be the posts that are of our post type and not in the new feed
			//delete them by id means you need to find them by slug
			add_action( 'import_end', array( &$this, 'delete_posts_not_found_in_new_import' ) );
		}

		/**
		 * In our implementation, checking only the first 7 characters of the photo file name base
		 * is optimal. Users that aren't using import files created by our BirdFeeder app may need
		 * a different implementation.
		 */
		add_filter( '_inventory_presser_create_photo_file_name_base', array( &$this, 'take_left_seven_characters' ) );

		/**
		 * Delete the pending import folder when the user deletes all plugin data
		 */
		add_action( 'inventory_presser_delete_all_data', array( &$this, 'delete_pending_import_folder' ) );

		//All our meta keys are unique, so tell the importer so, and...
		add_filter( 'wp_import_post_meta_unique', '__return_true' );
		//also make sure these unique post keys get updated, because they will
		//be ignored by the importer because they are unique and already exist
		add_action( 'import_post_meta', array( &$this, 'update_existing_unique_post_meta_values' ), 10, 3 );
	}

	function update_existing_unique_post_meta_values( $post_id, $key, $value ) {
		update_post_meta( $post_id, $key, $value );
	}

	function delete_directory( $dir ) {
		if ( !file_exists( $dir ) ) {
			return true;
		}

		if ( !is_dir( $dir ) ) {
			return unlink( $dir );
		}

		foreach ( scandir( $dir ) as $item ) {
			if ( $item == '.' || $item == '..' ) {
				continue;
			}

			if ( !$this->delete_directory( $dir . DIRECTORY_SEPARATOR . $item ) ) {
				return false;
			}

		}

		return rmdir( $dir );
	}

	function delete_pending_import_folder( ) {
		$upload_dir = wp_upload_dir();
		$result = $this->delete_directory( $upload_dir['basedir'] . '\\' . self::PENDING_DIR_NAME );
	}

	function delete_posts_not_found_in_new_import() {
		/* $this->existing_posts_before_an_import should now contain only posts
		 * that were in the database before the import ran and that were
		 * not contained in the import. this function is only called if the
		 * user wants to delete these posts, so delete them.
		 */
		 foreach( $this->existing_posts_before_an_import as $post ) {
			wp_delete_post( $post->ID, true );
			//make not of this deletion via the importer's error logging mechanism
			add_filter( 'wp_import_errors_before_end', function( $arr ) {
				array_push( $arr, 'This post was not contained in the latest import and has been deleted: ' . $post->post_title );
				return $arr;
			});
		 }
		 $this->existing_posts_before_an_import = array();
	}

	/**
	 * True or false: there is an attachment with a $file_name like file.jpg
	 *
	 * The file name match is a wildcard match on the right end of the post GUID.
	 */
	function have_attachment_with_file_name( $file_name ) {
		global $wpdb;
		$id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type = 'attachment' AND guid LIKE %s", '%%/' .$file_name ) );
		return '' != $id;
	}

	/**
	 * We keep a copy of all attachments that arrive alongside imports in a folder
	 * (whose name is stored in the constant PENDING_DIR_NAME) in the uploads folder.
	 * I use the word 'alongside' in
	 * the previous sentence because these files are uploaded via FTP rather than
	 * downloaded by the importer as it discovers the URLs in the XML file. This
	 * method deletes all attachments in the pending folder that have not
	 * been copied into the uploads folder proper and saved as an attachment type
	 * post in the database. These files are from previous imports and were not
	 * part of the most recent import.
	 */
	function prune_pending_attachments( ) {
		//need the allowed upload file extensions
		$upload_dir = wp_upload_dir();
		$files = glob( $upload_dir['path'] . '/' . self::PENDING_DIR_NAME . '/*.*', GLOB_BRACE );
		foreach( $files as $file ) {
			//do we have an attachment for this filename?
			if( ! $this->have_attachment_with_file_name( basename( $file ) ) ) {
				//no? delete the file
				unlink( $file );
				//make not of this deletion via the importer's error logging mechanism
				add_filter( 'wp_import_errors_before_end', function( $arr ) {
					array_push( $arr, 'A pending attachment that is no longer associated with any active inventory unit has been deleted: ' . $file );
					return $arr;
				});
			}
		}
	}

	function remember_posts_before_an_import( $posts ) {
		/**
		 * Store all the post titles and dates for our post type
		 * as they exist before the import runs
		 */
		$args = array(
			'post_status'    => 'publish',
			'post_type'      => $this->post_type,
			'posts_per_page' => -1,
		);
		$this->existing_posts_before_an_import = get_posts( $args );
		/**
		 * Do not modify the posts coming into this function, and return them
		 * back to the importer.
		 */
		return $posts;
	}

	function remove_existing_post_from_import_purge_list( $post ) {
		/**
		 * Loop through all the posts the importer is about to import that we
		 * copied into $this->existing_posts_before_an_import with the function
		 * remember_posts_before_an_import()
		 */
		for( $p = 0; $p < sizeof( $this->existing_posts_before_an_import ); $p++ ) {
			//if the title and date match
			if( $post['post_title'] == $this->existing_posts_before_an_import[$p]->post_title && $post['guid'] == $this->existing_posts_before_an_import[$p]->guid ) {
				//remove this one, it's in the new file
				array_splice( $this->existing_posts_before_an_import, $p, 1 );
				return;
			}
		}
		return $post;
	}

	function take_left_seven_characters( $a_string ) {
		return substr( $a_string, 0, 7 );
	}
}