<?php
/**
 * Lets block editor users rearrange the order of vehicle photos using a gallery
 * block.
 *
 * @since      14.5.0
 * @package    inventory-presser
 * @subpackage inventory-presser/includes/admin
 * @author     Corey Salzano <corey@friday.systems>
 */

defined( 'ABSPATH' ) || exit;

/**
 * Inventory_Presser_Admin_Photo_Arranger
 */
class Inventory_Presser_Admin_Photo_Arranger {

	const CSS_CLASS = 'invp-rearrange';

	/**
	 * Adds hooks that power the feature.
	 *
	 * @return void
	 */
	public function add_hooks() {
		if ( ! self::is_enabled() ) {
			return;
		}

		// Make sure all attachment IDs are stored in the Gallery block when photos are attached, detached, or deleted.
		add_action( 'add_attachment', array( $this, 'add_attachment_to_gallery' ), 11, 1 );
		add_action( 'delete_attachment', array( $this, 'delete_attachment_handler' ), 10, 2 );
		add_action( 'wp_media_attach_action', array( $this, 'maintain_gallery_during_attach_and_detach' ), 10, 3 );

		// When a vehicle is saved, the gallery should be examined and...
		// - change their post_parent values to the vehicle post ID
		// - make sure they have sequence numbers and VINs
		// - update all the sequence numbers to match the gallery order.
		add_action( 'edit_post_' . INVP::POST_TYPE, array( $this, 'change_parents_and_sequence' ), 10, 2 );
		// - unattach photos that are no longer in the gallery
		add_action( 'edit_post_' . INVP::POST_TYPE, array( $this, 'unattach_when_removed' ), 10, 2 );

		/**
		 * When the vehicle is opened in the block editor, make sure the
		 * gallery block is there waiting for the user.
		 */
		add_action( 'the_post', array( $this, 'create_gallery' ), 10, 1 );
	}

	/**
	 * add_attachment_to_gallery
	 *
	 * @param  mixed $post_id
	 * @param  mixed $parent_id
	 * @return void
	 */
	public function add_attachment_to_gallery( $post_id, $parent_id = null ) {
		$attachment = get_post( $post_id );

		// Is this attachment an image?
		if ( ! wp_attachment_is_image( $attachment ) ) {
			// No.
			return;
		}

		// Is this new attachment even attached to a post?
		$parent;
		if ( ! empty( $attachment->post_parent ) ) {
			$parent = get_post( $attachment->post_parent );
		} elseif ( ! empty( $parent_id ) ) {
			$parent = get_post( $parent_id );
		} else {
			return;
		}

		// Is the new attachment attached to a vehicle?
		if ( empty( $parent->post_type ) || INVP::POST_TYPE !== $parent->post_type ) {
			// Parent post isn't a vehicle.
			return;
		}

		// Update the photo's post_parent.
		if ( empty( $attachment->post_parent ) || $attachment->post_parent !== $parent->ID ) {
			$attachment->post_parent = $parent->ID;
			$this->safe_update_post( $attachment );
		}

		// Loop over all the post's blocks in search of our Gallery.
		$blocks = parse_blocks( $parent->post_content );
		foreach ( $blocks as $index => &$block ) {
			// Is this a core gallery block? With a specific CSS class?
			if ( ! $this->is_gallery_block_with_specific_css_class( $block ) ) {
				continue;
			}

			// Does the block already have this attachment?
			if ( ! $this->inner_blocks_contains_id( $block, $post_id ) ) {
				// Add the uploaded attachment to this gallery.
				$block['innerBlocks'][] = array(
					'blockName'    => 'core/image',
					'attrs'        => array(
						'id'              => $post_id,
						'sizeSlug'        => 'large',
						'linkDestination' => 'none',
					),
					'innerBlocks'  => array(),
					'innerHTML'    => '',
					'innerContent' => array(
						0 => '',
					),
				);
			}

			$photo_count = count( $block['innerBlocks'] );

			// Change a CSS class to reflect the number of photos in the Gallery
			// Replace all 'columns-#'.
			$block['innerContent'][0] = preg_replace(
				'/ columns-[0-9]+/',
				' columns-' . $photo_count ?? 1,
				$block['innerContent'][0]
			);
			// Do it again with extra parameter to replace just the first with a max of 'columns-3'.
			$block['innerContent'][0] = preg_replace(
				'/ columns-[0-9]+/',
				' columns-' . min( 3, $photo_count ?? 4 ),
				$block['innerContent'][0],
				1
			);
			if ( false === strpos( $block['attrs']['className'] ?? '', 'columns-' ) ) {
				$block['attrs']['className'] = sprintf(
					'columns-%s %s',
					$photo_count ?? 1,
					$block['attrs']['className']
				);
			} else {
				$block['attrs']['className'] = preg_replace(
					'/columns-[0-9]+/',
					'columns-' . $photo_count ?? 1,
					$block['attrs']['className'],
					1
				);
			}

			// Add HTML that renders the image in the gallery
			// Is this image already in the Gallery HTML though?
			if ( false === mb_strpos( $block['innerContent'][0], "class=\"wp-image-$post_id\"" ) ) {
				// No.
				$position_list_end        = mb_strpos( $block['innerContent'][0], "</figure>\r\n<!-- /wp:gallery -->" );
				$new_html                 = sprintf(
					'<!-- wp:image {"id":%1$d,"sizeSlug":"large","linkDestination":"none"} --><figure class="wp-block-image size-large"><img src="%2$s" alt="" class="wp-image-%1$d"/></figure><!-- /wp:image -->',
					$post_id,
					$attachment->guid
				);
				$block['innerContent'][0] =
					substr( $block['innerContent'][0], 0, $position_list_end )
					. $new_html
					. substr( $block['innerContent'][0], ( $position_list_end ) );
			}

			// Update the block in the $blocks array.
			$blocks[ $index ] = $block;

			// and then update the post.
			$parent->post_content = serialize_blocks( $blocks );
			$this->safe_update_post( $parent );
			break;
		}
	}

	/**
	 * Runs on edit_post hook. Changes the post_parent values on photos in our
	 * magic Gallery block.
	 *
	 * @param  mixed $post_id
	 * @param  mixed $post
	 * @return void
	 */
	public function change_parents_and_sequence( $post_id, $post ) {

		// If this post was just trashed, who cares.
		if ( ! empty( $post->post_status ) && 'trash' === $post->post_status ) {
			return;
		}

		// Make sure the photos in the gallery block have the post_parent value.
		$block = $this->find_gallery_block( $post );
		if ( false === $block ) {
			return;
		}

		if ( empty( $block['innerBlocks'] ) ) {
			// There are no photos in the gallery.
			return;
		}

		// Set a post_parent value on every photo in the gallery block.
		foreach ( $block['innerBlocks'] as $index => $image_block ) {
			if ( empty( $image_block['blockName'] ) || 'core/image' !== $image_block['blockName'] ) {
				continue;
			}
			if ( empty( $image_block['attrs']['id'] ) ) {
				continue;
			}
			$attachment = get_post( $image_block['attrs']['id'] );
			if ( ! empty( $attachment ) ) {
				// Update the photo's post_parent.
				if ( empty( $attachment->post_parent ) || $attachment->post_parent !== $post_id ) {
					$attachment->post_parent = $post_id;
					$this->safe_update_post( $attachment );
				}

				// Does the number match?
				if ( strval( $index + 1 ) !== INVP::get_meta( 'photo_number', $attachment->ID ) ) {
					// Save VIN and photo hash in photo meta.
					Inventory_Presser_Photo_Numberer::maybe_number_photo( $attachment->ID );
					// Force save the sequence number.
					Inventory_Presser_Photo_Numberer::save_meta_photo_number( $attachment->ID, $post_id, strval( $index + 1 ) );
				}
			}
		}
	}

	/**
	 * Makes sure the Gallery Block is waiting for the user when they open a
	 * vehicle post in the block editor.
	 *
	 * @param  WP_Post $post
	 * @return void
	 */
	public function create_gallery( $post ) {
		global $pagenow;
		if ( ! is_admin()
			|| get_post_type() !== INVP::POST_TYPE
			|| 'post-new.php' !== $pagenow ) {

			return;
		}

		// Does the post content contain a Gallery with a specific CSS class?
		if ( false !== $this->find_gallery_block( $post ) ) {
			// Yes.
			return;
		}

		$blocks             = parse_blocks( $post->post_content );
		$blocks[]           = array(
			'blockName'    => 'core/gallery',
			'attrs'        => array(
				'linkTo'    => 'none',
				'className' => self::CSS_CLASS,
			),
			'innerBlocks'  => array(),
			'innerHTML'    => '',
			'innerContent' => array(
				0 => '<figure class="wp-block-gallery has-nested-images columns-default is-cropped columns-0 ' . self::CSS_CLASS . '"></figure>',
			),
		);
		$post->post_content = serialize_blocks( $blocks );
		$this->safe_update_post( $post );

			// Add all this vehicle's photos.
			// $posts = get_children(
			// array(
			// 'meta_key'    => apply_filters( 'invp_prefix_meta_key', 'photo_number' ),
			// 'order'       => 'ASC',
			// 'orderby'     => 'meta_value_num',
			// 'post_parent' => $post->ID,
			// 'post_type'   => 'attachment',
			// )
			// );
			// foreach ( $posts as $post ) {
			// $this->add_attachment_to_gallery( $post->ID, $post->post_parent );
			// }
	}

	/**
	 * delete_attachment_handler
	 *
	 * @param  mixed $post_id
	 * @param  mixed $post
	 * @return void
	 */
	public function delete_attachment_handler( $post_id, $post ) {
		$attachment = get_post( $post_id );
		if ( empty( $attachment->post_parent ) ) {
			return;
		}
		$this->remove_attachment_from_gallery( $post_id, $attachment->post_parent );
	}

	/**
	 * Look at the blocks in a given post for a gallery block with a specific
	 * CSS class.
	 *
	 * @param  WP_Post $post A post to examine for our gallery block.
	 * @return array|false
	 */
	protected function find_gallery_block( $post ) {
		if ( is_int( $post ) ) {
			$post = get_post( $post );
		}
		$blocks = parse_blocks( $post->post_content );
		foreach ( $blocks as $block ) {
			// Is this a core gallery block? With a specific CSS class?
			if ( ! $this->is_gallery_block_with_specific_css_class( $block ) ) {
				continue;
			}
			return $block;
		}
		return false;
	}

	/**
	 * Is the photo arranger via a Gallery Block feature enabled?
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		if ( ! class_exists( 'INVP' ) ) {
			return false;
		}
		return INVP::settings()['use_arranger_gallery'] ?? false;
	}

	/**
	 * is_gallery_block_with_specific_css_class
	 *
	 * @param  mixed $block
	 * @return bool
	 */
	protected function is_gallery_block_with_specific_css_class( $block ) {
		return ! empty( $block['blockName'] )
			&& 'core/gallery' === $block['blockName']
			&& ! empty( $block['attrs']['className'] )
			&& false !== mb_strpos( $block['attrs']['className'], self::CSS_CLASS );
	}

	/**
	 * maintain_gallery_during_attach_and_detach
	 *
	 * @param  mixed $action
	 * @param  mixed $attachment_id
	 * @param  mixed $parent_id
	 * @return void
	 */
	public function maintain_gallery_during_attach_and_detach( $action, $attachment_id, $parent_id ) {
		$parent_id = intval( $parent_id );
		if ( 'detach' === $action ) {
			$this->remove_attachment_from_gallery( $attachment_id, $parent_id );
			// Remove vehicle-specific meta values.
			delete_post_meta( $attachment_id, apply_filters( 'invp_prefix_meta_key', 'photo_number' ) );
			delete_post_meta( $attachment_id, apply_filters( 'invp_prefix_meta_key', 'vin' ) );
			Inventory_Presser_Photo_Numberer::renumber_photos( $parent_id );
			return;
		}
		$this->add_attachment_to_gallery( $attachment_id, $parent_id );
	}

	/**
	 * remove_attachment_from_gallery
	 *
	 * @param  mixed $post_id
	 * @param  mixed $parent_id
	 * @return void
	 */
	protected function remove_attachment_from_gallery( $post_id, $parent_id ) {
		$attachment = get_post( $post_id );

		// Is this attachment an image?
		if ( ! wp_attachment_is_image( $attachment ) ) {
			// No.
			return;
		}

		// Is this new attachment even attached to a post?
		$parent = get_post( $parent_id );

		// Is the new attachment attached to a vehicle?
		if ( empty( $parent->post_type ) || INVP::POST_TYPE !== $parent->post_type ) {
			// Parent post isn't a vehicle.
			return;
		}

		// Does the post content of the vehicle have a Gallery with a specific CSS class?
		$blocks = parse_blocks( $parent->post_content );
		foreach ( $blocks as $index => $block ) {
			// Is this a core gallery block? With a specific CSS class?
			if ( ! $this->is_gallery_block_with_specific_css_class( $block ) ) {
				continue;
			}

			// Does the block already have this attachment ID? Remove it.
			if ( ! empty( $block['innerBlocks'] )
				&& $this->inner_blocks_contains_id( $block, $post_id ) ) {

				$inner_block_count = count( $block['innerBlocks'] );
				for ( $b = 0; $b < $inner_block_count; $b++ ) {
					if ( $post_id === $block['innerBlocks'][ $b ]['attrs']['id'] ) {
						unset( $block['innerBlocks'][ $b ] );
						$block['innerBlocks'] = array_values( $block['innerBlocks'] );
						break;
					}
				}
			}

			// Change a CSS class to reflect the number of photos in the Gallery.
			$block['innerContent'][0] = preg_replace(
				'/ columns-[0-9]+/',
				' columns-' . count( $block['innerBlocks'] ) ?? 0,
				$block['innerContent'][0],
				2
			);

			// Remove a list item HTML that renders the image in the gallery.
			$pattern                  = sprintf(
				'/<!-- wp:image {"id":%1$d,"sizeSlug":"large","linkDestination":"none"} -->'
					. "[\r\n]*"
					. '<figure class="wp-block-image size-large"><img src="[^"]+" alt="" class="wp-image-%1$d"/></figure>'
					. "[\r\n]*"
					. '<!-- /wp:image -->/',
				$post_id
			);
			$block['innerContent'][0] = preg_replace(
				$pattern,
				'',
				$block['innerContent'][0]
			);

			// Update the block in the $blocks array.
			$blocks[ $index ] = $block;

			// and then update the post.
			$parent->post_content = serialize_blocks( $blocks );
			$this->safe_update_post( $parent );
			break;
		}
	}

	/**
	 * Removes our `edit_post_{post-type}` hooks, calls `wp_update_post()`, and
	 * re-adds the hooks.
	 *
	 * @param  WP_Post $post The post to update.
	 * @return void
	 */
	protected function safe_update_post( $post ) {

		// Do not allow inserts!
		if ( 0 === $post->ID ) {
			return;
		}

		// Don't cause hooks to fire themselves.
		remove_action( 'edit_post_' . INVP::POST_TYPE, array( $this, 'change_parents_and_sequence' ), 10, 2 );
		remove_action( 'edit_post_' . INVP::POST_TYPE, array( $this, 'unattach_when_removed' ), 10, 2 );

		wp_update_post( $post );

		// Re-add the hooks now that we're done making changes.
		add_action( 'edit_post_' . INVP::POST_TYPE, array( $this, 'change_parents_and_sequence' ), 10, 2 );
		add_action( 'edit_post_' . INVP::POST_TYPE, array( $this, 'unattach_when_removed' ), 10, 2 );
	}

	/**
	 * unattach_when_removed
	 *
	 * @param  mixed $post_id
	 * @param  mixed $post
	 * @return void
	 */
	public function unattach_when_removed( $post_id, $post ) {

		// If this post was just trashed, who cares.
		if ( ! empty( $post->post_status ) && 'trash' === $post->post_status ) {
			return;
		}

		// Does the post have our gallery block?
		$block = $this->find_gallery_block( $post );
		if ( false === $block ) {
			return;
		}

		/**
		 * Are there attachments to this post that are no longer in the
		 * gallery block?
		 */
		$attachment_ids = get_children(
			array(
				'fields'         => 'ids',
				'post_parent'    => $post_id,
				'post_type'      => 'attachment',
				'posts_per_page' => 500,
			)
		);

		foreach ( $attachment_ids as $attachment_id ) {
			if ( $this->inner_blocks_contains_id( $block, $attachment_id ) ) {
				continue;
			}
			// Detach those from this vehicle.
			$attachment              = get_post( $attachment_id );
			$attachment->post_parent = 0;
			$this->safe_update_post( $attachment );
		}
	}

	/**
	 * inner_blocks_contains_id
	 *
	 * @param  mixed $block A core/gallery block output by parse_blocks().
	 * @param  mixed $id The attachment ID to search for.
	 * @return bool
	 */
	protected function inner_blocks_contains_id( $block, $id ) {
		if ( empty( $block['innerBlocks'] ) ) {
			return false;
		}
		foreach ( $block['innerBlocks'] as $inner_block ) {
			if ( empty( $inner_block['attrs']['id'] )
				|| $id !== $inner_block['attrs']['id'] ) {
				continue;
			}
			return true;
		}
		return false;
	}
}
