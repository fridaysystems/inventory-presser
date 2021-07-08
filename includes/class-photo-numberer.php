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
		add_filter( "rest_pre_insert_attachment", array( $this, 'set_post_parent' ), 10, 2 );
	}
	
	/**
	 * set_post_parent
	 * 
	 * The REST API does not support post_parent by default, so we have to move
	 * the `parent` value from the request into the prepared post in this filter
	 * callback.
	 *
	 * @param  WP_Post $prepared_post
	 * @param  WP_REST_Request $request
	 * @return void
	 */
	public function set_post_parent( $prepared_post, $request )
	{
		if( ! empty( $prepared_post->post_parent ) )
		{
			return $prepared_post;
		}

		if( 'attachment' != $prepared_post->post_type )
		{
			return $prepared_post;
		}

		$post_parent = $request->get_param( 'parent' );
		if( empty( $post_parent ) )
		{
			return $prepared_post;
		}

		$prepared_post->post_parent = $post_parent;
		return $prepared_post;
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
		if( empty( $parent ) || INVP::POST_TYPE != $parent->post_type )
		{
			//No
			return;
		}

		//Save the VIN in the photo meta
		$vin = get_post_meta( $attachment->post_parent, apply_filters( 'invp_prefix_meta_key', 'vin' ), true );
		if( ! empty( $vin ) )
		{
			update_post_meta( $post_id, apply_filters( 'invp_prefix_meta_key', 'vin' ), $vin );
		}

		//Save a md5 hash checksum of the attachment in meta
		$hash = hash_file( 'md5', get_attached_file( $post_id ) );
		update_post_meta( $post_id, apply_filters( 'invp_prefix_meta_key', 'hash' ), $hash );

		//Give it a number. Is the number in the slug?
		//photo-5-of-19-of-vinsgsdkdkdkgf
		$number = 0;
		if( ! empty( $_POST['slug'] ) && preg_match( '/photo\-([0-9]+)\-of\-[0-9]+\-of\-.*/', $_POST['slug'], $matches ) )
		{
			$number = $matches[1];
		}
		else
		{
			//Append the photo to the end
			//How many photos does this vehicle have already?
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
			
			if( 0 < sizeof( $photos ) )
			{
				$last_photo = end( $photos );
				$last_number = intval( get_post_meta( $last_photo->ID,  apply_filters( 'invp_prefix_meta_key', 'photo_number' ), true ) );
				$number = $last_number + 1;
			}
		}
		
		update_post_meta( $attachment->ID, apply_filters( 'invp_prefix_meta_key', 'photo_number' ), $number );
	}
}
