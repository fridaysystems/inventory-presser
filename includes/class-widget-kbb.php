<?php

class Inventory_Presser_KBB_Widget extends WP_Widget {

	const ID_BASE = '_invp_kbb';

	function images() {
		return array(
			'default' => array(
				'text' => __( 'Bordered Rectangle', 'inventory-presser' ),
				'img'  => 'kelley-blue-book.jpg'
			),
		);
	}

	function __construct() {
		parent::__construct(
			self::ID_BASE,
			__( 'Kelley Blue Book Logo', 'inventory-presser' ),
			array( 'description' => __( 'KBB logo image linked to kbb.com', 'inventory-presser' ), )
		);

		add_action( 'invp_delete_all_data', array( $this, 'delete_option' ) );
	}

	public function delete_option() {
		delete_option( 'widget_' . self::ID_BASE );
	}

	// front-end
	public function widget( $args, $instance ) {

		$image_keys = array_keys( $this->images() );
		$image = (isset( $instance['image'] ) && in_array($instance['image'], $image_keys)) ? $instance['image'] : $image_keys[0];

		$title = apply_filters( 'widget_title', isset( $instance['title'] ) ? $instance['title'] : '' );
		// before and after widget arguments are defined by themes
		echo $args['before_widget'];
		if (!empty( $title ))
		echo $args['before_title'] . $title . $args['after_title'];

		if( isset( $instance['before_image'] ) ) {
			echo wpautop($instance['before_image']);
		}
		printf('<a href="%s" target="_blank"><img src="%s"></a>','http://kbb.com',plugins_url( '/assets/' . $this->images()[$image]['img'], dirname(__FILE__)));
		if( isset( $instance['after_image'] ) ) {
			echo wpautop($instance['after_image']);
		}

		echo $args['after_widget'];
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
