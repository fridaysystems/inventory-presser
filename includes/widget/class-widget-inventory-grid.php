<?php
defined( 'ABSPATH' ) || exit;

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
	public function __construct() {
		parent::__construct(
			self::ID_BASE,
			'Grid',
			array(
				'description'           => __( 'Display a grid of vehicles.', 'inventory-presser' ),
				'show_instance_in_rest' => true,
			)
		);

		add_action( 'invp_delete_all_data', array( $this, 'delete_option' ) );
	}

	/**
	 * Deletes the option that stores this widget's data.
	 *
	 * @return void
	 */
	public function delete_option() {
		delete_option( 'widget_' . self::ID_BASE );
	}

	/**
	 * Creates a string of <option> HTML elements based on a provided WP_Term.
	 *
	 * @param  string $selected_term The saved number of columns.
	 * @return string HTML That creates <option> HTML elements
	 */
	private function get_column_options_html( $selected_term ) {
		$html = '';
		foreach ( array( 3, 4, 5 ) as $columns ) {
			$html .= sprintf(
				'<option value="%1$d"%2$s>%3$d %4$s</option>',
				esc_attr( $columns ),
				selected( $selected_term === $columns, true, false ),
				esc_html( $columns ),
				esc_html__( 'columns', 'inventory-presser' )
			);
		}
		return $html;
	}

	/**
	 * Creates HTML that renders the widget front-end.
	 *
	 * @param  array $args The widget's settings.
	 * @return string The HTML that creates the widget front-end
	 */
	public function content( $args ) {

		// Need the stylesheet for this content.
		wp_enqueue_style( 'invp-grid' );

		$default_args = array(
			'columns'                 => 5,     // In how many columns should the tiles be arranged?
			'featured_only'           => false, // Include only featured vehicles?
			'limit'                   => 15,    // How many vehicles in the grid maximum?
			'make'                    => '',    // Only show vehicles of this make.
			'model'                   => '',    // Only show vehicles of this model.
			'newest_first'            => false, // Sort the most recently modified vehicles first?
			'priced_first'            => false, // Sort the vehicles with prices first?
			'show_button'             => false, // Display a "Full Inventory" button below the grid?
			'show_captions'           => false, // Show text captions near each photo?
			'show_odometers'          => false, // Show odometers in the captions?
			'show_prices'             => false, // Show prices in the captions?
			'suppress_call_for_price' => false, // When the price setting is {$Price}, this prevents "Call for price" in the grid.
		);
		$args         = wp_parse_args( $args, $default_args );

		// Make sure the limit is not zero or empty string.
		if ( empty( $args['limit'] ) ) {
			$args['limit'] = apply_filters( 'invp_query_limit', 1000, __METHOD__ );
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
			'orderby'        => 'rand', // Defaults to random order.
			'order'          => 'ASC',
			'tax_query'      => array(),
		);

		if ( $args['newest_first'] ) {
			// Change the order to last_modified date.
			$post_args['meta_key'] = apply_filters( 'invp_prefix_meta_key', 'last_modified' );
			global $wpdb;
			$post_args['orderby'] = "STR_TO_DATE( {$wpdb->postmeta}.meta_value, '%a, %d %b %Y %T' )";
			$post_args['order']   = 'DESC';
		}

		// Do we want featured vehicles only?
		if ( $args['featured_only'] ) {
			$post_args['meta_query'][] = array(
				'key'   => apply_filters( 'invp_prefix_meta_key', 'featured' ),
				'value' => '1',
			);
		}

		// Are we only showing 1 make?
		if ( ! empty( $args['make'] ) ) {
			$post_args['tax_query'][] = array(
				'taxonomy' => 'make',
				'field'    => 'slug',
				'terms'    => $args['make'],
			);
		}

		// Are we only showing 1 model?
		if ( ! empty( $args['model'] ) ) {
			$post_args['meta_query'][] = array(
				'key'     => apply_filters( 'invp_prefix_meta_key', 'model' ),
				'value'   => $args['model'],
				'compare' => 'LIKE',
			);
		}

		// Should we exclude sold vehicles?
		$plugin_settings = INVP::settings();
		if ( isset( $plugin_settings['include_sold_vehicles'] ) && ! $plugin_settings['include_sold_vehicles'] ) {
			$post_args['tax_query'][] = Inventory_Presser_Taxonomies::tax_query_exclude_sold();
		}

		$inventory_ids = get_posts( $post_args );
		if ( empty( $inventory_ids ) ) {
			return;
		}

		// Do we want priced vehicles first?
		if ( $args['priced_first'] ) {
			// Yes. Scan the results for vehicles without prices.
			$ids_without_prices = array();
			foreach ( $inventory_ids as $inventory_id ) {
				if ( empty( get_post_meta( $inventory_id, apply_filters( 'invp_prefix_meta_key', 'price' ) ) ) ) {
					$ids_without_prices[] = $inventory_id;
				}
			}
			if ( ! empty( $ids_without_prices ) ) {
				// Remove IDs and then add them to the end of the array.
				$inventory_ids = array_merge( array_diff( $inventory_ids, $ids_without_prices ), $ids_without_prices );
			}
		}

		$grid_html = sprintf(
			'<div class="invp-grid"><ul class="grid-slides columns-%s">',
			$args['columns']
		);

		foreach ( $inventory_ids as $inventory_id ) {

			$grid_html .= sprintf(
				'<li><a class="grid-link" href="%s"><div class="grid-image" style="background-image: url(%s);"></div>',
				get_the_permalink( $inventory_id ),
				invp_get_the_photo_url( 'large', $inventory_id )
			);

			if ( $args['show_captions'] ) {
				$grid_html .= '<p class="grid-caption">' . get_the_title( $inventory_id );

				if ( $args['show_odometers'] ) {
					$grid_html .= sprintf(
						'<span class="grid-odometer">%s</span>',
						invp_get_the_odometer( ' ' . apply_filters( 'invp_odometer_word', 'mi' ), $inventory_id )
					);
				}

				if ( $args['show_prices'] ) {
					$grid_html .= sprintf(
						'<span class="grid-price">%s</span>',
						invp_get_the_price( $args['suppress_call_for_price'] ? ' ' : null, $inventory_id )
					);
				}

				$grid_html .= '</p>';
			}

			$grid_html .= '</a></li>';
		}
		$grid_html .= '</ul></div>';

		if ( $args['show_button'] ) {
			$grid_html .= sprintf(
				'<div class="invp-grid-button"><button onclick="location.href=\'%s\';" class="button wp-element-button">%s</button></div>',
				esc_url( get_post_type_archive_link( INVP::POST_TYPE ) ),
				esc_html__( 'Full Inventory', 'inventory-presser' )
			);
		}

		return $grid_html;
	}

	/**
	 * Outputs the widget front-end HTML
	 *
	 * @param  array $args
	 * @param  array $instance
	 * @return void
	 */
	public function widget( $args, $instance ) {

		// build $args array.
		$content_args = array();
		if ( isset( $instance['columns'] ) ) {
			$content_args['columns'] = $instance['columns'];
		}
		if ( isset( $instance['limit'] ) ) {
			$content_args['limit'] = $instance['limit'];
		}
		if ( isset( $instance['cb_showcaptions'] ) ) {
			$content_args['show_captions'] = ( 'true' === $instance['cb_showcaptions'] );
		}
		if ( isset( $instance['cb_showbutton'] ) ) {
			$content_args['show_button'] = ( 'true' === $instance['cb_showbutton'] );
		}
		if ( isset( $instance['cb_showprices'] ) ) {
			$content_args['show_prices'] = ( 'true' === $instance['cb_showprices'] );
		}
		if ( isset( $instance['newest_first'] ) ) {
			$content_args['newest_first'] = filter_var( $instance['newest_first'], FILTER_VALIDATE_BOOLEAN );
		}

		// before and after widget arguments are defined by themes.
		echo $args['before_widget'];
		$title = apply_filters( 'widget_title', $instance['title'] ?? '' );
		if ( ! empty( $title ) ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}

		echo $this->content( $content_args ) . $args['after_widget'];
	}

	/**
	 * Outputs the widget settings form that is shown in the dashboard.
	 *
	 * @param  array $instance The widget settings.
	 * @return void
	 */
	public function form( $instance ) {

		$title   = isset( $instance['title'] ) ? $instance['title'] : '';
		$columns = ( isset( $instance['columns'] ) ) ? $instance['columns'] : 5;
		$limit   = ( isset( $instance['limit'] ) ) ? $instance['limit'] : $columns * 3;

		// Widget admin form.
		?>
		<p>
		<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'inventory-presser' ); ?></label>
		<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'columns' ) ); ?>"><?php esc_html_e( 'Column count:', 'inventory-presser' ); ?></label>
			<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'columns' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'columns' ) ); ?>">
			<?php echo $this->get_column_options_html( $columns ); ?>
			</select>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'limit' ) ); ?>"><?php esc_html_e( 'Maximum:', 'inventory-presser' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'limit' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'limit' ) ); ?>" type="number" value="<?php echo esc_attr( $limit ); ?>" />
		</p>
		<p>
			<input type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'cb_showcaptions' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'cb_showcaptions' ) ); ?>" value="true"<?php checked( 'true', isset( $instance['cb_showcaptions'] ) ? $instance['cb_showcaptions'] : '', true ); ?>>
			<label for="<?php echo esc_attr( $this->get_field_id( 'cb_showcaptions' ) ); ?>"><?php esc_html_e( 'Show captions', 'inventory-presser' ); ?></label>
			<br />
			<input type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'cb_showprices' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'cb_showprices' ) ); ?>" value="true"<?php checked( 'true', isset( $instance['cb_showprices'] ) ? $instance['cb_showprices'] : '', true ); ?>>
			<label for="<?php echo esc_attr( $this->get_field_id( 'cb_showprices' ) ); ?>"><?php esc_html_e( 'Show prices', 'inventory-presser' ); ?></label>
			<br />
			<input type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'cb_showbutton' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'cb_showbutton' ) ); ?>" value="true"<?php checked( 'true', isset( $instance['cb_showbutton'] ) ? $instance['cb_showbutton'] : '', true ); ?>>
			<label for="<?php echo esc_attr( $this->get_field_id( 'cb_showbutton' ) ); ?>"><?php esc_html_e( 'Show "Full Inventory" button', 'inventory-presser' ); ?></label>
			<br />
			<input type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'cb_featured_only' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'cb_featured_only' ) ); ?>" value="true"<?php checked( 'true', isset( $instance['cb_featured_only'] ) ? $instance['cb_featured_only'] : '', true ); ?>>
			<label for="<?php echo esc_attr( $this->get_field_id( 'cb_featured_only' ) ); ?>"><?php esc_html_e( 'Featured vehicles only', 'inventory-presser' ); ?></label>
			<br />
			<input type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'newest_first' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'newest_first' ) ); ?>" value="true"<?php checked( true, isset( $instance['newest_first'] ) ? $instance['newest_first'] : false, true ); ?>>
			<label for="<?php echo esc_attr( $this->get_field_id( 'newest_first' ) ); ?>"><?php esc_html_e( 'Newest vehicles first', 'inventory-presser' ); ?></label>
		</p>
		<?php
	}

	/**
	 * Saves the widget settings when a dashboard user clicks the Save button.
	 *
	 * @param  array $new_instance The new widget options sent from the form.
	 * @param  array $old_instance The old, saved widget options.
	 * @return array The updated array full of settings
	 */
	public function update( $new_instance, $old_instance ) {

		$instance                     = array();
		$instance['title']            = ( ! empty( $new_instance['title'] ) ) ? wp_strip_all_tags( $new_instance['title'] ) : '';
		$instance['columns']          = ( ! empty( $new_instance['columns'] ) ) ? wp_strip_all_tags( $new_instance['columns'] ) : 5;
		$instance['limit']            = ( ! empty( $new_instance['limit'] ) ) ? wp_strip_all_tags( $new_instance['limit'] ) : 15;
		$instance['cb_showcaptions']  = ( ! empty( $new_instance['cb_showcaptions'] ) ) ? $new_instance['cb_showcaptions'] : '';
		$instance['cb_showprices']    = ( ! empty( $new_instance['cb_showprices'] ) ) ? $new_instance['cb_showprices'] : '';
		$instance['cb_showbutton']    = ( ! empty( $new_instance['cb_showbutton'] ) ) ? $new_instance['cb_showbutton'] : '';
		$instance['cb_featured_only'] = ( ! empty( $new_instance['cb_featured_only'] ) ) ? $new_instance['cb_featured_only'] : '';
		$instance['newest_first']     = ! empty( $new_instance['newest_first'] );
		return $instance;
	}
}
