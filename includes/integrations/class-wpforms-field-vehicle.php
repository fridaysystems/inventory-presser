<?php
defined( 'ABSPATH' ) || exit;

/**
 * Dropdown field.
 *
 * @since 1.0.0
 */
class WPForms_Field_Vehicle extends WPForms_Field {

	/**
	 * Choices JS version.
	 *
	 * @since 1.6.3
	 */
	const CHOICES_VERSION = '9.0.1';

	/**
	 * Classic (old) style.
	 *
	 * @since 1.6.1
	 *
	 * @var string
	 */
	const STYLE_CLASSIC = 'classic';

	/**
	 * Modern style.
	 *
	 * @since 1.6.1
	 *
	 * @var string
	 */
	const STYLE_MODERN = 'modern';

	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.0
	 */
	public function init() {

		// Define field type information.
		$this->name     = esc_html__( 'Vehicle', 'inventory-presser' );
		$this->type     = 'vehicle';
		$this->icon     = 'fa-caret-square-o-down';
		$this->order    = 200;
		$this->defaults = [
			1 => [
				'label'   => esc_html__( 'First Choice', 'inventory-presser' ),
				'value'   => '',
				'default' => '',
			],
			2 => [
				'label'   => esc_html__( 'Second Choice', 'inventory-presser' ),
				'value'   => '',
				'default' => '',
			],
			3 => [
				'label'   => esc_html__( 'Third Choice', 'inventory-presser' ),
				'value'   => '',
				'default' => '',
			],
		];

		// Define additional field properties.
		add_filter( 'wpforms_field_properties_' . $this->type, [ $this, 'field_properties' ], 5, 3 );

		// Form frontend CSS enqueues.
		add_action( 'wpforms_frontend_css', [ $this, 'enqueue_frontend_css' ] );

		// Form frontend JS enqueues.
		add_action( 'wpforms_frontend_js', [ $this, 'enqueue_frontend_js' ] );

		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_block_editor_assets' ] );
	}

	/**
	 * Define additional field properties.
	 *
	 * @since 1.5.0
	 *
	 * @param array $properties Field properties.
	 * @param array $field      Field settings.
	 * @param array $form_data  Form data and settings.
	 *
	 * @return array
	 */
	public function field_properties( $properties, $field, $form_data ) {

		// Remove primary input.
		unset( $properties['inputs']['primary'] );

		// Define data.
		$form_id  = absint( $form_data['id'] );
		$field_id = absint( $field['id'] );
		$choices = array(
			array(
				'label' => __( 'Please choose a vehicle...', 'inventory-presser' ),
				'value' => '',
			),
		);
		// The field will be hidden, do not show a label.
		if ( is_singular( INVP::POST_TYPE ) ) {
			$properties['label']['disabled']    = true;
			$properties['container']['class'][] = 'wpforms-field-hidden';
		} else {
			// Get the post IDs of vehicles sorted by year, then make, then model.
			global $wpdb;
			$post_ids = $wpdb->get_col(
				$wpdb->prepare(
					"
					SELECT 		ID

					FROM		$wpdb->posts
								LEFT JOIN $wpdb->postmeta meta1 ON wp_posts.ID = meta1.post_id
								LEFT JOIN $wpdb->postmeta meta2 ON wp_posts.ID = meta2.post_id
								LEFT JOIN $wpdb->postmeta meta3 ON wp_posts.ID = meta3.post_id

					WHERE 		post_type = '%s'
								AND post_status = 'publish'
								AND meta1.meta_key = '%s'
								AND meta2.meta_key = '%s'
								AND meta3.meta_key = '%s'

					ORDER BY	meta1.meta_value DESC,
								meta2.meta_value ASC,
								meta3.meta_value ASC;
				",
					INVP::POST_TYPE,
					apply_filters( 'invp_prefix_meta_key', 'year' ),
					apply_filters( 'invp_prefix_meta_key', 'make' ),
					apply_filters( 'invp_prefix_meta_key', 'model' ),
				) 
			);
			foreach ( $post_ids as $post_id ) {
				$year         = invp_get_the_year( $post_id );
				$make         = invp_get_the_make( $post_id );
				$model        = invp_get_the_model( $post_id );
				$trim         = invp_get_the_trim( $post_id );
				$stock_number = invp_get_the_stock_number( $post_id );
				$choices[]    = array(
					//2016 BMW 428 I, Black Sapphire Metallic, #GW228071
					'label' => sprintf(
						'%s %s %s %s, %s, #%s',
						$year,
						$make,
						$model,
						$trim,
						invp_get_the_color( $post_id ),
						$stock_number
						
					),
					//2016 Toyota Corolla L, P03013
					'value' => sprintf(
						'%s %s %s %s, %s',
						$year,
						$make,
						$model,
						$trim,
						$stock_number
					),
				);
			}
		}

		// Set options container (<select>) properties.
		$properties['input_container'] = [
			'class' => [],
			'data'  => [],
			'id'    => "wpforms-{$form_id}-field_{$field_id}",
			'attr'  => [
				'name' => "wpforms[fields][{$field_id}]",
			],
		];

		// Set properties.
		foreach ( $choices as $key => $choice ) {

			// Used for dynamic choices.
			$depth = 1;

			$properties['inputs'][ $key ] = [
				'container' => [
					'attr'  => [],
					'class' => [ "choice-{$key}", "depth-{$depth}" ],
					'data'  => [],
					'id'    => '',
				],
				'label'     => [
					'attr'  => [
						'for' => "wpforms-{$form_id}-field_{$field_id}_{$key}",
					],
					'class' => [ 'wpforms-field-label-inline' ],
					'data'  => [],
					'id'    => '',
					'text'  => $choice['label'],
				],
				'attr'      => [
					'name'  => "wpforms[fields][{$field_id}]",
					'value' => $choice['value'] ?: $choice['label'],
				],
				'class'     => [],
				'data'      => [],
				'id'        => "wpforms-{$form_id}-field_{$field_id}_{$key}",
				'required'  => ! empty( $field['required'] ) ? 'required' : '',
				'default'   => isset( $choice['default'] ),
			];
		}

		// Add class that changes the field size.
		if ( ! empty( $field['size'] ) ) {
			$properties['input_container']['class'][] = 'wpforms-field-' . esc_attr( $field['size'] );
		}

		// Required class for pagebreak validation.
		if ( ! empty( $field['required'] ) ) {
			$properties['input_container']['class'][] = 'wpforms-field-required';
		}

		// Add additional class for container.
		if (
			! empty( $field['style'] ) &&
			in_array( $field['style'], [ self::STYLE_CLASSIC, self::STYLE_MODERN ], true )
		) {
			$properties['container']['class'][] = "wpforms-field-vehicle-style-{$field['style']}";
		}

		return $properties;
	}

	/**
	 * Field options panel inside the builder.
	 *
	 * @since 1.0.0
	 *
	 * @param array $field Field settings.
	 */
	public function field_options( $field ) {

		/*
		 * Basic field options.
		 */

		// Options open markup.
		$this->field_option(
			'basic-options',
			$field,
			[
				'markup' => 'open',
			]
		);

		// Label.
		$this->field_option( 'label', $field );

		// Choices.
		//$this->field_option( 'choices', $field );

		// Description.
		$this->field_option( 'description', $field );

		// Required toggle.
		$this->field_option( 'required', $field );

		// Options close markup.
		$this->field_option(
			'basic-options',
			$field,
			[
				'markup' => 'close',
			]
		);

		/*
		 * Advanced field options.
		 */

		// Options open markup.
		$this->field_option(
			'advanced-options',
			$field,
			[
				'markup' => 'open',
			]
		);

		// Show Values toggle option. This option will only show if already used
		// or if manually enabled by a filter.
		// if ( ! empty( $field['show_values'] ) || wpforms_show_fields_options_setting() ) {
		// 	$show_values = $this->field_element(
		// 		'toggle',
		// 		$field,
		// 		[
		// 			'slug'    => 'show_values',
		// 			'value'   => isset( $field['show_values'] ) ? $field['show_values'] : '0',
		// 			'desc'    => esc_html__( 'Show Values', 'inventory-presser' ),
		// 			'tooltip' => esc_html__( 'Check this option to manually set form field values.', 'inventory-presser' ),
		// 		],
		// 		false
		// 	);
		// 	$this->field_element(
		// 		'row',
		// 		$field,
		// 		[
		// 			'slug'    => 'show_values',
		// 			'content' => $show_values,
		// 		]
		// 	);
		// }

		// Multiple options selection.
		$fld = $this->field_element(
			'toggle',
			$field,
			[
				'slug'    => 'multiple',
				'value'   => ! empty( $field['multiple'] ),
				'desc'    => esc_html__( 'Multiple Options Selection', 'inventory-presser' ),
				'tooltip' => esc_html__( 'Allow users to select multiple choices in this field.', 'inventory-presser' ) . '<br>' .
							sprintf(
								wp_kses( /* translators: %s - URL to WPForms.com doc article. */
									esc_html__( 'For details, including how this looks and works for your site\'s visitors, please check out <a href="%s" target="_blank" rel="noopener noreferrer">our doc</a>.', 'inventory-presser' ),
									[
										'a' => [
											'href'   => [],
											'target' => [],
											'rel'    => [],
										],
									]
								),
								esc_url( wpforms_utm_link( 'https://wpforms.com/docs/how-to-allow-multiple-selections-to-a-dropdown-field-in-wpforms/', 'Field Options', 'Multiple Options Selection Documentation' ) )
							),
			],
			false
		);

		$this->field_element(
			'row',
			$field,
			[
				'slug'    => 'multiple',
				'content' => $fld,
			]
		);

		// Style.
		$lbl = $this->field_element(
			'label',
			$field,
			[
				'slug'    => 'style',
				'value'   => esc_html__( 'Style', 'inventory-presser' ),
				'tooltip' => esc_html__( 'Classic style is the default one generated by your browser. Modern has a fresh look and displays all selected options in a single row.', 'inventory-presser' ),
			],
			false
		);

		$fld = $this->field_element(
			'select',
			$field,
			[
				'slug'    => 'style',
				'value'   => ! empty( $field['style'] ) ? $field['style'] : self::STYLE_CLASSIC,
				'options' => [
					self::STYLE_CLASSIC => esc_html__( 'Classic', 'inventory-presser' ),
					self::STYLE_MODERN  => esc_html__( 'Modern', 'inventory-presser' ),
				],
			],
			false
		);

		$this->field_element(
			'row',
			$field,
			[
				'slug'    => 'style',
				'content' => $lbl . $fld,
			]
		);

		// Size.
		$this->field_option( 'size', $field );

		// Placeholder.
		//$this->field_option( 'placeholder', $field );

		// Dynamic choice auto-populating toggle.
		//$this->field_option( 'dynamic_choices', $field );

		// Dynamic choice source.
		//$this->field_option( 'dynamic_choices_source', $field );

		// Custom CSS classes.
		$this->field_option( 'css', $field );

		// Hide label.
		$this->field_option( 'label_hide', $field );

		// Options close markup.
		$this->field_option(
			'advanced-options',
			$field,
			[
				'markup' => 'close',
			]
		);
	}

	/**
	 * Field preview inside the builder.
	 *
	 * @since 1.0.0
	 * @since 1.6.1 Added a `Modern` style select support.
	 *
	 * @param array $field Field settings.
	 */
	public function field_preview( $field ) {

		$args = [];

		// Label.
		$this->field_preview_option( 'label', $field );

		// Prepare arguments.
		$args['modern'] = false;

		if (
			! empty( $field['style'] ) &&
			self::STYLE_MODERN === $field['style']
		) {
			$args['modern'] = true;
			$args['class']  = 'choicesjs-select';
		}

		// Choices.
		$this->field_preview_option( 'choices', $field, $args );

		// Description.
		$this->field_preview_option( 'description', $field );
	}

	/**
	 * Field display on the form front-end.
	 *
	 * @since 1.0.0
	 * @since 1.5.0 Converted to a new format, where all the data are taken not from $deprecated, but field properties.
	 * @since 1.6.1 Added a multiple select support.
	 *
	 * @param array $field      Field data and settings.
	 * @param array $deprecated Deprecated array of field attributes.
	 * @param array $form_data  Form data and settings.
	 */
	public function field_display( $field, $deprecated, $form_data ) {
		$container         = $field['properties']['input_container'];
		$field_placeholder = ! empty( $field['placeholder'] ) ? $field['placeholder'] : '';
		$is_multiple       = ! empty( $field['multiple'] );
		$is_modern         = ! empty( $field['style'] ) && self::STYLE_MODERN === $field['style'];
		$choices           = $field['properties']['inputs'];

		// if ( ! $choices ) {
		// 	return;
		// }

		if ( ! empty( $field['required'] ) ) {
			$container['attr']['required'] = 'required';
		}

		// If it's a multiple select.
		if ( $is_multiple ) {
			$container['attr']['multiple'] = 'multiple';

			// Change a name attribute.
			if ( ! empty( $container['attr']['name'] ) ) {
				$container['attr']['name'] .= '[]';
			}
		}

		// Add a class for Choices.js initialization.
		if ( $is_modern ) {
			$container['class'][] = 'choicesjs-select';

			// Add a size-class to data attribute - it is used when Choices.js is initialized.
			if ( ! empty( $field['size'] ) ) {
				$container['data']['size-class'] = 'wpforms-field-row wpforms-field-' . sanitize_html_class( $field['size'] );
			}

			$container['data']['search-enabled'] = $this->is_choicesjs_search_enabled( count( $choices ) );
		}

		$has_default = false;

		// Check to see if any of the options were selected by default.
		foreach ( $choices as $choice ) {
			if ( ! empty( $choice['default'] ) ) {
				$has_default = true;
				break;
			}
		}

		// Fake placeholder for Modern style.
		if ( $is_modern && empty( $field_placeholder ) ) {
			$first_choices     = reset( $choices );
			$field_placeholder = $first_choices['label']['text'];
		}

		// Is this a vehicle details page?
		if ( is_singular( INVP::POST_TYPE ) ) {
			$value = sprintf( 
				'%s %s %s %s, %s',
				invp_get_the_year(),
				invp_get_the_make(),
				invp_get_the_model(),
				invp_get_the_trim(),
				invp_get_the_stock_number()
			);
			// Yes. Add a hidden input to the form instead of a select.
			printf(
				//<input type="hidden" name="vehicle" value="2016 Toyota Corolla L, P03013">
				'<input type="hidden" %s value="%s" />',
				wpforms_html_attributes( $container['id'], $container['class'], $container['data'], $container['attr'] ),
				esc_attr( $value )
			);
			return;
		}

		// Preselect default if no other choices were marked as default.
		printf(
			'<select %s>',
			wpforms_html_attributes( $container['id'], $container['class'], $container['data'], $container['attr'] )
		);

		// Optional placeholder.
		if ( ! empty( $field_placeholder ) ) {
			printf(
				'<option value="" class="placeholder" disabled %s>%s</option>',
				selected( false, $has_default || $is_multiple, false ),
				esc_html( $field_placeholder )
			);
		}

		// Build the select options.
		foreach ( $choices as $key => $choice ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $choice['attr']['value'] ),
				selected( true, ! empty( $choice['default'] ), false ),
				esc_html( $choice['label']['text'] )
			);
		}

		echo '</select>';
	}

	/**
	 * Format and sanitize field.
	 *
	 * @since 1.0.2
	 * @since 1.6.1 Added a support for multiple values.
	 *
	 * @param int          $field_id     Field ID.
	 * @param string|array $field_submit Submitted field value (selected option).
	 * @param array        $form_data    Form data and settings.
	 */
	public function format( $field_id, $field_submit, $form_data ) {

		$field    = $form_data['fields'][ $field_id ];
		$dynamic  = false;
		$multiple = ! empty( $field['multiple'] );
		$name     = sanitize_text_field( $field['label'] );
		$value    = [];

		// Convert submitted field value to array.
		if ( ! is_array( $field_submit ) ) {
			$field_submit = [ $field_submit ];
		}

		$value_raw = wpforms_sanitize_array_combine( $field_submit );

		$data = [
			'name'      => $name,
			'value'     => '',
			'value_raw' => $value_raw,
			'id'        => absint( $field_id ),
			'type'      => $this->type,
		];

		// Normal processing, dynamic population is off.

		// If show_values is true, that means values posted are the raw values
		// and not the labels. So we need to get the label values.
		if ( ! empty( $field['show_values'] ) && (int) $field['show_values'] === 1 ) {

			foreach ( $field_submit as $item ) {
				foreach ( $field['choices'] as $choice ) {
					if ( $item === $choice['value'] ) {
						$value[] = $choice['label'];

						break;
					}
				}
			}

			$data['value'] = ! empty( $value ) ? wpforms_sanitize_array_combine( $value ) : '';

		} else {
			$data['value'] = $value_raw;
		}

		// Backward compatibility: for single dropdown save a string, for multiple - array.
		if ( ! $multiple && is_array( $data ) && ( 1 === count( $data ) ) ) {
			$data = reset( $data );
		}

		// Push field details to be saved.
		wpforms()->process->fields[ $field_id ] = $data;
	}

	/**
	 * Form frontend CSS enqueues.
	 *
	 * @since 1.6.1
	 *
	 * @param array $forms Forms on the current page.
	 */
	public function enqueue_frontend_css( $forms ) {

		$has_modern_select = false;

		foreach ( $forms as $form ) {
			if ( $this->is_field_style( $form, self::STYLE_MODERN ) ) {
				$has_modern_select = true;

				break;
			}
		}

		if ( $has_modern_select || wpforms()->frontend->assets_global() ) {
			$min = wpforms_get_min_suffix();

			wp_enqueue_style(
				'wpforms-choicesjs',
				WPFORMS_PLUGIN_URL . "assets/css/choices{$min}.css",
				[],
				self::CHOICES_VERSION
			);
		}
	}

	/**
	 * Form frontend JS enqueues.
	 *
	 * @since 1.6.1
	 *
	 * @param array $forms Forms on the current page.
	 */
	public function enqueue_frontend_js( $forms ) {

		$has_modern_select = false;

		foreach ( $forms as $form ) {
			if ( $this->is_field_style( $form, self::STYLE_MODERN ) ) {
				$has_modern_select = true;

				break;
			}
		}

		if ( $has_modern_select || wpforms()->frontend->assets_global() ) {
			$this->enqueue_choicesjs_once( $forms );
		}
	}

	/**
	 * Load WPForms Gutenberg block scripts.
	 *
	 * @since 1.8.1
	 */
	public function enqueue_block_editor_assets() {

		$min = wpforms_get_min_suffix();

		wp_enqueue_style(
			'wpforms-choicesjs',
			WPFORMS_PLUGIN_URL . "assets/css/choices{$min}.css",
			[],
			self::CHOICES_VERSION
		);

		$this->enqueue_choicesjs_once( [] );
	}

	/**
	 * Whether the provided form has a dropdown field with a specified style.
	 *
	 * @since 1.6.1
	 *
	 * @param array  $form  Form data.
	 * @param string $style Desired field style.
	 *
	 * @return bool
	 */
	protected function is_field_style( $form, $style ) {

		$is_field_style = false;

		if ( empty( $form['fields'] ) ) {

			return $is_field_style;
		}

		foreach ( (array) $form['fields'] as $field ) {

			if (
				! empty( $field['type'] ) &&
				$field['type'] === $this->type &&
				! empty( $field['style'] ) &&
				sanitize_key( $style ) === $field['style']
			) {
				$is_field_style = true;
				break;
			}
		}

		return $is_field_style;
	}

	/**
	 * Get field name for ajax error message.
	 *
	 * @since 1.6.3
	 *
	 * @param string $name  Field name for error triggered.
	 * @param array  $field Field settings.
	 * @param array  $props List of properties.
	 * @param string $error Error message.
	 *
	 * @return string
	 */
	public function ajax_error_field_name( $name, $field, $props, $error ) {

		if ( ! isset( $field['type'] ) || 'vehicle' !== $field['type'] ) {
			return $name;
		}
		if ( ! empty( $field['multiple'] ) ) {
			$input = isset( $props['inputs'] ) ? end( $props['inputs'] ) : [];

			return isset( $input['attr']['name'] ) ? $input['attr']['name'] . '[]' : '';
		}

		return $name;
	}
}
new WPForms_Field_Vehicle();