<?php
/**
 * Inventory_Presser_Taxonomy_Overlapper
 *
 * @package inventory-presser
 */

defined( 'ABSPATH' ) || exit;

/**
 * Inventory_Presser_Taxonomy_Overlapper
 *
 * Some of our taxonomies, like year, make, and model, have companion values
 * stored in post meta. This class makes the values match whether taxonomy term
 * relationships are changed or post meta values are updated. The values are
 * overlapped between post meta and taxonomy terms so the job of REST API
 * clients that update vehicles is easier.
 */
class Inventory_Presser_Taxonomy_Overlapper {

	/**
	 * A meta value was just deleted after a term relationship was deleted.
	 * Check for another term relationship in the same taxonomy. A user
	 * could have marked a vehicle as both an Acura and Audi by mistake, and
	 * removed one. We want to make sure the meta value contains the
	 * remaining term name.
	 *
	 * @param  int    $object_id The vehicle post ID being updated.
	 * @param  string $taxonomy The taxonomy slug.
	 * @return void
	 */
	public function check_for_remaining_term_relationship( $object_id, $taxonomy ) {
		$terms = wp_get_object_terms( $object_id, $taxonomy );
		if ( empty( $terms ) ) {
			return;
		}
		$taxonomies_and_keys = self::overlapping_meta_keys_flipped();
		$this->hooks_remove_meta();
		update_post_meta( $object_id, apply_filters( 'invp_prefix_meta_key', $taxonomies_and_keys[ $taxonomy ] ), $terms[0]->name );
		$this->hooks_add_meta();
	}

	/**
	 * When a relationship in the Transmissions taxonomy is deleted, delete a
	 * post meta value that holds the transmission speeds, too.
	 *
	 * @param  int     $object_id The vehicle post ID being updated.
	 * @param  string  $taxonomy The taxonomy slug.
	 * @param  WP_Term $term The term object.
	 * @return void
	 */
	public function delete_transmission_speeds_meta( $object_id, $taxonomy, $term ) {
		if ( 'transmission' !== strtolower( $taxonomy ) ) {
			return;
		}
		delete_post_meta( $object_id, apply_filters( 'invp_prefix_meta_key', 'transmission_speeds' ), $this->extract_speeds( $term->name ) );
	}

	/**
	 * Takes a transmission taxonomy term name like "6 Speed Automatic",
	 * "5 Speed Manual", "Continuously Variable", or "Unknown" and returns "6",
	 * "5", "V", and "U", respectively.
	 *
	 * @param  string $transmission_term_name The name of a term in the transmission taxonomy.
	 * @return string
	 */
	private function extract_speeds( $transmission_term_name ) {
		// Does the term identify the number of speeds?
		$patterns = array(
			'/([0-9]+) Speed Automatic/',
			'/([0-9]+) Speed Automanual/',
			'/([0-9]+) Speed Manual/',
		);
		foreach ( $patterns as $pattern ) {
			preg_match( $pattern, $transmission_term_name, $matches );
			if ( ! empty( $matches ) ) {
				// Yes.
				return $matches[1];
			}
		}

		// Is it unknown or variable?
		if ( 'continuously variable' === strtolower( $transmission_term_name )
			|| 'CVT' === strtoupper( $transmission_term_name )
		) {
			// It's variable.
			return 'V';
		}

		// It's unknown.
		return 'U';
	}

	/**
	 * Adds hooks to catch updates to meta values and term relationships.
	 *
	 * @return void
	 */
	public function hooks_add() {
		$this->hooks_add_meta();
		$this->hooks_add_terms();
		add_action( 'invp_taxonomy_overlapper_updated_meta', array( $this, 'update_transmission_speeds_meta' ), 10, 3 );
		add_action( 'invp_taxonomy_overlapper_deleted_meta', array( $this, 'delete_transmission_speeds_meta' ), 10, 3 );

		add_action( 'invp_taxonomy_overlapper_deleted_meta', array( $this, 'check_for_remaining_term_relationship' ), 11, 2 );
	}

	/**
	 * When certain post meta fields like make & model are updated, also
	 * maintain terms in taxonomies to make filtering vehicles easy.
	 *
	 * @return void
	 */
	protected function hooks_add_meta() {
		add_action( 'updated_postmeta', array( $this, 'maintain_taxonomy_terms_during_meta_updates' ), 10, 4 );
		add_action( 'added_post_meta', array( $this, 'maintain_taxonomy_terms_during_meta_updates' ), 10, 4 );
		add_action( 'deleted_post_meta', array( $this, 'maintain_taxonomy_terms_during_meta_updates' ), 10, 4 );
	}

	/**
	 * When certain taxonomy term relationships are changed, update matching
	 * post meta fields.
	 *
	 * @return void
	 */
	protected function hooks_add_terms() {
		add_action( 'added_term_relationship', array( $this, 'term_relationship_updated' ), 10, 3 );
		add_action( 'deleted_term_relationships', array( $this, 'term_relationship_deleted' ), 10, 3 );
	}

	/**
	 * This method is the inverse of hooks_add().
	 *
	 * @return void
	 */
	protected function hooks_remove() {
		$this->hooks_remove_meta();
		$this->hooks_remove_terms();
		remove_action( 'invp_taxonomy_overlapper_updated_meta', array( $this, 'update_transmission_speeds_meta' ), 10, 3 );
		remove_action( 'invp_taxonomy_overlapper_deleted_meta', array( $this, 'delete_transmission_speeds_meta' ), 10, 3 );

		remove_action( 'invp_taxonomy_overlapper_deleted_meta', array( $this, 'check_for_remaining_term_relationship' ), 11, 2 );
	}

	/**
	 * Removes the hooks from post meta adds, updates, and deletes.
	 *
	 * @return void
	 */
	protected function hooks_remove_meta() {
		remove_action( 'updated_postmeta', array( $this, 'maintain_taxonomy_terms_during_meta_updates' ), 10, 4 );
		remove_action( 'added_post_meta', array( $this, 'maintain_taxonomy_terms_during_meta_updates' ), 10, 4 );
		remove_action( 'deleted_post_meta', array( $this, 'maintain_taxonomy_terms_during_meta_updates' ), 10, 4 );
	}

	/**
	 * Removes the hooks from taxonomy term updates and removals.
	 *
	 * @return void
	 */
	protected function hooks_remove_terms() {
		remove_action( 'added_term_relationship', array( $this, 'term_relationship_updated' ), 10, 3 );
		remove_action( 'deleted_term_relationships', array( $this, 'term_relationship_deleted' ), 10, 3 );
	}

	/**
	 * This is a formality to match the method naming scheme of all other
	 * classes in this plugin.
	 *
	 * @return void
	 */
	public function add_hooks() {
		$this->hooks_add();
	}

	/**
	 * When post meta values are updated on vehicle posts, check to see if the
	 * same value is also stored in one of our taxonomies to make filtering
	 * easy. If so, mirror the changes by creating a new term relationship.
	 *
	 * @param  int           $meta_id    ID of updated metadata entry.
	 * @param  int           $object_id  Post ID.
	 * @param  string        $meta_key   Metadata key.
	 * @param  string|object $meta_value Metadata value. This will be a PHP-serialized string representation of the value if the value is an array, an object, or itself a PHP-serialized string.
	 * @return void
	 */
	public function maintain_taxonomy_terms_during_meta_updates( $meta_id, $object_id, $meta_key, $meta_value ) {
		if ( '_edit_lock' === strtolower( $meta_key ) ) {
			return;
		}

		// These are the unprefixed meta keys that have overlapping taxonomies.
		$overlapping_keys = self::overlapping_meta_keys();
		// unprefix the meta key.
		$unprefixed = apply_filters( 'invp_unprefix_meta_key', $meta_key );

		// does $meta_key have a corresponding taxonomy?
		if ( ! in_array( $unprefixed, array_keys( $overlapping_keys ), true ) ) {
			// No.
			return;
		}

		$taxonomy = $overlapping_keys[ $unprefixed ];

		/**
		 * If we are in the Availability taxonomy, the end of this method
		 * appends terms instead of replacing so that a vehicle can be both
		 * For Sale and Wholesale. If the $meta_value is For Sale, Sold or Sale
		 * Pending, we need to remove the other of these terms.
		 */
		if ( 'availability' === strtolower( $unprefixed )
			&& ! empty( $meta_value )
			&& in_array( INVP::sluggify( $meta_value ), array( 'for-sale', 'sold', 'sale-pending' ), true )
		) {
			$remove_term_ids = get_terms(
				array(
					'taxonomy' => $taxonomy,
					'fields'   => 'ids',
					'slug'     => array_diff( array( 'for-sale', 'sold', 'sale-pending' ), array( INVP::sluggify( $meta_value ) ) ),
				)
			);
			$this->hooks_remove();
			wp_remove_object_terms( $object_id, $remove_term_ids, $taxonomy );
			$this->hooks_add();
		}

		// if $meta_value is empty or we have deleted a meta value, remove a term.
		global $action;
		if ( empty( $meta_value ) || 'delete-meta' === $action ) {
			// remove a term actually.
			$terms = array();
			if ( 'availability' === strtolower( $taxonomy ) ) {
				$terms      = wp_get_object_terms( $object_id, $taxonomy );
				$term_count = count( $terms );
				for ( $t = 0; $t < $term_count; $t++ ) {
					if ( $unprefixed === $terms[ $t ]->slug ) {
						/**
						 * Both $unprefixed and $terms[$t]->slug are 'wholesale'
						 * or $unprefixed and $taxonomy are both 'availability'
						 */
						// trash this one.
						unset( $terms[ $t ] );
						break;
					}
				}
			}

			$this->hooks_remove();
			wp_set_object_terms( $object_id, array_column( $terms, 'term_id' ), $taxonomy );
			$this->hooks_add();
			return;
		}

		/**
		 * Wholesale is a term in the Availability taxonomy rather than a real
		 * boolean as the meta field suggests & is registered.
		 */
		if ( 'wholesale' === strtolower( $unprefixed ) ) {
			$meta_value = 'Wholesale';
		}

		// is there already a term for this $meta_value in the taxonomy?
		$term = get_term_by( 'slug', INVP::sluggify( $meta_value ), $taxonomy );
		if ( ! $term ) {
			// it's not a slug, what about a name?
			$term = get_term_by( 'name', $meta_value, $taxonomy );
			if ( ! $term ) {
				// No, create a term.
				$term_id_array = wp_insert_term(
					$meta_value,
					$taxonomy,
					array(
						'description' => $meta_value,
						'slug'        => INVP::sluggify( $meta_value ),
					)
				);
				if ( ! is_wp_error( $term_id_array ) && ! empty( $term_id_array['term_id'] ) ) {
					$term = get_term( $term_id_array['term_id'], $taxonomy );
				}
			}
		}

		/**
		 * Assign the new term for this $object_id. The Availability taxonomy
		 * holds For Sale/Sold/Sale Pending and Wholesale, so append in that
		 * taxonomy.
		 */
		$this->hooks_remove();
		wp_set_object_terms( $object_id, $term->term_id, $taxonomy, ( 'availability' === strtolower( $taxonomy ) ) );
		$this->hooks_add();
	}

	/**
	 * Returns an array containing keys that are post meta field suffixes. The
	 * values are the overlapping taxonomy names.
	 *
	 * @return array
	 */
	public static function overlapping_meta_keys() {
		return apply_filters(
			'invp_overlapping_keys',
			array(
				'body_style'      => 'style',
				'make'            => 'make',
				'model'           => 'model',
				'year'            => 'model_year',

				'availability'    => 'availability',
				'color_base'      => 'colors',
				'condition'       => 'condition',
				'cylinders'       => 'cylinders',
				'drive_type'      => 'drive_type',
				'fuel'            => 'fuel',
				'location'        => 'location',
				'propulsion_type' => 'propulsion_type',
				'transmission'    => 'transmission',
				'type'            => 'type',
				'wholesale'       => 'availability', // Wholesale is a meta field and also a valid Availability taxonomy term.

				'condition_boat'  => 'condition_boat',
				'engine_count'    => 'engine_count',
				'engine_make'     => 'engine_make',
				'engine_model'    => 'engine_model',
				'horsepower'      => 'horsepower',
				'hull_material'   => 'hull_material',
			)
		);
	}

	/**
	 * Returns an array_flip() equivalent of the array returned by
	 * overlapping_meta_keys_flipped().
	 *
	 * @return array
	 */
	private static function overlapping_meta_keys_flipped() {
		$arr = self::overlapping_meta_keys();
		unset( $arr['wholesale'] ); // Wholesale is a meta field and also a valid Availability taxonomy term.
		return array_flip( $arr );
	}

	/**
	 * If the term relationship that was just deleted was in a vehicle taxonomy,
	 * also delete the meta value that contains the same value.
	 *
	 * @param  int    $object_id Object ID.
	 * @param  array  $tt_ids    An array of term taxonomy IDs.
	 * @param  string $taxonomy  Taxonomy slug.
	 * @return void
	 */
	public function term_relationship_deleted( $object_id, $tt_ids, $taxonomy ) {
		// Does $object_id belong to a vehicle?
		if ( INVP::POST_TYPE !== get_post_type( $object_id ) ) {
			// No.
			return;
		}

		// Is the taxonomy one that overlaps a meta field?
		$keys_and_taxonomies = self::overlapping_meta_keys();
		if ( ! in_array( $taxonomy, array_values( $keys_and_taxonomies ), true ) ) {
			// No.
			return;
		}

		$taxonomies_and_keys = self::overlapping_meta_keys_flipped();
		// delete post meta values from this post.
		foreach ( $tt_ids as $term_taxonomy_id ) {
			$term = get_term_by( 'term_taxonomy_id', $term_taxonomy_id, $taxonomy );
			if ( is_wp_error( $term ) ) {
				continue;
			}

			if ( 'availability' !== strtolower( $taxonomy ) ) {
				$this->hooks_remove_meta();
				delete_post_meta( $object_id, apply_filters( 'invp_prefix_meta_key', $taxonomies_and_keys[ $taxonomy ] ), $term->name );
				$this->hooks_add_meta();

				do_action( 'invp_taxonomy_overlapper_deleted_meta', $object_id, $taxonomy, $term );
				continue;
			}

			// The availability taxonomy can contain more than one term, so don't just blindly delete.
			if ( empty( $term->slug ) ) {
				continue;
			}

			if ( 'wholesale' === strtolower( $term->slug ) ) {
				$this->hooks_remove_meta();
				delete_post_meta( $object_id, apply_filters( 'invp_prefix_meta_key', 'wholesale' ), $term->name );
				$this->hooks_add_meta();

				do_action( 'invp_taxonomy_overlapper_deleted_meta', $object_id, $taxonomy, $term );
				continue;
			}

			// $term->slug must be for-sale or sold
			$this->hooks_remove_meta();
			delete_post_meta( $object_id, apply_filters( 'invp_prefix_meta_key', 'availability' ), $term->name );
			$this->hooks_add_meta();

			do_action( 'invp_taxonomy_overlapper_deleted_meta', $object_id, $taxonomy, $term );
		}
	}

	/**
	 * Callback when term relationships are updated. This method updates the
	 * post meta field that overlaps the taxonomy.
	 *
	 * @param  int    $object_id Object ID.
	 * @param  int    $tt_id     A term taxonomy ID.
	 * @param  string $taxonomy  Taxonomy slug.
	 * @return void
	 */
	public function term_relationship_updated( $object_id, $tt_id, $taxonomy ) {
		// Does $object_id belong to a vehicle?
		if ( INVP::POST_TYPE !== get_post_type( $object_id ) ) {
			// No.
			return;
		}

		// Is the taxonomy one that overlaps a meta field?
		$keys_and_taxonomies = self::overlapping_meta_keys();
		if ( ! in_array( $taxonomy, array_values( $keys_and_taxonomies ), true ) ) {
			// No.
			return;
		}

		// An empty array of terms is passed often.
		if ( empty( $tt_id ) ) {
			return;
		}

		$term = get_term_by( 'term_taxonomy_id', $tt_id, $taxonomy );
		if ( ! is_object( $term ) || 'WP_Term' !== get_class( $term ) ) {
			return;
		}

		$taxonomies_and_keys = self::overlapping_meta_keys_flipped();
		// For most taxonomies, we can just save the term name in the post meta field.
		if ( 'availability' !== strtolower( $taxonomy ) ) {
			$this->hooks_remove_meta();
			update_post_meta( $object_id, apply_filters( 'invp_prefix_meta_key', $taxonomies_and_keys[ $taxonomy ] ), $term->name );
			$this->hooks_add_meta();

			do_action( 'invp_taxonomy_overlapper_updated_meta', $object_id, $taxonomy, $term );

			return;
		}

		// The availability taxonomy was the one updated.
		switch ( strtolower( $term->slug ) ) {
			case 'for-sale':
			case 'sale-pending':
			case 'sold':
				$this->hooks_remove_meta();
				update_post_meta( $object_id, apply_filters( 'invp_prefix_meta_key', 'availability' ), $term->name );
				$this->hooks_add_meta();
				break;

			case 'wholesale':
				$this->hooks_remove_meta();
				update_post_meta( $object_id, apply_filters( 'invp_prefix_meta_key', 'wholesale' ), true );
				$this->hooks_add_meta();
				break;

			default:
				return; // Makes sure the action hook below only runs after an update.
		}

		do_action( 'invp_taxonomy_overlapper_updated_meta', $object_id, $taxonomy, $term );
	}

	/**
	 * When a relationship in the Transmissions taxonomy is changed, update a
	 * post meta value that holds the transmission speeds, too.
	 *
	 * @param  int     $object_id The vehicle post ID being updated.
	 * @param  string  $taxonomy The taxonomy slug.
	 * @param  WP_Term $term The term object.
	 * @return void
	 */
	public function update_transmission_speeds_meta( $object_id, $taxonomy, $term ) {
		if ( 'transmission' !== strtolower( $taxonomy ) ) {
			return;
		}
		update_post_meta( $object_id, apply_filters( 'invp_prefix_meta_key', 'transmission_speeds' ), $this->extract_speeds( $term->name ) );
	}
}
