<?php
/**
 * Gravity Forms Field Vehicle
 *
 * @package inventory-presser
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'GF_Field' ) ) {

	/**
	 * GF_Field_Vehicle
	 */
	class GF_Field_Vehicle extends GF_Field {

		/**
		 * Field type.
		 *
		 * @var string
		 */
		public $type = 'invp_vehicle';

		/**
		 * Returns the field title.
		 *
		 * @return string
		 */
		public function get_form_editor_field_title() {
			return esc_attr__( 'Vehicle', 'inventory-presser' );
		}

		/**
		 * Returns the field button properties for the form editor. The array
		 * contains two elements:
		 * 'group' => 'standard_fields'|'advanced_fields'|'post_fields'|'pricing_fields'
		 * 'text'  => 'Button text'
		 *
		 * @since 2.4
		 *
		 * @return array
		 */
		public function get_form_editor_button() {
			return array(
				'group' => 'advanced_fields',
				'text'  => $this->get_form_editor_field_title(),
			);
		}

		/**
		 * Adds conditional logic support.
		 *
		 * @return bool
		 */
		public function is_conditional_logic_supported() {
			return true;
		}

		/**
		 * Returns the field's form editor description.
		 *
		 * @return string
		 */
		public function get_form_editor_field_description() {
			return esc_attr__( 'Allows users to select one vehicle from a list. Hidden field on vehicle details pages.', 'inventory-presser' );
		}

		/**
		 * Returns the field's form editor icon.
		 *
		 * @return string
		 */
		public function get_form_editor_field_icon() {
			return 'gform-icon--shipping'; // Truck icon.
		}

		/**
		 * The class names of the settings which should be available on the field in
		 * the form editor.
		 *
		 * @return array
		 */
		public function get_form_editor_field_settings() {
			return array(
				'conditional_logic_field_setting',
				'prepopulate_field_setting',
				'error_message_setting',
				'label_setting',
				'label_placement_setting',
				'admin_label_setting',
				'size_setting', // ?
				'rules_setting',
				'visibility_setting',
				'duplicate_setting',
				'default_value_setting', // ?
				'placeholder_setting', // ?
				'description_setting',
				'css_class_setting',
			);
		}

		/**
		 * Define the fields inner markup, including the div with the
		 * ginput_container class.
		 *
		 * @param array        $form The Form Object currently being processed.
		 * @param string|array $value The field value. From default/dynamic population, $_POST, or a resumed incomplete submission.
		 * @param null|array   $entry Null or the Entry Object currently being edited.
		 *
		 * @return string
		 */
		public function get_field_input( $form, $value = '', $entry = null ) {
			$form_id         = $form['id'];
			$is_entry_detail = $this->is_entry_detail();
			$is_form_editor  = $this->is_form_editor();

			$id       = (int) $this->id;
			$field_id = $is_entry_detail || $is_form_editor || $form_id == 0 ? "input_$id" : 'input_' . $form_id . "_$id";

			$disabled_text = $is_form_editor ? 'disabled="disabled"' : '';

			$field_type         = $is_entry_detail || $is_form_editor ? 'text' : 'hidden';
			$class_attribute    = $is_entry_detail || $is_form_editor ? '' : 'class="gform_hidden"';
			$required_attribute = $this->isRequired ? 'aria-required="true"' : '';
			$invalid_attribute  = $this->failed_validation ? 'aria-invalid="true"' : 'aria-invalid="false"';

			if ( is_singular( INVP::POST_TYPE ) ) {
				$value = sprintf(
					'%s %s %s %s, %s',
					invp_get_the_year(),
					invp_get_the_make(),
					invp_get_the_model(),
					invp_get_the_trim(),
					invp_get_the_stock_number()
				);
			}

			$field = sprintf(
				'<input name="input_%d" id="%s" type="%s" {$class_attribute} {$required_attribute} {$invalid_attribute} aria-invalid="%s" value="%s" %s/>',
				esc_attr( $id ),
				esc_attr( $field_id ),
				esc_attr( $field_type ),
				esc_attr( $this->failed_validation ? 'true' : 'false' ),
				esc_attr( $value ),
				$disabled_text
			);

			// Is this a single vehicle page?
			if ( ! is_singular( INVP::POST_TYPE ) && ! $is_form_editor ) {
				// No.
				// The field is a select.
				$field_type = 'select';
				$field      = sprintf(
					'<select name="input_%1$d" id="%2$s" %3$s %4$s %5$s %6$s aria-invalid="%7$s">',
					/* 1 */ esc_attr( $id ),
					/* 2 */ esc_attr( $field_id ),
					/* 3 */ $this->get_tabindex(),
					/* 4 */ $this->get_aria_describedby(),
					/* 5 */ $disabled_text,
					/* 6 */ $required_attribute,
					/* 7 */ esc_attr( $this->failed_validation ? 'true' : 'false' )
				);
				$post_ids   = $this->get_vehicle_post_ids();
				foreach ( $post_ids as $post_id ) {
					$field .= sprintf(
						'<option value="%s">%s</option">',
						$this->create_option_value( $post_id ),
						$this->create_option_label( $post_id )
					);
				}
				$field .= '</select>';
			}

			return sprintf(
				'<div class="ginput_container ginput_container_%s">%s</div>',
				'select' === $field_type ? 'select' : 'text',
				$field
			);
		}

		/**
		 * This method is used to define the fields overall appearance, such as how
		 * the admin buttons, field label, description or validation messages are
		 * included.
		 *
		 * @param string|array $value                The field value. From default/dynamic population, $_POST, or a resumed incomplete submission.
		 * @param bool         $force_frontend_label Should the frontend label be displayed in the admin even if an admin label is configured.
		 * @param array        $form                 The Form Object currently being processed.
		 * @return string
		 */
		public function get_field_content( $value, $force_frontend_label, $form ) {
			$form_id             = $form['id'];
			$admin_buttons       = $this->get_admin_buttons();
			$is_entry_detail     = $this->is_entry_detail();
			$is_form_editor      = $this->is_form_editor();
			$is_admin            = $is_entry_detail || $is_form_editor;
			$field_label         = $this->get_field_label( $force_frontend_label, $value );
			$field_id            = $is_admin || $form_id == 0 ? "input_{$this->id}" : 'input_' . $form_id . "_{$this->id}";
			$admin_hidden_markup = ( $this->visibility == 'hidden' ) ? $this->get_hidden_admin_markup() : '';
			$field_content       = ! $is_admin ? '{FIELD}' : $field_content = sprintf( "%s%s<label class='gfield_label gform-field-label' for='%s'>%s</label>{FIELD}", $admin_buttons, $admin_hidden_markup, $field_id, esc_html( $field_label ) );

			return $field_content;
		}

		/**
		 * Takes a vehicle post ID, returns a string like "2016 BMW 428 I, Black
		 * Sapphire Metallic, #GW228071"
		 *
		 * @param  int $post_id Vehicle post ID.
		 * @return string
		 */
		protected function create_option_label( $post_id ) {
			return sprintf(
				'%s %s %s %s, %s, #%s',
				invp_get_the_year( $post_id ),
				invp_get_the_make( $post_id ),
				invp_get_the_model( $post_id ),
				invp_get_the_trim( $post_id ),
				invp_get_the_color( $post_id ),
				invp_get_the_stock_number( $post_id )
			);
		}

		/**
		 * Takes a vehicle post ID, returns a string like "2016 Toyota Corolla L,
		 * P03013"
		 *
		 * @param  int $post_id Vehicle post ID.
		 * @return string
		 */
		protected function create_option_value( $post_id ) {
			return sprintf(
				'%s %s %s %s, %s',
				invp_get_the_year( $post_id ),
				invp_get_the_make( $post_id ),
				invp_get_the_model( $post_id ),
				invp_get_the_trim( $post_id ),
				invp_get_the_stock_number( $post_id )
			);
		}

		/**
		 * Get the post IDs of vehicles sorted by year, then make, then model.
		 *
		 * @return array
		 */
		protected function get_vehicle_post_ids() {
			global $wpdb;
			return $wpdb->get_col(
				$wpdb->prepare(
					"
					SELECT 		ID

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
								meta3.meta_value ASC;
				",
					INVP::POST_TYPE,
					apply_filters( 'invp_prefix_meta_key', 'year' ),
					apply_filters( 'invp_prefix_meta_key', 'make' ),
					apply_filters( 'invp_prefix_meta_key', 'model' ),
				)
			);
		}
	}
}
