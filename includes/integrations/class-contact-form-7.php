<?php
defined( 'ABSPATH' ) || exit;

/**
 * Inventory_Presser_Contact_Form_7
 * 
 * Adds a form tag that creates a vehicle selector or hidden vehicle input on
 * vehicle singles.
 */
class Inventory_Presser_Contact_Form_7 {
	public function add_hooks() {
		//Add an [invp_vehicle] form tag
		add_action( 'wpcf7_init', array( $this, 'add_form_tags' ) );

		//Add special mail tags [invp_adf_timestamp] and [invp_adf_vehicle]
		add_filter( 'wpcf7_special_mail_tags', array( $this, 'add_mail_tags' ), 10, 4 );

		//Add a link to the vehicle before emails are sent
		add_filter( 'wpcf7_mail_tag_replaced_invp_vehicle', array( $this, 'add_link' ), 10, 4 );
	}

	/**
	 * add_form_tags
	 *
	 * Adds an [invp_vehicle] form tag to Contact Form 7
	 *
	 * @return void
	 */
	public function add_form_tags() {
		wpcf7_add_form_tag(
			array( 'invp_vehicle', 'invp_vehicle*', ),
			array( $this, 'handler_vehicle' ),
			array( 'name-attr' => true )
		);
	}

	public function add_mail_tags( $output, $name, $html, $mail_tag = null ) {
		$name = preg_replace( '/^wpcf7\./', '_', $name ); // for back-compat
		$submission = WPCF7_Submission::get_instance();
		if ( ! $submission ) {
			return $output;
		}

		if ( 'invp_adf_timestamp' == $name
			&& $timestamp = $submission->get_meta('timestamp') 
		) {
			return wp_date('c', $timestamp);
		}

		if ( 'invp_adf_vehicle' == $name ) {
			add_filter('wp_mail_content_type', array( $this, 'html_mail_content_type' ));

			//What name in posted_data is the vehicle field?
			foreach( $submission->get_contact_form()->scan_form_tags() as $form_tag ) {
				if( $form_tag->basetype != 'invp_vehicle' ) {
					continue;
				}

				$post_id = $this->extract_post_id_from_value( $submission->get_posted_data()[$form_tag->name] );
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
		}
		return $output;
	}

	/**
	 * add_link
	 * 
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
		//Allow HTML in emails
		add_filter( 'wp_mail_content_type', array( $this, 'html_mail_content_type' ) );

		$post_id = $this->extract_post_id_from_value( $submitted );
		if ( empty( $post_id ) ) {
			return $replaced;
		}

		return sprintf( '<a href="%s">%s</a>', get_permalink( $post_id ), $replaced );
	}

	protected function extract_post_id_from_value( $value ) {
		//submitted "2020 Toyota Sienna LE, 10329A"
		$pieces = explode( ', ', $value );
		if ( 1 == sizeof( $pieces ) ) {
			//delimiter not found
			return '';
		}
		$stock_number = $pieces[sizeof( $pieces )-1];
		$post_ids = get_posts(
			array(
				'fields'         => 'ids',
				'meta_key'       => apply_filters( 'invp_prefix_meta_key', 'stock_number' ),
				'meta_value'     => $stock_number,
				'post_status'    => 'publish',
				'post_type'      => INVP::POST_TYPE,
				'posts_per_page' => 1,
			) 
		);

		if ( empty( $post_ids ) || ! is_array( $post_ids ) ) {
			return '';
		}

		return $post_ids[0];
	}

	public function handler_vehicle( $tag ) {
		$validation_error = wpcf7_get_validation_error( $tag->name );
		$atts = array(
			'id' => '',
		);

		if ( ! empty( $tag->options ) ) {
			array_walk(
				$tag->options, function ( $item ) use ( &$atts ) {
					list( $key, $val ) = explode( ':', $item );
					$atts[$key] = $val;
				} 
			);
		}

		//are we on a vdp? return a hidden field
		if ( is_singular( INVP::POST_TYPE ) ) {
			$input_atts = array(
				'type'  => 'hidden',
				'name'  => $tag->name,
				'value' => $this->prepare_value()
			);
			if ( ! empty( $atts['id'] ) ) {
				$input_atts['id'] = $atts['id'];
			}
			$html = sprintf( 
				'<input %s />',
				wpcf7_format_atts( $input_atts )
			);
			return apply_filters( 'invp_cf7_field_vehicle_html', $html . $validation_error, $atts) ;
		}

		/**
		 * Here's a query to get the post IDs of the vehicles we want
		 * in the order we want. The reason we are not using get_posts
		 * is because we want to sort by 3 post meta values
		 */
		global $wpdb;
		$post_IDs = $wpdb->get_col(
			$wpdb->prepare(
				"
			SELECT 		DISTINCT ID
			FROM		$wpdb->posts
						LEFT JOIN $wpdb->postmeta meta1 ON $wpdb->posts.ID = meta1.post_id
						LEFT JOIN $wpdb->postmeta meta2 ON $wpdb->posts.ID = meta2.post_id
						LEFT JOIN $wpdb->postmeta meta3 ON $wpdb->posts.ID = meta3.post_id
			WHERE 		post_type = '%s'
						AND post_status = 'publish'
						AND meta1.meta_key = '%s'
						AND meta2.meta_key = '%s'
						AND meta3.meta_key = '%s'
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

		//no results? no HTML
		if ( empty( $post_IDs ) ) {
			return apply_filters( 'invp_cf7_field_vehicle_html', '' . $validation_error, $atts );
		}

		//build a drop down select
		$select_atts = array(
			'name' => $tag->name,
			'class' => wpcf7_form_controls_class( 'select' )
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
		foreach ( $post_IDs as $post_ID ) {
			//is this vehicle sold?
			if ( has_term( 'sold', 'availability', $post_ID ) ) {
				continue;
			}
			$meta = get_metadata( 'post', $post_ID );

			//label is "2005 Subaru Baja Turbo, Blue, #12022"
			$html .= sprintf(
				'<option value="%s" %s>%s %s %s',
				esc_attr( $this->prepare_value( $post_ID ) ),
				isset( $_REQUEST['v'] ) ? selected( $post_ID, $_REQUEST['v'], false ) : '',
				esc_html( $meta[apply_filters( 'invp_prefix_meta_key', 'year' )][0]),
				esc_html( $meta[apply_filters( 'invp_prefix_meta_key', 'make' )][0]),
				esc_html( $meta[apply_filters( 'invp_prefix_meta_key', 'model' )][0])
			);

			if(isset($meta[apply_filters( 'invp_prefix_meta_key', 'trim' )][0]) ) {
				$html .= ' ' . $meta[apply_filters( 'invp_prefix_meta_key', 'trim' )][0];
			}

			if(isset($meta[apply_filters( 'invp_prefix_meta_key', 'color' )][0]) ) {
				$html .= ', ' . $meta[apply_filters( 'invp_prefix_meta_key', 'color' )][0];
			}

			$html .= sprintf(
				', &#35;%s</option>',
				$meta[apply_filters( 'invp_prefix_meta_key', 'stock_number' )][0]
			);
		}
		return apply_filters( 'invp_cf7_field_vehicle_html', $html . '</select></span>' . $validation_error, $atts );
	}

	/**
	 * html_mail_content_type
	 * 
	 * Returns the string 'text/html'
	 *
	 * @param  string $type
	 * @return string
	 */
	public static function html_mail_content_type( $type ) {
		return 'text/html';
	}

	protected function prepare_value( $post_ID = null ) {
		$post_ID = $post_ID ?? get_the_ID();
		$value = trim(
			sprintf(
				'%s %s %s',
				invp_get_the_year( $post_ID ),
				invp_get_the_make( $post_ID ),
				invp_get_the_model( $post_ID )
			) 
		);
		if ( $trim = invp_get_the_trim( $post_ID ) ) {
			$value .= ' ' . $trim;
		}
		if( $stock_number = invp_get_the_stock_number( $post_ID ) ) {
			$value .= ', ' . $stock_number;
		}
		return $value;
	}
}
