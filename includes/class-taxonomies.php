<?php
defined( 'ABSPATH' ) || exit;

/**
 * Inventory_Presser_Taxonomies
 *
 * Registers and manipulates our custom taxonomies and their terms.
 *
 * @since      1.3.1
 * @package inventory-presser
 * @subpackage inventory-presser/includes
 * @author     Corey Salzano <corey@friday.systems>, John Norton <norton@fridaynet.com>
 */
class Inventory_Presser_Taxonomies {

	const CRON_HOOK_DELETE_TERMS = 'inventory_presser_delete_unused_terms';

	/**
	 * Users can show and hide the taxonomies in the dashboard menu.
	 *
	 * @param  array $taxonomy_data
	 * @return array
	 */
	public function change_taxonomy_show_in_menu_attribute( $taxonomy_data ) {
		$settings = INVP::settings();
		if ( empty( $settings['taxonomies'] ) ) {
			return $taxonomy_data;
		}
		$count = count( $taxonomy_data );
		for ( $i = 0; $i < $count; $i++ ) {
			// Should this taxonomy be shown in the dashboard menu under Vehicles?
			$taxonomy_data[ $i ]['args']['show_in_menu'] = $settings['taxonomies'][ $taxonomy_data[ $i ]['args']['query_var'] ]['admin_menu'] ?? false;
		}
		return $taxonomy_data;
	}

	/**
	 * Removes all terms in all our taxonomies. Used when uninstalling the
	 * plugin.
	 *
	 * @return void
	 */
	public function delete_term_data() {
		// remove the terms in taxonomies.
		$taxonomy_data = self::taxonomy_data();
		$count         = count( $taxonomy_data );
		for ( $i = 0; $i < $count; $i++ ) {
			$tax   = $taxonomy_data[ $i ]['args']['label'];
			$terms = get_terms(
				array(
					'taxonomy'   => $tax,
					'fields'     => 'ids',
					'hide_empty' => false,
				)
			);
			foreach ( $terms as $value ) {
				wp_delete_term( $value, $tax );
			}
		}
	}

	/**
	 * The nature of inserting and deleting vehicles means terms in a few of our
	 * taxonomies will be left behind and unused. This method deletes some of
	 * them. Runs once daily in a WordPress cron job.
	 *
	 * @return void
	 */
	public function delete_unused_terms() {
		$terms = get_terms(
			array(
				'taxonomy'   => array( 'model_year', 'make', 'model', 'style' ),
				'childless'  => true,
				'count'      => true,
				'hide_empty' => false,
			)
		);

		foreach ( $terms as $term ) {
			if ( 0 === $term->count ) {
				wp_delete_term( $term->term_id, $term->taxonomy );
			}
		}
	}

	/**
	 * Given taxonomy and post ID, find the term with a relationship to the post
	 * and return its slug.
	 *
	 * @param  string $taxonomy_name A taxonomy name
	 * @param  int    $post_id       A Post ID
	 * @return string A term slug
	 */
	protected static function get_term_slug( $taxonomy_name, $post_id ) {
		$terms = wp_get_object_terms(
			$post_id,
			$taxonomy_name,
			array(
				'orderby' => 'term_id',
				'order'   => 'ASC',
			)
		);
		if ( ! is_wp_error( $terms ) && isset( $terms[0] ) && isset( $terms[0]->name ) ) {
			return $terms[0]->slug;
		}
		return '';
	}

	/**
	 * Adds hooks to register and manage our taxonomies
	 *
	 * @return void
	 */
	public function add_hooks() {
		// Create custom taxonomies for vehicles.
		add_action( 'init', array( $this, 'register_taxonomies' ) );

		// Remove meta boxes from editors if taxonomies are not active.
		add_action( 'admin_head', array( $this, 'editor_remove_meta_boxes' ) );

		add_action( 'init', array( $this, 'register_meta' ) );

		add_action( 'invp_delete_all_data', array( $this, 'delete_term_data' ) );

		// Sort some taxonomy terms as numbers.
		add_filter( 'get_terms_orderby', array( $this, 'sort_terms_as_numbers' ), 10, 3 );

		// Do not include sold vehicles in listings unless an option is checked.
		add_action( 'pre_get_posts', array( $this, 'maybe_exclude_sold_vehicles' ) );

		// Run a cron job to delete empty terms.
		add_action( self::CRON_HOOK_DELETE_TERMS, array( $this, 'delete_unused_terms' ) );

		// Put terms into our taxonomies when the plugin is activated.
		register_activation_hook( INVP_PLUGIN_FILE_PATH, array( 'Inventory_Presser_Taxonomies', 'populate_default_terms' ) );
		// Schedule a weekly wp-cron job to delete empty terms in our taxonomies.
		register_activation_hook( INVP_PLUGIN_FILE_PATH, array( 'Inventory_Presser_Taxonomies', 'schedule_terms_cron_job' ) );
		// Remove the wp-cron job during deactivation.
		register_deactivation_hook( INVP_PLUGIN_FILE_PATH, array( 'Inventory_Presser_Taxonomies', 'remove_terms_cron_job' ) );

		// Change which taxonomies appear in the dashboard menu.
		add_filter( 'invp_taxonomy_data', array( $this, 'change_taxonomy_show_in_menu_attribute' ) );

		// Add a noindex meta tag to vehicle archive pages that have a lot of filters.
		add_filter( 'wp_robots', array( $this, 'maybe_add_noindex' ) );
	}

	/**
	 * A URL like this works:
	 * example.com/inventory/make/honda/make/gmc/make/toyota/make/ford. Try to
	 * prevent tons of bot traffic to deep filtered inventory pages with a
	 * noindex meta tag.
	 *
	 * @param  array $robots
	 * @return array
	 */
	public function maybe_add_noindex( $robots ) {
		// Is this a vehicle archive?
		if ( ! defined( 'INVP::POST_TYPE' ) || ! is_post_type_archive( INVP::POST_TYPE ) ) {
			// No.
			return $robots;
		}

		// How many taxonomy filters are in the query?
		$filter_count = 0;
		foreach ( self::query_vars_array() as $query_var ) {
			$vars = get_query_var( $query_var );
			if ( '' !== $vars ) {
				if ( is_array( $vars ) ) {
					$filter_count += count( $vars );
				} else {
					++$filter_count;
				}

				// More than one?
				if ( $filter_count > 1 ) {
					$robots['noindex'] = $robots['nofollow'] = true;
					return $robots;
				}
			}
		}
		return $robots;
	}

	/**
	 * Filter callback. Implements the "include sold vehicles" checkbox feature
	 * in vehicle archives and search results.
	 *
	 * @param  WP_Query $query The posts query object.
	 * @return void
	 */
	public function maybe_exclude_sold_vehicles( $query ) {
		if ( is_admin() || ! $query->is_main_query()
			|| ! ( is_search() || is_post_type_archive( INVP::POST_TYPE ) )
		) {
			return;
		}

		// If there is already a tax_query for taxonomy availability, abort.
		if ( $query->is_tax( 'availability' ) ) {
			return;
		}

		// If the checkbox to include sold vehicles is checked, abort.
		$plugin_settings = INVP::settings();
		if ( isset( $plugin_settings['include_sold_vehicles'] ) && $plugin_settings['include_sold_vehicles'] ) {
			return;
		}

		$query->set( 'tax_query', self::tax_query_exclude_sold() );
	}

	/**
	 * Outputs HTML that renders a meta box in the Classic Editor for all our taxonomies.
	 *
	 * @param  WP_Post $post Current post object.
	 * @param  array   $box  Categories meta box arguments.
	 * @return void
	 */
	public static function meta_box_html( $post, $box ) {
		$taxonomy = $box['args']['taxonomy'] ?? '';
		if ( '' === $taxonomy ) {
			return;
		}
		printf(
			'%1$s <a href="edit-tags.php?taxonomy=%2$s&post_type=%3$s">%4$s</a>',
			self::taxonomy_meta_box_html( $taxonomy, apply_filters( 'invp_prefix_meta_key', $taxonomy ), $post ),
			esc_attr( $taxonomy ),
			esc_attr( INVP::POST_TYPE ),
			esc_html__( 'Manage', 'inventory-presser' )
		);
	}

	/**
	 * Outputs HTML that renders a meta box for the type taxonomy
	 *
	 * @param  WP_Post $post Current post object.
	 * @return void
	 */
	public static function meta_box_html_type( $post ) {
		$taxonomy = 'type';
		$html     = sprintf(
			'%1$s <a href="edit-tags.php?taxonomy=%2$s&post_type=%3$s">%4$s</a>',
			self::taxonomy_meta_box_html( $taxonomy, apply_filters( 'invp_prefix_meta_key', $taxonomy ), $post ),
			esc_attr( $taxonomy ),
			esc_attr( INVP::POST_TYPE ),
			esc_html__( 'Manage', 'inventory-presser' )
		);
		// add an onchange attribute to the select.
		$html = str_replace( '<select', '<select onchange="invp_vehicle_type_changed( this.value );" ', $html );
		echo $html;
	}

	/**
	 * Populate our taxonomies with terms if they do not already exist
	 *
	 * @return void
	 */
	public static function populate_default_terms() {
		// create the taxonomies or else our wp_insert_term calls will fail.
		self::register_taxonomies();

		$taxonomy_data = self::taxonomy_data();
		$count         = count( $taxonomy_data );
		for ( $i = 0; $i < $count; $i++ ) {
			if ( ! isset( $taxonomy_data[ $i ]['term_data'] ) ) {
				continue;
			}

			foreach ( $taxonomy_data[ $i ]['term_data'] as $abbr => $desc ) {
				$taxonomy_name = str_replace( '-', '_', $taxonomy_data[ $i ]['args']['query_var'] ?? '' );
				if ( ! is_array( term_exists( $desc, $taxonomy_name ) ) ) {
					$term_exists = wp_insert_term(
						$desc,
						$taxonomy_name,
						array(
							'description' => $desc,
							'slug'        => $abbr,
						)
					);
				}
			}
		}
	}

	/**
	 * An array of all our taxonomy query variables.
	 *
	 * @return array
	 */
	public static function query_vars_array() {
		$arr = array();
		foreach ( self::taxonomy_data() as $taxonomy_array ) {
			if ( ! isset( $taxonomy_array['args'] ) || ! isset( $taxonomy_array['args']['query_var'] ) ) {
				continue;
			}
			$slug = str_replace( ' ', '_', strtolower( $taxonomy_array['args']['query_var'] ?? '' ) );
			array_push( $arr, $slug );
		}
		return $arr;
	}

	/**
	 * Registers term meta fields for our Location taxonomy to help store phone
	 * numbers and hours of operation. Also allows the storage of the individual
	 * pieces of the address that previously lived only in the term description.
	 *
	 * @return void
	 */
	public function register_meta() {
		/**
		 * Register some address fields so the pieces of the address can be
		 * accessed individually. For all of 2015-2019, we left the whole
		 * address in the term description.
		 */
		$address_keys = array(
			'address_street',
			'address_street_line_two',
			'address_city',
			'address_state',
			'address_zip',
			'address_lat',
			'address_lon',
		);
		foreach ( $address_keys as $meta_key ) {
			register_term_meta(
				'location',
				$meta_key,
				array(
					'sanitize_callback' => 'sanitize_text_field',
					'show_in_rest'      => true,
					'single'            => true,
					'type'              => 'string',
				)
			);
		}

		/**
		 * Register a dealer_id field on location terms to help when there are
		 * many location terms.
		 */
		register_term_meta(
			'location',
			'dealer_id',
			array(
				'sanitize_callback' => 'sanitize_text_field',
				'show_in_rest'      => true,
				'single'            => true,
				'type'              => 'integer',
			)
		);

		/**
		 * Phone Numbers
		 */
		$phone_key_suffixes = array(
			'uid',
			'description',
			'number',
		);

		// How many phone numbers do we plan to store per address?
		$loop_max = apply_filters( 'invp_max_phone_numbers_per_address', 10 );

		for ( $i = 1; $i <= $loop_max; $i++ ) {
			foreach ( $phone_key_suffixes as $suffix ) {
				$meta_key = 'phone_' . $i . '_' . $suffix;
				register_term_meta(
					'location',
					$meta_key,
					array(
						'sanitize_callback' => 'sanitize_text_field',
						'show_in_rest'      => true,
						'single'            => true,
						'type'              => 'string',
					)
				);
			}
		}

		/**
		 * Hours
		 */
		$hours_key_suffixes = array(
			'uid',
			'title',
			'sunday_appt',
			'sunday_open',
			'sunday_close',
			'saturday_appt',
			'saturday_open',
			'saturday_close',
			'friday_appt',
			'friday_open',
			'friday_close',
			'thursday_appt',
			'thursday_open',
			'thursday_close',
			'wednesday_appt',
			'wednesday_open',
			'wednesday_close',
			'tuesday_appt',
			'tuesday_open',
			'tuesday_close',
			'monday_appt',
			'monday_open',
			'monday_close',
		);

		// How many sets of hours do we plan to store per address?
		$loop_max = apply_filters( 'invp_max_hours_sets_per_address', 5 );

		for ( $i = 1; $i <= $loop_max; $i++ ) {
			foreach ( $hours_key_suffixes as $suffix ) {
				$meta_key = 'hours_' . $i . '_' . $suffix;
				register_term_meta(
					'location',
					$meta_key,
					array(
						'sanitize_callback' => 'sanitize_text_field',
						'show_in_rest'      => true,
						'single'            => true,
						'type'              => 'string',
					)
				);
			}
		}
	}

	/**
	 * Registers all our custom taxonomies
	 *
	 * @return void
	 */
	public static function register_taxonomies() {
		// Get the huge array of taxonomy data.
		$taxonomy_data = self::taxonomy_data();

		// Get the plugin settings where some taxonomies may be disabled.
		$settings = INVP::settings();

		$count = count( $taxonomy_data );
		for ( $i = 0; $i < $count; $i++ ) {
			// If the taxonomy is disabled in the settings, skip it.
			if ( ! isset( $settings['taxonomies'][ $taxonomy_data[ $i ]['args']['query_var'] ]['active'] )
				|| ! $settings['taxonomies'][ $taxonomy_data[ $i ]['args']['query_var'] ]['active'] ) {
				continue;
			}

			// Replace hyphens with underscores.
			$taxonomy_name = str_replace( '-', '_', $taxonomy_data[ $i ]['args']['query_var'] ?? '' );
			register_taxonomy( $taxonomy_name, INVP::POST_TYPE, $taxonomy_data[ $i ]['args'] );
		}
	}

	/**
	 * Removes a WordPress cron job that we schedule daily to clean up empty
	 * terms in a few of our taxonomies.
	 *
	 * @param  bool $network_wide True if this plugin is being Network Activated or Network Deactivated by the multisite admin.
	 * @return void
	 */
	public static function remove_terms_cron_job( $network_wide ) {
		if ( ! is_multisite() || ! $network_wide ) {
			wp_unschedule_event( wp_next_scheduled( self::CRON_HOOK_DELETE_TERMS ), self::CRON_HOOK_DELETE_TERMS );
			return;
		}

		$sites = get_sites(
			array(
				'network' => 1,
				'limit'   => apply_filters( 'invp_query_limit', 1000, __METHOD__ ),
			)
		);
		foreach ( $sites as $site ) {
			switch_to_blog( $site->blog_id );
			wp_unschedule_event( wp_next_scheduled( self::CRON_HOOK_DELETE_TERMS ), self::CRON_HOOK_DELETE_TERMS );
			restore_current_blog();
		}
	}

	/**
	 * Used by the save_post_{post_type} hook. Create a term relationship
	 * between a post and a term. Inserts the term first if it does not exist.
	 *
	 * @param  int    $post_id
	 * @param  string $taxonomy_name
	 * @param  string $element_name
	 * @return void
	 */
	public static function save_taxonomy_term( $post_id, $taxonomy_name, $element_name ) {
		if ( ! isset( $_POST[ $element_name ] ) ) {
			return;
		}

		$term_slug = sanitize_text_field( wp_unslash( $_POST[ $element_name ] ) );
		if ( '' === $term_slug ) {
			// the user is setting the vehicle type to empty string.
			wp_remove_object_terms( $post_id, self::get_term_slug( $taxonomy_name, $post_id ), $taxonomy_name );
			return;
		}
		$term = get_term_by( 'slug', $term_slug, $taxonomy_name );
		if ( empty( $term ) || is_wp_error( $term ) ) {
			// the term does not exist. create it.
			$term_arr = array(
				'slug'        => sanitize_title( $term_slug ),
				'description' => $term_slug,
				'name'        => $term_slug,
			);
			$id_arr   = wp_insert_term( $term_slug, $taxonomy_name, $term_arr );
			if ( ! is_wp_error( $id_arr ) ) {
				$term = WP_Term::get_instance( $id_arr['term_id'], $taxonomy_name );
			}
		}
		$set = wp_set_object_terms( $post_id, $term->term_id, $taxonomy_name, false );
	}

	/**
	 * Schedules a daily WordPress cron job to clean up empty terms in a few of
	 * our taxonomies and also correct counts.
	 *
	 * @param  bool $network_wide True if this plugin is being Network Activated or Network Deactivated by the multisite admin.
	 * @return void
	 */
	public static function schedule_terms_cron_job( $network_wide ) {
		if ( ! wp_next_scheduled( self::CRON_HOOK_DELETE_TERMS ) ) {
			if ( ! is_multisite() || ! $network_wide ) {
				wp_schedule_event( time(), 'daily', self::CRON_HOOK_DELETE_TERMS );
				return;
			}

			$sites = get_sites(
				array(
					'network' => 1,
					'limit'   => apply_filters( 'invp_query_limit', 1000, __METHOD__ ),
				)
			);
			foreach ( $sites as $site ) {
				switch_to_blog( $site->blog_id );
				wp_schedule_event( time(), 'daily', self::CRON_HOOK_DELETE_TERMS );
				restore_current_blog();
			}
		}
	}

	/**
	 * Returns an array of all our taxonomy slugs
	 *
	 * @return array An array of taxonomy slugs
	 */
	public static function slugs_array() {
		$arr = array();
		foreach ( self::query_vars_array() as $query_var ) {
			array_push( $arr, str_replace( '-', '_', $query_var ) );
		}
		return $arr;
	}


	/**
	 * Makes sure that taxonomy terms that are numbers are sorted as numbers.
	 *
	 * @param  string   $order_by   ORDERBY clause of the terms query.
	 * @param  array    $args       An array of term query arguments.
	 * @param  string[] $taxonomies An array of taxonomy names.
	 * @return string The changed ORDERBY clause of the terms query
	 */
	public function sort_terms_as_numbers( $order_by, $args, $taxonomies ) {
		if ( '' === $order_by ) {
			return $order_by;
		}

		if ( null === $taxonomies ) {
			return $order_by;
		}

		$taxonomies_to_sort = array(
			'cylinders',
			'model_year',
		);
		foreach ( $taxonomies_to_sort as $taxonomy_to_sort ) {
			if ( in_array( $taxonomy_to_sort, $taxonomies, true ) ) {
				$order_by .= '+0';
				break;
			}
		}
		return $order_by;
	}

	/**
	 * Creates an array that can be set as a query's tax_query that will
	 * exclude sold vehicles.
	 *
	 * @return array
	 */
	public static function tax_query_exclude_sold() {
		return array(
			array(
				'taxonomy' => 'availability',
				'field'    => 'slug',
				'terms'    => 'sold',
				'operator' => 'NOT IN',
			),
		);
	}

	/**
	 * An array of taxonomy data used during registration and default term
	 * population
	 *
	 * @return array
	 */
	public static function taxonomy_data() {
		$base_colors = INVP::base_colors();
		$taxonomies  = array(
			array(
				'args' => array(
					'hierarchical'   => true,
					'label'          => __( 'Model years', 'inventory-presser' ),
					'labels'         => array(
						'name'          => __( 'Model years', 'inventory-presser' ),
						'singular_name' => __( 'Model year', 'inventory-presser' ),
						'search_items'  => __( 'Search years', 'inventory-presser' ),
						'popular_items' => __( 'Popular years', 'inventory-presser' ),
						'all_items'     => __( 'All years', 'inventory-presser' ),
						'parent_item'   => __( 'Parent Model Year', 'inventory-presser' ),
						'edit_item'     => __( 'Edit Model Year', 'inventory-presser' ),
						'add_new_item'  => __( 'Add New Model Year', 'inventory-presser' ),
						'new_item_name' => __( 'New Model Year', 'inventory-presser' ),
						'back_to_items' => __( '&larr; Go to years', 'inventory-presser' ),
					),
					'meta_box_cb'    => null,
					'query_var'      => 'model-year',
					'singular_label' => __( 'Model year', 'inventory-presser' ),
					'show_in_menu'   => false,
					'show_in_rest'   => true,
					'show_ui'        => true,
				),
			),
			array(
				'args' => array(
					'hierarchical'   => true,
					'label'          => __( 'Makes', 'inventory-presser' ),
					'labels'         => array(
						'name'          => __( 'Makes', 'inventory-presser' ),
						'singular_name' => __( 'Make', 'inventory-presser' ),
						'search_items'  => __( 'Search makes', 'inventory-presser' ),
						'popular_items' => __( 'Popular makes', 'inventory-presser' ),
						'all_items'     => __( 'All makes', 'inventory-presser' ),
						'parent_item'   => __( 'Parent Make', 'inventory-presser' ),
						'edit_item'     => __( 'Edit Make', 'inventory-presser' ),
						'add_new_item'  => __( 'Add New Make', 'inventory-presser' ),
						'new_item_name' => __( 'New Make Name', 'inventory-presser' ),
						'back_to_items' => __( '&larr; Go to makes', 'inventory-presser' ),
					),
					'meta_box_cb'    => null,
					'query_var'      => 'make',
					'singular_label' => __( 'Make', 'inventory-presser' ),
					'show_in_menu'   => false,
					'show_in_rest'   => true,
					'show_ui'        => true,
				),
			),
			array(
				'args' => array(
					'hierarchical'   => true,
					'label'          => __( 'Models', 'inventory-presser' ),
					'labels'         => array(
						'name'          => __( 'Models', 'inventory-presser' ),
						'singular_name' => __( 'Model', 'inventory-presser' ),
						'search_items'  => __( 'Search models', 'inventory-presser' ),
						'popular_items' => __( 'Popular models', 'inventory-presser' ),
						'all_items'     => __( 'All models', 'inventory-presser' ),
						'parent_item'   => __( 'Parent Model', 'inventory-presser' ),
						'edit_item'     => __( 'Edit Model', 'inventory-presser' ),
						'add_new_item'  => __( 'Add New Model', 'inventory-presser' ),
						'new_item_name' => __( 'New Model Name', 'inventory-presser' ),
						'back_to_items' => __( '&larr; Go to models', 'inventory-presser' ),
					),
					'meta_box_cb'    => null,
					'query_var'      => 'model',
					'singular_label' => __( 'Model', 'inventory-presser' ),
					'show_in_menu'   => false,
					'show_in_rest'   => true,
					'show_ui'        => true,
				),
			),
			array(
				'args'      => array(
					'hierarchical'   => true,
					'label'          => __( 'Conditions', 'inventory-presser' ),
					'labels'         => array(
						'name'          => __( 'Conditions', 'inventory-presser' ),
						'singular_name' => __( 'Condition', 'inventory-presser' ),
						'search_items'  => __( 'Search new and used', 'inventory-presser' ),
						'popular_items' => __( 'Popular conditions', 'inventory-presser' ),
						'all_items'     => __( 'All new and used', 'inventory-presser' ),
						'parent_item'   => __( 'Parent Condition', 'inventory-presser' ),
						'edit_item'     => __( 'Edit Condition', 'inventory-presser' ),
						'add_new_item'  => __( 'Add New Condition', 'inventory-presser' ),
						'new_item_name' => __( 'New Condition Name', 'inventory-presser' ),
						'back_to_items' => __( '&larr; Go to conditions', 'inventory-presser' ),
					),
					'meta_box_cb'    => array( 'Inventory_Presser_Taxonomies', 'meta_box_html' ),
					'query_var'      => 'condition',
					'singular_label' => __( 'Condition', 'inventory-presser' ),
					'show_in_menu'   => false,
					'show_in_rest'   => true,
					'show_ui'        => true,
				),
				'term_data' => array(
					'New'  => 'New',
					'Used' => 'Used',
				),
			),
			array(
				'args'      => array(
					'hierarchical'   => true,
					'label'          => __( 'Types', 'inventory-presser' ),
					'labels'         => array(
						'name'          => __( 'Types', 'inventory-presser' ),
						'singular_name' => __( 'Type', 'inventory-presser' ),
						'search_items'  => __( 'Search types', 'inventory-presser' ),
						'popular_items' => __( 'Popular types', 'inventory-presser' ),
						'all_items'     => __( 'All types', 'inventory-presser' ),
						'parent_item'   => __( 'Parent Type', 'inventory-presser' ),
						'edit_item'     => __( 'Edit Type', 'inventory-presser' ),
						'add_new_item'  => __( 'Add New Type', 'inventory-presser' ),
						'new_item_name' => __( 'New Type Name', 'inventory-presser' ),
						'back_to_items' => __( '&larr; Go to types', 'inventory-presser' ),
					),
					'meta_box_cb'    => array( 'Inventory_Presser_Taxonomies', 'meta_box_html_type' ),
					'query_var'      => 'type',
					'rest_base'      => 'inventory_type',
					'singular_label' => __( 'Type', 'inventory-presser' ),
					'show_in_menu'   => false,
					'show_in_rest'   => true,
					'show_ui'        => true,
				),
				'term_data' => array(
					'ATV'  => 'All Terrain Vehicle',
					'BOAT' => 'Boat',
					'BUS'  => 'Bus',
					'CAR'  => 'Passenger Car',
					'MOT'  => 'Motorcycle',
					'MOW'  => 'Mower',
					'OTH'  => 'Other',
					'RV'   => 'Recreational Vehicle',
					'SUV'  => 'Sport Utility Vehicle',
					'TRLR' => 'Trailer',
					'TRU'  => 'Truck',
					'VAN'  => 'Van',
				),
			),
			array(
				'args'      => array(
					'hierarchical'   => true,
					'label'          => __( 'Availabilities', 'inventory-presser' ),
					'labels'         => array(
						'name'          => __( 'Availabilities', 'inventory-presser' ),
						'singular_name' => __( 'Availability', 'inventory-presser' ),
						'search_items'  => __( 'Search availabilities', 'inventory-presser' ),
						'popular_items' => __( 'Popular availabilities', 'inventory-presser' ),
						'all_items'     => __( 'All sold and for sale', 'inventory-presser' ),
						'parent_item'   => __( 'Parent Availability', 'inventory-presser' ),
						'edit_item'     => __( 'Edit Availability', 'inventory-presser' ),
						'add_new_item'  => __( 'Add New Availability', 'inventory-presser' ),
						'new_item_name' => __( 'New Availability Name', 'inventory-presser' ),
						'back_to_items' => __( '&larr; Go to availabilities', 'inventory-presser' ),
					),
					'meta_box_cb'    => array( 'Inventory_Presser_Taxonomies', 'meta_box_html' ),
					'query_var'      => 'availability',
					'singular_label' => __( 'Availability', 'inventory-presser' ),
					'show_in_menu'   => false,
					'show_in_rest'   => true,
					'show_ui'        => true,
				),
				'term_data' => array(
					'For sale'     => 'For sale',
					'Sale pending' => 'Sale pending',
					'Sold'         => 'Sold',
					'Wholesale'    => 'Wholesale',
				),
			),
			array(
				'args'      => array(
					'hierarchical'   => true,
					'label'          => __( 'Drive types', 'inventory-presser' ),
					'labels'         => array(
						'name'          => __( 'Drive types', 'inventory-presser' ),
						'singular_name' => __( 'Drive type', 'inventory-presser' ),
						'search_items'  => __( 'Search drive types', 'inventory-presser' ),
						'popular_items' => __( 'Popular drive types', 'inventory-presser' ),
						'all_items'     => __( 'All drive types', 'inventory-presser' ),
						'parent_item'   => __( 'Parent Drive Type', 'inventory-presser' ),
						'edit_item'     => __( 'Edit Drive Type', 'inventory-presser' ),
						'add_new_item'  => __( 'Add New Drive Type', 'inventory-presser' ),
						'new_item_name' => __( 'New Drive Type Name', 'inventory-presser' ),
						'back_to_items' => __( '&larr; Go to drive types', 'inventory-presser' ),
					),
					'meta_box_cb'    => array( 'Inventory_Presser_Taxonomies', 'meta_box_html' ),
					'query_var'      => 'drive-type',
					'singular_label' => __( 'Drive type', 'inventory-presser' ),
					'show_in_menu'   => false,
					'show_in_rest'   => true,
					'show_ui'        => true,
				),
				'term_data' => array(
					'4FD' => 'Front Wheel Drive w/4x4',
					'4RD' => 'Rear Wheel Drive w/4x4',
					'2WD' => 'Two Wheel Drive',
					'4WD' => 'Four Wheel Drive',
					'AWD' => 'All Wheel Drive',
					'FWD' => 'Front Wheel Drive',
					'RWD' => 'Rear Wheel Drive',
				),
			),

			/**
			* Propulsion type is essentially drive type for boats
			*/

			array(
				'args'      => array(
					'hierarchical'   => true,
					'label'          => __( 'Propulsion types', 'inventory-presser' ),
					'labels'         => array(
						'name'          => __( 'Propulsion types', 'inventory-presser' ),
						'singular_name' => __( 'Propulsion type', 'inventory-presser' ),
						'search_items'  => __( 'Search propulsion types', 'inventory-presser' ),
						'popular_items' => __( 'Popular propulsion types', 'inventory-presser' ),
						'all_items'     => __( 'All propulsion types', 'inventory-presser' ),
						'parent_item'   => __( 'Parent Propulsion Type', 'inventory-presser' ),
						'edit_item'     => __( 'Edit Propulsion Type', 'inventory-presser' ),
						'add_new_item'  => __( 'Add New Propulsion Type', 'inventory-presser' ),
						'new_item_name' => __( 'New Propulsion Type Name', 'inventory-presser' ),
						'back_to_items' => __( '&larr; Go to propulsion types', 'inventory-presser' ),
					),
					'meta_box_cb'    => array( 'Inventory_Presser_Taxonomies', 'meta_box_html' ),
					'query_var'      => 'propulsion-type',
					'singular_label' => __( 'Propulsion type', 'inventory-presser' ),
					'show_in_menu'   => false,
					'show_in_rest'   => true,
					'show_ui'        => true,
				),
				'term_data' => array(
					'IN'  => 'Inboard',
					'OUT' => 'Outboard',
					'IO'  => 'Inboard/Outboard',
					'JET' => 'Jet',
				),
			),

			array(
				'args'      => array(
					'hierarchical'   => true,
					'label'          => __( 'Fuels', 'inventory-presser' ),
					'labels'         => array(
						'name'          => __( 'Fuel types', 'inventory-presser' ),
						'singular_name' => __( 'Fuel type', 'inventory-presser' ),
						'search_items'  => __( 'Search fuel types', 'inventory-presser' ),
						'popular_items' => __( 'Popular fuel types', 'inventory-presser' ),
						'all_items'     => __( 'All fuel types', 'inventory-presser' ),
						'parent_item'   => __( 'Parent Fuel Type', 'inventory-presser' ),
						'edit_item'     => __( 'Edit Fuel Type', 'inventory-presser' ),
						'add_new_item'  => __( 'Add New Fuel Type', 'inventory-presser' ),
						'new_item_name' => __( 'New Fuel Type Name', 'inventory-presser' ),
						'back_to_items' => __( '&larr; Go to fuel types', 'inventory-presser' ),
					),
					'meta_box_cb'    => array( 'Inventory_Presser_Taxonomies', 'meta_box_html' ),
					'query_var'      => 'fuel',
					'singular_label' => __( 'Fuel', 'inventory-presser' ),
					'show_in_menu'   => false,
					'show_in_rest'   => true,
					'show_ui'        => true,
				),
				'term_data' => array(
					'B' => 'Electric and Gas Hybrid',
					'C' => 'Convertible',
					'D' => 'Diesel',
					'E' => 'Electric',
					'F' => 'Flexible',
					'G' => 'Gas',
					'N' => 'Compressed Natural Gas',
					'P' => 'Propane',
					'R' => 'Hydrogen Fuel Cell',
					'U' => 'Unknown',
					'Y' => 'Electric and Diesel Hybrid',
				),
			),
			array(
				'args'      => array(
					'hierarchical'   => true,
					'label'          => __( 'Transmissions', 'inventory-presser' ),
					'labels'         => array(
						'name'          => __( 'Transmissions', 'inventory-presser' ),
						'singular_name' => __( 'Transmission', 'inventory-presser' ),
						'search_items'  => __( 'Search transmissions', 'inventory-presser' ),
						'popular_items' => __( 'Popular transmissions', 'inventory-presser' ),
						'all_items'     => __( 'All transmissions', 'inventory-presser' ),
						'parent_item'   => __( 'Parent Transmission', 'inventory-presser' ),
						'edit_item'     => __( 'Edit Transmission', 'inventory-presser' ),
						'add_new_item'  => __( 'Add New Transmission', 'inventory-presser' ),
						'new_item_name' => __( 'New Transmission Name', 'inventory-presser' ),
						'back_to_items' => __( '&larr; Go to transmissions', 'inventory-presser' ),
					),
					'meta_box_cb'    => array( 'Inventory_Presser_Taxonomies', 'meta_box_html' ),
					'query_var'      => 'transmission',
					'singular_label' => __( 'Transmission', 'inventory-presser' ),
					'show_in_menu'   => false,
					'show_in_rest'   => true,
					'show_ui'        => true,
				),
				'term_data' => array(
					'A' => 'Automatic',
					'E' => 'ECVT',
					'M' => 'Manual',
					'U' => 'Unknown',
				),
			),
			array(
				'args'      => array(
					'hierarchical'   => true,
					'label'          => __( 'Cylinders', 'inventory-presser' ),
					'labels'         => array(
						'name'          => __( 'Cylinders', 'inventory-presser' ),
						'singular_name' => __( 'Cylinder count', 'inventory-presser' ),
						'search_items'  => __( 'Search cylinder counts', 'inventory-presser' ),
						'popular_items' => __( 'Popular cylinder counts', 'inventory-presser' ),
						'all_items'     => __( 'All cylinder counts', 'inventory-presser' ),
						'parent_item'   => __( 'Parent Cylinder Count', 'inventory-presser' ),
						'edit_item'     => __( 'Edit Cylinder Count', 'inventory-presser' ),
						'add_new_item'  => __( 'Add New Cylinder Count', 'inventory-presser' ),
						'new_item_name' => __( 'New Cylinder Count', 'inventory-presser' ),
						'back_to_items' => __( '&larr; Go to cylinder counts', 'inventory-presser' ),
					),
					'meta_box_cb'    => array( 'Inventory_Presser_Taxonomies', 'meta_box_html' ),
					'query_var'      => 'cylinders',
					'singular_label' => __( 'Cylinders', 'inventory-presser' ),
					'show_in_menu'   => false,
					'show_in_rest'   => true,
					'show_ui'        => true,
				),
				'term_data' => array(
					'1'  => '1',
					'2'  => '2',
					'3'  => '3',
					'4'  => '4',
					'5'  => '5',
					'6'  => '6',
					'8'  => '8',
					'10' => '10',
					'12' => '12',
				),
			),
			array(
				'args' => array(
					'hierarchical'   => true,
					'label'          => __( 'Body styles', 'inventory-presser' ),
					'labels'         => array(
						'name'          => __( 'Body styles', 'inventory-presser' ),
						'singular_name' => __( 'Body style', 'inventory-presser' ),
						'search_items'  => __( 'Search body styles', 'inventory-presser' ),
						'popular_items' => __( 'Popular body styles', 'inventory-presser' ),
						'all_items'     => __( 'All body styles', 'inventory-presser' ),
						'parent_item'   => __( 'Parent Body Style', 'inventory-presser' ),
						'edit_item'     => __( 'Edit Body Style', 'inventory-presser' ),
						'add_new_item'  => __( 'Add New Body Style', 'inventory-presser' ),
						'new_item_name' => __( 'New Body Style Name', 'inventory-presser' ),
						'back_to_items' => __( '&larr; Go to body styles', 'inventory-presser' ),
					),
					'meta_box_cb'    => null,
					'query_var'      => 'style',
					'singular_label' => __( 'Body style', 'inventory-presser' ),
					'show_in_menu'   => false,
					'show_in_rest'   => true,
					'show_ui'        => true,
				),
			),
			array(
				'args'      => array(
					'hierarchical'   => true,
					'label'          => __( 'Colors', 'inventory-presser' ),
					'labels'         => array(
						'name'          => __( 'Color', 'inventory-presser' ),
						'singular_name' => __( 'Color', 'inventory-presser' ),
						'search_items'  => __( 'Search colors', 'inventory-presser' ),
						'popular_items' => __( 'Popular colors', 'inventory-presser' ),
						'all_items'     => __( 'All colors', 'inventory-presser' ),
						'parent_item'   => __( 'Parent Color', 'inventory-presser' ),
						'edit_item'     => __( 'Edit Color', 'inventory-presser' ),
						'add_new_item'  => __( 'Add New Color', 'inventory-presser' ),
						'new_item_name' => __( 'New Color Name', 'inventory-presser' ),
						'back_to_items' => __( '&larr; Go to colors', 'inventory-presser' ),
					),
					'meta_box_cb'    => array( 'Inventory_Presser_Taxonomies', 'meta_box_html' ),
					'query_var'      => 'colors',
					'singular_label' => __( 'Color', 'inventory-presser' ),
					'show_in_menu'   => false,
					'show_in_rest'   => true,
					'show_ui'        => true,
				),
				'term_data' => array_combine( $base_colors, $base_colors ),
			),
			array(
				'args' => array(
					'hierarchical'   => false,
					'label'          => __( 'Locations', 'inventory-presser' ),
					'labels'         => array(
						'name'          => __( 'Location', 'inventory-presser' ),
						'singular_name' => __( 'Location', 'inventory-presser' ),
						'search_items'  => __( 'Search locations', 'inventory-presser' ),
						'popular_items' => __( 'Popular locations', 'inventory-presser' ),
						'all_items'     => __( 'All locations', 'inventory-presser' ),
						'parent_item'   => __( 'Parent Location', 'inventory-presser' ),
						'edit_item'     => __( 'Edit Location', 'inventory-presser' ),
						'view_item'     => __( 'View Location', 'inventory-presser' ),
						'update_item'   => __( 'Update Location', 'inventory-presser' ),
						'add_new_item'  => __( 'Add New Location', 'inventory-presser' ),
						'new_item_name' => __( 'New Location Name', 'inventory-presser' ),
						'not_found'     => __( 'No locations found', 'inventory-presser' ),
						'no_terms'      => __( 'No locations', 'inventory-presser' ),
						'menu_name'     => __( 'Locations', 'inventory-presser' ),
						'back_to_items' => __( '&larr; Go to locations', 'inventory-presser' ),
					),
					'meta_box_cb'    => array( 'Inventory_Presser_Taxonomies', 'meta_box_html' ),
					'query_var'      => 'location',
					'singular_label' => __( 'Location', 'inventory-presser' ),
					'show_in_menu'   => true,
					'show_in_rest'   => true,
					'show_ui'        => true,
				),
			),

			// Boat Condition.
			array(
				'args'      => array(
					'hierarchical'   => true,
					'label'          => __( 'Condition', 'inventory-presser' ),
					'labels'         => array(
						'name'          => __( 'Condition', 'inventory-presser' ),
						'singular_name' => __( 'Condition', 'inventory-presser' ),
						'search_items'  => __( 'Search conditions', 'inventory-presser' ),
						'popular_items' => __( 'Popular conditions', 'inventory-presser' ),
						'all_items'     => __( 'All conditions', 'inventory-presser' ),
						'parent_item'   => __( 'Parent Condition', 'inventory-presser' ),
						'edit_item'     => __( 'Edit Condition', 'inventory-presser' ),
						'add_new_item'  => __( 'Add New Condition', 'inventory-presser' ),
						'new_item_name' => __( 'New Condition Name', 'inventory-presser' ),
						'back_to_items' => __( '&larr; Go to condition', 'inventory-presser' ),
					),
					'meta_box_cb'    => array( 'Inventory_Presser_Taxonomies', 'meta_box_html' ),
					'query_var'      => 'condition_boat',
					'singular_label' => __( 'Condition', 'inventory-presser' ),
					'show_in_menu'   => false,
					'show_in_rest'   => true,
					'show_ui'        => true,
				),
				'term_data' => array(
					'New'       => 'New',
					'Excellent' => 'Excellent',
					'Good'      => 'Good',
					'Fair'      => 'Fair',
					'Poor'      => 'Poor',
				),
			),

			// Number of Engines (boats).
			array(
				'args'      => array(
					'hierarchical'   => true,
					'label'          => __( 'Number of Engines', 'inventory-presser' ),
					'labels'         => array(
						'name'          => __( 'Number of Engines', 'inventory-presser' ),
						'singular_name' => __( 'Number', 'inventory-presser' ),
						'search_items'  => __( 'Search numbers', 'inventory-presser' ),
						'popular_items' => __( 'Popular numbers', 'inventory-presser' ),
						'all_items'     => __( 'All numbers', 'inventory-presser' ),
						'parent_item'   => __( 'Parent numbers', 'inventory-presser' ),
						'edit_item'     => __( 'Edit Number', 'inventory-presser' ),
						'add_new_item'  => __( 'Add New Number of Engines', 'inventory-presser' ),
						'new_item_name' => __( 'New Number Name', 'inventory-presser' ),
						'back_to_items' => __( '&larr; Go to number', 'inventory-presser' ),
					),
					'meta_box_cb'    => array( 'Inventory_Presser_Taxonomies', 'meta_box_html' ),
					'query_var'      => 'engine_count',
					'singular_label' => __( 'Number of Engines', 'inventory-presser' ),
					'show_in_menu'   => false,
					'show_in_rest'   => true,
					'show_ui'        => true,
				),
				'term_data' => array(
					'1' => '1',
					'2' => '2',
				),
			),

			// Engine Make (boats).
			array(
				'args' => array(
					'hierarchical'   => true,
					'label'          => __( 'Engine Makes', 'inventory-presser' ),
					'labels'         => array(
						'name'          => __( 'Engine Makes', 'inventory-presser' ),
						'singular_name' => __( 'Engine Make', 'inventory-presser' ),
						'search_items'  => __( 'Search engine makes', 'inventory-presser' ),
						'popular_items' => __( 'Popular engine makes', 'inventory-presser' ),
						'all_items'     => __( 'All engine makes', 'inventory-presser' ),
						'parent_item'   => __( 'Parent Engine Make', 'inventory-presser' ),
						'edit_item'     => __( 'Edit Engine Make', 'inventory-presser' ),
						'add_new_item'  => __( 'Add New Engine Make', 'inventory-presser' ),
						'new_item_name' => __( 'New Engine Make Name', 'inventory-presser' ),
						'back_to_items' => __( '&larr; Go to engine make', 'inventory-presser' ),
					),
					'meta_box_cb'    => array( 'Inventory_Presser_Taxonomies', 'meta_box_html' ),
					'query_var'      => 'engine_make',
					'singular_label' => __( 'Engine Make', 'inventory-presser' ),
					'show_in_menu'   => false,
					'show_in_rest'   => true,
					'show_ui'        => true,
				),
			),

			// Engine Model (boats).
			array(
				'args' => array(
					'hierarchical'   => true,
					'label'          => __( 'Engine Models', 'inventory-presser' ),
					'labels'         => array(
						'name'          => __( 'Engine Models', 'inventory-presser' ),
						'singular_name' => __( 'Engine Model', 'inventory-presser' ),
						'search_items'  => __( 'Search engine models', 'inventory-presser' ),
						'popular_items' => __( 'Popular engine models', 'inventory-presser' ),
						'all_items'     => __( 'All engine models', 'inventory-presser' ),
						'parent_item'   => __( 'Parent Engine Model', 'inventory-presser' ),
						'edit_item'     => __( 'Edit Engine Model', 'inventory-presser' ),
						'add_new_item'  => __( 'Add New Engine Model', 'inventory-presser' ),
						'new_item_name' => __( 'New Engine Model Name', 'inventory-presser' ),
						'back_to_items' => __( '&larr; Go to engine model', 'inventory-presser' ),
					),
					'meta_box_cb'    => array( 'Inventory_Presser_Taxonomies', 'meta_box_html' ),
					'query_var'      => 'engine_model',
					'singular_label' => __( 'Engine Model', 'inventory-presser' ),
					'show_in_menu'   => false,
					'show_in_rest'   => true,
					'show_ui'        => true,
				),
			),

			// Horsepower (boats).
			array(
				'args' => array(
					'hierarchical'   => true,
					'label'          => __( 'Horsepower', 'inventory-presser' ),
					'labels'         => array(
						'name'          => __( 'Horsepower', 'inventory-presser' ),
						'singular_name' => __( 'Horsepower', 'inventory-presser' ),
						'search_items'  => __( 'Search horsepower', 'inventory-presser' ),
						'popular_items' => __( 'Popular horsepower', 'inventory-presser' ),
						'all_items'     => __( 'All horsepower', 'inventory-presser' ),
						'parent_item'   => __( 'Parent Horsepower', 'inventory-presser' ),
						'edit_item'     => __( 'Edit Horsepower', 'inventory-presser' ),
						'add_new_item'  => __( 'Add New Horsepower', 'inventory-presser' ),
						'new_item_name' => __( 'New Horsepower Name', 'inventory-presser' ),
						'back_to_items' => __( '&larr; Go to horsepower', 'inventory-presser' ),
					),
					'meta_box_cb'    => array( 'Inventory_Presser_Taxonomies', 'meta_box_html' ),
					'query_var'      => 'horsepower',
					'singular_label' => __( 'Horsepower', 'inventory-presser' ),
					'show_in_menu'   => false,
					'show_in_rest'   => true,
					'show_ui'        => true,
				),
			),

			// Hull Material (boats).
			array(
				'args'      => array(
					'hierarchical'   => true,
					'label'          => __( 'Hull Materials', 'inventory-presser' ),
					'labels'         => array(
						'name'          => __( 'Hull Materials', 'inventory-presser' ),
						'singular_name' => __( 'Hull Material', 'inventory-presser' ),
						'search_items'  => __( 'Search hull materials', 'inventory-presser' ),
						'popular_items' => __( 'Popular hull materials', 'inventory-presser' ),
						'all_items'     => __( 'All hull materials', 'inventory-presser' ),
						'parent_item'   => __( 'Parent Hull Material', 'inventory-presser' ),
						'edit_item'     => __( 'Edit Hull Material', 'inventory-presser' ),
						'add_new_item'  => __( 'Add New Hull Material', 'inventory-presser' ),
						'new_item_name' => __( 'New Hull Material Name', 'inventory-presser' ),
						'back_to_items' => __( '&larr; Go to hull materials', 'inventory-presser' ),
					),
					'meta_box_cb'    => array( 'Inventory_Presser_Taxonomies', 'meta_box_html' ),
					'query_var'      => 'hull_materials',
					'singular_label' => __( 'Hull Material', 'inventory-presser' ),
					'show_in_menu'   => false,
					'show_in_rest'   => true,
					'show_ui'        => true,
				),
				'term_data' => array(
					__( 'Aluminum', 'inventory-presser' )  => __( 'Aluminum', 'inventory-presser' ),
					__( 'Carbon Fiber', 'inventory-presser' ) => __( 'Carbon Fiber', 'inventory-presser' ),
					__( 'Composite', 'inventory-presser' ) => __( 'Composite', 'inventory-presser' ),
					__( 'Ferro-Cement', 'inventory-presser' ) => __( 'Ferro-Cement', 'inventory-presser' ),
					__( 'Fiberglass', 'inventory-presser' ) => __( 'Fiberglass', 'inventory-presser' ),
					__( 'Hypalon', 'inventory-presser' )   => __( 'Hypalon', 'inventory-presser' ),
					__( 'Other', 'inventory-presser' )     => __( 'Other', 'inventory-presser' ),
					__( 'PVC', 'inventory-presser' )       => __( 'PVC', 'inventory-presser' ),
					__( 'Steel', 'inventory-presser' )     => __( 'Steel', 'inventory-presser' ),
					__( 'Wood', 'inventory-presser' )      => __( 'Wood', 'inventory-presser' ),
				),
			),
		);
		return apply_filters( 'invp_taxonomy_data', $taxonomies );
	}

	/**
	 * Creates HTML output for a meta box that turns a taxonomy into
	 * a select drop-down list instead of the typical checkboxes. Including
	 * a blank option is the only way a user can remove the value.
	 *
	 * @param  string  $taxonomy_name
	 * @param  string  $element_name
	 * @param  WP_Post $post          A post
	 * @return string HTML that renders a editor meta box for a taxonomy
	 */
	protected static function taxonomy_meta_box_html( $taxonomy_name, $element_name, $post ) {
		$html = sprintf(
			'<select name="%s" id="%s"><option></option>',
			$element_name,
			$element_name
		);

		// get all the term names and slugs for $taxonomy_name.
		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy_name,
				'hide_empty' => false,
			)
		);

		if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
			// get the saved term for this taxonomy.
			$saved_term_slug = self::get_term_slug( $taxonomy_name, $post->ID );

			foreach ( $terms as $term ) {
				$html .= sprintf(
					'<option value="%s"%s>%s</option>',
					$term->slug,
					selected( strtolower( $term->slug ), strtolower( $saved_term_slug ), false ),
					$term->name
				);
			}
		}
		return $html . '</select>';
	}

	/**
	 * Uses the type of the vehicle loaded into the editor to show or hide other
	 * taxonomies.
	 *
	 * @return void
	 */
	public function editor_remove_meta_boxes() {
		global $wp_meta_boxes;
		if ( ! isset( $wp_meta_boxes[ INVP::POST_TYPE ]['side']['core'] ) ) {
			return;
		}
		$settings = INVP::settings();
		if ( empty( $settings['taxonomies'] ) ) {
			return;
		}
		// What is the type of the vehicle we are editing?
		$type = $this->get_term_slug( 'type', get_the_ID() );
		if ( '' === $type ) {
			/**
			 * Type not set, or user just pressed "Add New Vehicle". Lie so the
			 * editor looks ready for a car.
			 */
			$type = 'car';
		}
		foreach ( self::query_vars_array() as $query_var ) {
			$is_active = $settings['taxonomies'][ $query_var ][ $type ] ?? false;
			if ( ! $is_active ) {
				$id = $query_var . 'div';
				if ( 'location' === $query_var ) {
					$id = 'tagsdiv-location';
				}
				unset( $wp_meta_boxes[ INVP::POST_TYPE ]['side']['core'][ $id ] );
			}
		}
	}
}
