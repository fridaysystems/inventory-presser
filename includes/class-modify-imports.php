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
	var $post_titles_that_were_deleted = array();
	var $upload_dir;
	var $vin_to_parent_post_id = array();

	function __construct( $post_type ) {
		$this->post_type = $post_type;
		$this->upload_dir = wp_upload_dir();

		//don't actually download attachments during imports if they are already on this server
		add_filter( 'import_allow_fetch_file', array( &$this, 'allow_fetch_attachments' ), 10, 2 ) ;

		//Delete the pending import folder when the user deletes all plugin data
		add_action( 'inventory_presser_delete_all_data', array( &$this, 'delete_pending_import_folder' ) );

		//Recount term relationships when a post's terms are updated during imports
		add_action( 'wp_import_set_post_terms', array( &$this, 'force_term_recount' ), 10, 5 );
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

	function append_aborting_deletions_message( $arr ) {
		array_push( $arr, 'Not going to delete '. sizeof( $this->existing_posts_before_an_import ) .' posts after an import.<br />' );
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
}
