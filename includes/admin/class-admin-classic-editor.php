<?php
/**
 * Classic Editor
 *
 * @package inventory-presser
 */

defined( 'ABSPATH' ) || exit;

/**
 * Inventory_Presser_Admin_Classic_Editor
 *
 * Adds features to the Classic Editor.
 *
 * @since      14.10.0
 * @package    Inventory_Presser
 * @subpackage Inventory_Presser/includes/admin
 * @author     Corey Salzano <corey@friday.systems>
 */
class Inventory_Presser_Admin_Classic_Editor {

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
		global $pagenow, $current_screen;
		if ( ( $pagenow !== 'post.php' || INVP::POST_TYPE !== $current_screen->post_type ) ) {
			// No. Not editing a vehicle.
			return;
		}

		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes_to_cpt' ) );

		// Move all "advanced" meta boxes above the default editor
		// http://wordpress.stackexchange.com/a/88103
		add_action( 'edit_form_after_title', array( $this, 'move_advanced_meta_boxes' ) );

		// Move the "Tags" metabox below the meta boxes for vehicle custom taxonomies
		add_action( 'add_meta_boxes', array( $this, 'move_tags_meta_box' ), 0 );

		// Load our scripts
		add_action( 'admin_enqueue_scripts', array( $this, 'scripts_and_styles' ) );

		// Add some content next to the "Add Media" button
		add_action( 'media_buttons', array( $this, 'annotate_add_media_button' ) );

		// Define an AJAX handler for the 'Delete All Media' button
		add_filter( 'wp_ajax_delete_all_post_attachments', array( $this, 'delete_all_post_attachments' ) );

		// Make our Add Media button annotation available from an AJAX call
		add_action( 'wp_ajax_output_add_media_button_annotation', array( $this, 'output_add_media_button_annotation' ) );
	}

	/**
	 * Adds meta boxes to the editor when editing vehicles.
	 *
	 * @return void
	 */
	public function add_meta_boxes_to_cpt() {
		// Add a meta box to the New/Edit post page
		// add_meta_box('vehicle-meta', 'Attributes', array( $this, 'meta_box_html_vehicle' ), INVP::POST_TYPE, 'normal', 'high' );

		// and another for prices
		// add_meta_box('prices-meta', 'Prices', array( $this, 'meta_box_html_prices' ), INVP::POST_TYPE, 'normal', 'high' );

		// Add another meta box to the New/Edit post page
		add_meta_box( 'options-meta', 'Optional equipment', array( $this, 'meta_box_html_options' ), INVP::POST_TYPE, 'normal', 'high' );

		// Add a meta box to the side column for a featured vehicle checkbox
		add_meta_box( 'featured', 'Featured Vehicle', array( $this, 'meta_box_html_featured' ), INVP::POST_TYPE, 'side', 'low' );
	}

	/**
	 * Adds HTML near the Add Media button on the classic editor.
	 *
	 * @param  mixed $editor_id
	 * @return void
	 */
	public function annotate_add_media_button( $editor_id ) {
		if ( 'content' != $editor_id ) {
			return;
		}

		printf(
			'%s<span id="media-annotation" class="annotation">%s</span>',
			$this->create_delete_all_post_attachments_button(),
			$this->create_add_media_button_annotation()
		);
	}

	/**
	 * create_add_media_button_annotation
	 *
	 * Computes the number of attachments on vehicles so the number can be
	 * shown in the classic editor near the Add Media button.
	 *
	 * @return string
	 */
	protected function create_add_media_button_annotation() {
		global $post;
		if ( ! is_object( $post ) && isset( $_POST['post_ID'] ) ) {
			/**
			 * This function is being called via AJAX and the
			 * post_id is incoming, so get the post
			 */
			$post = get_post( intval( $_POST['post_ID'] ) );
		}

		if ( INVP::POST_TYPE != $post->post_type ) {
			return '';
		}

		$attachments = get_children(
			array(
				'post_parent'    => $post->ID,
				'post_type'      => 'attachment',
				'posts_per_page' => -1,
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
					$counts['image']++;
					break;
				case 'video/mpeg':
				case 'video/mp4':
				case 'video/quicktime':
					$counts['video']++;
					break;
				case 'text/csv':
				case 'text/plain':
				case 'text/xml':
					$counts['text']++;
					break;
				case 'application/pdf':
					$counts['PDF']++;
					break;
				default:
					$counts['other']++;
					break;
			}
		}
		if ( 0 < ( $counts['image'] + $counts['video'] + $counts['text'] + $counts['PDF'] + $counts['other'] ) ) {
			$note = '';
			foreach ( $counts as $key => $count ) {
				if ( 0 < $count ) {
					if ( '' != $note ) {
						$note .= ', ';
					}
					$note .= $count . ' ' . $key . ( 1 != $count ? 's' : '' );
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
		$post = get_post( $post->ID );
		if ( INVP::POST_TYPE != $post->post_type ) {
			return '';
		}
		$attachments = get_children(
			array(
				'post_parent'    => $post->ID,
				'post_type'      => 'attachment',
				'posts_per_page' => -1,
			)
		);
		if ( 0 === sizeof( $attachments ) ) {
			return '';
		}
		return sprintf(
			'<button type="button" id="delete-media-button" class="button" onclick="delete_all_post_attachments();">'
			. '<span class="wp-media-buttons-icon"></span> %s</button>',
			__( 'Delete All Media', 'inventory-presser' )
		);
	}

	/**
	 * delete_all_post_attachments
	 *
	 * Deletes all a post's attachments. The callback behind the Delete All
	 * Media button.
	 *
	 * @return void
	 */
	public function delete_all_post_attachments() {
		if ( empty( $_POST['_ajax_nonce'] ) || ! wp_verify_nonce( $_POST['_ajax_nonce'], self::NONCE_DELETE_ALL_MEDIA ) ) {
			return;
		}

		$post_id = isset( $_POST['post_ID'] ) ? $_POST['post_ID'] : 0;

		if ( ! isset( $post_id ) ) {
			return; // Will die in case you run a function like this: delete_post_media($post_id); if you will remove this line - ALL ATTACHMENTS WHO HAS A PARENT WILL BE DELETED PERMANENTLY!
		} elseif ( 0 == $post_id ) {
			return; // Will die in case you have 0 set. there's no page id called 0 :)
		} elseif ( is_array( $post_id ) ) {
			return; // Will die in case you place there an array of pages.
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
		$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		wp_enqueue_style(
			$handle,
			plugins_url( "/css/classic-editor{$min}.css", INVP_PLUGIN_FILE_PATH ),
			array(),
			INVP_PLUGIN_VERSION
		);
		wp_enqueue_script(
			$handle,
			plugins_url( "/js/classic-editor{$min}.js", INVP_PLUGIN_FILE_PATH ),
			array(),
			INVP_PLUGIN_VERSION,
			true
		);

		// Provide data to JavaScript for the editor
		wp_add_inline_script(
			$handle,
			'const invp_classic_editor = ' . json_encode(
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
			'<input type="checkbox" id="%s" name="%s" value="1"%s><label for="%s">%s</label>',
			$meta_key,
			$meta_key,
			checked( '1', INVP::get_meta( $meta_key, $post->ID ), false ),
			$meta_key,
			__( 'Featured in Slideshows', 'inventory-presser' )
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

		// turn the array into an associative array with value false for all
		$options = array_fill_keys( $options, false );

		$options_array = invp_get_the_options( $post->ID );
		if ( is_array( $options_array ) ) {
			foreach ( $options_array as $option ) {
				$options[ $option ] = true;
			}
		}
		// sort the array by key
		ksort( $options );
		// output a bunch of checkboxes
		$HTML = '<div class="list-with-columns"><ul class="optional-equipment">';
		foreach ( $options as $key => $value ) {
			// element IDs cannot contain slashes, spaces or parentheses
			$id    = 'option-' . preg_replace( '/\/\(\)/i', '', str_replace( ' ', '_', $key ) );
			$HTML .= sprintf(
				'<li><input type="checkbox" id="%s" name="%s" value="%s"%s><label for="%s">%s</label></li>',
				$id,
				'inventory_presser_options_array[]',
				$key,
				checked( true, $value, false ),
				$id,
				$key
			);
		}
		echo $HTML . '</ul></div>';
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
			'price'        => 'Price',
			'msrp'         => 'MSRP',
			'down_payment' => 'Down payment',
			'payment'      => 'Payment',
		);

		echo '<table class="form-table"><tbody>';
		foreach ( $prices as $key => $label ) {
			$meta_key = apply_filters( 'invp_prefix_meta_key', $key );

			printf(
				'<tr><th scope="row"><label for="%s">%s</label></th>'
				. '<td><input type="text" name="%s" value="%s" onkeypress="return is_number(event)"></td></tr>',
				$meta_key,
				$label,
				$meta_key,
				INVP::get_meta( $key, $post->ID )
			);
		}

		// Payment frequency is a drop-down
		printf(
			'<tr><th scope="row"><label for="%s">Payment frequency</label></th>'
			. '<td><select name="%s"><option></option>',
			$meta_key,
			$meta_key
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
		// HTML output for vehicle data meta box
		$custom = get_post_custom( $post->ID );

		$body_style     = ( isset( $custom[ apply_filters( 'invp_prefix_meta_key', 'body_style' ) ] ) ? $custom[ apply_filters( 'invp_prefix_meta_key', 'body_style' ) ][0] : '' );
		$color          = ( isset( $custom[ apply_filters( 'invp_prefix_meta_key', 'color' ) ] ) ? $custom[ apply_filters( 'invp_prefix_meta_key', 'color' ) ][0] : '' );
		$engine         = ( isset( $custom[ apply_filters( 'invp_prefix_meta_key', 'engine' ) ] ) ? $custom[ apply_filters( 'invp_prefix_meta_key', 'engine' ) ][0] : '' );
		$interior_color = ( isset( $custom[ apply_filters( 'invp_prefix_meta_key', 'interior_color' ) ] ) ? $custom[ apply_filters( 'invp_prefix_meta_key', 'interior_color' ) ][0] : '' );
		$make           = ( isset( $custom[ apply_filters( 'invp_prefix_meta_key', 'make' ) ] ) ? $custom[ apply_filters( 'invp_prefix_meta_key', 'make' ) ][0] : '' );
		$model          = ( isset( $custom[ apply_filters( 'invp_prefix_meta_key', 'model' ) ] ) ? $custom[ apply_filters( 'invp_prefix_meta_key', 'model' ) ][0] : '' );
		$odometer       = ( isset( $custom[ apply_filters( 'invp_prefix_meta_key', 'odometer' ) ] ) ? $custom[ apply_filters( 'invp_prefix_meta_key', 'odometer' ) ][0] : '' );
		$stock_number   = ( isset( $custom[ apply_filters( 'invp_prefix_meta_key', 'stock_number' ) ] ) ? $custom[ apply_filters( 'invp_prefix_meta_key', 'stock_number' ) ][0] : '' );
		$trim           = ( isset( $custom[ apply_filters( 'invp_prefix_meta_key', 'trim' ) ] ) ? $custom[ apply_filters( 'invp_prefix_meta_key', 'trim' ) ][0] : '' );
		$VIN            = ( isset( $custom[ apply_filters( 'invp_prefix_meta_key', 'vin' ) ] ) ? $custom[ apply_filters( 'invp_prefix_meta_key', 'vin' ) ][0] : '' );
		$year           = ( isset( $custom[ apply_filters( 'invp_prefix_meta_key', 'year' ) ] ) ? $custom[ apply_filters( 'invp_prefix_meta_key', 'year' ) ][0] : '' );
		$youtube        = ( isset( $custom[ apply_filters( 'invp_prefix_meta_key', 'youtube' ) ] ) ? $custom[ apply_filters( 'invp_prefix_meta_key', 'youtube' ) ][0] : '' );

		// boat items
		$beam          = ( isset( $custom[ apply_filters( 'invp_prefix_meta_key', 'beam' ) ] ) ? $custom[ apply_filters( 'invp_prefix_meta_key', 'beam' ) ][0] : '' );
		$length        = ( isset( $custom[ apply_filters( 'invp_prefix_meta_key', 'length' ) ] ) ? $custom[ apply_filters( 'invp_prefix_meta_key', 'length' ) ][0] : '' );
		$hull_material = ( isset( $custom[ apply_filters( 'invp_prefix_meta_key', 'hull_material' ) ] ) ? $custom[ apply_filters( 'invp_prefix_meta_key', 'hull_material' ) ][0] : '' );

		printf(
			'<table class="form-table"><tbody>'

			// VIN
			. '<tr><th scope="row"><label for="%s">%s</label></th>'
			. '<td>%s</td>'

			// Stock number
			. '<tr><th scope="row"><label for="%s">%s</label></th>'
			. '<td><input type="text" name="%s" value="%s"></td>'

			// Year
			. '<tr><th scope="row"><label for="%s">%s</label></th>'
			. '<td><select name="%s"><option></option>',
			apply_filters( 'invp_prefix_meta_key', 'vin' ),
			__( 'VIN', 'inventory-presser' ),
			apply_filters(
				'invp_edit_control_vin',
				sprintf(
					'<input type="text" name="%s" maxlength="17" value="%s">',
					apply_filters( 'invp_prefix_meta_key', 'vin' ),
					$VIN
				)
			),
			apply_filters( 'invp_prefix_meta_key', 'stock_number' ),
			__( 'Stock number', 'inventory-presser' ),
			apply_filters( 'invp_prefix_meta_key', 'stock_number' ),
			$stock_number,
			apply_filters( 'invp_prefix_meta_key', 'year' ),
			__( 'Year', 'inventory-presser' ),
			apply_filters( 'invp_prefix_meta_key', 'year' )
		);

		for ( $y = date( 'Y' ) + 2; $y >= 1920; $y-- ) {
			printf(
				'<option%s>%s</option>',
				selected( $y, $year, false ),
				$y
			);
		}

		printf(
			'</select></td></tr>'

			// Make
			. '<tr><th scope="row"><label for="%s">%s</label></th>'
			. '<td><input type="text" name="%s" value="%s"></td></tr>'

			// Model
			. '<tr><th scope="row"><label for="%s">%s</label></th>'
			. '<td><input type="text" name="%s" value="%s"></td></tr>'

			// Trim level
			. '<tr><th scope="row"><label for="%s">%s</label></th>'
			. '<td><input type="text" name="%s" value="%s"></td></tr>'

			// Engine
			. '<tr><th scope="row"><label for="%s">%s</label></th>'
			. '<td><input type="text" name="%s" value="%s"></td></tr>'

			// Body style
			. '<tr><th scope="row"><label for="%s">%s</label></th>'
			. '<td><input type="text" name="%s" id="%s" value="%s">'

			. '<select name="%s_hidden" id="%s_hidden">',
			apply_filters( 'invp_prefix_meta_key', 'make' ),
			__( 'Make', 'inventory-presser' ),
			apply_filters( 'invp_prefix_meta_key', 'make' ),
			$make,
			apply_filters( 'invp_prefix_meta_key', 'model' ),
			__( 'Model', 'inventory-presser' ),
			apply_filters( 'invp_prefix_meta_key', 'model' ),
			$model,
			apply_filters( 'invp_prefix_meta_key', 'trim' ),
			__( 'Trim', 'inventory-presser' ),
			apply_filters( 'invp_prefix_meta_key', 'trim' ),
			$trim,
			apply_filters( 'invp_prefix_meta_key', 'engine' ),
			__( 'Engine', 'inventory-presser' ),
			apply_filters( 'invp_prefix_meta_key', 'engine' ),
			$engine,
			apply_filters( 'invp_prefix_meta_key', 'body_style' ),
			__( 'Body style', 'inventory-presser' ),
			apply_filters( 'invp_prefix_meta_key', 'body_style' ),
			apply_filters( 'invp_prefix_meta_key', 'body_style' ),
			$body_style,
			apply_filters( 'invp_prefix_meta_key', 'body_style' ),
			apply_filters( 'invp_prefix_meta_key', 'body_style' )
		);

		$boat_styles = apply_filters(
			'invp_default_boat_styles',
			array(
				'Bass boat',
				'Bow Rider',
				'Cabin Cruiser',
				'Center Console',
				'Cuddy Cabin',
				'Deck boat',
				'Performance',
				'Pontoon',
			)
		);
		foreach ( $boat_styles as $s ) {
			printf(
				'<option%s>%s</option>',
				selected( $s, $body_style ),
				$s
			);
		}

		printf(
			'</select></td></tr>'

			// Color
			. '<tr><th scope="row"><label for="%s">%s</label></th>'
			. '<td><input type="text" name="%s" value="%s"></td></tr>'

			// Interior color
			. '<tr><th scope="row"><label for="%s">%s</label></th>'
			. '<td><input type="text" name="%s" value="%s"></td></tr>'

			// Odometer
			. '<tr><th scope="row"><label for="%s">%s</label></th>'
			. '<td><input type="text" name="%s" value="%s">'
			. ' <span class="invp_odometer_units">%s</span></td></tr>'

			// YouTube
			. '<tr><th scope="row"><label for="%s">%s</label></th>'
			. '<td><input type="text" name="%s" value="%s"></td></tr>'

			// Beam (boats)
			. '<tr class="boat-postmeta"><th scope="row"><label for="%s">%s</label></th>'
			. '<td><input type="text" name="%s" value="%s"></td></tr>'

			// Length (boats)
			. '<tr class="boat-postmeta"><th scope="row"><label for="%s">%s</label></th>'
			. '<td><input type="text" name="%s" value="%s"></td></tr>'

			// Hull material
			. '<tr class="boat-postmeta"><th scope="row"><label for="%s">%s</label></th>'
			. '<td><select name="%s"><option></option>',
			apply_filters( 'invp_prefix_meta_key', 'color' ),
			__( 'Color', 'inventory-presser' ),
			apply_filters( 'invp_prefix_meta_key', 'color' ),
			$color,
			apply_filters( 'invp_prefix_meta_key', 'interior_color' ),
			__( 'Interior color', 'inventory-presser' ),
			apply_filters( 'invp_prefix_meta_key', 'interior_color' ),
			$interior_color,
			apply_filters( 'invp_prefix_meta_key', 'odometer' ),
			__( 'Odometer', 'inventory-presser' ),
			apply_filters( 'invp_prefix_meta_key', 'odometer' ),
			$odometer,
			apply_filters( 'invp_odometer_word', 'miles' ),
			apply_filters( 'invp_prefix_meta_key', 'youtube' ),
			__( 'YouTube video ID', 'inventory-presser' ),
			apply_filters( 'invp_prefix_meta_key', 'youtube' ),
			$youtube,
			apply_filters( 'invp_prefix_meta_key', 'beam' ),
			__( 'Beam', 'inventory-presser' ),
			apply_filters( 'invp_prefix_meta_key', 'beam' ),
			$beam,
			apply_filters( 'invp_prefix_meta_key', 'length' ),
			__( 'Length', 'inventory-presser' ),
			apply_filters( 'invp_prefix_meta_key', 'length' ),
			$length,
			apply_filters( 'invp_prefix_meta_key', 'hull_material' ),
			__( 'Hull material', 'inventory-presser' ),
			apply_filters( 'invp_prefix_meta_key', 'hull_material' )
		);

		$hull_materials = apply_filters(
			'invp_default_hull_materials',
			array(
				'Aluminum',
				'Carbon Fiber',
				'Composite',
				'Ferro-Cement',
				'Fiberglass',
				'Hypalon',
				'Other',
				'PVC',
				'Steel',
				'Wood',
			)
		);
		foreach ( $hull_materials as $m ) {
			printf(
				'<option%s>%s</option>',
				selected( $m, $hull_material, false ),
				$m
			);
		}
		echo '</select></tbody></table>';
	}

	/**
	 * Rearranges meta boxes on the editor when editing vehicles
	 *
	 * @return void
	 */
	public function move_advanced_meta_boxes() {
		global $post, $wp_meta_boxes;
		$post_type = get_post_type( $post );

		if ( INVP::POST_TYPE != $post_type ) {
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
