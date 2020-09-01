<?php
defined( 'ABSPATH' ) or exit;

/**
 * Inventory_Presser_Taxonomy_Overlapper
 * 
 * Some of our taxonomies, like year, make, and model, have companion values
 * stored in post meta. This class makes the values match whether taxonomy term
 * relationships are changed or post meta values are updated. The values are 
 * overlapped between post meta and taxonomy terms so the job of REST API 
 * clients that update vehicles is easier.
 */
class Inventory_Presser_Taxonomy_Overlapper
{	
	/**
	 * hooks_add
	 * 
	 * Adds hooks to catch updates to meta values and term relationships.
	 *
	 * @return void
	 */
	function hooks_add()
	{
		/**
		 * When certain post meta fields like make & model are updated, also 
		 * maintain terms in taxonomies to make filtering vehicles easy.
		 */
		add_action( 'updated_postmeta', array( $this, 'maintain_taxonomy_terms_during_meta_updates' ), 10, 4 );
		add_action( 'added_post_meta', array( $this, 'maintain_taxonomy_terms_during_meta_updates' ), 10, 4 );
		add_action( 'deleted_post_meta', array( $this, 'maintain_taxonomy_terms_during_meta_updates' ), 10, 4 );

		//Do the same when taxonomy term relationships are changed
		add_action( 'set_object_terms', array( $this, 'term_relationship_updated' ), 10, 6 );
		add_action( 'deleted_term_relationships', array( $this, 'term_relationship_deleted' ), 10, 3 );
	}
	
	/**
	 * hooks_remove
	 *
	 * This is the inverse to hooks_add(). It removes all the hooks this class
	 * adds.
	 * 
	 * @return void
	 */
	function hooks_remove()
	{
		remove_action( 'updated_postmeta', array( $this, 'maintain_taxonomy_terms_during_meta_updates' ), 10, 4 );
		remove_action( 'added_post_meta', array( $this, 'maintain_taxonomy_terms_during_meta_updates' ), 10, 4 );
		remove_action( 'deleted_post_meta', array( $this, 'maintain_taxonomy_terms_during_meta_updates' ), 10, 4 );

		//Do the same when taxonomy term relationships are changed
		remove_action( 'set_object_terms', array( $this, 'term_relationship_updated' ), 10, 6 );
		remove_action( 'delete_term_relationships', array( $this, 'term_relationship_deleted' ), 10, 3 );
	}
	
	/**
	 * hooks
	 * 
	 * This is a formality to match the method naming scheme of all other 
	 * classes in this plugin. 
	 *
	 * @return void
	 */
	function hooks()
	{
		$this->hooks_add();
	}
	
	/**
	 * maintain_taxonomy_terms_during_meta_updates
	 * 
	 * When post meta values are updated on vehicle posts, check to see if the
	 * same value is also stored in one of our taxonomies to make filtering 
	 * easy. If so, mirror the changes by creating a new term relationship.
	 *
	 * @param  int $meta_id ID of updated metadata entry.
	 * @param  int $object_id Post ID.
	 * @param  string $meta_key Metadata key.
	 * @param  string|object $meta_value Metadata value. This will be a PHP-serialized string representation of the value if the value is an array, an object, or itself a PHP-serialized string.
	 * @return void
	 */
	function maintain_taxonomy_terms_during_meta_updates( $meta_id, $object_id, $meta_key, $meta_value )
	{
		//These are the unprefixed meta keys that have overlapping taxonomies
		$overlapping_keys = $this->overlapping_meta_keys();
		//unprefix the meta key
		$unprefixed = apply_filters( 'invp_unprefix_meta_key', $meta_key );

		//does $meta_key have a corresponding taxonomy?
		if( ! in_array( $unprefixed, array_keys( $overlapping_keys ) ) )
		{
			//No
			return;
		}

		$taxonomy = $overlapping_keys[$unprefixed];

		/**
		 * If we are in the Availability taxonomy, the end of this method
		 * appends terms instead of replacing. That means if the $meta_value is
		 * For Sale or Sold, we need to remove the opposite term.
		 */
		if( 'availability' == $unprefixed && ! empty( $meta_value ) )
		{
			$for_sale_and_sold_term_ids = get_terms( array(
				'taxonomy' => $taxonomy,
				'fields'   => 'ids',
				'slug'     => array( 'for-sale', 'sold' ),
			) );
			$this->hooks_remove();
			wp_remove_object_terms( $object_id, $for_sale_and_sold_term_ids, $taxonomy );
			$this->hooks_add();
		}

		//if $meta_value is empty, then remove a term & exit
		//will this only happen for wholesale?
		if( empty( $meta_value ) )
		{
			//remove a term actually
			$terms = array();
			if( 'availability' == $taxonomy )
			{
				$terms = wp_get_object_terms( $object_id, $taxonomy );
				for( $t=0; $t<sizeof($terms); $t++ )
				{
					if( $unprefixed == $terms[$t]->slug )
					{
						/**
						 * Both $unprefixed and $terms[$t]->slug are 'wholesale'
						 * or $unprefixed and $taxonomy are both 'availability'
						 */
						//trash this one
						unset( $terms[$t] );
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
		if( 'wholesale' == $unprefixed )
		{
			$meta_value = 'Wholesale';
		}

		//is there already a term for this $meta_value in the taxonomy?
		$term = get_term_by( 'slug', $this->sluggify( $meta_value ), $taxonomy );
		if( ! $term )
		{
			//it's not a slug, what about a name?
			$term = get_term_by( 'name', $meta_value, $taxonomy );
			if( ! $term )
			{
				//No, create a term
				$term_id_array = wp_insert_term( $meta_value, $taxonomy, array(
					'description' => $meta_value,
					'slug'        => $this->sluggify( $meta_value ),
				) );
				if( ! empty( $term_id_array['term_id'] ) )
				{
					$term = get_term( $term_id_array['term_id'], $taxonomy );
				}
			}
		}

		/** 
		 * Assign the new term for this $object_id. The Availability taxonomy
		 * holds For Sale/Sold and Wholesale, so append in that taxonomy.
		 */
		$this->hooks_remove();
		wp_set_object_terms( $object_id, $term->term_id, $taxonomy, ( 'availability' == $taxonomy ) );
		$this->hooks_add();
	}

	/**
	 * overlapping_meta_keys
	 * 
	 * Returns an array containing keys that are post meta field suffixes. The
	 * values are the overlapping taxonomy names.
	 *
	 * @return array
	 */
	private function overlapping_meta_keys()
	{
		return apply_filters( 'invp_overlapping_keys', array(
			'body_style'      => 'style',
			'make'            => 'make',
			'model'           => 'model',
			'year'            => 'model_year',

			'availability'    => 'availability',
			'condition'       => 'condition',
			'cylinders'       => 'cylinders',
			'drive_type'      => 'drive_type',
			'fuel'            => 'fuel',
			'location'        => 'location',
			'propulsion_type' => 'propulsion_type',
			'transmission'    => 'transmission',
			'type'            => 'type',
			'wholesale'       => 'availability',
		) );
	}

	/**
	 * sluggify
	 *
	 * Turns the name of something into a slug that WordPress will accept when
	 * creating objects like terms. WordPress slugs are described as containing
	 * only letters, numbers, and hyphens.
	 * 
	 * @param  string $name
	 * @return string An alteration of $name that WordPress will accept as a term slug
	 */
	private function sluggify( $name )
	{
		$name = preg_replace( '/[^a-zA-Z0-9\\-]/', '', str_replace( '/', '-', str_replace( ' ', '-', $name ) ) );
		return strtolower( str_replace( '--', '-', str_replace( '---', '-', $name ) ) );
	}
	
	/**
	 * term_relationship_deleted
	 * 
	 * If the term relationship that was just deleted was in a vehicle taxonomy,
	 * also delete the meta value that contains the same value.
	 *
	 * @param  int $object_id Object ID.
	 * @param  array $tt_ids An array of term taxonomy IDs.
	 * @param  string $taxonomy Taxonomy slug.
	 * @return void
	 */
	function term_relationship_deleted( $object_id, $tt_ids, $taxonomy )
	{
		//Does $object_id belong to a vehicle?
		if( Inventory_Presser_Plugin::CUSTOM_POST_TYPE != get_post_type( $object_id ) )
		{
			//No
			return;
		}

		//Is the taxonomy one that overlaps a meta field?
		$keys_and_taxonomies = $this->overlapping_meta_keys();
		$taxonomies_and_keys = array_flip( $keys_and_taxonomies );
		if( ! in_array( $taxonomy, array_values( $keys_and_taxonomies ) ) )
		{
			//No
			return;
		}

		//delete post meta values from this post 
		foreach( $tt_ids as $term_taxonomy_id )
		{
			if( 'availability' != $taxonomy )
			{
				$this->hooks_remove();
				delete_post_meta( $object_id, apply_filters( 'invp_prefix_meta_key', $taxonomies_and_keys[$taxonomy] ) );
				$this->hooks_add();
				continue;
			}

			//The availability taxonomy can contain more than one term, so don't just blindly delete
			$term = get_term_by( 'term_taxonomy_id', $term_taxonomy_id, $taxonomy );
			if( empty( $term->slug ) )
			{
				continue;
			}

			if( 'wholesale' == $term->slug )
			{
				$this->hooks_remove();
				delete_post_meta( $object_id, apply_filters( 'invp_prefix_meta_key', 'wholesale' ) );
				$this->hooks_add();
				continue;
			}

			//$term->slug must be for-sale or sold
			$this->hooks_remove();
			delete_post_meta( $object_id, apply_filters( 'invp_prefix_meta_key', 'availability' ) );
			$this->hooks_add();
		}
	}
	
	/**
	 * term_relationship_updated
	 *
	 * @param  int $object_id Object ID.
	 * @param  array $terms An array of object terms.
	 * @param  array $tt_ids An array of term taxonomy IDs.
	 * @param  string $taxonomy Taxonomy slug.
	 * @param  bool $append Whether to append new terms to the old terms.
	 * @param  array $old_tt_ids Old array of term taxonomy IDs.
	 * @return void
	 */
	function term_relationship_updated( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids )
	{
		//Does $object_id belong to a vehicle?
		if( Inventory_Presser_Plugin::CUSTOM_POST_TYPE != get_post_type( $object_id ) )
		{
			//No
			return;
		}

		//Is the taxonomy one that overlaps a meta field?
		$keys_and_taxonomies = $this->overlapping_meta_keys();
		$taxonomies_and_keys = array_flip( $keys_and_taxonomies );
		if( ! in_array( $taxonomy, array_values( $keys_and_taxonomies ) ) )
		{
			//No
			return;
		}

		//An empty array of terms is passed often
		if( empty( $terms ) )
		{
			return;
		}

		foreach( $terms as $term_id )
		{
			$term = get_term( $term_id, $taxonomy );
			if( ! is_object( $term ) || 'WP_Term' != get_class( $term ) )
			{
				continue;
			}

			//For most taxonomies, we can just save the term name in the post meta field
			if( 'availability' != $taxonomy )
			{
				$this->hooks_remove();
				update_post_meta( $object_id, apply_filters( 'invp_prefix_meta_key', $taxonomies_and_keys[$taxonomy] ), $term->name );
				$this->hooks_add();
				continue;
			}

			//The availability taxonomy was the one updated
			switch( $term->slug )
			{
				case "for-sale":
				case "sold":
					$this->hooks_remove();
					update_post_meta( $object_id, apply_filters( 'invp_prefix_meta_key', 'availability' ), $term->name );
					$this->hooks_add();
					break;

				case "wholesale":
					$this->hooks_remove();
					update_post_meta( $object_id, apply_filters( 'invp_prefix_meta_key', 'wholesale' ), true );
					$this->hooks_add();
					break;
			}
		}
	}
}
