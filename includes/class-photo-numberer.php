<?php
defined( 'ABSPATH' ) or exit;

/**
 * Inventory_Presser_Photo_Numberer
 * 
 * If a user uploads a photo to a vehicle in the dashboard, it needs meta data
 * that tells this plugin how to order that photo among the others during
 * display. This class sets that meta data. It also sets other meta values,
 * including the VIN, and md5 hash checksum of the photo file.
 */
class Inventory_Presser_Photo_Numberer{
	
	/**
	 * hooks
	 *
	 * Adds hooks
	 * 
	 * @return void
	 */
	function hooks()
	{
		add_action( 'add_attachment', array( $this, 'maybe_number_photo' ), 10, 1 );
	}
	
	/**
	 * maybe_number_photo
	 * 
	 * Filter callback on add_attachment. Decides whether to write meta values 
	 * on attachments if they are uploaded to vehicles.
	 *
	 * @param  int $post_id
	 * @return void
	 */
	function maybe_number_photo( $post_id )
	{
		//Is this new attachment even attached to a post?
		$attachment = get_post( $post_id );
		if( empty( $attachment->post_parent ) )
		{
			//No
			return;
		}

		$parent = get_post( $attachment->post_parent );

		//Is this even attached to a vehicle?
		if( empty( $parent )
			|| ! class_exists( 'Inventory_Presser_Plugin' )
			|| INVP::POST_TYPE != $parent->post_type )
		{
			//No
			return;
		}

		//Save the VIN in the photo meta
		$vin = get_post_meta( $attachment->post_parent, apply_filters( 'invp_prefix_meta_key', 'vin' ), true );
		update_post_meta( $post_id, apply_filters( 'invp_prefix_meta_key', 'vin' ), $vin );

		//Save a md5 hash checksum of the attachment in meta
		$hash = hash_file( 'md5', $attachment_path );
		update_post_meta( $post_id, apply_filters( 'invp_prefix_meta_key', 'hash' ), $hash );

		//Does it have a number?
		$number = get_post_meta( $attachment->ID, apply_filters( 'invp_prefix_meta_key', 'photo_number' ), true );
		if( '' !== $number ) {
			//Yes
			return;
		}

		//Give it a number. How many photos does this vehicle have already?
		$photos = get_posts( array(
			'meta_query'     => array(
				array(
					'key'     => apply_filters( 'invp_prefix_meta_key', 'photo_number' ),
					'compare' => 'EXISTS'
				)
			),
			'order'          => 'ASC',
			'orderby'        => 'meta_value_num',
			'post_parent'    => $parent->ID,
			'post_type'      => 'attachment',
			'posts_per_page' => -1,
		) );

		if( 0 == sizeof( $photos ) ) {
			return;
		}

		$last_photo = end( $photos );
		$last_number = get_post_meta( $last_photo->ID,  apply_filters( 'invp_prefix_meta_key', 'photo_number' ), true );

		update_post_meta( $attachment->ID, apply_filters( 'invp_prefix_meta_key', 'photo_number' ), $last_number + 1 );
	}
}
