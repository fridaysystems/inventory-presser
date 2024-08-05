<?php
/**
 * Classic Editor
 *
 * @package inventory-presser
 */

defined( 'ABSPATH' ) || exit;

/**
 * Inventory_Presser_Classic_Editor
 *
 * Adds features to the Classic Editor.
 *
 * @since      14.10.0
 * @package    inventory-presser
 * @subpackage inventory-presser/includes/integrations
 * @author     Corey Salzano <corey@friday.systems>
 */
class Inventory_Presser_Classic_Editor {

	const NONCE_DELETE_ALL_MEDIA = 'invp_delete_all_media';

	/**
	 * Adds hooks
	 *
	 * @return void
	 */
	public function add_hooks() {
		add_action( 'current_screen', array( $this, 'add_hooks_current_screen' ) );
	}

	public function add_hooks_current_screen() {
		// Are we editing a vehicle in the Classic Editor?
		if ( ! is_plugin_active( 'classic-editor/classic-editor.php' ) ) {
			// No. Classic Editor plugin is not active.
			return;
		}
		// Is the Block Editor enabled as a default, though?
		$block_editor_active = array( 'no-replace', 'block' );
		if ( in_array( get_option( 'classic-editor-replace' ), $block_editor_active, true ) ) {
			// Yes.
			return;
		}

		global $pagenow, $current_screen;
		if ( ! in_array( $pagenow, array( 'post-new.php', 'post.php' ), true ) || INVP::POST_TYPE !== $current_screen->post_type ) {
			// No. Not adding or editing editing a vehicle.
			return;
		}

		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes_to_cpt' ) );

		// Move all "advanced" meta boxes above the default editor.
		// https://wordpress.stackexchange.com/a/88103.
		add_action( 'edit_form_after_title', array( $this, 'move_advanced_meta_boxes' ) );

		// Move the "Tags" metabox below the meta boxes for vehicle custom taxonomies.
		add_action( 'add_meta_boxes', array( $this, 'move_tags_meta_box' ), 0 );

		// Load our scripts.
		add_action( 'admin_enqueue_scripts', array( $this, 'scripts_and_styles' ) );

		// Add some content next to the "Add Media" button.
		add_action( 'media_buttons', array( $this, 'annotate_add_media_button' ) );

		// Define an AJAX handler for the 'Delete All Media' button.
		add_filter( 'wp_ajax_delete_all_post_attachments', array( $this, 'delete_all_post_attachments' ) );

		// Make our Add Media button annotation available from an AJAX call.
		add_action( 'wp_ajax_output_add_media_button_annotation', array( $this, 'output_add_media_button_annotation' ) );
	}

	/**
	 * Adds meta boxes to the editor when editing vehicles.
	 *
	 * @return void
	 */
	public function add_meta_boxes_to_cpt() {
		// Add a meta box to the New/Edit post page.
		add_meta_box( 'vehicle-meta', 'Attributes', array( $this, 'meta_box_html_vehicle' ), INVP::POST_TYPE, 'normal', 'high' );

		// And another for prices.
		add_meta_box( 'prices-meta', 'Prices', array( $this, 'meta_box_html_prices' ), INVP::POST_TYPE, 'normal', 'high' );

		// Add another meta box to the New/Edit post page.
		add_meta_box( 'options-meta', 'Optional equipment', array( $this, 'meta_box_html_options' ), INVP::POST_TYPE, 'normal', 'high' );

		// Add a meta box to the side column for a featured vehicle checkbox.
		add_meta_box( 'featured', 'Featured Vehicle', array( $this, 'meta_box_html_featured' ), INVP::POST_TYPE, 'side', 'low' );
	}

	/**
	 * Adds HTML near the Add Media button on the classic editor.
	 *
	 * @param  mixed $editor_id
	 * @return void
	 */
	public function annotate_add_media_button( $editor_id ) {
		if ( 'content' !== $editor_id ) {
			return;
		}

		printf(
			'%s<span id="media-annotation" class="annotation">%s</span>',
			$this->create_delete_all_post_attachments_button(),
			$this->create_add_media_button_annotation()
		);
	}

	/**
	 * Computes the number of attachments on vehicles so the number can be
	 * shown in the classic editor near the Add Media button.
	 *
	 * @return string
	 */
	protected function create_add_media_button_annotation() {
		global $post;
		$the_post = $post;
		if ( ! is_object( $the_post ) && isset( $_POST['post_ID'] ) ) {
			/**
			 * This function is being called via AJAX and the
			 * post_id is incoming, so get the post
			 */
			$the_post = get_post( intval( $_POST['post_ID'] ) );
		}

		if ( INVP::POST_TYPE !== $the_post->post_type ) {
			return '';
		}

		$attachments = get_children(
			array(
				'post_parent'    => $the_post->ID,
				'post_type'      => 'attachment',
				'posts_per_page' => apply_filters( 'invp_query_limit', 1000, __METHOD__ ),
			)
		);
		$counts      = array(
			'image' => 0,
			'video' => 0,
			'text'  => 0,
			'PDF'   => 0,
			'other' => 0,
		);
		foreach ( $attachments as $attachment ) {
			switch ( $attachment->post_mime_type ) {
				case 'image/jpeg':
				case 'image/png':
				case 'image/gif':
					++$counts['image'];
					break;
				case 'video/mpeg':
				case 'video/mp4':
				case 'video/quicktime':
					++$counts['video'];
					break;
				case 'text/csv':
				case 'text/plain':
				case 'text/xml':
					++$counts['text'];
					break;
				case 'application/pdf':
					++$counts['PDF'];
					break;
				default:
					++$counts['other'];
					break;
			}
		}
		if ( 0 < ( $counts['image'] + $counts['video'] + $counts['text'] + $counts['PDF'] + $counts['other'] ) ) {
			$note = '';
			foreach ( $counts as $key => $count ) {
				if ( 0 < $count ) {
					if ( '' !== $note ) {
						$note .= ', ';
					}
					$note .= $count . ' ' . $key . ( 1 !== $count ? 's' : '' );
				}
			}
			return $note . ' ';
		}
		return '0 ' . __( 'photos', 'inventory-presser' ) . ' ';
	}

	/**
	 * Creates HTML that renders a button that says "Delete All Media" that will
	 * be placed near the Add Media button.
	 *
	 * @return string
	 */
	protected function create_delete_all_post_attachments_button() {
		global $post;
		if ( ! is_object( $post ) || ! isset( $post->ID ) ) {
			return '';
		}
		// does this post have attachments?
		$the_post = get_post( $post->ID );
		if ( INVP::POST_TYPE !== $the_post->post_type ) {
			return '';
		}
		$attachments = get_children(
			array(
				'post_parent'    => $the_post->ID,
				'post_type'      => 'attachment',
				'posts_per_page' => -1,
			)
		);
		if ( 0 === count( $attachments ) ) {
			return '';
		}
		return sprintf(
			'<button type="button" id="delete-media-button" class="button" onclick="delete_all_post_attachments();">'
			. '<span class="wp-media-buttons-icon"></span> %s</button>',
			__( 'Delete All Media', 'inventory-presser' )
		);
	}

	/**
	 * Deletes all a post's attachments. The callback behind the Delete All
	 * Media button.
	 *
	 * @return void
	 */
	public function delete_all_post_attachments() {
		if ( empty( $_POST['_ajax_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['_ajax_nonce'] ), self::NONCE_DELETE_ALL_MEDIA ) ) {
			return;
		}

		$post_id = isset( $_POST['post_ID'] ) ? wp_unslash( $_POST['post_ID'] ) : 0;

		if ( ! isset( $post_id ) ) {
			return;
		} elseif ( 0 === $post_id ) {
			return;
		} elseif ( is_array( $post_id ) ) {
			return;
		} else {
			INVP::delete_attachments( $post_id );
		}
	}

	/**
	 * Includes JavaScripts and stylesheets that power our changes to the
	 * dashboard.
	 *
	 * @param  string $hook
	 * @return void
	 */
	public function scripts_and_styles( $hook ) {
		$handle = 'invp-classic-editor';
		if ( ! wp_script_is( $handle, 'registered' ) ) {
			Inventory_Presser_Plugin::include_scripts_and_styles();
		}
		$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		wp_enqueue_style(
			$handle,
			plugins_url( "/css/classic-editor{$min}.css", INVP_PLUGIN_FILE_PATH ),
			array(),
			INVP_PLUGIN_VERSION
		);
		wp_enqueue_script( $handle );

		// Provide data to JavaScript for the editor.
		wp_add_inline_script(
			$handle,
			'const invp_classic_editor = ' . wp_json_encode(
				array(
					'delete_all_media_nonce' => wp_create_nonce( self::NONCE_DELETE_ALL_MEDIA ),
				)
			),
			'before'
		);
	}

	/**
	 * Creates an editor meta box to help users mark vehicles as featured.
	 *
	 * @param  WP_Post $post
	 * @return void
	 */
	public function meta_box_html_featured( $post ) {
		$meta_key = apply_filters( 'invp_prefix_meta_key', 'featured' );
		printf(
			'<input type="checkbox" id="%1$s" name="%1$s" value="1"%2$s><label for="%1$s">%3$s</label>',
			esc_attr( $meta_key ),
			checked( '1', INVP::get_meta( $meta_key, $post->ID ), false ),
			esc_html__( 'Featured in Slideshows', 'inventory-presser' )
		);
	}

	/**
	 * Creates a meta box to help the user see and manage vehicle options while
	 * editing a vehicle post.
	 *
	 * @param  WP_Post $post
	 * @return void
	 */
	public function meta_box_html_options( $post ) {
		$options = apply_filters(
			'invp_default_options',
			array(
				__( '3rd Row Seats', 'inventory-presser' ),
				__( 'Air Bags', 'inventory-presser' ),
				__( 'Air Conditioning', 'inventory-presser' ),
				__( 'Alloy Wheels', 'inventory-presser' ),
				__( 'Aluminum Wheels', 'inventory-presser' ),
				__( 'AM/FM Stereo', 'inventory-presser' ),
				__( 'Anti-lock Brakes', 'inventory-presser' ),
				__( 'Backup Camera', 'inventory-presser' ),
				__( 'Bed Cap', 'inventory-presser' ),
				__( 'Bluetooth, Hands Free', 'inventory-presser' ),
				__( 'Cassette', 'inventory-presser' ),
				__( 'CD Player', 'inventory-presser' ),
				__( 'Cell or Intergrated Cell Phone', 'inventory-presser' ),
				__( 'Cloth Seats', 'inventory-presser' ),
				__( 'Conversion Package', 'inventory-presser' ),
				__( 'Convertible', 'inventory-presser' ),
				__( 'Cooled Seats', 'inventory-presser' ),
				__( 'Cruise Control', 'inventory-presser' ),
				__( 'Custom Paint', 'inventory-presser' ),
				__( 'Disability Equipped', 'inventory-presser' ),
				__( 'Dual Sliding Doors', 'inventory-presser' ),
				__( 'DVD Player', 'inventory-presser' ),
				__( 'Extended Cab', 'inventory-presser' ),
				__( 'Fog Lights', 'inventory-presser' ),
				__( 'Heated Seats', 'inventory-presser' ),
				__( 'Keyless Entry', 'inventory-presser' ),
				__( 'Leather Seats', 'inventory-presser' ),
				__( 'Lift Kit', 'inventory-presser' ),
				__( 'Long Bed', 'inventory-presser' ),
				__( 'Memory Seat(s)', 'inventory-presser' ),
				__( 'Moon Roof', 'inventory-presser' ),
				__( 'Multi-zone Climate Control', 'inventory-presser' ),
				__( 'Navigation System', 'inventory-presser' ),
				__( 'Oversize Off Road Tires', 'inventory-presser' ),
				__( 'Portable Audio Connection', 'inventory-presser' ),
				__( 'Power Brakes', 'inventory-presser' ),
				__( 'Power Lift Gate', 'inventory-presser' ),
				__( 'Power Locks', 'inventory-presser' ),
				__( 'Power Seats', 'inventory-presser' ),
				__( 'Power Steering', 'inventory-presser' ),
				__( 'Power Windows', 'inventory-presser' ),
				__( 'Premium Audio', 'inventory-presser' ),
				__( 'Premium Wheels', 'inventory-presser' ),
				__( 'Privacy Glass', 'inventory-presser' ),
				__( 'Quad Seating', 'inventory-presser' ),
				__( 'Rear Air Bags', 'inventory-presser' ),
				__( 'Rear Air Conditioning', 'inventory-presser' ),
				__( 'Rear Defroster', 'inventory-presser' ),
				__( 'Rear Heat', 'inventory-presser' ),
				__( 'Refrigerator', 'inventory-presser' ),
				__( 'Roof Rack', 'inventory-presser' ),
				__( 'Running Boards', 'inventory-presser' ),
				__( 'Satellite Radio', 'inventory-presser' ),
				__( 'Security System', 'inventory-presser' ),
				__( 'Short Bed', 'inventory-presser' ),
				__( 'Side Air Bags', 'inventory-presser' ),
				__( 'Skid Plate(s)', 'inventory-presser' ),
				__( 'Snow Plow', 'inventory-presser' ),
				__( 'Spoiler', 'inventory-presser' ),
				__( 'Sport Package', 'inventory-presser' ),
				__( 'Step Side Bed', 'inventory-presser' ),
				__( 'Steering Wheel Controls', 'inventory-presser' ),
				__( 'Styled Steel Wheels', 'inventory-presser' ),
				__( 'Sunroof', 'inventory-presser' ),
				__( 'Supercharger', 'inventory-presser' ),
				__( 'Tilt Steering Wheel', 'inventory-presser' ),
				__( 'Tonneau Cover', 'inventory-presser' ),
				__( 'Topper', 'inventory-presser' ),
				__( 'Tow Package', 'inventory-presser' ),
				__( 'Traction Control', 'inventory-presser' ),
				__( 'Trailer Hitch', 'inventory-presser' ),
				__( 'Turbo', 'inventory-presser' ),
				__( 'Two Tone Paint', 'inventory-presser' ),
				__( 'Wheelchair Access', 'inventory-presser' ),
				__( 'Wide Tires', 'inventory-presser' ),
				__( 'Winch', 'inventory-presser' ),
				__( 'Wire Wheels', 'inventory-presser' ),
				__( 'Xenon Headlights', 'inventory-presser' ),
			)
		);

		// Turn the array into an associative array with value false for all.
		$options = array_fill_keys( $options, false );

		$options_array = invp_get_the_options( $post->ID );
		if ( is_array( $options_array ) ) {
			foreach ( $options_array as $option ) {
				$options[ $option ] = true;
			}
		}
		// Sort the array by key.
		ksort( $options );
		// Output a bunch of checkboxes.
		$html = '<div class="list-with-columns"><ul class="optional-equipment">';
		foreach ( $options as $key => $value ) {
			// Element IDs cannot contain slashes, spaces or parentheses.
			$id    = 'option-' . preg_replace( '/\/\(\)/i', '', str_replace( ' ', '_', $key ) );
			$html .= sprintf(
				'<li><input type="checkbox" id="%1$s" name="inventory_presser_options_array[]" value="%2$s"%3$s><label for="%1$s">%2$s</label></li>',
				esc_attr( $id ),
				esc_attr( $key ),
				checked( true, $value, false )
			);
		}
		echo $html . '</ul></div>';
	}

	/**
	 * Creates a meta box to help users manage a vehicles prices in the editor.
	 *
	 * @param  WP_Post $post
	 * @param  mixed   $meta_box
	 * @return void
	 */
	public function meta_box_html_prices( $post, $meta_box ) {
		$prices = array(
			'price'        => __( 'Price', 'inventory-presser' ),
			'msrp'         => __( 'MSRP', 'inventory-presser' ),
			'down_payment' => __( 'Down payment', 'inventory-presser' ),
			'payment'      => __( 'Payment', 'inventory-presser' ),
		);

		echo '<table class="form-table"><tbody>';
		foreach ( $prices as $key => $label ) {
			$meta_key = apply_filters( 'invp_prefix_meta_key', $key );

			printf(
				'<tr><th scope="row"><label for="%1$s">%2$s</label></th>'
				. '<td><input type="text" name="%1$s" value="%3$s" onkeypress="return is_number(event)"></td></tr>',
				esc_attr( $meta_key ),
				esc_html( $label ),
				esc_attr( INVP::get_meta( $key, $post->ID ) )
			);
		}

		// Payment frequency is a drop-down.
		printf(
			'<tr><th scope="row"><label for="%1$s">Payment frequency</label></th>'
			. '<td><select name="%1$s"><option></option>',
			esc_attr( $meta_key )
		);

		$frequencies = apply_filters(
			'invp_default_payment_frequencies',
			array(
				'Monthly'      => 'monthly',
				'Weekly'       => 'weekly',
				'Bi-weekly'    => 'biweekly',
				'Semi-monthly' => 'semimonthly',
			)
		);
		foreach ( $frequencies as $key => $value ) {
			printf(
				'<option value="%s"%s>%s</option>',
				$value,
				selected( invp_get_the_payment_frequency( $post->ID ), $value, false ),
				$key
			);
		}
		echo '</select></td></tr>'
		. '</tbody></table>';
	}

	/**
	 * Creates a meta box to help the user manage the bulk of the meta fields
	 * that define a vehicle.
	 *
	 * @param  WP_Post $post
	 * @param  mixed   $meta_box
	 * @return void
	 */
	public function meta_box_html_vehicle( $post, $meta_box ) {
		// Get the post meta and flatten some of our keys into an array.
		$custom = get_post_custom( $post->ID );
		$meta   = array();
		$keys   = array(
			'body_style',
			'color',
			'engine',
			'interior_color',
			'make',
			'model',
			'odometer',
			'stock_number',
			'trim',
			'vin',
			'year',
			'youtube',
			// Boat fields.
			'beam',
			'condition_boat',
			'draft',
			'engine_count',
			'engine_make',
			'engine_model',
			'horsepower',
			'hull_material',
			'length',
		);

		foreach ( $keys as $key ) {
			$meta[ $key ] = ( isset( $custom[ apply_filters( 'invp_prefix_meta_key', $key ) ] ) ? $custom[ apply_filters( 'invp_prefix_meta_key', $key ) ][0] : '' );
		}

		printf(
			'<table class="form-table"><tbody>'

			// VIN.
			. '<tr><th scope="row"><label for="%1$s">%2$s</label></th>'
			. '<td>%3$s</td>'

			// Stock number.
			. '<tr><th scope="row"><label for="%4$s">%5$s</label></th>'
			. '<td><input type="text" name="%4$s" value="%6$s"></td>'

			// Year.
			. '<tr><th scope="row"><label for="%7$s">%8$s</label></th>'
			. '<td><select name="%7$s"><option></option>',
			/*  1 */ esc_attr( apply_filters( 'invp_prefix_meta_key', 'vin' ) ),
			/*  2 */ esc_html__( 'VIN', 'inventory-presser' ),
			/*  3 */ apply_filters(
				'invp_edit_control_vin',
				sprintf(
					'<input type="text" name="%s" maxlength="17" value="%s">',
					esc_attr( apply_filters( 'invp_prefix_meta_key', 'vin' ) ),
					esc_attr( $meta['vin'] )
				)
			),
			/*  4 */ esc_attr( apply_filters( 'invp_prefix_meta_key', 'stock_number' ) ),
			/*  5 */ esc_html__( 'Stock number', 'inventory-presser' ),
			/*  6 */ esc_attr( $meta['stock_number'] ),
			/*  7 */ esc_attr( apply_filters( 'invp_prefix_meta_key', 'year' ) ),
			/*  8 */ esc_html__( 'Year', 'inventory-presser' )
		);

		for ( $y = gmdate( 'Y' ) + 2; $y >= 1920; $y-- ) {
			printf(
				'<option%s>%s</option>',
				selected( $y, $meta['year'], false ),
				esc_html( $y )
			);
		}

		printf(
			'</select></td></tr>'

			// Make.
			. '<tr><th scope="row"><label for="%1$s">%2$s</label></th>'
			. '<td><input type="text" name="%1$s" value="%3$s"></td></tr>'

			// Model.
			. '<tr><th scope="row"><label for="%4$s">%5$s</label></th>'
			. '<td><input type="text" name="%4$s" value="%6$s"></td></tr>'

			// Trim level.
			. '<tr><th scope="row"><label for="%7$s">%8$s</label></th>'
			. '<td><input type="text" name="%7$s" value="%9$s"></td></tr>'

			// Engine.
			. '<tr><th scope="row"><label for="%10$s">%11$s</label></th>'
			. '<td><input type="text" name="%10$s" value="%12$s"></td></tr>'

			// Body style.
			. '<tr><th scope="row"><label for="%13$s">%14$s</label></th>'
			. '<td><input type="text" name="%13$s" id="%13$s" value="%15$s">'

			. '<select name="%13$s_hidden" id="%13$s_hidden">',
			/*  1 */ esc_attr( apply_filters( 'invp_prefix_meta_key', 'make' ) ),
			/*  2 */ esc_html__( 'Make', 'inventory-presser' ),
			/*  3 */ esc_attr( $meta['make'] ),
			/*  4 */ esc_attr( apply_filters( 'invp_prefix_meta_key', 'model' ) ),
			/*  5 */ esc_html__( 'Model', 'inventory-presser' ),
			/*  6 */ esc_attr( $meta['model'] ),
			/*  7 */ esc_attr( apply_filters( 'invp_prefix_meta_key', 'trim' ) ),
			/*  8 */ esc_html__( 'Trim', 'inventory-presser' ),
			/*  9 */ esc_attr( $meta['trim'] ),
			/* 10 */ esc_attr( apply_filters( 'invp_prefix_meta_key', 'engine' ) ),
			/* 11 */ esc_html__( 'Engine', 'inventory-presser' ),
			/* 12 */ esc_attr( $meta['engine'] ),
			/* 13 */ esc_attr( apply_filters( 'invp_prefix_meta_key', 'body_style' ) ),
			/* 14 */ esc_html__( 'Body style', 'inventory-presser' ),
			/* 15 */ esc_attr( $meta['body_style'] )
		);

		$boat_styles = apply_filters(
			'invp_default_boat_styles',
			array(
				__( 'Bass boat', 'inventory-presser' ),
				__( 'Bow Rider', 'inventory-presser' ),
				__( 'Cabin Cruiser', 'inventory-presser' ),
				__( 'Center Console', 'inventory-presser' ),
				__( 'Cuddy Cabin', 'inventory-presser' ),
				__( 'Deck boat', 'inventory-presser' ),
				__( 'Performance', 'inventory-presser' ),
				__( 'Pontoon', 'inventory-presser' ),
			)
		);
		foreach ( $boat_styles as $s ) {
			printf(
				'<option%s>%s</option>',
				selected( $s, $meta['body_style'] ),
				esc_html( $s )
			);
		}

		printf(
			'</select></td></tr>'

			// Color.
			. '<tr><th scope="row"><label for="%1$s">%2$s</label></th>'
			. '<td><input type="text" name="%1$s" value="%3$s"></td></tr>'

			// Interior color.
			. '<tr><th scope="row"><label for="%4$s">%5$s</label></th>'
			. '<td><input type="text" name="%4$s" value="%6$s"></td></tr>'

			// Odometer.
			. '<tr><th scope="row"><label for="%7$s">%8$s</label></th>'
			. '<td><input type="text" name="%7$s" value="%9$s">'
			. ' <span class="invp_odometer_units">%10$s</span></td></tr>'

			// YouTube.
			. '<tr><th scope="row"><label for="%11$s">%12$s</label></th>'
			. '<td><input type="text" name="%11$s" value="%13$s"></td></tr>',
			/*  1 */ esc_attr( apply_filters( 'invp_prefix_meta_key', 'color' ) ),
			/*  2 */ esc_html__( 'Color', 'inventory-presser' ),
			/*  3 */ esc_attr( $meta['color'] ),
			/*  4 */ esc_attr( apply_filters( 'invp_prefix_meta_key', 'interior_color' ) ),
			/*  5 */ esc_html__( 'Interior color', 'inventory-presser' ),
			/*  6 */ esc_attr( $meta['interior_color'] ),
			/*  7 */ esc_attr( apply_filters( 'invp_prefix_meta_key', 'odometer' ) ),
			/*  8 */ esc_attr( apply_filters( 'invp_odometer_word', __( 'Odometer', 'inventory-presser' ) ) ),
			/*  9 */ esc_attr( $meta['odometer'] ),
			/* 10 */ esc_attr( apply_filters( 'invp_odometer_word', 'miles' ) ),
			/* 11 */ esc_attr( apply_filters( 'invp_prefix_meta_key', 'youtube' ) ),
			/* 12 */ esc_html__( 'YouTube video ID', 'inventory-presser' ),
			/* 13 */ esc_attr( $meta['youtube'] )
		);

		$type = $custom[ apply_filters( 'invp_prefix_meta_key', 'type' ) ][0] ?? '';
		if ( 'boat' === strtolower( $type ) ) {
			// Add boat fields.
			printf( '<tr><td><h3>%s</h3></td></tr>', esc_html__( 'Boat-specific', 'inventory-presser' ) );
			printf(
				// Beam (boats).
				'<tr class="boat-postmeta"><th scope="row"><label for="%1$s">%2$s</label></th>'
				. '<td><input type="text" name="%1$s" value="%3$s"></td></tr>'

				// Boat condition.
				. '<tr class="boat-postmeta"><th scope="row"><label for="%4$s">%5$s</label></th>'
				. '<td><input type="text" name="%4$s" value="%6$s"></td></tr>'

				// Draft (boats).
				. '<tr class="boat-postmeta"><th scope="row"><label for="%7$s">%8$s</label></th>'
				. '<td><input type="text" name="%7$s" value="%9$s"></td></tr>'

				// # of Engines (boats).
				. '<tr class="boat-postmeta"><th scope="row"><label for="%10$s">%11$s</label></th>'
				. '<td><input type="text" name="%10$s" value="%12$s"></td></tr>'

				// Engine make (boats).
				. '<tr class="boat-postmeta"><th scope="row"><label for="%13$s">%14$s</label></th>'
				. '<td><input type="text" name="%13$s" value="%15$s"></td></tr>'

				// Engine model (boats).
				. '<tr class="boat-postmeta"><th scope="row"><label for="%16$s">%17$s</label></th>'
				. '<td><input type="text" name="%16$s" value="%18$s"></td></tr>'

				// Horsepower (boats).
				. '<tr class="boat-postmeta"><th scope="row"><label for="%19$s">%20$s</label></th>'
				. '<td><input type="text" name="%19$s" value="%21$s"></td></tr>'

				// Length (boats).
				. '<tr class="boat-postmeta"><th scope="row"><label for="%22$s">%23$s</label></th>'
				. '<td><input type="text" name="%22$s" value="%24$s"></td></tr>'

				// Hull material (boats).
				. '<tr class="boat-postmeta"><th scope="row"><label for="%25$s">%26$s</label></th>'
				. '<td><select name="%25$s"><option></option>',
				/*  1 */ esc_attr( apply_filters( 'invp_prefix_meta_key', 'beam' ) ),
				/*  2 */ esc_html__( 'Beam', 'inventory-presser' ),
				/*  3 */ esc_attr( $meta['beam'] ),
				/*  4 */ esc_attr( apply_filters( 'invp_prefix_meta_key', 'condition_boat' ) ),
				/*  5 */ esc_html__( 'Boat Condition', 'inventory-presser' ),
				/*  6 */ esc_attr( $meta['condition_boat'] ),
				/*  7 */ esc_attr( apply_filters( 'invp_prefix_meta_key', 'draft' ) ),
				/*  8 */ esc_html__( 'Max Draft', 'inventory-presser' ),
				/*  9 */ esc_attr( $meta['draft'] ),
				/* 10 */ esc_attr( apply_filters( 'invp_prefix_meta_key', 'engine_count' ) ),
				/* 11 */ esc_html__( 'Number of Engines', 'inventory-presser' ),
				/* 12 */ esc_attr( $meta['engine_count'] ),
				/* 13 */ esc_attr( apply_filters( 'invp_prefix_meta_key', 'engine_make' ) ),
				/* 14 */ esc_html__( 'Engine Make', 'inventory-presser' ),
				/* 15 */ esc_attr( $meta['engine_make'] ),
				/* 16 */ esc_attr( apply_filters( 'invp_prefix_meta_key', 'engine_model' ) ),
				/* 17 */ esc_html__( 'Engine Model', 'inventory-presser' ),
				/* 18 */ esc_attr( $meta['engine_model'] ),
				/* 19 */ esc_attr( apply_filters( 'invp_prefix_meta_key', 'horsepower' ) ),
				/* 20 */ esc_html__( 'Horsepower', 'inventory-presser' ),
				/* 21 */ esc_attr( $meta['horsepower'] ),
				/* 22 */ esc_attr( apply_filters( 'invp_prefix_meta_key', 'length' ) ),
				/* 23 */ esc_html__( 'Length', 'inventory-presser' ),
				/* 24 */ esc_attr( $meta['length'] ),
				/* 25 */ esc_attr( apply_filters( 'invp_prefix_meta_key', 'hull_material' ) ),
				/* 26 */ esc_html__( 'Hull material', 'inventory-presser' )
			);
			$hull_materials = apply_filters(
				'invp_default_hull_materials',
				array(
					__( 'Aluminum', 'inventory-presser' ),
					__( 'Carbon Fiber', 'inventory-presser' ),
					__( 'Composite', 'inventory-presser' ),
					__( 'Ferro-Cement', 'inventory-presser' ),
					__( 'Fiberglass', 'inventory-presser' ),
					__( 'Hypalon', 'inventory-presser' ),
					__( 'Other', 'inventory-presser' ),
					__( 'PVC', 'inventory-presser' ),
					__( 'Steel', 'inventory-presser' ),
					__( 'Wood', 'inventory-presser' ),
				)
			);
			foreach ( $hull_materials as $m ) {
				printf(
					'<option%s>%s</option>',
					selected( $m, $meta['hull_material'], false ),
					esc_html( $m )
				);
			}
			echo '</select></td></tr>';
		}
		echo '</tbody></table>';
	}

	/**
	 * Rearranges meta boxes on the editor when editing vehicles
	 *
	 * @return void
	 */
	public function move_advanced_meta_boxes() {
		global $post, $wp_meta_boxes;
		$post_type = get_post_type( $post );

		if ( INVP::POST_TYPE !== $post_type ) {
			return;
		}

		do_meta_boxes( get_current_screen(), 'advanced', $post );
		unset( $wp_meta_boxes[ get_post_type( $post ) ]['advanced'] );
	}

	/**
	 * Remove and re-add the "Tags" meta box so it ends up at the bottom for our CPT
	 *
	 * @return void
	 */
	public function move_tags_meta_box() {
		global $wp_meta_boxes;
		unset( $wp_meta_boxes[ INVP::POST_TYPE ]['side']['core']['tagsdiv-post_tag'] );
		add_meta_box(
			'tagsdiv-post_tag',
			'Tags',
			'post_tags_meta_box',
			INVP::POST_TYPE,
			'side',
			'core',
			array( 'taxonomy' => 'post_tag' )
		);
	}

	/**
	 * AJAX callback to output the content we put near the Add Media button in
	 * the classic editor.
	 *
	 * @return void
	 */
	public function output_add_media_button_annotation() {
		// because AJAX.
		echo $this->create_add_media_button_annotation();
		wp_die();
	}
}
