<?php
defined( 'ABSPATH' ) || exit;

if ( class_exists( 'Inventory_Presser_Forms_Integration' ) ) {
	/**
	 * Inventory_Presser_Contact_Form_7
	 *
	 * Adds a form tag that creates a vehicle selector or hidden vehicle input on
	 * vehicle singles.
	 */
	class Inventory_Presser_Contact_Form_7 extends Inventory_Presser_Forms_Integration {
		/**
		 * Adds hooks that power the feature.
		 *
		 * @return void
		 */
		public function add_hooks() {
			// Add an [invp_vehicle] form tag.
			add_action( 'wpcf7_init', array( $this, 'add_form_tags' ) );

			// Add special mail tags [invp_adf_timestamp] and [invp_adf_vehicle].
			add_filter( 'wpcf7_special_mail_tags', array( $this, 'add_mail_tags' ), 10, 4 );

			// Add a link to the vehicle before emails are sent.
			add_filter( 'wpcf7_mail_tag_replaced_invp_vehicle', array( $this, 'add_link' ), 10, 4 );
		}

		/**
		 * Adds an [invp_vehicle] form tag to Contact Form 7
		 *
		 * @return void
		 */
		public function add_form_tags() {
			wpcf7_add_form_tag(
				array( 'invp_vehicle', 'invp_vehicle*' ),
				array( $this, 'handler_vehicle' ),
				array( 'name-attr' => true )
			);
		}

		/**
		 * Replaces mail tags with the data we promised.
		 *
		 * @param  string $output
		 * @param  string $name
		 * @param  mixed $html
		 * @param  mixed $mail_tag
		 * @return string
		 */
		public function add_mail_tags( $output, $name, $html, $mail_tag = null ) {
			$name       = preg_replace( '/^wpcf7\./', '_', $name ); // for back-compat.
			$submission = WPCF7_Submission::get_instance();
			if ( ! $submission ) {
				return $output;
			}

			// [invp_adf_timestamp] handler
			if ( 'invp_adf_timestamp' === $name
				&& $timestamp = $submission->get_meta( 'timestamp' )
			) {
				return wp_date( 'c', $timestamp );
			}

			// [invp_adf_vehicle] handler
			if ( 'invp_adf_vehicle' === $name ) {
				if ( ! has_filter( 'wp_mail_content_type', array( $this, 'html_mail_content_type' ) ) ) {
					add_filter( 'wp_mail_content_type', array( $this, 'html_mail_content_type' ) );
				}

				// What name in posted_data is the vehicle field?
				$post_id = $this->get_submission_vehicle_post_id();
				if ( false === $post_id ) {
					return '';
				}

				/*
				<vehicle>
					<id>286535725</id>
					<year>2017</year>
					<make>NISSAN</make>
					<model>ROGUE</model>
					<vin>5N1AT2MV8HC876642</vin>
					<stock>876642-A</stock>
				</vehicle>
				*/
				return sprintf(
					'<vehicle><id>%s</id><year>%s</year><make>%s</make><model>%s</model><vin>%s</vin><stock>%s</stock></vehicle>',
					INVP::get_meta( 'car_id', $post_id ),
					invp_get_the_year( $post_id ),
					invp_get_the_make( $post_id ),
					invp_get_the_model( $post_id ),
					invp_get_the_vin( $post_id ),
					invp_get_the_stock_number( $post_id )
				);
			}

			// [invp_adf_vendor] handler
			if ( 'invp_adf_vendor' === $name ) {
				if ( ! has_filter( 'wp_mail_content_type', array( $this, 'html_mail_content_type' ) ) ) {
					add_filter( 'wp_mail_content_type', array( $this, 'html_mail_content_type' ) );
				}
				// Contact Form 7 default mail tag equivalents.
				$_site_title = wp_specialchars_decode( get_bloginfo( 'name', true ), ENT_QUOTES );
				$_site_url   = get_bloginfo( 'url', true );

				// Get the vehicle.
				$post_id     = $this->get_submission_vehicle_post_id();
				$vendor_name = $_site_title;

				// Does the vehicle have a term in the locations taxonomy?
				$location_terms = get_the_terms( $post_id, 'location' );
				if ( ! empty( $location_terms ) ) {
					$vendor_name             = $location_terms[0]->name;
					$term_id                 = $location_terms[0]->term_id;
					$address_street          = get_term_meta( $term_id, 'address_street', true );
					$address_street_line_two = get_term_meta( $term_id, 'address_street_line_two', true );
					$address_city            = get_term_meta( $term_id, 'address_city', true );
					$address_state           = get_term_meta( $term_id, 'address_state', true );
					$address_zip             = get_term_meta( $term_id, 'address_zip', true );
					$phone                   = get_term_meta( $term_id, 'phone_1_number', true );

					return sprintf(
						'<vendor><vendorname>%1$s</vendorname><contact><name part="full" type="business">%1$s</name><phone type="voice">%2$s</phone><address><street line="1">%3$s</street><street line="2">%4$s</street><city>%5$s</city><regioncode>%6$s</regioncode><postalcode>%7$s</postalcode><url>%8$s</url></address></contact></vendor>',
						$vendor_name,
						$phone,
						$address_street,
						$address_street_line_two,
						$address_city,
						$address_state,
						$address_zip,
						$_site_url
					);

					/*
					<vendor>
						<vendorname>Friday Demo</vendorname>
						<contact>
							<name part="full" type="business">Friday Demo</name>
							<phone type="voice">800-677-7160</phone>
							<address>
								<street line="1">1185 Division Hwy</street>
								<street line="2">Suite B</street>
								<city>Ephrata</city>
								<regioncode>PA</regioncode>
								<postalcode>17522</postalcode>
								<url>https://fridaydemo.wpengine.com</url>
							</address>
						</contact>
					</vendor>
					*/
				} else {
					// This vehicle does not have a term in the location taxonomy.
					return sprintf(
						'<vendor><vendorname>%1$s</vendorname><contact><name part="full" type="business">%1$s</name><url>%2$s</url></contact>',
						$_site_title,
						$_site_url
					);
				}
			}
			return $output;
		}

		/**
		 * Wraps strings like "2020 Toyota Sienna LE, 10329A" with a link in form
		 * submission emails.
		 *
		 * @param  string        $replaced
		 * @param  string        $submitted "2020 Toyota Sienna LE, 10329A"
		 * @param  string        $html
		 * @param  WPCF7_MailTag $mail_tag
		 * @return string
		 */
		public function add_link( $replaced, $submitted, $html, $mail_tag ) {
			// Allow HTML in emails.
			add_filter( 'wp_mail_content_type', array( $this, 'html_mail_content_type' ) );

			$post_id = $this->get_submission_vehicle_post_id( $submitted );
			if ( empty( $post_id ) ) {
				return $replaced;
			}

			return sprintf( '<a href="%s">%s</a>', get_permalink( $post_id ), $replaced );
		}

		/**
		 * Find the post ID of the vehicle that was submitted in the first
		 * [invp_vehicle] field on this form. Returns false if not found.
		 *
		 * @param string $vehicle_field_value The submitted value in the form's [invp_vehicle] field.
		 * @return int|false
		 */
		public function get_submission_vehicle_post_id( $vehicle_field_value = '' ) {
			if ( '' === $vehicle_field_value ) {
				$submission = WPCF7_Submission::get_instance();
				// What name in posted_data is the vehicle field?
				foreach ( $submission->get_contact_form()->scan_form_tags() as $form_tag ) {
					if ( 'invp_vehicle' !== $form_tag->basetype ) {
						continue;
					}
					$vehicle_field_value = $submission->get_posted_data()[ $form_tag->name ];
					break;
				}
			}
			return $this->extract_post_id_from_value( $vehicle_field_value );
		}

		/**
		 * handler_vehicle
		 *
		 * @param  object $tag
		 * @return void
		 */
		public function handler_vehicle( $tag ) {
			$validation_error = wpcf7_get_validation_error( $tag->name );
			$atts             = array(
				'id' => '',
			);

			if ( ! empty( $tag->options ) ) {
				array_walk(
					$tag->options,
					function ( $item ) use ( &$atts ) {
						list( $key, $val ) = explode( ':', $item );
						$atts[ $key ]      = $val;
					}
				);
			}

			// Are we on a vdp? return a hidden field.
			if ( is_singular( INVP::POST_TYPE ) ) {
				$input_atts = array(
					'type'  => 'hidden',
					'name'  => $tag->name,
					'value' => $this->prepare_value(),
				);
				if ( ! empty( $atts['id'] ) ) {
					$input_atts['id'] = $atts['id'];
				}
				$html = sprintf(
					'<input %s />',
					wpcf7_format_atts( $input_atts )
				);
				return apply_filters( 'invp_cf7_field_vehicle_html', $html . $validation_error, $atts );
			}

			/**
			 * Here's a query to get the post IDs of the vehicles we want
			 * in the order we want. The reason we are not using get_posts
			 * is because we want to sort by 3 post meta values
			 */
			global $wpdb;
			$post_ids = $wpdb->get_col(
				$wpdb->prepare(
					"
				SELECT 		DISTINCT ID
				FROM		$wpdb->posts
							LEFT JOIN $wpdb->postmeta meta1 ON $wpdb->posts.ID = meta1.post_id
							LEFT JOIN $wpdb->postmeta meta2 ON $wpdb->posts.ID = meta2.post_id
							LEFT JOIN $wpdb->postmeta meta3 ON $wpdb->posts.ID = meta3.post_id
				WHERE 		post_type = %s
							AND post_status = 'publish'
							AND meta1.meta_key = %s
							AND meta2.meta_key = %s
							AND meta3.meta_key = %s
				ORDER BY	meta1.meta_value DESC,
							meta2.meta_value ASC,
							meta3.meta_value ASC
				",
					INVP::POST_TYPE,
					apply_filters( 'invp_prefix_meta_key', 'year' ),
					apply_filters( 'invp_prefix_meta_key', 'make' ),
					apply_filters( 'invp_prefix_meta_key', 'model' )
				)
			);

			// no results? no HTML.
			if ( empty( $post_ids ) ) {
				return apply_filters( 'invp_cf7_field_vehicle_html', '' . $validation_error, $atts );
			}

			// build a drop down select.
			$select_atts = array(
				'name'  => $tag->name,
				'class' => wpcf7_form_controls_class( 'select' ),
			);
			if ( ! empty( $atts['id'] ) ) {
				$select_atts['id'] = $atts['id'];
			}
			$html = sprintf(
				'<span class="wpcf7-form-control-wrap" data-name="%s"><select %s><option value="">%s</option>',
				esc_attr( $tag->name ),
				wpcf7_format_atts( $select_atts ),
				__( 'Please choose a vehicle...', 'inventory-presser' )
			);
			foreach ( $post_ids as $post_id ) {
				// is this vehicle sold?
				if ( has_term( 'sold', 'availability', $post_id ) ) {
					continue;
				}
				$meta = get_metadata( 'post', $post_id );

				// label is "2005 Subaru Baja Turbo, Blue, #12022".
				$html .= sprintf(
					'<option value="%s" %s>%s %s %s',
					esc_attr( $this->prepare_value( $post_id ) ),
					isset( $_REQUEST['v'] ) ? selected( $post_id, $_REQUEST['v'], false ) : '',
					esc_html( $meta[ apply_filters( 'invp_prefix_meta_key', 'year' ) ][0] ),
					esc_html( $meta[ apply_filters( 'invp_prefix_meta_key', 'make' ) ][0] ),
					esc_html( $meta[ apply_filters( 'invp_prefix_meta_key', 'model' ) ][0] )
				);

				if ( isset( $meta[ apply_filters( 'invp_prefix_meta_key', 'trim' ) ][0] ) ) {
					$html .= ' ' . $meta[ apply_filters( 'invp_prefix_meta_key', 'trim' ) ][0];
				}

				if ( isset( $meta[ apply_filters( 'invp_prefix_meta_key', 'color' ) ][0] ) ) {
					$html .= ', ' . $meta[ apply_filters( 'invp_prefix_meta_key', 'color' ) ][0];
				}

				$html .= sprintf(
					', &#35;%s</option>',
					$meta[ apply_filters( 'invp_prefix_meta_key', 'stock_number' ) ][0]
				);
			}
			return apply_filters( 'invp_cf7_field_vehicle_html', $html . '</select></span>' . $validation_error, $atts );
		}

		/**
		 * Returns the string 'text/html'
		 *
		 * @param  string $type
		 * @return string
		 */
		public static function html_mail_content_type( $type ) {
			return 'text/html';
		}

		/**
		 * prepare_value
		 *
		 * @param  int $post_id
		 * @return string
		 */
		protected function prepare_value( $post_id = null ) {
			$post_id = $post_id ?? get_the_ID();
			$value   = trim(
				sprintf(
					'%s %s %s',
					invp_get_the_year( $post_id ),
					invp_get_the_make( $post_id ),
					invp_get_the_model( $post_id )
				)
			);
			$trim    = invp_get_the_trim( $post_id );
			if ( ! empty( $trim ) ) {
				$value .= ' ' . $trim;
			}
			$stock_number = invp_get_the_stock_number( $post_id );
			if ( ! empty( $stock_number ) ) {
				$value .= ', ' . $stock_number;
			}
			return $value;
		}
	}
}
