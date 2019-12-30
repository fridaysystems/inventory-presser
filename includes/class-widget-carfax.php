<?php
defined( 'ABSPATH' ) or exit;

class Inventory_Presser_Carfax_Widget extends WP_Widget {

	function images() {
		return array(
			'default' => array(
				'text' => __( 'Simple Show Me Logo', 'inventory-presser' ),
				'img'  => 'show-me-carfax.svg'
			),
			'advantage' => array(
				'text' => __( 'Advantage Dealer Badge', 'inventory-presser' ),
				'img'  => 'carfax-advantage-dealer.png'
			),
			'dealership' => array(
				'text' => __( 'Car Fox Dealership', 'inventory-presser' ),
				'img'  => 'carfax-portrait-blue.jpg'
			),
			'foxleft' => array(
				'text' => __( 'Car Fox Left', 'inventory-presser' ),
				'img'  => 'carfax-show-me-blue.png'
			),
			'foxoval' => array(
				'text' => __( 'Car Fox Oval', 'inventory-presser' ),
				'img'  => 'carfax-show-me-blue-oval.png'
			),
			'landscape' => array(
				'text' => __( 'Landscape Blue', 'inventory-presser' ),
				'img'  => 'carfax-show-me-landscape.jpg'
			),
		);
	}

	const ID_BASE = '_invp_carfax';

	function __construct() {
		parent::__construct(
			self::ID_BASE,
			'Carfax Badge',
			array( 'description' => 'Choose a Carfax badge that links to your inventory.', )
		);

		add_action( 'invp_delete_all_data', array( $this, 'delete_option' ) );
	}

	public function delete_option() {
		delete_option( 'widget_' . self::ID_BASE );
	}

	// front-end
	public function widget( $args, $instance ) {

		$image_keys = array_keys( $this->images() );
		$image = (in_array($instance['image'], $image_keys)) ? $instance['image'] : $image_keys[0];

		$title = apply_filters( 'widget_title', $instance['title'] );
		// before and after widget arguments are defined by themes
		echo $args['before_widget'];
		if (!empty( $title ))
		echo $args['before_title'] . $title . $args['after_title'];

		echo wpautop( $instance['before_image'] );
		if( 'svg' == strtolower( pathinfo( $this->images()[$image]['img'], PATHINFO_EXTENSION ) ) ) {
			//Include the SVG inline instead of using an <img> element
			$svg = file_get_contents( dirname( dirname( __FILE__ ) ) . '/assets/' . $this->images()[$image]['img'] );
			printf(
				'<a href="%s">%s</a>',
				get_post_type_archive_link( Inventory_Presser_Plugin::CUSTOM_POST_TYPE ),
				$svg
			);
		} else {
			printf(
				'<a href="%s"><img src="%s"></a>',
				get_post_type_archive_link( Inventory_Presser_Plugin::CUSTOM_POST_TYPE ),
				plugins_url( '/assets/' . $this->images()[$image]['img'], dirname(__FILE__) )
			);
		}
		echo wpautop( $instance['after_image'] ). $args['after_widget'];
	}

	// Widget Backend
	public function form( $instance ) {

		$image_keys = array_keys( $this->images() );

		$title = isset($instance[ 'title' ]) ? $instance[ 'title' ] : '';
		$before_image = isset($instance[ 'before_image' ]) ? $instance[ 'before_image' ] : '';
		$image = isset($instance[ 'image' ]) ? $instance[ 'image' ] : $image_keys[0];
		$after_image = isset($instance[ 'after_image' ]) ? $instance[ 'after_image' ] : '';

		// Widget admin form
		?>
		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'inventory-presser' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<p>
		<label for="<?php echo $this->get_field_id( 'before_image' ); ?>"><?php _e( 'Text before image:', 'inventory-presser' ); ?></label>
		<textarea class="widefat" id="<?php echo $this->get_field_id('before_image'); ?>" name="<?php echo $this->get_field_name('before_image'); ?>"><?php echo esc_attr( $before_image ); ?></textarea>
		</p>
		<p>
		<label for="<?php echo $this->get_field_id( 'image' ); ?>"><?php _e( 'Image:', 'inventory-presser' ); ?></label>

		<select class="widefat" id="<?php echo $this->get_field_id('image'); ?>" name="<?php echo $this->get_field_name('image'); ?>">
		<?php foreach ( $this->images() as $key => $imginfo ) {
			printf(
				'<option value="%s"%s>%s</option>',
				$key,
				selected( $key == $image, true, false ),
				$imginfo['text']
			);
		} ?>
		</select>

		</p>

		<p>
		<label for="<?php echo $this->get_field_id( 'after_image' ); ?>"><?php _e( 'Text after image:', 'inventory-presser' ); ?></label>
		<textarea class="widefat" id="<?php echo $this->get_field_id('after_image'); ?>" name="<?php echo $this->get_field_name('after_image'); ?>"><?php echo esc_attr( $after_image ); ?></textarea>
		</p>
		<?php
	}

	// Updating widget replacing old instances with new
	public function update( $new_instance, $old_instance ) {
		$image_keys = array_keys( $this->images() );
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['before_image'] = ( ! empty( $new_instance['before_image'] ) ) ? strip_tags( $new_instance['before_image'] ) : '';
		$instance['image'] = ( ! empty( $new_instance['image'] ) ) ? strip_tags( $new_instance['image'] ) : $image_keys[0];
		$instance['after_image'] = ( ! empty( $new_instance['after_image'] ) ) ? strip_tags( $new_instance['after_image'] ) : '';
		return $instance;
	}
}
