<?php
defined( 'ABSPATH' ) or exit;

/**
 * Inventory_Presser_Contact_Form_7
 * 
 * Adds a form tag that creates a vehicle selector or hidden vehicle input on
 * vehicle singles.
 */
class Inventory_Presser_Contact_Form_7
{
	public function add_hooks()
	{
		add_action( 'wpcf7_init', array( $this, 'add_form_tags' ) );
	}
 
	public function add_form_tags()
	{
		wpcf7_add_form_tag(
			array( 'invp_vehicle', 'invp_vehicle*', ),
			array( $this, 'handler_vehicle' ),
			array( 'name-attr' => true )
		);
	}

	public function handler_vehicle( $tag )
	{
		$validation_error = wpcf7_get_validation_error( $tag->name );

		$atts = array(
			'id'         => '',
			'value_type' => 'ymm',
		);

		if( ! empty( $tag->options ) )
		{
			array_walk( $tag->options, function( $item ) use ( &$atts ) {
				list($key, $val) = explode(':', $item);
				$atts[$key] = $val;
			} );
		}

		$value_types = array(
			'post_id',
			'stock_number',
			'ymm',
		);

		if( ! in_array( $atts['value_type'], $value_types ) )
		{
			$atts['value_type'] = 'ymm';
		}

		//are we on a vdp? return a hidden field
		if( is_singular( INVP::POST_TYPE ) )
		{
			$input_atts = array(
				'type'  => 'hidden',
				'name'  => $tag->name,
				'value' => $this->prepare_value( $atts['value_type'] )
			);
			if( ! empty( $atts['id'] ) )
			{
				$input_atts['id'] = $atts['id'];
			}
			$html = sprintf( 
				'<input %s />',
				wpcf7_format_atts( $input_atts ),
			);
			return apply_filters( 'invp_cf7_field_vehicle_html', $html . $validation_error, $atts );
		}

		/**
		 * Here's a query to get the post IDs of the vehicles we want
		 * in the order we want. The reason we are not using get_posts
		 * is because we want to sort by 3 post meta values
		 */
		global $wpdb;
		$post_IDs = $wpdb->get_col( $wpdb->prepare(
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
		) );

		//no results? no HTML
		if( empty( $post_IDs ) )
		{
			return apply_filters( 'invp_cf7_field_vehicle_html', '' . $validation_error, $atts );
		}

		//build a drop down select
		$select_atts = array(
			'name' => $tag->name,
			'class' => wpcf7_form_controls_class( 'select' )
		);
		if( ! empty( $atts['id'] ) )
		{
			$select_atts['id'] = $atts['id'];
		}
		$html = sprintf(
			'<span class="wpcf7-form-control-wrap" data-name="%s"><select %s><option value="">%s</option>',
			esc_attr( $tag->name ),
			wpcf7_format_atts( $select_atts ),
			__( 'Please choose a vehicle...', 'inventory-presser' )
		);
		foreach ( $post_IDs as $post_ID )
		{
			//is this vehicle sold?
			if( has_term( 'sold', 'availability', $post_ID ) )
			{
				continue;
			}
			$meta = get_metadata( 'post', $post_ID );

			//label is "2005 Subaru Baja Turbo, Blue, #12022"
			$html .= sprintf(
				'<option value="%s" %s>%s %s %s',
				esc_attr( $this->prepare_value( $atts['value_type'], $post_ID ) ),
				isset( $_REQUEST['v'] ) ? selected( $post_ID, $_REQUEST['v'], false ) : '',
				esc_html( $meta[apply_filters( 'invp_prefix_meta_key', 'year' )][0] ),
				esc_html( $meta[apply_filters( 'invp_prefix_meta_key', 'make' )][0] ),
				esc_html( $meta[apply_filters( 'invp_prefix_meta_key', 'model' )][0] )
			);

			if( isset( $meta[apply_filters( 'invp_prefix_meta_key', 'trim' )][0] ) )
			{
				$html .= ' ' . $meta[apply_filters( 'invp_prefix_meta_key', 'trim' )][0];
			}

			if( isset( $meta[apply_filters( 'invp_prefix_meta_key', 'color' )][0] ) )
			{
				$html .= ', ' . $meta[apply_filters( 'invp_prefix_meta_key', 'color' )][0];
			}

			$html .= sprintf(
				', &#35;%s</option>',
				$meta[apply_filters( 'invp_prefix_meta_key', 'stock_number' )][0]
			);
		}
		return apply_filters( 'invp_cf7_field_vehicle_html', $html . '</select></span>' . $validation_error , $atts );
	}

	protected function prepare_value( $value, $post_ID = null )
	{
		$post_ID = $post_ID ?? get_the_ID();
		switch( $value )
		{
			case 'post_id':
				return $post_ID;

			case 'stock_number':
				return invp_get_the_stock_number( $post_ID );

			case 'ymm':
				$value = sprintf( '%s %s %s',
					invp_get_the_year( $post_ID ),
					invp_get_the_make( $post_ID ),
					invp_get_the_model( $post_ID )
				);
				if( $trim = invp_get_the_trim( $post_ID ) )
				{
					$value .= ' ' . $trim;
				}
				return $value;
		}
	}
}
