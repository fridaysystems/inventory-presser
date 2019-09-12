<?php

/**
 * If a user uploads a photo to a vehicle in the dashboard, it needs meta data
 * that tells this plugin how to order that photo among the others during
 * display. This class sets that meta data.
 */
class Inventory_Presser_Photo_Numberer{

	function hooks()
	{
		add_action( 'add_attachment', array( $this, 'maybe_number_photo' ), 10, 1 );
	}

	function maybe_number_photo( $post_id )
	{
		//Is this new attachment even attached to a post?
		$attachment = get_post( $post_id );
		if( empty( $attachment->post_parent ) ) {
			//No
			return;
		}

		$parent = get_post( $attachment->post_parent );

		//Is this even attached to a vehicle?
		if( empty( $parent )
			|| ! class_exists( 'Inventory_Presser_Plugin' )
			|| Inventory_Presser_Plugin::CUSTOM_POST_TYPE != $parent->post_type )
		{
			//No
			return;
		}

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

		error_log( 'Last photo number was ' . $last_number );
		update_post_meta( $attachment->ID, apply_filters( 'invp_prefix_meta_key', 'photo_number' ), $last_number + 1 );
	}
}