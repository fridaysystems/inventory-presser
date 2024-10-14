<?php
defined( 'ABSPATH' ) || exit;

/**
 * Inventory_Presser_Carfax_Widget
 *
 * This class creates the Carfax Badge widget.
 */
class Inventory_Presser_Carfax_Widget extends WP_Widget {


	/**
	 * Creates an array that defines the available images the widget can display
	 *
	 * @return array
	 */
	protected function images() {
		return array(
			'default'    => array(
				'text' => __( 'Simple Show Me Logo', 'inventory-presser' ),
				'img'  => 'show-me-carfax.svg',
			),
			'advantage'  => array(
				'text' => __( 'Advantage Dealer Badge', 'inventory-presser' ),
				'img'  => 'carfax-advantage-dealer.png',
			),
			'dealership' => array(
				'text' => __( 'Car Fox Dealership', 'inventory-presser' ),
				'img'  => 'carfax-portrait-blue.jpg',
			),
			'foxleft'    => array(
				'text' => __( 'Car Fox Left', 'inventory-presser' ),
				'img'  => 'carfax-show-me-blue.png',
			),
			'foxoval'    => array(
				'text' => __( 'Car Fox Oval', 'inventory-presser' ),
				'img'  => 'carfax-show-me-blue-oval.png',
			),
			'landscape'  => array(
				'text' => __( 'Landscape Blue', 'inventory-presser' ),
				'img'  => 'carfax-show-me-landscape.jpg',
			),
		);
	}

	const ID_BASE = '_invp_carfax';

	/**
	 * Calls the parent class' contructor and adds a hook that will delete the
	 * option that stores this widget's data when the plugin's delete all data
	 * method is run.
	 *
	 * @return void
	 */
	public function __construct() {
		parent::__construct(
			self::ID_BASE,
			'Carfax Badge',
			array(
				'description'           => __( 'Choose a Carfax badge that links to your inventory.', 'inventory-presser' ),
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
	 * Outputs the content of the widget
	 *
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance ) {

		$image_keys = array_keys( $this->images() );
		$image      = $image_keys[0];
		if ( ! empty( $instance['image'] ) && in_array( $instance['image'], $image_keys, true ) ) {
			$image = $instance['image'];
		}

		$path = dirname( INVP_PLUGIN_FILE_PATH ) . '/images/' . $this->images()[ $image ]['img'];
		if ( ! file_exists( $path ) ) {
			return;
		}

		$title = apply_filters( 'widget_title', $instance['title'] );
		// before and after widget arguments are defined by themes.
		echo $args['before_widget'];
		if ( ! empty( $title ) ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}

		echo wpautop( $instance['before_image'] );
		if ( 'svg' === strtolower( pathinfo( $this->images()[ $image ]['img'], PATHINFO_EXTENSION ) ) ) {
			// Include the SVG inline instead of using an <img> element.
			$svg = file_get_contents( $path );
			printf(
				'<a href="%s">%s</a>',
				get_post_type_archive_link( INVP::POST_TYPE ),
				$svg
			);
		} else {
			printf(
				'<a href="%s"><img src="%s"></a>',
				esc_url( get_post_type_archive_link( INVP::POST_TYPE ) ),
				esc_url( plugins_url( '/images/' . $this->images()[ $image ]['img'], INVP_PLUGIN_FILE_PATH ) )
			);
		}
		echo wpautop( $instance['after_image'] ) . $args['after_widget'];
	}

	/**
	 * Outputs the widget settings form that is shown in the dashboard.
	 *
	 * @param  array $instance
	 * @return void
	 */
	public function form( $instance ) {

		$image_keys = array_keys( $this->images() );

		$title        = isset( $instance['title'] ) ? $instance['title'] : '';
		$before_image = isset( $instance['before_image'] ) ? $instance['before_image'] : '';
		$image        = isset( $instance['image'] ) ? $instance['image'] : $image_keys[0];
		$after_image  = isset( $instance['after_image'] ) ? $instance['after_image'] : '';

		// Widget admin form.
		?>
		<p>
		<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'inventory-presser' ); ?></label>
		<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<p>
		<label for="<?php echo esc_attr( $this->get_field_id( 'before_image' ) ); ?>"><?php esc_html_e( 'Text before image:', 'inventory-presser' ); ?></label>
		<textarea class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'before_image' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'before_image' ) ); ?>"><?php echo esc_attr( $before_image ); ?></textarea>
		</p>
		<p>
		<label for="<?php echo esc_attr( $this->get_field_id( 'image' ) ); ?>"><?php esc_html_e( 'Image:', 'inventory-presser' ); ?></label>

		<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'image' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'image' ) ); ?>">
		<?php
		foreach ( $this->images() as $key => $imginfo ) {
			printf(
				'<option value="%s"%s>%s</option>',
				esc_attr( $key ),
				selected( $key === $image, true, false ),
				esc_html( $imginfo['text'] )
			);
		}
		?>
		</select>

		</p>

		<p>
		<label for="<?php echo esc_attr( $this->get_field_id( 'after_image' ) ); ?>"><?php esc_html_e( 'Text after image:', 'inventory-presser' ); ?></label>
		<textarea class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'after_image' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'after_image' ) ); ?>"><?php echo esc_attr( $after_image ); ?></textarea>
		</p>
		<?php
	}

	/**
	 * Saves the widget settings when a dashboard user clicks the Save button.
	 *
	 * @param  array $new_instance
	 * @param  array $old_instance
	 * @return array The updated array full of settings
	 */
	public function update( $new_instance, $old_instance ) {
		$image_keys               = array_keys( $this->images() );
		$instance                 = array();
		$instance['title']        = ( ! empty( $new_instance['title'] ) ) ? wp_strip_all_tags( $new_instance['title'] ) : '';
		$instance['before_image'] = ( ! empty( $new_instance['before_image'] ) ) ? wp_strip_all_tags( $new_instance['before_image'] ) : '';
		$instance['image']        = ( ! empty( $new_instance['image'] ) ) ? wp_strip_all_tags( $new_instance['image'] ) : $image_keys[0];
		$instance['after_image']  = ( ! empty( $new_instance['after_image'] ) ) ? wp_strip_all_tags( $new_instance['after_image'] ) : '';
		return $instance;
	}
}
