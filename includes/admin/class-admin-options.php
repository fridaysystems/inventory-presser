<?php
defined( 'ABSPATH' ) || exit;

/**
 * Inventory_Presser_Admin_Options
 *
 * Creates an options page in the dashboard to hold this plugin and its add-ons
 * settings.
 *
 * @since      0.5
 * @package    inventory-presser
 * @subpackage inventory-presser/includes
 * @author     Corey Salzano <corey@friday.systems>
 */
class Inventory_Presser_Admin_Options {

	/**
	 * This plugin's option that holds all the settings.
	 *
	 * @var array
	 */
	private $option;
	const QUERY_VAR_MANAGE_VEHICLES = 'invp_manage_vehicles';

	/**
	 * Adds hooks
	 *
	 * @return void
	 */
	public function add_hooks() {
		add_action( 'admin_enqueue_scripts', array( $this, 'scripts_and_styles' ) );
		add_action( 'admin_init', array( $this, 'add_settings' ) );
		add_action( 'admin_menu', array( $this, 'add_options_pages' ) );

		// Look for a query variable that triggers Delete All Vehicles.
		add_action( 'admin_init', array( $this, 'detect_manage_vehicles_query_var' ) );

		/**
		 * When the option is updated, check if the additional listings pages
		 * settings changed. If they have, flush permalinks.
		 */
		add_action( 'update_option_' . INVP::OPTION_NAME, array( $this, 'maybe_flush_permalinks' ), 10, 2 );
	}

	/**
	 * Adds an options page to the dashboard to hold all this plugin's settings.
	 *
	 * @return void
	 */
	public function add_options_pages() {
		if ( post_type_exists( INVP::POST_TYPE ) ) {
			// Taxonomy settings page.
			add_submenu_page(
				'edit.php?post_type=' . INVP::POST_TYPE,
				__( 'Taxonomies', 'inventory-presser' ), // page_title.
				__( 'Taxonomies', 'inventory-presser' ), // menu_title.
				'manage_options', // capability.
				'invp-taxonomies', // menu_slug.
				array( $this, 'options_page_content_taxonomies' ) // function.
			);

			// Main options page at Vehicles > Options.
			add_submenu_page(
				'edit.php?post_type=' . INVP::POST_TYPE,
				__( 'Options', 'inventory-presser' ), // page_title.
				__( 'Options', 'inventory-presser' ), // menu_title.
				'manage_options', // capability.
				INVP::OPTION_PAGE, // menu_slug.
				array( $this, 'options_page_content' ) // function.
			);
		}
	}

	/**
	 * Registers sections and settings using the Settings API.
	 *
	 * @return void
	 */
	public function add_settings() {
		register_setting(
			INVP::option_group(), // option_group.
			INVP::OPTION_NAME, // option_name.
			array( $this, 'sanitize_options' ) // sanitize_callback.
		);

		/**
		 * SECTION General
		 */
		$section = 'dealership_options_setting_section';
		add_settings_section(
			$section, // id.
			__( 'General', 'inventory-presser' ), // title.
			'__return_empty_string', // callback.
			INVP::option_page() // page.
		);

		// Manage.
		if ( current_user_can( 'delete_posts' ) ) {
			add_settings_field(
				'manage', // id.
				__( 'Manage Inventory', 'inventory-presser' ), // title.
				array( $this, 'callback_manage' ), // callback.
				INVP::option_page(), // page.
				$section // section.
			);
		}

		// [x] Skip trash when deleting vehicles and delete permanently
		add_settings_field(
			'skip_trash', // id.
			__( 'Skip Trash', 'inventory-presser' ), // title.
			array( $this, 'callback_skip_trash' ), // callback.
			INVP::option_page(), // page.
			$section // section.
		);

		// Taxonomies is just a link to a separate settings page since 14.14.0.
		add_settings_field(
			'manage_taxonomies', // id.
			__( 'Taxonomies', 'inventory-presser' ), // title.
			array( $this, 'callback_manage_taxonomies' ), // callback.
			INVP::option_page(), // page.
			$section // section.
		);

		// MapBox Public Token [____________].
		add_settings_field(
			'mapbox_public_token', // id.
			__( 'MapBox Public Token', 'inventory-presser' ), // title.
			array( $this, 'callback_mapbox_public_token' ), // callback.
			INVP::option_page(), // page.
			$section // section.
		);

		add_settings_field(
			'use_arranger_gallery', // id.
			__( 'Rearrange Photos Block', 'inventory-presser' ), // title.
			array( $this, 'callback_use_arranger_gallery' ), // callback.
			INVP::option_page(), // page.
			$section // section.
		);

		/**
		 * SECTION Listings
		 */
		add_settings_section(
			'dealership_options_section_listings', // id.
			__( 'Listings', 'inventory-presser' ), // title.
			'__return_empty_string', // callback.
			INVP::option_page() // page.
		);

		// Sort vehicles by [Field] in [Ascending] order.
		add_settings_field(
			'sort_vehicles_by', // id.
			__( 'Sort Vehicles By', 'inventory-presser' ), // title.
			array( $this, 'callback_sort_vehicles_by' ), // callback.
			INVP::option_page(), // page.
			'dealership_options_section_listings' // section.
		);

		// Price Display.
		add_settings_field(
			'price_display', // id.
			__( 'Price Display', 'inventory-presser' ), // title.
			array( $this, 'callback_price_display' ), // callback.
			INVP::option_page(), // page.
			'dealership_options_section_listings' // section.
		);

		// [x] Include sold vehicles in listings and search results
		add_settings_field(
			'include_sold_vehicles', // id.
			__( 'Sold Vehicles', 'inventory-presser' ), // title.
			array( $this, 'callback_include_sold_vehicles' ), // callback.
			INVP::option_page(), // page.
			'dealership_options_section_listings' // section.
		);

		// [x] Provide single and archive templates
		add_settings_field(
			'provide_templates', // id.
			__( 'Templates', 'inventory-presser' ), // title.
			array( $this, 'callback_provide_templates' ), // callback.
			INVP::option_page(), // page.
			'dealership_options_section_listings' // section.
		);

		/**
		 * Create an additional inventory archive at pmgautosales.com/[cash-deals]
		 * that contains vehicles that have a value for field [Down Payment]
		 */
		add_settings_field(
			'additional_listings_page', // id.
			__( 'Listings Pages', 'inventory-presser' ), // title.
			array( $this, 'callback_additional_listings_page' ), // callback.
			INVP::option_page(), // page.
			'dealership_options_section_listings' // section.
		);

		/**
		 * Simplify adding a contact form to vehicle singles.
		 */
		add_settings_field(
			'singles_contact_form', // id.
			__( 'Singles Contact Form', 'inventory-presser' ), // title.
			array( $this, 'callback_singles_contact_form' ), // callback.
			INVP::option_page(), // page.
			'dealership_options_section_listings' // section.
		);

		/**
		 * SECTION Carfax
		 */
		add_settings_section(
			'dealership_options_section_carfax', // id.
			__( 'Carfax', 'inventory-presser' ), // title.
			'__return_empty_string', // callback.
			INVP::option_page() // page.
		);

		// [x] Display Carfax buttons near vehicles that link to free Carfax reports
		add_settings_field(
			'use_carfax', // id.
			__( 'Enable Carfax', 'inventory-presser' ), // title.
			array( $this, 'callback_use_carfax' ), // callback.
			INVP::option_page(), // page.
			'dealership_options_section_carfax' // section.
		);

		// [x] Use Carfax-provided, dynamic buttons that may also say things like "GOOD VALUE"
		add_settings_field(
			'use_carfax_provided_buttons', // id.
			__( 'Use Newest Buttons', 'inventory-presser' ), // title.
			array( $this, 'callback_use_carfax_provided_buttons' ), // callback.
			INVP::option_page(), // page.
			'dealership_options_section_carfax' // section.
		);

		if ( defined( 'INVP::OPTION_PAGE_TAXONOMIES' ) ) {
			/**
			 * Register a section and setting stored in the same option but managed
			 * on a different page.
			 */
			add_settings_section(
				'section_taxonomies', // id.
				__( 'Taxonomies', 'inventory-presser' ), // title.
				array( $this, 'settings_section_content_taxonomies' ), // callback.
				INVP::OPTION_PAGE_TAXONOMIES . '-admin' // page.
			);
			add_settings_field(
				'taxonomies', // id.
				__( 'Taxonomies', 'inventory-presser' ), // title.
				array( $this, 'callback_taxonomies' ), // callback.
				INVP::OPTION_PAGE_TAXONOMIES . '-admin', // page.
				'section_taxonomies' // section.
			);
		}
	}

	/**
	 * Outputs HTML that renders checkboxes.
	 *
	 * @param  string $setting_name   The name of the setting and control
	 * @param  string $checkbox_label The checkbox label that the user sees
	 * @return void
	 */
	protected function boolean_checkbox_setting_callback( $setting_name, $checkbox_label ) {
		printf(
			'<input type="checkbox" name="%1$s[%2$s]" id="%2$s" %3$s> <label for="%2$s">%4$s</label>',
			esc_attr( INVP::OPTION_NAME ),
			esc_attr( $setting_name ),
			isset( $this->option[ $setting_name ] ) ? checked( $this->option[ $setting_name ], true, false ) : '',
			esc_html( $checkbox_label )
		);
	}

	/**
	 * Outputs a table to manage additional inventory listing pages.
	 *
	 * Helps users create an additional inventory archive at
	 * example.com/[cash-deals] that contains vehicles that have a value for
	 * field [Down Payment].
	 *
	 * @return void
	 */
	public function callback_additional_listings_page() {
		// Is Divi or a Divi child theme active?
		if ( in_array( 'Divi', array( get_template(), get_stylesheet() ), true ) ) {
			// Yes. This feature does not work on Divi.
			printf(
				'<div class="invp-notice invp-notice-error"><p>%s %s <a href="https://inventorypresser.com/docs/divi-setup-guide/">%s →</a></p></div>',
				esc_html__( 'Listings Pages feature does not work on Divi.', 'inventory-presser' ),
				esc_html__( 'Visit', 'inventory-presser' ),
				esc_html__( 'Divi Setup Guide', 'inventory-presser' )
			);
		}

		?>
		<div id="additional_listings_pages_settings">
			<table class="wp-list-table widefat striped invp-settings">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Active', 'inventory-presser' ); ?></th>
						<th><?php esc_html_e( 'Settings', 'inventory-presser' ); ?></th>
						<th></th>
					</tr>
				</thead>
				<tbody>
				<?php

				// output a row for each saved additional listing page + one blank.
				$additional_listings = Inventory_Presser_Additional_Listings_Pages::additional_listings_pages_array();
				$keys                = array(
					'url_path',
					'key',
					'operator',
					'value',
				);
				$page_count          = count( $additional_listings );
				for ( $a = 0; $a < $page_count; $a++ ) {
					foreach ( $keys as $key ) {
						if ( ! isset( $additional_listings[ $a ][ $key ] ) ) {
							$additional_listings[ $a ][ $key ] = '';
						}
					}

					?>
					<tr id="row_<?php echo esc_attr( $a ); ?>">
						<td class="active">
							<?php
							printf(
								'<input type="checkbox" id="additional_listings_pages_active_%1$s" name="%2$s[additional_listings_pages][%1$s][active]" title="%4$s" %3$s />',
								esc_attr( $a ),
								esc_attr( INVP::OPTION_NAME ),
								checked( true, $additional_listings[ $a ]['active'] ?? true, false ),
								esc_html__( 'Toggle active or inactive', 'inventory-presser' )
							);
							?>
						</td>
						<td>
							<?php
							// Title.
							printf(
								'<span class="item"><label for="additional_listings_pages_title_%1$s">%4$s</label> <input type="text" id="additional_listings_pages_title_%1$s" name="%2$s[additional_listings_pages][%1$s][title]" value="%3$s" /></span>',
								esc_attr( $a ),
								esc_attr( INVP::OPTION_NAME ),
								esc_attr( $additional_listings[ $a ]['title'] ?? '' ),
								esc_html__( 'Title', 'inventory-presser' )
							);

							// URL.
							printf(
								'<span class="item"><label for="additional_listings_pages_slug_%2$s">%5$s</label> <input type="text" disabled="disabled" value="%1$s/" /><input type="text" id="additional_listings_pages_slug_%2$s" name="%3$s[additional_listings_pages][%2$s][url_path]" class="additional_listings_pages_slug" value="%4$s" /></span>',
								esc_url( site_url() ),
								esc_attr( $a ),
								esc_attr( INVP::OPTION_NAME ),
								esc_attr( $additional_listings[ $a ]['url_path'] ?? '' ),
								esc_html__( 'URL', 'inventory-presser' )
							);

							// Filter.
							printf(
								'<span class="item"><label for="additional_listings_pages_key_%1$s">%2$s</label> ',
								esc_attr( 'additional_listings_pages_key_' . $a ),
								esc_html__( 'Filter', 'inventory-presser' )
							);
							echo $this->html_select_vehicle_keys(
								array(
									'id'    => esc_attr( 'additional_listings_pages_key_' . $a ),
									'name'  => esc_attr( INVP::OPTION_NAME . '[additional_listings_pages][' . $a . '][key]' ),
									'class' => esc_attr( 'filter-key' ),
									'title' => esc_attr__( 'Filter field', 'inventory-presser' ),
								),
								$additional_listings[ $a ]['key'] ?? ''
							);

							// Operator.
							echo $this->html_select_operator(
								array(
									'id'    => esc_attr( 'additional_listings_pages_operator_' . $a ),
									'name'  => esc_attr( INVP::OPTION_NAME . '[additional_listings_pages][' . $a . '][operator]' ),
									'class' => esc_attr( 'operator' ),
									'title' => esc_attr__( 'Operator', 'inventory-presser' ),
								),
								$additional_listings[ $a ]['operator'] ?? ''
							);

							// Comparison value.
							printf(
								'<input type="text" class="value" id="additional_listings_pages_value_%1$s" name="%2$s[additional_listings_pages][%1$s][value]" value="%3$s" title="%4$s" /></span>',
								esc_attr( $a ),
								esc_attr( INVP::OPTION_NAME ),
								esc_attr( $additional_listings[ $a ]['value'] ?? '' ),
								esc_attr__( 'Comparison value', 'inventory-presser' )
							);
							?>
							</td>
						<td class="actions">
							<a href="<?php echo esc_url( site_url( $additional_listings[ $a ]['url_path'] ?? '' ) ); ?>" class="button action" title="<?php esc_attr__( 'View this page', 'inventory-presser' ); ?>"><span class="dashicons dashicons-welcome-view-site"></span></a><button class="button action delete-button" id="delete_<?php echo esc_attr( $a ); ?>" title="<?php esc_attr__( 'Delete this page', 'inventory-presser' ); ?>"><span class="dashicons dashicons-trash"></span></button>
						</td>
					</tr>
					<?php

				}

				?>
				</tbody>
			</table>
			<button class="button action" id="add_additional_listings_page"><?php esc_html_e( 'Add Listings Page', 'inventory-presser' ); ?></button>
		</div>
		<?php
	}

	/**
	 * Outputs a checkbox control for the Include Sold Vehicles setting.
	 *
	 * @return void
	 */
	public function callback_include_sold_vehicles() {
		$this->boolean_checkbox_setting_callback(
			'include_sold_vehicles',
			__( 'Include sold vehicles in listings and search results', 'inventory-presser' )
		);
	}

	public function callback_manage() {
		// Load Sample Vehicles button.
		if ( current_user_can( 'edit_posts' ) ) {
			$url = add_query_arg(
				array(
					self::QUERY_VAR_MANAGE_VEHICLES => 'load_sample_vehicles',
					'_wpnonce'                      => wp_create_nonce( self::QUERY_VAR_MANAGE_VEHICLES ),
				),
				admin_url( 'edit.php?post_type=' . INVP::POST_TYPE . '&page=' . INVP::OPTION_PAGE )
			);
			printf(
				'<a class="button action" id="load_sample_vehicles" href="%s">%s</a> ',
				esc_url( $url ),
				esc_html__( 'Load Sample Vehicles', 'inventory-presser' )
			);
		}

		// Delete All Vehicles button.
		if ( current_user_can( 'delete_posts' ) ) {
			$url = add_query_arg(
				array(
					self::QUERY_VAR_MANAGE_VEHICLES => 'delete_all_vehicles',
					'_wpnonce'                      => wp_create_nonce( self::QUERY_VAR_MANAGE_VEHICLES ),
				),
				admin_url( 'edit.php?post_type=' . INVP::POST_TYPE . '&page=' . INVP::OPTION_PAGE )
			);
			printf(
				'<a class="button action" id="delete_all_vehicles" href="%s">%s</a>',
				esc_url( $url ),
				esc_html__( 'Delete All Vehicles', 'inventory-presser' )
			);
		}

		if ( current_user_can( 'edit_posts' ) || current_user_can( 'delete_posts' ) ) {
			printf(
				'<p class="description">%s</p>',
				esc_html__( 'These features may be impacted by time and memory limits set by your web host.', 'inventory-presser' )
			);
		}
	}

	/**
	 * Outputs a text box control for the MapBox Public Token setting
	 *
	 * @return void
	 */
	public function callback_mapbox_public_token() {
		printf(
			'<p><input type="text" name="%1$s" class="regular-text code" id="%1$s" value="%2$s" /></p><p class="description">%3$s <a href="%4$s">%5$s</a> %6$s</p>',
			'inventory_presser[mapbox_public_token]',
			esc_attr( isset( $this->option['mapbox_public_token'] ) ? $this->option['mapbox_public_token'] : '' ),
			esc_html__( 'Obtain a key at', 'inventory-presser' ),
			'https://mapbox.com/',
			'mapbox.com',
			esc_html__( 'to use the Map widget.', 'inventory-presser' )
		);
	}


	/**
	 * Outputs a dropdown select control for the Price Display setting
	 *
	 * @return void
	 */
	public function callback_price_display() {
		$separator             = apply_filters( 'invp_price_display_separator', ' / ', $this->option['price_display'] ?? '', 0 );
		$price_display_options = apply_filters(
			'invp_price_display_options',
			array(
				'default'          => INVP::currency_symbol() . '{Price}',
				'msrp'             => INVP::currency_symbol() . '{MSRP}',
				'full_or_down'     => INVP::currency_symbol() . '{Price}' . $separator . INVP::currency_symbol() . '{Down Payment} Down',
				'down_only'        => INVP::currency_symbol() . '{Down Payment} Down',
				'was_now_discount' => sprintf(
					'%1$s %2$s{MSRP} %3$s %2$s{Price} %4$s %2$s{MSRP}-{Price}',
					apply_filters( 'invp_price_was_now_discount_retail', __( 'Retail', 'inventory-presser' ) ),
					INVP::currency_symbol(),
					apply_filters( 'invp_price_was_now_discount_now', __( 'Now', 'inventory-presser' ) ),
					apply_filters( 'invp_price_was_now_discount_save', __( 'You Save', 'inventory-presser' ) )
				),
				'payment_only'     => INVP::currency_symbol() . '{Payment} {Frequency}',
				'down_and_payment' => INVP::currency_symbol() . '{Down payment}' . $separator . INVP::currency_symbol() . '{Payment} {Frequency}',
				'call_for_price'   => __( 'Call For Price', 'inventory-presser' ),
			)
		);

		$selected_val = null;
		if ( isset( $this->option['price_display'] ) ) {
			$selected_val = $this->option['price_display'];
		}

		printf(
			'<select name="%s[price_display]" id="price_display">',
			esc_attr( INVP::OPTION_NAME )
		);
		foreach ( $price_display_options as $val => $name ) {
			printf(
				'<option value="%s"%s>%s</option>',
				esc_attr( $val ),
				selected( $val, $selected_val, false ),
				esc_html( $name )
			);
		}
		printf(
			'</select><p class="description" id="%s[price_display]-description">&quot;%s&quot; %s.</p>',
			esc_attr( INVP::OPTION_NAME ),
			esc_html__( 'Call for Price', 'inventory-presser' ),
			esc_html__( 'will display for any price that is zero', 'inventory-presser' )
		);
	}

	/**
	 * Outputs a checkbox control for the Provide Templates setting.
	 *
	 * @return void
	 */
	public function callback_provide_templates() {
		$this->boolean_checkbox_setting_callback(
			'provide_templates',
			__( 'Provide single and archive templates', 'inventory-presser' )
		);
	}

	/**
	 * Output the controls that create the Taxonomies setting. A link to another
	 * settings page.
	 *
	 * @return void
	 */
	public function callback_manage_taxonomies() {
		printf(
			'<a class="button" href="%1$s">%2$s</a>',
			esc_url_raw( admin_url( sprintf( 'edit.php?post_type=%s&page=invp-taxonomies', INVP::POST_TYPE ) ) ),
			esc_html__( 'Manage Taxonomies', 'inventory-presser' )
		);

		// Write a sentence that explains what this setting does with links to all the taxonomies.
		$links          = array();
		$taxonomy_data  = Inventory_Presser_Taxonomies::taxonomy_data();
		$taxonomy_count = count( $taxonomy_data );
		for ( $i = 0; $i < $taxonomy_count; $i++ ) {
			if ( empty( $taxonomy_data[ $i ]['args']['query_var'] )
				|| empty( $taxonomy_data[ $i ]['args']['label'] ) ) {
				continue;
			}
			$links[] = '<a href="' . admin_url(
				sprintf(
					'edit-tags.php?taxonomy=%s&post_type=%s',
					str_replace( '-', '_', $taxonomy_data[ $i ]['args']['query_var'] ?? '' ),
					INVP::POST_TYPE
				)
			) . '">' . $taxonomy_data[ $i ]['args']['label'] . '</a>';
		}

		printf(
			'<p class="description">%s %s</p>',
			esc_html__( 'Enable, disable, show, and hide these taxonomies in the dashboard menu and Editors:', 'inventory-presser' ),
			implode( ', ', $links )
		);
	}

	/**
	 * Outputs the controls that create the Singles Contact Form setting.
	 *
	 * @return void
	 */
	public function callback_singles_contact_form() {
		$setting_name  = 'singles_contact_form';
		$current_value = isset( $this->option[ $setting_name ] ) ? $this->option[ $setting_name ] : 0;
		$options       = sprintf( '<option>%s</option>', esc_html__( 'None', 'inventory-presser' ) );

		// Get all Contact Form 7 forms and build <option> elements.
		if ( class_exists( 'WPCF7_ContactForm' ) ) {
			$args = array(
				'order'   => 'ASC',
				'orderby' => 'title',
			);
			foreach ( \WPCF7_ContactForm::find( $args ) as $cf7form ) {
				$options .= sprintf(
					'<option value="CF7_%1$s"%2$s>%3$s: %4$s</option>',
					esc_attr( $cf7form->id() ),
					selected( 'CF7_' . $cf7form->id(), $current_value, false ),
					esc_html__( 'Contact Form 7', 'inventory-presser' ),
					esc_attr( $cf7form->title() )
				);
			}
		}

		// Get all WP Forms and build <option> elements.
		if ( function_exists( 'wpforms' ) ) {
			$wpforms = wpforms()->get( 'form' )->get(
				'',
				array(
					'orderby' => 'title',
				)
			);
			foreach ( $wpforms as $wpform ) {
				$options .= sprintf(
					'<option value="WPF_%1$s"%2$s>%3$s: %4$s</option>',
					esc_attr( $wpform->ID ),
					selected( $wpform->ID, $current_value, false ),
					esc_html__( 'WP Forms', 'inventory-presser' ),
					esc_attr( $wpform->post_title )
				);
			}
		}

		// Get all Gravity Forms and build <option> elements.
		if ( class_exists( 'GFAPI' ) ) {
			foreach ( GFAPI::get_forms() as $gform ) {
				$options .= sprintf(
					'<option value="GF_%s"%s>%s: %s</option>',
					esc_attr( $gform['id'] ),
					selected( 'GF_' . $gform['id'], $current_value, false ),
					esc_html__( 'Gravity Forms', 'event-milestones' ),
					esc_attr( $gform['title'] )
				);
			}
		}

		// Get all WS Forms and build <option> elements.
		if ( class_exists( 'WS_Form_Common' ) ) {
			foreach ( WS_Form_Common::get_forms_array( false ) as $id => $wsform ) {
				$options .= sprintf(
					'<option value="WSF_%s"%s>%s: %s</option>',
					esc_attr( $id ),
					selected( 'WSF_' . $id, $current_value, false ),
					esc_html__( 'WS Form', 'inventory-presser' ),
					esc_attr( preg_replace( '/\s\([^\)]+\)$/', '', $wsform ) )
				);
			}
		}

		printf(
			'<select name="%1$s[%2$s]" id="%2$s">%3$s</select><p class="description" id="%1$s[%2$s]-description">%4$s</p>',
			esc_attr( INVP::OPTION_NAME ),
			esc_attr( $setting_name ),
			$options,
			esc_html__( 'Choose a form to include on vehicle single pages.', 'inventory-presser' )
		);
	}

	/**
	 * Output the controls that create the Skip Trash setting.
	 *
	 * @return void
	 */
	public function callback_skip_trash() {
		$this->boolean_checkbox_setting_callback(
			'skip_trash',
			__( 'Skip trash when deleting vehicles and delete permanently', 'inventory-presser' )
		);
	}

	/**
	 * change_sort_by_option_values
	 *
	 * @param  mixed $value
	 * @return void
	 */
	public function change_sort_by_option_values( $value ) {
		if ( 'date_entered' === $value ) {
			return 'post_date';
		}
		if ( 'last_modified' === $value ) {
			return 'post_modified';
		}
		return $value;
	}

	/**
	 * Output the controls that create the default vehicle sort setting.
	 *
	 * @return void
	 */
	public function callback_sort_vehicles_by() {
		// use these default values if we have none
		if ( ! isset( $this->option['sort_vehicles_by'] ) ) {
			$this->option['sort_vehicles_by'] = 'make';
		}
		if ( ! isset( $this->option['sort_vehicles_order'] ) ) {
			$this->option['sort_vehicles_order'] = 'ASC';
		}

		add_filter( 'invp_html_select_vehicle_keys_value', array( $this, 'change_sort_by_option_values' ) );

		$select = $this->html_select_vehicle_keys(
			array(
				'name' => INVP::OPTION_NAME . '[sort_vehicles_by]',
				'id'   => 'sort_vehicles_by',
			),
			$this->option['sort_vehicles_by']
		);

		remove_filter( 'invp_html_select_vehicle_keys_value', array( $this, 'change_sort_by_option_values' ) );

		printf(
			'%s %s <select name="%s[sort_vehicles_order]" id="sort_vehicles_order">',
			$select,
			esc_html__( 'in', 'inventory-presser' ),
			esc_attr( INVP::OPTION_NAME )
		);

		foreach ( array(
			'ascending'  => 'ASC',
			'descending' => 'DESC',
		) as $direction => $abbr ) {
			echo '<option value="' . esc_attr( $abbr ) . '"';
			if ( isset( $this->option['sort_vehicles_order'] ) ) {
				selected( $this->option['sort_vehicles_order'], $abbr );
			}
			echo '>' . esc_html( $direction ) . '</option>';
		}
		echo '</select> ' . esc_html__( 'order', 'inventory-presser' );
	}

	/**
	 * Output the controls that create the Taxonomies settings.
	 *
	 * @return void
	 */
	public function callback_taxonomies() {
		?>
		<div id="taxonomies_settings">
			<table class="wp-list-table widefat striped taxonomies invp-settings">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Taxonomy', 'inventory-presser' ); ?></th>
						<th><?php esc_html_e( 'Active', 'inventory-presser' ); ?></th>
						<th><?php esc_html_e( 'Show in Admin Menu', 'inventory-presser' ); ?></th>
		<?php
		$types = get_terms(
			array(
				'taxonomy'   => 'type',
				'hide_empty' => false,
			)
		);
		foreach ( $types as $type ) {
			printf(
				'<th>%s</th>',
				esc_html( $type->name )
			);
		}
		?>
					</tr>
				</thead>
				<tbody>
		<?php
		$taxonomy_data  = Inventory_Presser_Taxonomies::taxonomy_data();
		$taxonomy_count = count( $taxonomy_data );
		$type_count     = count( $types );
		for ( $i = 0; $i < $taxonomy_count; $i++ ) {
			if ( empty( $taxonomy_data[ $i ]['args']['query_var'] )
				|| empty( $taxonomy_data[ $i ]['args']['label'] ) ) {
				continue;
			}
			echo '<tr><td>';
			// Link the taxonomy name to where terms can be edited if the taxonomy is active.
			if ( $this->option['taxonomies'][ $taxonomy_data[ $i ]['args']['query_var'] ]['active'] ?? false ) {
				printf(
					'<a href="%s">%s</a>',
					esc_url_raw(
						admin_url(
							sprintf(
								'edit-tags.php?taxonomy=%s&post_type=%s',
								$taxonomy_data[ $i ]['args']['query_var'],
								INVP::POST_TYPE
							)
						)
					),
					esc_html( $taxonomy_data[ $i ]['args']['label'] )
				);
			} else {
				echo esc_html( $taxonomy_data[ $i ]['args']['label'] );
			}
			// Output the Active and Show in Admin Menu checkboxes.
			printf(
				'</td><td><input type="checkbox" name="%1$s[taxonomies][%2$s][active]" %3$s /></td><td><input type="checkbox" name="%1$s[taxonomies][%2$s][admin_menu]" %4$s /></td>',
				esc_attr( INVP::OPTION_NAME ),
				esc_attr( $taxonomy_data[ $i ]['args']['query_var'] ),
				checked( true, boolval( $this->option['taxonomies'][ $taxonomy_data[ $i ]['args']['query_var'] ]['active'] ?? false ), false ), // Active.
				checked( true, boolval( $this->option['taxonomies'][ $taxonomy_data[ $i ]['args']['query_var'] ]['admin_menu'] ?? false ), false ), // Show in Admin Menu.
			);

			// Output checkboxes for each vehicle type.
			for ( $j = 0; $j < $type_count; $j++ ) {
				printf(
					'<td><input type="checkbox" name="%1$s[taxonomies][%2$s][%3$s]" %4$s /></td>',
					esc_attr( INVP::OPTION_NAME ),
					esc_attr( $taxonomy_data[ $i ]['args']['query_var'] ),
					esc_attr( $types[ $j ]->slug ),
					checked( true, boolval( $this->option['taxonomies'][ $taxonomy_data[ $i ]['args']['query_var'] ][ $types[ $j ]->slug ] ?? false ), false )
				);
			}
			echo '</tr>';
		}

		echo '</tbody></table></div>';
	}

	/**
	 * Outputs a checkbox and label to enable the photo arranger Gallery Block
	 * feature.
	 *
	 * @return void
	 */
	public function callback_use_arranger_gallery() {
		$setting_name = 'use_arranger_gallery';
		$options      = INVP::settings();
		printf(
			'<p><input type="checkbox" name="%s[%s]" id="%s" %s> <label for="%s">%s</label></p>',
			esc_attr( INVP::OPTION_NAME ),
			esc_attr( $setting_name ),
			esc_attr( $setting_name ),
			isset( $options[ $setting_name ] ) ? checked( $options[ $setting_name ], true, false ) : '',
			esc_attr( $setting_name ),
			esc_html__( 'Add a Gallery Block to new vehicle posts to manage photo uploads and their order', 'inventory-presser' )
		);
	}

	/**
	 * Output the controls that create the Display Carfax Buttons setting.
	 *
	 * @return void
	 */
	public function callback_use_carfax() {
		$this->boolean_checkbox_setting_callback(
			'use_carfax',
			__( 'Display Carfax buttons near vehicles that link to free Carfax reports', 'inventory-presser' )
		);
	}

	/**
	 * Output the controls that create the Use Carfax-provided Buttons setting.
	 *
	 * @return void
	 */
	public function callback_use_carfax_provided_buttons() {
		$this->boolean_checkbox_setting_callback(
			'use_carfax_provided_buttons',
			__( 'Use Carfax-provided, dynamic buttons that may also say things like "GOOD VALUE"', 'inventory-presser' )
		);
	}

	/**
	 * detect_manage_vehicles_query_var
	 *
	 * @return void
	 */
	public function detect_manage_vehicles_query_var() {
		if ( 'GET' !== $_SERVER['REQUEST_METHOD'] ?? '' ) {
			return;
		}

		if ( empty( $_GET['_wpnonce'] ) || empty( $_GET[ self::QUERY_VAR_MANAGE_VEHICLES ] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_GET['_wpnonce'], self::QUERY_VAR_MANAGE_VEHICLES ) ) {
			return;
		}

		$action = sanitize_text_field( $_GET[ self::QUERY_VAR_MANAGE_VEHICLES ] );
		switch ( $action ) {
			case 'delete_all_vehicles':
				INVP::delete_all_inventory();
				break;
			case 'load_sample_vehicles':
				INVP::load_sample_vehicles();
				break;
		}

		// Redirect to prevent duplicate actions on reloads.
		wp_safe_redirect( admin_url( 'edit.php?post_type=' . INVP::POST_TYPE . '&page=' . INVP::OPTION_PAGE ) );
		exit;
	}

	/**
	 * Creates a dropdown select that contains logical operators.
	 *
	 * @param  array  $attributes
	 * @param  string $selected_value
	 * @return string
	 */
	private function html_select_operator( $attributes = null, $selected_value = null ) {
		$keys = array(
			__( 'exists', 'inventory-presser' ),
			__( 'does not exist', 'inventory-presser' ),
			__( 'greater than', 'inventory-presser' ),
			__( 'less than', 'inventory-presser' ),
			__( 'equal to', 'inventory-presser' ),
			__( 'not equal to', 'inventory-presser' ),
			__( 'contains', 'inventory-presser' ),
		);

		$options = '';
		foreach ( $keys as $key ) {
			$slug     = str_replace( ' ', '_', $key );
			$options .= sprintf(
				'<option value="%s"%s>%s</option>',
				$slug,
				selected( $selected_value, $slug, false ),
				$key
			);
		}

		$attribute_string = '';
		if ( ! empty( $attributes ) ) {
			$attribute_string = ' ' . str_replace( '=', '="', http_build_query( $attributes, '', '" ', PHP_QUERY_RFC3986 ) ) . '"';
		}

		return sprintf(
			'<select%s>%s</select>',
			urldecode( $attribute_string ),
			$options
		);
	}

	/**
	 * Get a list of all the post meta keys in our CPT. Let the user choose one
	 * as a default sort.
	 *
	 * @param  array  $attributes
	 * @param  string $selected_value
	 * @return string
	 */
	private function html_select_vehicle_keys( $attributes = null, $selected_value = null ) {
		$options = '<option value="">' . esc_html__( 'No filter', 'inventory-presser' ) . '</option>';
		foreach ( INVP::keys( false ) as $key ) {
			$meta_key = apply_filters( 'invp_prefix_meta_key', $key );

			// Skip hidden post meta keys.
			if ( '_' === $meta_key[0] ) {
				continue;
			}

			$value = apply_filters( 'invp_html_select_vehicle_keys_value', $key );

			$options .= sprintf(
				'<option value="%s"%s>%s</option>',
				$value,
				selected( $selected_value, $value, false ),
				str_replace( '_', ' ', ucfirst( $key ) )
			);
		}

		$attribute_string = '';
		if ( ! empty( $attributes ) ) {
			$attribute_string = ' ' . str_replace( '=', '="', http_build_query( $attributes, '', '" ', PHP_QUERY_RFC3986 ) ) . '"';
		}

		return sprintf(
			'<select%s>%s</select>',
			urldecode( $attribute_string ),
			$options
		);
	}

	/**
	 * If the additional listing pages settings are changed, or the switch
	 * is on and the array of pages is different, flush rewrite rules
	 *
	 * @param  string $option
	 * @param  mixed  $old_value
	 * @param  mixed  $value
	 * @return void
	 */
	public function maybe_flush_permalinks( $old_value, $value ) {
		// Did the additional listings pages settings change?
		if ( ( ! isset( $old_value['additional_listings_pages'] ) && isset( $value['additional_listings_pages'] ) )
			|| ( isset( $old_value['additional_listings_pages'] ) && ! isset( $value['additional_listings_pages'] ) )
			|| ( isset( $old_value['additional_listings_pages'] ) && isset( $value['additional_listings_pages'] ) && $old_value['additional_listings_pages'] !== $value['additional_listings_pages'] )
		) {
			Inventory_Presser_Plugin::flush_rewrite( false );
		}
	}

	/**
	 * Outputs the settings page HTML content for the primary options page.
	 *
	 * @return void
	 */
	public function options_page_content() {
		wp_enqueue_script( 'invp_page_settings' );
		wp_enqueue_style( 'invp_page_settings' );

		$this->option = INVP::settings();

		?>
		<div class="wrap">
			<h2><?php esc_html_e( 'Inventory Presser Settings', 'inventory-presser' ); ?></h2>
		<?php
		settings_errors();

		?>
		<form method="post" action="options.php">
		<?php
		settings_fields( INVP::option_group() );
		do_settings_sections( INVP::option_page() );
		submit_button();

		?>
		</form>
		</div>
		<?php
	}

	/**
	 * Outputs the settings page HTML content for the taxonomies settings page.
	 *
	 * @return void
	 */
	public function options_page_content_taxonomies() {
		wp_enqueue_script( 'invp_page_settings' );
		wp_enqueue_style( 'invp_page_settings' );

		$this->option = INVP::settings();

		?>
		<div class="wrap">
			<h2><?php esc_html_e( 'Inventory Presser Settings', 'inventory-presser' ); ?></h2>
		<?php
		settings_errors();

		?>
		<form method="post" action="options.php">
		<?php
		settings_fields( INVP::option_group() );
		do_settings_sections( 'invp-taxonomies-admin' );
		submit_button();

		?>
		</form>
		</div>
		<?php
	}

	/**
	 * Santitizes the user input into the options inputs before they are saved.
	 *
	 * @param  array $input Array of submitted settings page values.
	 * @return array
	 */
	public function sanitize_options( $input ) {
		$settings = INVP::settings();

		// Is this the taxonomies page at edit.php?post_type=inventory_vehicle&page=invp-taxonomies?
		if ( 1 === count( $input ) && 'taxonomies' === key( $input ) ) {
			// Yes, this is not the main settings page.
			array_walk_recursive( $input['taxonomies'], array( $this, 'sanitize_taxonomies_walker' ) );
			$sanitary_values = array_merge( $settings, $input );
			// Do not allow the Types taxonomy to be disabled.
			$sanitary_values['taxonomies']['type']['active'] = true;
			// Return early, the rest of this method is sanitizing a different page.
			return apply_filters( 'invp_options_page_taxonomies_sanitized_values', $sanitary_values, $input );
		}

		// Sanitize settings from the main settings page at edit.php?post_type=inventory_vehicle&page=dealership-options.
		$sanitary_values = array(
			'taxonomies' => $settings['taxonomies'] ?? self::taxonomies_setting_default( $settings ), // Keep the taxonomies settings saved by a separate page.
		);

		// If the old show_all_taxonomies setting is set, translate it.
		if ( isset( $settings['show_all_taxonomies'] ) ) {
			unset( $settings['show_all_taxonomies'] );
		}

		$boolean_settings = array(
			'include_sold_vehicles',
			'provide_templates',
			'skip_trash',
			'use_arranger_gallery',
			'use_carfax',
			'use_carfax_provided_buttons',
		);
		foreach ( $boolean_settings as $b ) {
			$sanitary_values[ $b ] = isset( $input[ $b ] );
		}

		/**
		 * Backwards compatibility. No s. Will arrive when 14.6.0 and
		 * older-saved settings are saved on newer versions.
		 */
		if ( isset( $input['additional_listings_page'] ) ) {
			$sanitary_values['additional_listings_page'] = filter_var( $input['additional_listings_page'], FILTER_VALIDATE_BOOLEAN );
		}

		if ( isset( $input['price_display'] ) ) {
			$sanitary_values['price_display'] = $input['price_display'];
		}

		if ( isset( $input['mapbox_public_token'] ) ) {
			$sanitary_values['mapbox_public_token'] = $input['mapbox_public_token'];
		}

		if ( isset( $input['sort_vehicles_by'] ) ) {
			$sanitary_values['sort_vehicles_by'] = $input['sort_vehicles_by'];
		}

		if ( isset( $input['sort_vehicles_order'] ) ) {
			$sanitary_values['sort_vehicles_order'] = $input['sort_vehicles_order'];
		}

		if ( ! empty( $input['additional_listings_pages'] )
			&& is_array( $input['additional_listings_pages'] ) ) {
			/**
			 * array_values() re-indexes the array starting at zero in case the
			 * first rule was deleted and index 0 doesn't exist.
			 */
			$sanitary_values['additional_listings_pages'] = array_values( $input['additional_listings_pages'] );
			/**
			 * This feature doesn't work when two rules have the same URL path.
			 * Drop any duplicates. Also reject any invalid rules.
			 */
			$url_paths    = array();
			$unique_rules = array();
			foreach ( $sanitary_values['additional_listings_pages'] as $additional_listing ) {
				// Is this even a valid rule?
				if ( ! Inventory_Presser_Additional_Listings_Pages::is_valid_rule( $additional_listing ) ) {
					// No.
					continue;
				}

				// Ignore the operator and comparison value if there is no key.
				if ( '' === $additional_listing['key'] ) {
					$additional_listing['operator'] = '';
					$additional_listing['value']    = '';
				}

				/**
				 * Convert the active toggle to a real boolean. If a pre-14.6.0
				 * setting value is still set, this is where we convert all
				 * additional listings pages to active.
				 */
				$additional_listing['active'] = isset( $additional_listing['active'] )
					|| isset( $sanitary_values['additional_listings_page'] ); // No s.

				if ( in_array( $additional_listing['url_path'], $url_paths, true ) ) {
					// sorry!
					continue;
				}
				$unique_rules[] = $additional_listing;
				$url_paths[]    = $additional_listing['url_path'];
			}
			$sanitary_values['additional_listings_pages'] = $unique_rules;
		}

		if ( isset( $input['singles_contact_form'] ) ) {
			$sanitary_values['singles_contact_form'] = $input['singles_contact_form'];
		}

		return apply_filters( 'invp_options_page_sanitized_values', $sanitary_values, $input );
	}

	/**
	 * Callback function for array_walk_recursive to sanitize taxonomies settings.
	 *
	 * @param  mixed $value
	 * @param  mixed $index
	 * @return bool
	 */
	protected function sanitize_taxonomies_walker( &$value, $index ) {
		$value = boolval( $value );
	}

	/**
	 * Registers JavaScript and CSS file that power the settings page.
	 *
	 * @return void
	 */
	public function scripts_and_styles() {
		$handle = 'invp_page_settings';
		$min    = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		wp_register_script(
			$handle,
			plugins_url( "/js/page-settings${min}.js", INVP_PLUGIN_FILE_PATH ),
			array( 'jquery' ),
			INVP_PLUGIN_VERSION,
			true
		);
		wp_add_inline_script(
			$handle,
			'const invp_page_settings = ' . wp_json_encode(
				array(
					'taxonomies' => Inventory_Presser_Taxonomies::query_vars_array(),
				)
			),
			'before'
		);
		wp_register_style(
			$handle,
			plugins_url( "/css/page-settings${min}.css", INVP_PLUGIN_FILE_PATH ),
			array(),
			INVP_PLUGIN_VERSION
		);
	}

	/**
	 * Outputs some instructions for the Taxonomies settings page.
	 *
	 * @return void
	 */
	public function settings_section_content_taxonomies() {
		printf(
			/* translators: 1. An opening anchor tag, 2. A closing anchor tag */
			esc_html__( 'Configure which taxonomies are active, whether they are shown in the dashboard menu, and for which vehicle types they should appear in the editor. Vehicle types can be added and removed %1$shere%2$s. Deleted types provided by the plugin will be restored during plugin activation.', 'inventory-presser' ),
			'<a href="' . esc_url( admin_url( 'edit-tags.php?taxonomy=type&post_type=' . INVP::POST_TYPE ) ) . '">',
			'</a>'
		);
	}

	/**
	 * Creates a default value for the `taxonomies` key stored in the option.
	 *
	 * @param  array $settings The value of this plugin's option full of settings.
	 * @return array
	 */
	public static function taxonomies_setting_default( $settings = null ) {
		// Backwards compatibility with a deleted setting.
		$show_menu = $settings['show_all_taxonomies'] ?? false;
		// This array is used 10 times below.
		$common = array(
			'active'     => true,
			'admin_menu' => $show_menu,
			'atv'        => true,
			'boat'       => true,
			'bus'        => true,
			'mot'        => true,
			'mow'        => true,
			'oth'        => true,
			'car'        => true,
			'rv'         => true,
			'suv'        => true,
			'trlr'       => true,
			'tru'        => true,
			'van'        => true,
		);
		return array(
			'model-year'      => $common,
			'make'            => $common,
			'model'           => $common,
			'condition'       => $common,
			'type'            => $common,
			'availability'    => $common,
			'drive-type'      => array(
				'active'     => true,
				'admin_menu' => $show_menu,
				'atv'        => true,
				'bus'        => true,
				'mot'        => true,
				'mow'        => true,
				'oth'        => true,
				'car'        => true,
				'rv'         => true,
				'suv'        => true,
				'trlr'       => true,
				'tru'        => true,
				'van'        => true,
			),
			'propulsion-type' => array(
				'active'     => true,
				'admin_menu' => $show_menu,
				'boat'       => true,
			),
			'fuel'            => $common,
			'transmission'    => array(
				'active'     => true,
				'admin_menu' => $show_menu,
				'atv'        => true,
				'bus'        => true,
				'mot'        => true,
				'mow'        => true,
				'oth'        => true,
				'car'        => true,
				'rv'         => true,
				'suv'        => true,
				'trlr'       => true,
				'tru'        => true,
				'van'        => true,
			),
			'cylinders'       => array(
				'active'     => true,
				'admin_menu' => $show_menu,
				'atv'        => true,
				'bus'        => true,
				'mot'        => true,
				'mow'        => true,
				'oth'        => true,
				'car'        => true,
				'rv'         => true,
				'suv'        => true,
				'trlr'       => true,
				'tru'        => true,
				'van'        => true,
			),
			'style'           => $common,
			'colors'          => $common,
			'location'        => $common,
			'condition_boat'  => array(
				'active' => true,
				'boat'   => true,
			),
			'engine_count'    => array(
				'active' => true,
				'boat'   => true,
			),
			'engine_make'     => array(
				'active' => true,
				'boat'   => true,
			),
			'engine_model'    => array(
				'active' => true,
				'boat'   => true,
			),
			'horsepower'      => array(
				'active' => true,
				'boat'   => true,
			),
			'hull_materials'  => array(
				'active' => true,
				'boat'   => true,
			),
		);
	}
}
