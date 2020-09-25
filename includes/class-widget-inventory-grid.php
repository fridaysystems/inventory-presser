<?php
defined( 'ABSPATH' ) or exit;

/**
 * Inventory_Presser_Grid
 * 
 * This class creates the Grid widget.
 */
class Inventory_Presser_Grid extends WP_Widget {

	const ID_BASE = '_invp_inventory_grid';

	/**
	 * __construct
	 *
	 * Calls the parent class' contructor and adds a hook that will delete the
	 * option that stores this widget's data when the plugin's delete all data
	 * method is run.
	 * 
	 * @return void
	 */
	function __construct() {
		parent::__construct(
			self::ID_BASE,
			'Grid',
			array( 'description' => 'Display a grid of vehicles.', )
		);

		add_action( 'invp_delete_all_data', array( $this, 'delete_option' ) );
	}

	/**
	 * delete_option
	 * 
	 * Deletes the option that stores this widget's data.
	 *
	 * @return void
	 */
	public function delete_option() {
		delete_option( 'widget_' . self::ID_BASE );
	}
	
	/**
	 * get_column_options_html
	 *
	 * @param  string $selected_term The saved number of columns
	 * @return string HTML That creates <option> HTML elements
	 */
	private function get_column_options_html($selected_term) {
 		$html = '';
 		foreach( [ 3, 4, 5 ] as $columns ) {
 			$html .= sprintf(
 				'<option value="%1$d"%2$s>%1$d columns</option>',
 				$columns,
 				selected( $selected_term == $columns, true, false )
 			);
 		}
 		return $html;
 	}

 	/**
 	 * content
	 *
	 * Creates HTML that renders the widget front-end.
	 * 
 	 * @param  array $args The widget's settings
 	 * @return string The HTML that creates the widget front-end
 	 */
 	function content( $args ) {

		//Need the stylesheet for this content
		wp_enqueue_style( 'invp-grid' );

 		/**
 		 * $args array keys
 		 * ----------------
 		 * + columns
 		 * + featured_only
 		 * + limit
 		 * + newest_first
 		 * + show_button
 		 * + show_captions
 		 * + show_prices
 		 */
 		$default_args = array(
 			'columns'       => 5,
 			'featured_only' => false,
 			'limit'         => 15,
 			'newest_first'  => false,
 			'show_button'   => false,
 			'show_captions' => false,
 			'show_prices'   => false,
 		);
 		$args = wp_parse_args( $args, $default_args );

		//Make sure the limit is not zero or empty string
		if( empty( $args['limit'] ) ) {
			$args['limit'] = -1;
		}

		$post_args = array(
			'posts_per_page' => $args['limit'],
			'post_type'      => INVP::POST_TYPE,
			'meta_query'     => array(
				array(
					'key'     => '_thumbnail_id',
					'compare' => 'EXISTS',
				),
			),
			'fields'         => 'ids',
			'orderby'        => 'rand',
			'order'          => 'ASC'
		);

		if( $args['newest_first'] ) {
			$post_args['meta_key'] = apply_filters( 'invp_prefix_meta_key', 'last_modified' );
			$post_args['orderby']  = 'STR_TO_DATE( meta1.meta_value, \'%a, %d %b %Y %T\' )';
			$post_args['order']    = 'DESC';
		}

		//Does the user want featured vehicles only?
		if( $args['featured_only'] ) {
			$post_args['meta_query'][] = array(
				'key'   => apply_filters( 'invp_prefix_meta_key', 'featured' ),
				'value' => '1',
			);
		}

		$inventory_ids = get_posts( $post_args );
		if( empty( $inventory_ids ) ) {
			return;
		}

		$grid_html = sprintf(
			'<div class="invp-grid"><ul class="grid-slides columns-%s">',
			$args['columns']
		);

		foreach( $inventory_ids as $inventory_id ) {

			$vehicle = new Inventory_Presser_Vehicle( $inventory_id );

			$grid_html .= sprintf(
				'<li><a class="grid-link" href="%s"><div class="grid-image" style="background-image: url(%s);"></div>',
				$vehicle->url,
				wp_get_attachment_image_url( get_post_thumbnail_id( $inventory_id ), 'large' )
			);

			if( $args['show_captions'] ) {
				$grid_html .= sprintf(
					'<p class="grid-caption">%s&nbsp;%s</p>',
					$vehicle->post_title,
					$args['show_prices'] ? $vehicle->price(' ') : ''
				);
			}

			$grid_html .= '</a></li>';
		}
		$grid_html .= '</ul></div>';

		if( $args['show_button'] ) {
			$grid_html .= sprintf(
				'<div class="invp-grid-button"><button onclick="location.href=\'%s\';" class="button">%s</button></div>',
				get_post_type_archive_link( INVP::POST_TYPE ),
				__( 'Full Inventory', 'inventory-presser' )
			);
		}

		return $grid_html;
 	}

	/**
	 * widget
	 * 
	 * Outputs the widget front-end HTML
	 *
	 * @param  array $args
	 * @param  array $instance
	 * @return void
	 */
	public function widget( $args, $instance ) {

		//build $args array
		$content_args = array();
		if( isset( $instance['columns'] ) ) {
			$content_args['columns'] = $instance['columns'];
		}
		if( isset( $instance['limit'] ) ) {
			$content_args['limit'] = $instance['limit'];
		}
		if( isset( $instance['cb_showcaptions'] ) ) {
			$content_args['show_captions'] = ( $instance['cb_showcaptions'] == 'true' );
		}
		if( isset( $instance['cb_showbutton'] ) ) {
			$content_args['show_button'] = ( $instance['cb_showbutton'] == 'true' );
		}
		if( isset( $instance['cb_showprices'] ) ) {
			$content_args['show_prices'] = ( $instance['cb_showprices'] == 'true' );
		}

		// before and after widget arguments are defined by themes
		echo $args['before_widget'];
		$title = apply_filters( 'widget_title', $instance['title'] );
		if ( ! empty( $title ) ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}

		echo $this->content( $content_args ) . $args['after_widget'];
	}

	/**
	 * form
	 * 
	 * Outputs the widget settings form that is shown in the dashboard.
	 *
	 * @param  array $instance
	 * @return void
	 */
	public function form( $instance ) {

		$title = isset($instance[ 'title' ]) ? $instance[ 'title' ] : '';
		$columns = (isset($instance['columns'])) ? $instance['columns'] : 5;
		$limit = (isset($instance['limit'])) ? $instance['limit'] : $columns * 3;

		// Widget admin form
		?>
		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'inventory-presser' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>

		<p>
		<label for="<?php echo $this->get_field_id('columns'); ?>"><?php _e( 'Column count:', 'inventory-presser' ); ?></label>
		<select class="widefat" id="<?php echo $this->get_field_id('columns'); ?>" name="<?php echo $this->get_field_name('columns'); ?>">
		<?php echo $this->get_column_options_html($columns); ?>
		</select>
		</p>
		<p>
		<label for="<?php echo $this->get_field_id( 'limit' ); ?>"><?php _e( 'Maximum:', 'inventory-presser' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id('limit'); ?>" name="<?php echo $this->get_field_name('limit'); ?>" type="number" value="<?php echo esc_attr( $limit ); ?>" />
		</p>
		<p>
			<input type="checkbox" id="<?php echo $this->get_field_id('cb_showcaptions'); ?>" name="<?php echo $this->get_field_name('cb_showcaptions'); ?>" value="true"<?php checked( 'true', isset( $instance['cb_showcaptions'] ) ? $instance['cb_showcaptions'] : '', true ); ?>>
			<label for="<?php echo $this->get_field_id('cb_showcaptions'); ?>"><?php _e( 'Show captions', 'inventory-presser' ); ?></label>
			<br />
			<input type="checkbox" id="<?php echo $this->get_field_id('cb_showprices'); ?>" name="<?php echo $this->get_field_name('cb_showprices'); ?>" value="true"<?php checked( 'true', isset( $instance['cb_showprices'] ) ? $instance['cb_showprices'] : '', true ); ?>>
			<label for="<?php echo $this->get_field_id('cb_showprices'); ?>"><?php _e( 'Show prices', 'inventory-presser' ); ?></label>
			<br />
			<input type="checkbox" id="<?php echo $this->get_field_id('cb_showbutton'); ?>" name="<?php echo $this->get_field_name('cb_showbutton'); ?>" value="true"<?php checked( 'true', isset( $instance['cb_showbutton'] ) ? $instance['cb_showbutton'] : '', true ); ?>>
			<label for="<?php echo $this->get_field_id('cb_showbutton'); ?>"><?php _e( 'Show "Full Inventory" button', 'inventory-presser' ); ?></label>
			<br />
			<input type="checkbox" id="<?php echo $this->get_field_id('cb_featured_only'); ?>" name="<?php echo $this->get_field_name('cb_featured_only'); ?>" value="true"<?php checked( 'true', isset( $instance['cb_featured_only'] ) ? $instance['cb_featured_only'] : '', true ); ?>>
			<label for="<?php echo $this->get_field_id('cb_featured_only'); ?>"><?php _e( 'Featured vehicles only', 'inventory-presser' ); ?></label>
			<br />
			<input type="checkbox" id="<?php echo $this->get_field_id( 'newest_first' ); ?>" name="<?php echo $this->get_field_name( 'newest_first' ); ?>" value="true"<?php checked( true, isset( $instance['newest_first'] ) ? $instance['newest_first'] : false, true ); ?>>
			<label for="<?php echo $this->get_field_id( 'newest_first' ); ?>"><?php _e( 'Newest vehicles first', 'inventory-presser' ); ?></label>
		</p>

		<?php
	}

	/**
	 * update
	 *
	 * Saves the widget settings when a dashboard user clicks the Save button.
	 * 
	 * @param  array $new_instance
	 * @param  array $old_instance
	 * @return array The updated array full of settings
	 */
	public function update( $new_instance, $old_instance ) {

		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['columns'] = ( ! empty( $new_instance['columns'] ) ) ? strip_tags( $new_instance['columns'] ) : 5;
		$instance['limit'] = ( ! empty( $new_instance['limit'] ) ) ? strip_tags( $new_instance['limit'] ) : 15;
		$instance['cb_showcaptions'] = ( !empty( $new_instance['cb_showcaptions'] ) ) ? $new_instance['cb_showcaptions'] : '';
		$instance['cb_showprices'] = ( !empty( $new_instance['cb_showprices'] ) ) ? $new_instance['cb_showprices'] : '';
		$instance['cb_showbutton'] = ( !empty( $new_instance['cb_showbutton'] ) ) ? $new_instance['cb_showbutton'] : '';
		$instance['cb_featured_only'] = ( !empty( $new_instance['cb_featured_only'] ) ) ? $new_instance['cb_featured_only'] : '';
		$instance['newest_first'] = ( !empty( $new_instance['newest_first'] ) ) ? $new_instance['newest_first'] == 'true' : false;
		return $instance;
	}
}
