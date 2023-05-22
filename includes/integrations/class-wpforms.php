<?php
defined( 'ABSPATH' ) || exit;

//List of all built-in smart tags? https://formviewswp.com/docs/how-to-use-wpforms-smart-tags-in-custom-html-field/

/**
 * Inventory_Presser_WPForms
 * 
 * Adds a smart tag that creates ADF XML for email bodies sent to CRMs.
 */
class Inventory_Presser_WPForms {

	public function add_hooks() {
		add_filter( 'wpforms_smart_tags', array( $this, 'smart_tags_register' ), 10, 1 );
		add_filter( 'wpforms_smart_tag_process', array( $this, 'smart_tags_process' ), 10, 2 );

		// No hook because this add_hooks() method is called at plugins_loaded hook.
		if ( ! class_exists( 'WPForms_Field' ) || ! empty( $this->field_instance ) ) {
			return;
		}
		include_once plugin_dir_path( INVP_PLUGIN_FILE_PATH ) . 'includes/integrations/class-wpforms-field-vehicle.php';
		$this->field_instance = new WPForms_Field_Vehicle();

		// Add our field type to the list of allowed fields in wpforms_get_form_fields().
		add_filter( 'wpforms_get_form_fields_allowed', array( $this, 'add_field_types' ) );
	}

	public function add_field_types( $allowed_form_fields ) {
		$allowed_form_fields[] = 'vehicle';
		return $allowed_form_fields;
	}
	
	/**
	 * adf_xml
	 *
	 * @return string
	 */
	protected function adf_xml() {
		$form_post_id = intval( $_POST['wpforms']['id'] );
		$fields = wpforms_get_form_fields( $form_post_id );

		$field_id = 0;
		foreach( $fields as $field ) {
			if ( 'vehicle' !== $field['type'] ) {
				continue;
			}
			$field_id = $field['id'];
			break;
		}

		if ( 0 === $field_id ) {
			return '';
		}

		$value = sanitize_text_field( $_POST['wpforms']['fields'][ $field_id ] );
		$post_id = $this->extract_post_id_from_value( $value );

		if ( empty( $post_id ) ) {
			return '';
		}
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

	/**
	 * Register Smart Tags with the form builder.
	 *
	 * @link   https://wpforms.com/developers/how-to-create-a-custom-smart-tag/
	 *
	 * @param  array $tags
	 * @return array
	 */
	public function smart_tags_register( $tags ) {
		// Key is the tag, item is the tag name.
		foreach ( $this->tags() as $tag => $name ) {
			$tags[ $tag ] = $name;
		}
		return $tags;
	}

	/**
	 * Process the Smart Tags when rendering entries.
	 *
	 * @link   https://wpforms.com/developers/how-to-create-a-custom-smart-tag/
	 */
	public function smart_tags_process( $content, $tag ) {
		$our_tags = array_keys( $this->tags() );
		if ( ! in_array( $tag, $our_tags, true ) ) {
			return $content;
		}
		switch( $tag ) {
			case 'invp_adf_vehicle':
				$content = str_replace( '{invp_adf_vehicle}', $this->adf_xml(), $content );
				break;
			case 'invp_site_url':
				$content = str_replace( '{invp_site_url}', site_url(), $content );
				break;
		}
		return $content;
	}

	protected function tags() {
		return array(
			'invp_adf_vehicle' => __( 'ADF XML Vehicle', 'inventory-presser' ),
			'invp_site_url'    => __( 'Site URL', 'inventory-presser' ),
		);
	}
}
