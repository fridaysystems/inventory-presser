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

	var $attachment_counts = array();
	var $delete_vehicles_not_in_new_feeds;
	var $existing_posts_before_an_import;
	var $post_type;
	var $post_titles_that_were_deleted = array();
	var $upload_dir;
	var $vin_to_parent_post_id = array();

	function __construct( $post_type, $delete_vehicles_not_in_new_feeds ) {
		$this->post_type = $post_type;
		$this->delete_vehicles_not_in_new_feeds = $delete_vehicles_not_in_new_feeds;
		$this->upload_dir = wp_upload_dir();

		//don't actually download attachments during imports if they are already on this server
		add_filter( 'import_allow_fetch_file', array( &$this, 'allow_fetch_attachments' ), 10, 2 ) ;

		//decide if we want to let the importer replace all the terms on an object
		add_filter( 'wp_import_set_object_terms', array( &$this, 'allow_set_object_terms'), 10, 4 );

		/**
		 * Go all in on our lie to the importer. When we tell it not to really
		 * go download attachment payloads, we want to massage the URL to remove
		 * our pending directory folder as if it was downloaded into the uploads
		 * folder.
		 */
		add_filter( 'import_fetched_file_url', array( &$this, 'alter_fetched_file_url' ) );

		/**
		 * Build a list of posts in a class member variable that identifies
		 * posts that exist before an import. the next action hook will
		 * remove some items from said list (that are in the import), and
		 * the third action hook will delete the remaining members because
		 * they are units that were not found in the new feed.
		 */
		add_filter( 'wp_import_posts', array( &$this, 'remember_posts_before_an_import' ) );

		//remove the posts from the list of posts to delete as they come through the import
		add_action( 'wp_import_post_and_type_exist', array( &$this, 'remove_existing_post_from_import_purge_list' ) );

		/**
		 * Posts left over should be of our post type and not in the new
		 * feed. Delete them.
		 */
		add_action( 'import_end', array( &$this, 'delete_posts_not_found_in_new_import' ) );

		//this filter wraps the output of a post_exists() call
		add_filter( 'post_exists', array( &$this, 'inventory_post_exists' ), 10, 2 );

		add_action( 'wp_import_parsing_attachment', array( &$this, 'keep_track_of_attachment_counts' ), 10, 1 );

		//Delete the pending import folder when the user deletes all plugin data
		add_action( 'inventory_presser_delete_all_data', array( &$this, 'delete_pending_import_folder' ) );

		//All our meta keys are unique, so tell the importer so
		add_filter( 'wp_import_post_meta_unique', '__return_true' );
		//Likewise with term meta keys, they are unique
		add_filter( 'wp_import_term_meta_unique', '__return_true' );
		/**
		 * Also make sure these unique post keys get updated, because they will
		 * be ignored by the importer because they are unique and already exist
		 */
		add_action( 'import_post_meta', array( &$this, 'update_existing_unique_post_meta_values' ), 10, 3 );
		//Likewise with term meta
		add_action( 'import_term_meta', array( &$this, 'update_existing_unique_term_meta_values' ), 10, 3 );

		//Recount term relationships when a post's terms are updated during imports
		add_action( 'wp_import_set_post_terms', array( &$this, 'force_term_recount' ), 10, 5 );

		add_filter( 'wp_import_find_parent_id', array( &$this, 'find_attachment_parent_id' ), 10, 2 );
	}

	function find_attachment_parent_id( $value, $post ) {
		//get VIN out of guid
		$vin = $this->extract_vin_from_attachment_url( $post['guid'] );

		if( isset( $this->vin_to_parent_post_id[$vin] ) ) {
			return $this->vin_to_parent_post_id[$vin];
		}

		/**
		 * Do we have a post that uses our custom post type and has a meta
		 * key named `inventory_presser_vin` that contains
		 * the value $file_name_base? If so, that's this attachment's parent.
		 */
		$parent_posts = get_posts( array(
			'meta_query' => array(
				array(
					'key'   => 'inventory_presser_vin',
					'value' => $vin,
				)
			),
			'post_type'  => $this->post_type,
			'posts_per_page' => -1,
		) );

		if( 1 == sizeof( $parent_posts ) ) {
			//only one post was found, great
			$this->vin_to_parent_post_id[$vin] = $parent_posts[0]->ID;
			return $parent_posts[0]->ID;
		}

		return $value;
	}

	function allow_fetch_attachments( $value /* boolean */, $URL ) {
		/**
		 * Avoid downloading files that are 1) already in the uploads folder
		 * and 2) already in a subfolder of the uploads folder.
		 */
		$attachment_URL_parts = parse_url( $URL );
		$this_site_URL_parts = parse_url( $this->upload_dir['url'] );
		$same_host = $this_site_URL_parts['host'] == $attachment_URL_parts['host'];
		// They live on different servers, go fetch it
		if( ! $same_host ){ return true; }
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
			 * or, more recently,
			 * http://localhost/test-site/wp-content/uploads/{PENDING_DIR_NAME}/{VIN}/H517718-2.jpg
			 *
			 * Copy the file to the uploads folder, preserving the path after the
			 * pending directory.
			 */
			$source = $this->upload_dir['basedir'] . substr( $attachment_URL_parts['path'], strlen( $this_site_URL_parts['path'] ) );
			$destination = str_replace( '/' . self::PENDING_DIR_NAME, '', $source );

			if( is_file( $source ) && ( ! file_exists( $destination ) || filemtime( $source ) > filemtime( $destination ) ) ) {
				//Does the directory exist?
				if( ! is_dir( dirname( $destination ) ) ) {
					mkdir( dirname( $destination ), 0777, true );
				}
				copy( $source, $destination );
			}
			return false;
		}
		return true;
	}

	function allow_set_object_terms( $allow, $post_id, $term_ids, $taxonomy ) {
		//only erase all the terms on the object if we have new ones to add
		return 0 < sizeof( $term_ids );
	}

	function alter_fetched_file_url( $url ) {
		return str_replace( '/' . self::PENDING_DIR_NAME, '', $url );
	}

	function append_aborting_deletions_message( $arr ) {
		array_push( $arr, 'Not going to delete '. sizeof( $this->existing_posts_before_an_import ) .' posts after an import.<br />' );
		return $arr;
	}

	function append_about_to_delete_posts_message( $arr ) {
		array_push( $arr, 'About to delete ' . sizeof( $this->existing_posts_before_an_import ) . ' posts that were not found in the current import file.<br />' );
		return $arr;
	}

	function append_list_of_post_titles_we_deleted( $arr ) {
		foreach( $this->post_titles_that_were_deleted as $post_title ) {
			array_push( $arr, 'This post was not contained in the latest import file and has been deleted: ' . $post_title . '<br />' );
		}
		return $arr;
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
					'compare' => 'EXISTS'
				)
			),
			'orderby'        => 'guid',
			'post_parent'    => '0',
			'post_status'    => 'inherit',
			'post_type'      => 'attachment',
			'posts_per_page' => -1,
		) );

		foreach( $attachments as $attachment ) {

			//the post guid is a URL to the attachment, we need the file name without extension
			$file_name_base = $this->extract_vin_from_attachment_url( $attachment->guid );

			/**
			 * Do we have a post that uses our custom post type and has a meta
			 * key named `inventory_presser_vin` that contains
			 * the value $file_name_base? If so, that's this attachment's parent.
			 */
			$parent_posts = get_posts( array(
				'meta_query' => array(
					array(
						'key'   => 'inventory_presser_vin',
						'value' => $file_name_base,
					)
				),
				'post_type'  => $this->post_type,
				'posts_per_page' => -1,
			) );

			if( 0 < sizeof( $parent_posts ) ) {
				if( 1 == sizeof( $parent_posts ) ) {
					//only one post was found, great, use it's ID as our parent
					$attachment->post_parent = $parent_posts[0]->ID;
					wp_update_post( $attachment );

					$photo_num = get_post_meta( $attachment->ID, '_inventory_presser_photo_number', true );
					if( '1' === $photo_num ) {
						set_post_thumbnail( $attachment->post_parent, $attachment->ID );
					}
				}
			} else {
				/**
				 * No posts found. This attachment has our meta value that
				 * defines which photo it is in a series, but no vehicle was
				 * found that to which it could be attached. It's doomed.
				 */
				wp_delete_attachment( $attachment->ID, true );
			}
		}
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
		$result = $this->delete_directory( $this->upload_dir['basedir'] . '\\' . self::PENDING_DIR_NAME );
	}

	function delete_posts_not_found_in_new_import() {

		/**
		 * $this->existing_posts_before_an_import should now contain only posts
		 * that were in the database before the import ran and that were
		 * not contained in the import. this function is only called if the
		 * user wants to delete these posts, so delete them.
		 */
		if( 0 < sizeof( $this->existing_posts_before_an_import ) ) {
			add_filter( 'wp_import_errors_before_end', array( &$this, 'append_about_to_delete_posts_message' ));
		}

		foreach( $this->existing_posts_before_an_import as $post ) {
			/**
			 * Before we delete the post, decide if it's photos should be kept.
			 * If the unique slug of this post has changed, there might actually
			 * be two posts that represent this vehicle in the database at this
			 * moment. Changing the year, make model or trim, or adding another
			 * vehicle that has those same attributes results in a new unique
			 * slug being created for the same VIN. That means we are about to
			 * delete a post, but the photos should actually be dettached and
			 * preserved so they can be associated with the other post.
			 */
			if( 'attachment' != $post->post_type ) {
				$key = 'inventory_presser_vin';

				$duplicates = get_posts( array(
					'posts_per_page' => -1,
					'meta_query' => array(
						array(
							'key'     => $key,
							'value'   => get_post_meta( $post->ID, $key, true ),
							'compare' => '=',
						)
					)
				) );

				if( 1 < $duplicates->found_posts ) {
					/**
					 * There is at least one other post in the database with the
					 * same VIN, dettach this post's photos before deleting.
					 */
					dissociate_attachments( $post->ID );
				}
			}

			wp_delete_post( $post->ID, true );

			//save this post title for later
			array_push( $this->post_titles_that_were_deleted, $post->post_title );
			//make sure our filter is in place but only once
			if ( ! has_filter( 'wp_import_errors_before_end', array( &$this, 'append_list_of_post_titles_we_deleted' ) ) ) {
    			add_filter( 'wp_import_errors_before_end', array( &$this, 'append_list_of_post_titles_we_deleted' ) );
    		}
		}
	}

	function dissociate_attachments( $post_id ) {
		$args = array(
			'post_parent'    => $post_id,
			'post_type'      => 'attachment',
			'posts_per_page' => -1,
			'post_status'    => 'any'
		);

		foreach( get_posts( $args ) as $attachment ) {
			$attachment->post_parent = 0;
			wp_update_post( $attachment );
		}
	}

	function extract_vin_from_attachment_url( $url ) {
		$file_slug = pathinfo( basename( $url ), PATHINFO_FILENAME );

		//this might be a VIN, it might be a VIN-#
		$hyphen_pos = strrpos( $file_slug, '-' );

		if( false === $hyphen_pos ) {
			return $file_slug;
		}

		return $file_slug = substr( $file_slug, 0, $hyphen_pos );
	}

	function force_term_recount( $term_taxonomy_ids, $term_ids, $taxonomy, $post_id, $post ) {
		wp_update_term_count_now( $term_taxonomy_ids, $taxonomy );
	}

	function get_post_ID_from_guid( $guid ){
		global $wpdb;
		return $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE guid=%s", $guid ) );
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

	function inventory_post_exists ( $value, $post ) {

		if( 0 != $value ) {
			return $value;
		}

		if( 'attachment' == $post['post_type'] ) {
			//does an attachment exist with the same guid?
			$post_id = $this->get_post_ID_from_guid( $post['guid'] );
			if( null != $post_id ) { return $post_id; }
		} else {
			//check for a VIN match
			if ( isset( $post['postmeta'] ) ){
				foreach ( $post['postmeta'] as $meta ) {
					if( 'inventory_presser_vin' == $meta['key'] ) {
						$posts = get_posts( array(
							'post_type'  => $this->post_type,
							'meta_query' => array(
								array(
									'key'   => $meta['key'],
									'value' => $meta['value'],
								)
							)
						) );
						if( 0 < count( $posts ) ) {
							return $posts[0]->ID;
						}
					}
				}
			}
		}
		return $value;
	}

	/**
	 * Saves inserted or updated attachment image counts by VIN in a class
	 * variable called $this->attachment_counts
	 */
	function keep_track_of_attachment_counts( /* mixed */ $post ) {

		if( ! is_array( $post ) ) { return; }

		//extract VIN from payload URL
		$vin = $this->extract_vin_from_attachment_url( $post['attachment_url'] );

		//maintain our array of vin->counts
		if( isset( $this->attachment_counts[$vin] ) ) {
			$this->attachment_counts[$vin]++;
		} else {
			$this->attachment_counts[$vin] = 1;
		}
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

		$files = glob( $this->upload_dir['path'] . '/' . self::PENDING_DIR_NAME . '/*', GLOB_MARK | GLOB_NOSORT | GLOB_NOESCAPE ) or array();
		$photo_paths = $files;

		foreach( $files as $file ) {

			$path = $this->upload_dir['path'] . '/' . self::PENDING_DIR_NAME . '/' . basename( $file ) . '/*';
			$more = glob( $path, GLOB_NOSORT | GLOB_NOESCAPE );

			if( ! is_array( $more ) ) { continue; }

			$photo_paths = array_merge(
				$photo_paths,
				$more
			);
		}

		foreach( $photo_paths as $file ) {
			//is this "file" actually a directory? skip it
			if( '/' == strrev( $file )[0] || '\\' == strrev( $file )[0] ) {

				/**
				 * Attempt to delete the directory it lives in if it is a
				 * subfolder of the uploads folder. This will fail if the
				 * directory is not empty, so suppress warnings.
				 */

				if( $this->upload_dir['path'] != $file ) {
					@rmdir( $file );
				}
				continue;
			}

			if( ! $this->have_attachment_with_file_name( basename( $file ) ) ) {
				//no? delete the file
				unlink( $file );

				/**
				 * Attempt to delete the directory it lives in if it is a
				 * subfolder of the uploads folder. This will fail if the
				 * directory is not empty, so suppress warnings.
				 */

				if( $this->upload_dir['path'] != dirname( $file ) ) {
					@rmdir( dirname( $file ) );
				}
			}
		}
	}

	function remember_posts_before_an_import( $posts ) {
		/**
		 * Get a list of all vehicle photos in the database before this
		 * import runs. We'll prune the list as we import, and the remaining
		 * items will be deleted when the import is over.
		 */

		$this->existing_posts_before_an_import = get_posts( array(
			'meta_query'     => array(
				array(
					'key'     => '_inventory_presser_photo_number',
					'compare' => 'EXISTS'
				)
			),
			'post_status'    => 'inherit',
			'post_type'      => 'attachment',
			'posts_per_page' => -1,
		) );

		/**
		 * There exists a setting that determines whether or not we delete
		 * vehicles that aren't in the file we are importing. It's the only
		 * automated removal mechanism. Add posts to this array if it's on.
		 */

		if( $this->delete_vehicles_not_in_new_feeds ) {

			/**
			 * Also store all the post titles and dates for our post type
			 * as they exist before the import runs
			 */
			$this->existing_posts_before_an_import = array_merge(
				$this->existing_posts_before_an_import,
				get_posts( array(
					'post_status'    => 'publish',
					'post_type'      => $this->post_type,
					'posts_per_page' => -1,
				) )
			);
		}

		/**
		 * Do not modify the posts coming into this function, and return them
		 * back to the importer.
		 */
		return $posts;
	}

	function find_post_meta_value( $post_array, $meta_key ) {

		if( ! isset( $post_array['postmeta'] ) ) {
			return;
		}

		foreach( $post_array['postmeta'] as $meta ) {
			if( $meta['key'] == $meta_key ) {
				return $meta['value'];
			}
		}

		return false;
	}

	function remove_existing_post_from_import_purge_list( $post ) {
		/**
		 * Loop through all the posts the importer is about to import that we
		 * copied into $this->existing_posts_before_an_import with the function
		 * remember_posts_before_an_import()
		 */
		for( $p = 0; $p < sizeof( $this->existing_posts_before_an_import ); $p++ ) {

			$this_vin = $this->find_post_meta_value( $post, 'inventory_presser_vin' );
			$that_vin = get_post_meta( $this->existing_posts_before_an_import[$p]->ID, 'inventory_presser_vin', true );

			if( $this_vin == $that_vin ) {
				//remove this one, it's in the new file
				array_splice( $this->existing_posts_before_an_import, $p, 1 );
				return;
			}
		}
		return $post;
	}

	function update_existing_unique_post_meta_values( $post_id, $key, $value ) {
		update_post_meta( $post_id, $key, $value );
	}

	function update_existing_unique_term_meta_values( $term_id, $key, $value ) {
		update_term_meta( $term_id, $key, $value );
	}
}
