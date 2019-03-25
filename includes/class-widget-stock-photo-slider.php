<?php

class Inventory_Presser_Stock_Photo_Slider extends WP_Widget {

	const ID_BASE = '_invp_sps';

	// the image sets, follow the pattern to add more
	function image_sets() {
		return array(
			'acura' => array(
				'label' => 'Acura',
				'photos' => array(
					'acura-01.jpg',
					'acura-02.jpg',
					'acura-03.jpg',
					'acura-04.jpg',
					),
				),
			'audi' => array(
				'label' => 'Audi',
				'photos' => array(
					'audi-01.jpg',
					'audi-02.jpg',
					),
				),
			'bmw' => array(
				'label' => 'BMW',
				'photos' => array(
					'bmw-01.jpg',
					'bmw-02.jpg',
					'bmw-03.jpg',
					),
				),
			'ford' => array(
				'label' => 'Ford',
				'photos' => array(
					'ford-2013-taurus.jpg',
					'ford-2015-explorer.jpg',
					'ford-2015-taurus.jpg',
					'ford-2016-focus.jpg',
					'ford-2017-escape.jpg',
					),
				),
			'hyundai' => array(
				'label' => 'Hyundai',
				'photos' => array(
					'hyundai-2016-elantra.jpg',
					'hyundai-2016-genesis.jpg',
					'hyundai-2016-santa-fe.jpg',
					),
				),
			'mercedes' => array(
				'label' => 'Mercedes-Benz',
				'photos' => array(
					'mercedes-01.jpg',
					'mercedes-02.jpg',
					'mercedes-03.jpg',
					),
				),
			'nissan' => array(
				'label' => 'Nissan',
				'photos' => array(
					'nissan-2013-altima.jpg',
					'nissan-2016-altima.jpg',
					'nissan-2016-pulsar.jpg',
					'nissan-2016-sentra.jpg',
					),
				),
			'boats' => array(
				'label' => __( 'Boats', 'inventory-presser' ),
				'photos' => array(
					'boat-1.jpg',
					'boat-2.jpg',
					'boat-3.jpg',
					'boat-4.jpg',
					'boat-5.jpg',
					),
				),
			'trucks' => array(
				'label' => __( 'Trucks', 'inventory-presser' ),
				'photos' => array(
					'truck-1.jpg',
					'truck-2.jpg',
					'truck-3.jpg',
					'truck-4.jpg',
					),
				),
			'suvs' => array(
				'label' => __( 'SUVs', 'inventory-presser' ),
				'photos' => array(
					'suv-01.jpg',
					'suv-02.jpg',
					'suv-03.jpg',
					'suv-04.jpg',
					'suv-05.jpg',
					'suv-06.jpg',
					'suv-07.jpg',
					),
				),
			'italian' => array(
				'label' => __( 'Italian', 'inventory-presser' ),
				'photos' => array(
					'italian-01.jpg',
					'italian-02.jpg',
					'italian-03.jpg',
					'italian-04.jpg',
					'italian-05.jpg',
					'italian-06.jpg',
					),
				),
			);
	}

	function __construct() {
		parent::__construct(
			self::ID_BASE,
			__( 'Stock Photo Slider', 'inventory-presser' ),
			array( 'description' => __( 'Full width slideshow with multiple vehicle photo sets.', 'inventory-presser' ), )
		);

		add_action( 'invp_delete_all_data', array( $this, 'delete_option' ) );
	}

	public function delete_option() {
		delete_option( 'widget_' . self::ID_BASE );
	}

	// front-end
	public function widget( $args, $instance ) {

		$link_slides = (isset($instance['link_slides']) && $instance['link_slides'] == 'true');

		$image_pool = array();
		// merge each photo set into one array
		foreach ($instance['image_sets'] as $set) {
			$image_pool = array_merge($image_pool, $this->image_sets()[$set]['photos']);
		}
		// mix em up for random display
		shuffle($image_pool);
		// take the first 5
		$display_images = array_slice($image_pool, 0, 5);
		// base url of photos
		$base_url = plugins_url( '/assets/stock-slider/', dirname(__FILE__));

		$title = apply_filters( 'widget_title', $instance['title'] );
		// before and after widget arguments are defined by themes
		echo $args['before_widget'];

		if (!empty( $title ))
		echo $args['before_title'] . $title . $args['after_title'];

		?>

		<div class="flexslider flex-native">
		<ul class="slides">
		<?php
		$inventory_link = get_post_type_archive_link( Inventory_Presser_Plugin::CUSTOM_POST_TYPE );
		foreach ($display_images as $filename) {
			if ($link_slides) {
				printf(
					'<li><a href="%s"><img src="%s"></a></li>',
					$inventory_link,
					$base_url . $filename
				);
			} else {
				printf(
					'<li><img src="%s"></li>',
					$base_url . $filename
				);
			}
		}
		?>
		</ul>
		</div>
		<?php

		echo $args['after_widget'];
	}

	// Widget Backend
	public function form( $instance ) {

		$image_keys = array_keys($this->image_sets());

		$title = isset($instance[ 'title' ]) ? $instance[ 'title' ] : '';
		$selected_sets = isset($instance[ 'image_sets' ]) ? $instance[ 'image_sets' ] : $image_keys;

		// Widget admin form
		?>
		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'inventory-presser' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<p>
		<label for="<?php echo $this->get_field_id('link_slides'); ?>"><input type="checkbox" id="<?php echo $this->get_field_id('link_slides'); ?>" name="<?php echo $this->get_field_name('link_slides'); ?>" value="true"<?php checked( (isset($instance['link_slides']) && $instance['link_slides'] == 'true'), true ); ?>> <?php _e( 'Link slides to Inventory', 'inventory-presser' ); ?></label>
		</p>
		<p>
		<label for="<?php echo $this->get_field_id( 'image_sets[]' ); ?>"><?php _e( 'Image Sets:', 'inventory-presser' ); ?></label>

		<table>
		<?php

		foreach ($this->image_sets() as $slug => $info) {
			printf(
				'<tr><td><input type="checkbox" id="%s" name="%s" value="%s"%s></td><td>%s (%s)</td></tr>',
				$this->get_field_id('image_sets'),
				$this->get_field_name('image_sets[]'),
				$slug,
				checked( in_array($slug, $selected_sets), true, false ),
				$info['label'],
				count( $info['photos'] )
			);
		}

		?>
		</table>
		</p>
		<?php
	}

	// Updating widget replacing old instances with new
	public function update( $new_instance, $old_instance ) {
		$image_keys = array_keys($this->image_sets());
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['image_sets'] = ( ! empty( $new_instance['image_sets'] ) ) ? $new_instance['image_sets'] : $image_keys;
		$instance['link_slides'] = ( !empty( $new_instance['link_slides'] ) ) ? $new_instance['link_slides'] : '';
		return $instance;
	}
}
