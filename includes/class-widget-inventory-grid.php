<?php

class Inventory_Presser_Grid extends WP_Widget {

	const ID_BASE = '_invp_inventory_grid';

	function __construct() {
		parent::__construct(
			self::ID_BASE,
			'Grid',
			array( 'description' => 'Display a grid of vehicles.', )
		);

		add_action( 'invp_delete_all_data', array( $this, 'delete_option' ) );
	}

	public function delete_option() {
		delete_option( 'widget_' . self::ID_BASE );
	}

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

	// front-end
	public function widget( $args, $instance ) {

		$title = apply_filters( 'widget_title', $instance['title'] );
		$columns = (isset($instance['columns'])) ? $instance['columns'] : 5;
		$limit = (isset($instance['limit'])) ? $instance['limit'] : $columns * 3;
		$show_captions = (isset($instance['cb_showcaptions']) && $instance['cb_showcaptions'] == 'true');
		$show_button = (isset($instance['cb_showbutton']) && $instance['cb_showbutton'] == 'true');

		switch ($columns) {
		    case 3:
		        $col_class = 'one-third';
		        break;
		    case 4:
		        $col_class = 'one-fourth';
		        break;
		    case 5:
		        $col_class = 'one-fifth';
		        break;
		}

		$limit = ($limit != 0) ? $limit : -1;

		// before and after widget arguments are defined by themes
		echo $args['before_widget'];
		if ( ! empty( $title ) ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}

		$gp_args = array(
			'posts_per_page' => $limit,
			'post_type'      => Inventory_Presser_Plugin::CUSTOM_POST_TYPE,
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

		if( isset( $instance['newest_first'] ) && $instance['newest_first'] ) {
			$gp_args['meta_key'] = apply_filters( 'invp_prefix_meta_key', 'last_modified' );
			$gp_args['orderby'] = 'STR_TO_DATE( meta1.meta_value, \'%a, %d %b %Y %T\' )';
			$gp_args['order']   = 'DESC';
		}

		//Does the user want featured vehicles only?
		if( isset( $instance['cb_featured_only'] ) && 'true' == $instance['cb_featured_only'] ) {
			$gp_args['meta_query'][] = array(
				'key'   => apply_filters( 'invp_prefix_meta_key', 'featured' ),
				'value' => '1',
			);
		}

		$inventory_ids = get_posts( $gp_args );
		if( empty( $inventory_ids ) ) {
			return;
		}

		$grid_html = '<div class="invp-grid pad cf"><ul class="grid-slides">';

		foreach ($inventory_ids as $inventory_id) {

			$vehicle = new Inventory_Presser_Vehicle( $inventory_id );

			$grid_html .= sprintf(
				'<li class="grid %s"><a class="grid-link" href="%s"><div class="grid-image" style="background-image: url(%s);"></div>',
				$col_class,
				$vehicle->url,
				wp_get_attachment_image_url( get_post_thumbnail_id( $inventory_id ), 'large' )
			);

			if ( $show_captions ) {
				$grid_html .= sprintf(
					'<p class="grid-caption">%s&nbsp;&nbsp;%s</p>',
					$vehicle->post_title,
					$vehicle->price(' ')
				);
			}

			$grid_html .= '</a></li>';
		}

		$grid_html .= '</ul></div>';

		if ( $show_button ) {
			$grid_html .= sprintf(
				'<div class="invp-grid-button"><a href="%s" class="_button _button-med">%s</a></div>',
				get_post_type_archive_link( Inventory_Presser_Plugin::CUSTOM_POST_TYPE ),
				__( 'Full Inventory', 'inventory-presser' )
			);
		}
		echo $grid_html . $args['after_widget'];
	}

	// Widget Backend
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

	// Updating widget replacing old instances with new
	public function update( $new_instance, $old_instance ) {

		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['columns'] = ( ! empty( $new_instance['columns'] ) ) ? strip_tags( $new_instance['columns'] ) : 5;
		$instance['limit'] = ( ! empty( $new_instance['limit'] ) ) ? strip_tags( $new_instance['limit'] ) : 15;
		$instance['cb_showcaptions'] = ( !empty( $new_instance['cb_showcaptions'] ) ) ? $new_instance['cb_showcaptions'] : '';
		$instance['cb_showbutton'] = ( !empty( $new_instance['cb_showbutton'] ) ) ? $new_instance['cb_showbutton'] : '';
		$instance['cb_featured_only'] = ( !empty( $new_instance['cb_featured_only'] ) ) ? $new_instance['cb_featured_only'] : '';
		$instance['newest_first'] = ( !empty( $new_instance['newest_first'] ) ) ? $new_instance['newest_first'] == 'true' : false;
		return $instance;
	}
}
