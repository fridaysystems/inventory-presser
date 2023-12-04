<?php
defined( 'ABSPATH' ) || exit;

/**
 * Inventory_Presser_Photo_Numberer
 *
 * If a user uploads a photo to a vehicle in the dashboard, it needs meta data
 * that tells this plugin how to order that photo among the others during
 * display. This class sets that and other meta values, including the VIN, and
 * md5 hash checksum of the photo file. If it determines a photo upload is the
 * first photo to be attached to a vehicle, that photo is set as the featured
 * image for the vehicle post.
 */
class Inventory_Presser_Photo_Numberer {


	/**
	 * hooks
	 *
	 * Adds hooks
	 *
	 * @return void
	 */
	public function add_hooks() {
		add_action( 'add_attachment', array( __CLASS__, 'maybe_number_photo' ), 10, 1 );

		/**
		 * Put the sequence number in the title of the post to which photos are
		 * attached in the Media Library table view.
		 */
		add_filter( 'the_title', array( $this, 'add_sequence_number_to_titles' ), 10, 2 );
	}

	public function add_sequence_number_to_titles( $title, $id = null ) {
		if ( ! is_admin() ) {
			return $title;
		}

		// Is this the Media Library upload.php?
		if ( function_exists( 'get_current_screen' ) && $screen = get_current_screen() ) {
			if ( empty( $screen->parent_file ) || 'upload.php' != $screen->parent_file ) {
				// No
				return $title;
			}
		} else {
			return $title;
		}

		// Is this post ID a vehicle photo?
		if ( empty( $id ) ) {
			return $title;
		}

		if ( 'attachment' !== get_post_type( $id ) ) {
			// No.
			return $title;
		}

		$parent = get_post_parent( $id );
		if ( empty( $parent ) || INVP::POST_TYPE != $parent->post_type ) {
			// No
			return $title;
		}

		// Get the photo count
		return sprintf(
			'%s (%s %s %s %s)',
			$title,
			__( 'Photo', 'inventory-presser' ),
			INVP::get_meta( 'photo_number', $id ),
			__( 'of', 'inventory-presser' ),
			invp_get_the_photo_count( $parent->ID )
		);
	}

	/**
	 * maybe_number_photo
	 *
	 * Filter callback on add_attachment. Decides whether to write meta values
	 * on attachments if they are uploaded to vehicles. If this method
	 * determines the photo sequence number to be 1, the attachment is also set
	 * as the featured image.
	 *
	 * @param  int $post_id
	 * @return void
	 */
	public static function maybe_number_photo( $post_id ) {
		// Is this new attachment even attached to a post?
		$attachment = get_post( $post_id );
		if ( empty( $attachment->post_parent ) ) {
			// No
			return;
		}

		$parent = get_post( $attachment->post_parent );

		// Is this even attached to a vehicle?
		if ( empty( $parent ) || INVP::POST_TYPE != $parent->post_type ) {
			// No
			return;
		}

		// Save the VIN in the photo meta
		self::save_meta_vin( $post_id, $attachment->post_parent );

		// Save a md5 hash checksum of the attachment in meta
		self::save_meta_hash( $post_id );

		// Assign and save a sequence number for the photo like 1, 2, 3, etc
		self::save_meta_photo_number( $post_id, $attachment->post_parent );
	}

	/**
	 * renumber_photos
	 *
	 * Reassigns sequence numbers to all photos attached to a vehicle post.
	 *
	 * Designed to run after a photo is deleted from the middle.
	 *
	 * @param  int $post_id The post ID of a vehicle
	 * @return void
	 */
	public static function renumber_photos( $post_id ) {
		// Get all of this vehicle's photos
		$posts = get_children(
			array(
				'meta_key'    => apply_filters( 'invp_prefix_meta_key', 'photo_number' ),
				'orderby'     => 'meta_value_num',
				'post_parent' => $post_id,
				'post_type'   => 'attachment',
			)
		);

		for ( $s = 1; $s <= sizeof( $posts ); $s++ ) {
			self::save_meta_photo_number( $posts[ $s ]->ID, $post_id, $s );
		}
	}

	/**
	 * save_meta_hash
	 *
	 * Saves an MD5 hash checksum of the attachment file bytes in the attachment
	 * post meta. This 32 character string can be used for file comparisons
	 * while not taxing the server as much as other methods.
	 *
	 * @param  int $post_id
	 * @return void
	 */
	protected static function save_meta_hash( $post_id ) {
		// Save a md5 hash checksum of the attachment in meta
		$file_path = get_attached_file( $post_id );
		if ( false === $file_path ) {
			return;
		}
		update_post_meta(
			$post_id,
			apply_filters( 'invp_prefix_meta_key', 'hash' ),
			hash_file( 'md5', $file_path )
		);
	}

	/**
	 * save_meta_photo_number
	 *
	 * Saves a sequence number like 1, 2, or 99 in attachment post meta. This
	 * number dictates the order in which the photos will be disabled in sliders
	 * and galleries.
	 *
	 * @param  int $post_id         The post ID of the attachment
	 * @param  int $parent_post_id  The post ID of the vehicle to which $post_id is a child
	 * @param  int $sequence_number The sequence number to save. Do not provide to append.
	 * @return void
	 */
	public static function save_meta_photo_number( $post_id, $parent_post_id, $sequence_number = null ) {
		// Does this photo already have a sequence number?
		if ( null == $sequence_number ) {
			if ( ! empty( INVP::get_meta( 'photo_number', $post_id ) ) ) {
				// Yes
				return;
			}

			// Is the number in the slug?
			// photo-5-of-19-of-vinsgsdkdkdkgf
			$number = 0;
			if ( ! empty( $_POST['slug'] ) && preg_match( '/photo\-([0-9]+)\-of\-[0-9]+\-of\-.*/', $_POST['slug'], $matches ) ) {
				$number = intval( $matches[1] );
			} else {
				// Append the photo to the end
				// This hook fires after the attachment is added.
				// How many photos does this vehicle have?
				$number = invp_get_the_photo_count( $parent_post_id );
				if ( 1 == $number ) {
					// This is photo number 1, it should be the featured image
					set_post_thumbnail( $parent_post_id, $post_id );
				}
			}
		} else {
			$number = $sequence_number;
		}

		if ( 0 !== $number ) {
			update_post_meta( $post_id, apply_filters( 'invp_prefix_meta_key', 'photo_number' ), $number );
		}
	}

	/**
	 * save_meta_vin
	 *
	 * Saves the vehicle VIN in attachment meta when an attachment is uploaded
	 * to a vehicle post.
	 *
	 * @param  int $post_id
	 * @param  int $attachment
	 * @return void
	 */
	protected static function save_meta_vin( $post_id, $parent_post_id ) {
		$vin = invp_get_the_VIN( $parent_post_id );
		if ( empty( $vin ) ) {
			return;
		}
		update_post_meta( $post_id, apply_filters( 'invp_prefix_meta_key', 'vin' ), $vin );
	}
}
