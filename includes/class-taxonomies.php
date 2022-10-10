<?php
defined( 'ABSPATH' ) || exit;

/**
 * Inventory_Presser_Taxonomies
 *
 * Registers and manipulates our custom taxonomies and their terms.
 *
 * @since      1.3.1
 * @package    Inventory_Presser
 * @subpackage Inventory_Presser/includes
 * @author     Corey Salzano <corey@friday.systems>, John Norton <norton@fridaynet.com>
 */
class Inventory_Presser_Taxonomies {

	const CRON_HOOK_DELETE_TERMS = 'inventory_presser_delete_unused_terms';

	/**
	 * delete_term_data
	 *
	 * Removes all terms in all our taxonomies. Used when uninstalling the
	 * plugin.
	 *
	 * @return void
	 */
	function delete_term_data() {
		// remove the terms in taxonomies
		$taxonomy_data = self::taxonomy_data();
		for ( $i = 0; $i < sizeof( $taxonomy_data ); $i++ ) {
			$tax   = $taxonomy_data[ $i ]['args']['label'];
			$terms = get_terms(
				$tax,
				array(
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
	 * delete_unused_terms
	 *
	 * The nature of inserting and deleting vehicles means terms in a few of our
	 * taxonomies will be left behind and unused. This method deletes some of
	 * them. Runs once daily in a WordPress cron job.
	 *
	 * @return void
	 */
	function delete_unused_terms() {
		$terms = get_terms(
			array(
				'taxonomy'   => array( 'model_year', 'make', 'model', 'style' ),
				'childless'  => true,
				'count'      => true,
				'hide_empty' => false,
			)
		);

		foreach ( $terms as $term ) {
			if ( 0 == $term->count ) {
				wp_delete_term( $term->term_id, $term->taxonomy );
			}
		}
	}

	/**
	 * get_term_slug
	 *
	 * Given taxonomy and post ID, find the term with a relationship to the post
	 * and return its slug.
	 *
	 * @param  string $taxonomy_name A taxonomy name
	 * @param  int    $post_id       A Post ID
	 * @return string A term slug
	 */
	static function get_term_slug( $taxonomy_name, $post_id ) {
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
	 * hooks
	 *
	 * Adds hooks to register and manage our taxonomies
	 *
	 * @return void
	 */
	function hooks() {
		// create custom taxonomies for vehicles
		add_action( 'init', array( $this, 'register_taxonomies' ) );
		add_action( 'init', array( $this, 'register_meta' ) );

		add_action( 'invp_delete_all_data', array( $this, 'delete_term_data' ) );

		// Sort some taxonomy terms as numbers
		add_filter( 'get_terms_orderby', array( $this, 'sort_terms_as_numbers' ), 10, 3 );

		// Do not include sold vehicles in listings unless an option is checked
		add_action( 'pre_get_posts', array( $this, 'maybe_exclude_sold_vehicles' ) );

		// Run a weekly cron job to delete empty terms.
		add_action( self::CRON_HOOK_DELETE_TERMS, array( $this, 'delete_unused_terms' ) );

		// Put terms into our taxonomies when the plugin is activated
		register_activation_hook( INVP_PLUGIN_FILE_PATH, array( 'Inventory_Presser_Taxonomies', 'populate_default_terms' ) );
		// Schedule a weekly wp-cron job to delete empty terms in our taxonomies
		register_activation_hook( INVP_PLUGIN_FILE_PATH, array( 'Inventory_Presser_Taxonomies', 'schedule_terms_cron_job' ) );
		// Remove the wp-cron job during deactivation
		register_deactivation_hook( INVP_PLUGIN_FILE_PATH, array( 'Inventory_Presser_Taxonomies', 'remove_terms_cron_job' ) );
	}

	/**
	 * maybe_exclude_sold_vehicles
	 *
	 * Filter callback. Implements the "include sold vehicles" checkbox feature
	 * in vehicle archives and search results.
	 *
	 * @param  WP_Query $query
	 * @return void
	 */
	function maybe_exclude_sold_vehicles( $query ) {
		if ( is_admin() || ! $query->is_main_query()
			|| ! ( is_search() || is_post_type_archive( INVP::POST_TYPE ) )
		) {
			return;
		}

		// if the checkbox to include sold vehicles is checked, abort
		$plugin_settings = INVP::settings();
		if ( isset( $plugin_settings['include_sold_vehicles'] ) && $plugin_settings['include_sold_vehicles'] ) {
			return;
		}

		$taxonomy = 'availability';

		// if there is already a tax_query for taxonomy availability, abort
		if ( $query->is_tax( $taxonomy ) ) {
			return;
		}

		// do this
		$tax_query = array(
			array(
				'taxonomy' => $taxonomy,
				'field'    => 'slug',
				'terms'    => 'sold',
				'operator' => 'NOT IN',
			),
		);

		$query->set( 'tax_query', $tax_query );
	}

	/**
	 * meta_box_html_colors
	 *
	 * Outputs HTML that renders a meta box for the colors taxonomy
	 *
	 * @param  mixed $post
	 * @return void
	 */
	static function meta_box_html_colors( $post ) {
		echo self::taxonomy_meta_box_html( 'colors', apply_filters( 'invp_prefix_meta_key', 'color_base' ), $post );
	}

	/**
	 * meta_box_html_condition
	 *
	 * Outputs HTML that renders a meta box for the condition taxonomy
	 *
	 * @param  mixed $post
	 * @return void
	 */
	static function meta_box_html_condition( $post ) {
		echo self::taxonomy_meta_box_html( 'condition', apply_filters( 'invp_prefix_meta_key', 'condition' ), $post );
	}

	/**
	 * meta_box_html_cylinders
	 *
	 * Outputs HTML that renders a meta box for the cylinders taxonomy
	 *
	 * @param  mixed $post
	 * @return void
	 */
	static function meta_box_html_cylinders( $post ) {
		echo self::taxonomy_meta_box_html( 'cylinders', apply_filters( 'invp_prefix_meta_key', 'cylinders' ), $post );
	}

	/**
	 * meta_box_html_availability
	 *
	 * Outputs HTML that renders a meta box for the availability taxonomy
	 *
	 * @param  mixed $post
	 * @return void
	 */
	static function meta_box_html_availability( $post ) {
		echo self::taxonomy_meta_box_html( 'availability', apply_filters( 'invp_prefix_meta_key', 'availability' ), $post );
	}

	/**
	 * meta_box_html_drive_type
	 *
	 * Outputs HTML that renders a meta box for the drive type taxonomy
	 *
	 * @param  mixed $post
	 * @return void
	 */
	static function meta_box_html_drive_type( $post ) {
		echo self::taxonomy_meta_box_html( 'drive_type', apply_filters( 'invp_prefix_meta_key', 'drive_type' ), $post );
	}

	/**
	 * meta_box_html_fuel
	 *
	 * Outputs HTML that renders a meta box for the fuel taxonomy
	 *
	 * @param  mixed $post
	 * @return void
	 */
	static function meta_box_html_fuel( $post ) {
		echo self::taxonomy_meta_box_html( 'fuel', apply_filters( 'invp_prefix_meta_key', 'fuel' ), $post );
	}

	/**
	 * meta_box_html_propulsion_type
	 *
	 * Outputs HTML that renders a meta box for the propulsion type taxonomy
	 *
	 * @param  mixed $post
	 * @return void
	 */
	static function meta_box_html_propulsion_type( $post ) {
		echo self::taxonomy_meta_box_html( 'propulsion_type', apply_filters( 'invp_prefix_meta_key', 'propulsion_type' ), $post );
	}

	/**
	 * meta_box_html_transmission
	 *
	 * Outputs HTML that renders a meta box for the transmission taxonomy
	 *
	 * @param  mixed $post
	 * @return void
	 */
	static function meta_box_html_transmission( $post ) {
		echo self::taxonomy_meta_box_html( 'transmission', apply_filters( 'invp_prefix_meta_key', 'transmission' ), $post );
	}

	/**
	 * meta_box_html_type
	 *
	 * Outputs HTML that renders a meta box for the type taxonomy
	 *
	 * @param  mixed $post
	 * @return void
	 */
	static function meta_box_html_type( $post ) {
		$html = self::taxonomy_meta_box_html( 'type', apply_filters( 'invp_prefix_meta_key', 'type' ), $post );
		// add an onchange attribute to the select
		$html = str_replace( '<select', '<select onchange="invp_vehicle_type_changed( this.value );" ', $html );
		echo $html;
	}

	/**
	 * meta_box_html_locations
	 *
	 * Outputs HTML that renders a meta box for the location taxonomy
	 *
	 * @param  mixed $post
	 * @return void
	 */
	static function meta_box_html_locations( $post ) {
		printf(
			'%s<p><a href="edit-tags.php?taxonomy=location&post_type=%s">Manage locations</a></p>',
			self::taxonomy_meta_box_html( 'location', apply_filters( 'invp_prefix_meta_key', 'location' ), $post ),
			INVP::POST_TYPE
		);
	}

	/**
	 * populate_default_terms
	 *
	 * Populate our taxonomies with terms if they do not already exist
	 *
	 * @return void
	 */
	static function populate_default_terms() {
		// create the taxonomies or else our wp_insert_term calls will fail
		self::register_taxonomies();

		$taxonomy_data = self::taxonomy_data();
		for ( $i = 0; $i < sizeof( $taxonomy_data ); $i++ ) {
			if ( ! isset( $taxonomy_data[ $i ]['term_data'] ) ) {
				continue;
			}

			foreach ( $taxonomy_data[ $i ]['term_data'] as $abbr => $desc ) {
				$taxonomy_name = str_replace( '-', '_', $taxonomy_data[ $i ]['args']['query_var'] );
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
	 * query_vars_array
	 *
	 * An array of all our taxonomy query variables.
	 *
	 * @return void
	 */
	static function query_vars_array() {
		$arr = array();
		foreach ( self::taxonomy_data() as $taxonomy_array ) {
			if ( ! isset( $taxonomy_array['args'] ) || ! isset( $taxonomy_array['args']['query_var'] ) ) {
				continue;
			}
			$slug = str_replace( ' ', '_', strtolower( $taxonomy_array['args']['query_var'] ) );
			array_push( $arr, $slug );
		}
		return $arr;
	}

	/**
	 * register_meta
	 *
	 * Registers term meta fields for our Location taxonomy to help store phone
	 * numbers and hours of operation. Also allows the storage of the individual
	 * pieces of the address that previously lived only in the term description.
	 *
	 * @return void
	 */
	function register_meta() {
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
	 * register_taxonomies
	 *
	 * Registers all our custom taxonomies
	 *
	 * @return void
	 */
	static function register_taxonomies() {
		// loop over this data, register the taxonomies, and populate the terms if needed
		$taxonomy_data = self::taxonomy_data();
		for ( $i = 0; $i < sizeof( $taxonomy_data ); $i++ ) {
			// create the taxonomy, replace hyphens with underscores
			$taxonomy_name = str_replace( '-', '_', $taxonomy_data[ $i ]['args']['query_var'] );
			register_taxonomy( $taxonomy_name, INVP::POST_TYPE, $taxonomy_data[ $i ]['args'] );
		}
	}

	/**
	 * remove_terms_cron_job
	 *
	 * Removes a WordPress cron job that we schedule daily to clean up empty
	 * terms in a few of our taxonomies.
	 *
	 * @param  bool $network_wide True if this plugin is being Network Activated or Network Deactivated by the multisite admin
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
				'limit'   => 1000,
			)
		);
		foreach ( $sites as $site ) {
			switch_to_blog( $site->blog_id );
			wp_unschedule_event( wp_next_scheduled( self::CRON_HOOK_DELETE_TERMS ), self::CRON_HOOK_DELETE_TERMS );
			restore_current_blog();
		}
	}

	/**
	 * schedule_terms_cron_job
	 *
	 * Schedules a daily WordPress cron job to clean up empty terms in a few of
	 * our taxonomies and also correct counts.
	 *
	 * @param  bool $network_wide True if this plugin is being Network Activated or Network Deactivated by the multisite admin
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
					'limit'   => 1000,
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
	 * sort_terms_as_numbers
	 *
	 * Makes sure that taxonomy terms that are numbers are sorted as numbers.
	 *
	 * @param  string   $order_by   ORDERBY clause of the terms query.
	 * @param  array    $args       An array of term query arguments.
	 * @param  string[] $taxonomies An array of taxonomy names.
	 * @return string The changed ORDERBY clause of the terms query
	 */
	function sort_terms_as_numbers( $order_by, $args, $taxonomies ) {
		if ( '' == $order_by ) {
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
			if ( in_array( $taxonomy_to_sort, $taxonomies ) ) {
				$order_by .= '+0';
				break;
			}
		}
		return $order_by;
	}

	/**
	 * taxonomy_data
	 *
	 * An array of taxonomy data used during registration and default term
	 * population
	 *
	 * @return array
	 */
	public static function taxonomy_data() {
		return apply_filters(
			'invp_taxonomy_data',
			array(
				array(
					'args' => array(
						'hierarchical'   => true,
						'label'          => 'Model years',
						'labels'         => array(
							'name'          => 'Model years',
							'singular_name' => 'Model year',
							'search_items'  => 'Search years',
							'popular_items' => 'Popular years',
							'all_items'     => 'All years',
						),
						'meta_box_cb'    => null,
						'query_var'      => 'model-year',
						'singular_label' => 'Model year',
						'show_in_menu'   => false,
						'show_in_rest'   => true,
						'show_ui'        => true,
					),
				),
				array(
					'args' => array(
						'hierarchical'   => true,
						'label'          => 'Makes',
						'labels'         => array(
							'name'          => 'Makes',
							'singular_name' => 'Make',
							'search_items'  => 'Search makes',
							'popular_items' => 'Popular makes',
							'all_items'     => 'All makes',
						),
						'meta_box_cb'    => null,
						'query_var'      => 'make',
						'singular_label' => 'Make',
						'show_in_menu'   => false,
						'show_in_rest'   => true,
						'show_ui'        => true,
					),
				),
				array(
					'args' => array(
						'hierarchical'   => true,
						'label'          => 'Models',
						'labels'         => array(
							'name'          => 'Models',
							'singular_name' => 'Model',
							'search_items'  => 'Search models',
							'popular_items' => 'Popular models',
							'all_items'     => 'All models',
						),
						'meta_box_cb'    => null,
						'query_var'      => 'model',
						'singular_label' => 'Model',
						'show_in_menu'   => false,
						'show_in_rest'   => true,
						'show_ui'        => true,
					),
				),
				array(
					'args'      => array(
						'hierarchical'   => true,
						'label'          => 'Conditions',
						'labels'         => array(
							'name'          => 'Conditions',
							'singular_name' => 'Condition',
							'search_items'  => 'Search new and used',
							'popular_items' => 'Popular conditions',
							'all_items'     => 'All new and used',
						),
						'meta_box_cb'    => array( 'Inventory_Presser_Taxonomies', 'meta_box_html_condition' ),
						'query_var'      => 'condition',
						'singular_label' => 'Condition',
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
						'label'          => 'Types',
						'labels'         => array(
							'name'          => 'Types',
							'singular_name' => 'Type',
							'search_items'  => 'Search types',
							'popular_items' => 'Popular types',
							'all_items'     => 'All types',
						),
						'meta_box_cb'    => array( 'Inventory_Presser_Taxonomies', 'meta_box_html_type' ),
						'query_var'      => 'type',
						'rest_base'      => 'inventory_type',
						'singular_label' => 'Type',
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
						'label'          => 'Availabilities',
						'labels'         => array(
							'name'          => 'Availabilities',
							'singular_name' => 'Availability',
							'search_items'  => 'Search availabilities',
							'popular_items' => 'Popular availabilities',
							'all_items'     => 'All sold and for sale',
						),
						'meta_box_cb'    => array( 'Inventory_Presser_Taxonomies', 'meta_box_html_availability' ),
						'query_var'      => 'availability',
						'singular_label' => 'Availability',
						'show_in_menu'   => false,
						'show_in_rest'   => true,
						'show_ui'        => true,
					),
					'term_data' => array(
						'For sale'  => 'For sale',
						'Sold'      => 'Sold',
						'Wholesale' => 'Wholesale',
					),
				),
				array(
					'args'      => array(
						'hierarchical'   => true,
						'label'          => 'Drive types',
						'labels'         => array(
							'name'          => 'Drive types',
							'singular_name' => 'Drive type',
							'search_items'  => 'Search drive types',
							'popular_items' => 'Popular drive types',
							'all_items'     => 'All drive types',
						),
						'meta_box_cb'    => array( 'Inventory_Presser_Taxonomies', 'meta_box_html_drive_type' ),
						'query_var'      => 'drive-type',
						'singular_label' => 'Drive type',
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
						'label'          => 'Propulsion types',
						'labels'         => array(
							'name'          => 'Propulsion types',
							'singular_name' => 'Propulsion type',
							'search_items'  => 'Search propulsion types',
							'popular_items' => 'Popular propulsion types',
							'all_items'     => 'All propulsion types',
						),
						'meta_box_cb'    => array( 'Inventory_Presser_Taxonomies', 'meta_box_html_propulsion_type' ),
						'query_var'      => 'propulsion-type',
						'singular_label' => 'Propulsion type',
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
						'label'          => 'Fuels',
						'labels'         => array(
							'name'          => 'Fuel types',
							'singular_name' => 'Fuel type',
							'search_items'  => 'Search fuel types',
							'popular_items' => 'Popular fuel types',
							'all_items'     => 'All fuel types',
						),
						'meta_box_cb'    => array( 'Inventory_Presser_Taxonomies', 'meta_box_html_fuel' ),
						'query_var'      => 'fuel',
						'singular_label' => 'Fuel',
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
						'label'          => 'Transmissions',
						'labels'         => array(
							'name'          => 'Transmissions',
							'singular_name' => 'Transmission',
							'search_items'  => 'Search transmissions',
							'popular_items' => 'Popular transmissions',
							'all_items'     => 'All transmissions',
						),
						'meta_box_cb'    => array( 'Inventory_Presser_Taxonomies', 'meta_box_html_transmission' ),
						'query_var'      => 'transmission',
						'singular_label' => 'Transmission',
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
						'label'          => 'Cylinders',
						'labels'         => array(
							'name'          => 'Cylinders',
							'singular_name' => 'Cylinder count',
							'search_items'  => 'Search cylinder counts',
							'popular_items' => 'Popular cylinder counts',
							'all_items'     => 'All cylinder counts',
						),
						'meta_box_cb'    => array( 'Inventory_Presser_Taxonomies', 'meta_box_html_cylinders' ),
						'query_var'      => 'cylinders',
						'singular_label' => 'Cylinders',
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
						'label'          => 'Body styles',
						'labels'         => array(
							'name'          => 'Body styles',
							'singular_name' => 'Body style',
							'search_items'  => 'Search body styles',
							'popular_items' => 'Popular body styles',
							'all_items'     => 'All body styles',
						),
						'meta_box_cb'    => null,
						'query_var'      => 'style',
						'singular_label' => 'Body style',
						'show_in_menu'   => false,
						'show_in_rest'   => true,
						'show_ui'        => true,
					),
				),
				array(
					'args'      => array(
						'hierarchical'   => true,
						'label'          => 'Colors',
						'labels'         => array(
							'name'          => 'Color',
							'singular_name' => 'Color',
							'search_items'  => 'Search colors',
							'popular_items' => 'Popular colors',
							'all_items'     => 'All colors',
						),
						'meta_box_cb'    => array( 'Inventory_Presser_Taxonomies', 'meta_box_html_colors' ),
						'query_var'      => 'colors',
						'singular_label' => 'Color',
						'show_in_menu'   => false,
						'show_in_rest'   => true,
						'show_ui'        => true,
					),
					'term_data' => array(
						'Beige'    => 'Beige',
						'Black'    => 'Black',
						'Blue'     => 'Blue',
						'Brown'    => 'Brown',
						'Burgundy' => 'Burgundy',
						'Gold'     => 'Gold',
						'Grey'     => 'Grey',
						'Green'    => 'Green',
						'Ivory'    => 'Ivory',
						'Orange'   => 'Orange',
						'Purple'   => 'Purple',
						'Red'      => 'Red',
						'Silver'   => 'Silver',
						'White'    => 'White',
						'Yellow'   => 'Yellow',
					),
				),
				array(
					'args' => array(
						'hierarchical'   => false,
						'label'          => 'Locations',
						'labels'         => array(
							'name'          => 'Location',
							'singular_name' => 'Location',
							'search_items'  => __( 'Search locations', 'inventory-presser' ),
							'popular_items' => __( 'Popular locations', 'inventory-presser' ),
							'all_items'     => __( 'All locations', 'inventory-presser' ),
							'edit_item'     => __( 'Edit Location', 'inventory-presser' ),
							'view_item'     => __( 'View Location', 'inventory-presser' ),
							'update_item'   => __( 'Update Location', 'inventory-presser' ),
							'add_new_item'  => __( 'Add New Location', 'inventory-presser' ),
							'new_item_name' => __( 'New Location Name', 'inventory-presser' ),
							'not_found'     => __( 'No locations found', 'inventory-presser' ),
							'no_terms'      => __( 'No locations', 'inventory-presser' ),
							'menu_name'     => __( 'Locations', 'inventory-presser' ),
						),
						'meta_box_cb'    => array( 'Inventory_Presser_Taxonomies', 'meta_box_html_locations' ),
						'query_var'      => 'location',
						'singular_label' => 'Location',
						'show_in_menu'   => true,
						'show_in_rest'   => true,
						'show_ui'        => true,
					),
				),
			)
		);
	}

	/**
	 * taxonomy_meta_box_html
	 *
	 * Creates HTML output for a meta box that turns a taxonomy into
	 * a select drop-down list instead of the typical checkboxes. Including
	 * a blank option is the only way a user can remove the value.
	 *
	 * @param  string  $taxonomy_name
	 * @param  string  $element_name
	 * @param  WP_Post $post          A post
	 * @return string HTML that renders a editor meta box for a taxonomy
	 */
	static function taxonomy_meta_box_html( $taxonomy_name, $element_name, $post ) {
		$HTML = sprintf(
			'<select name="%s" id="%s"><option></option>',
			$element_name,
			$element_name
		);

		// get all the term names and slugs for $taxonomy_name
		$terms = get_terms( $taxonomy_name, array( 'hide_empty' => false ) );

		if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
			// get the saved term for this taxonomy
			$saved_term_slug = self::get_term_slug( $taxonomy_name, $post->ID );

			foreach ( $terms as $term ) {
				$HTML .= sprintf(
					'<option value="%s"%s>%s</option>',
					$term->slug,
					selected( strtolower( $term->slug ), strtolower( $saved_term_slug ), false ),
					$term->name
				);
			}
		}
		return $HTML . '</select>';
	}
}
