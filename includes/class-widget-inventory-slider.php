<?php
defined( 'ABSPATH' ) or exit;

class Inventory_Presser_Slider extends WP_Widget {

	const ID_BASE = '_invp_slick';

	var $text_displays = array(
		'none'   => 'None',
		'top'    => 'Top',
		'bottom' => 'Bottom'
	);

	function __construct() {
		parent::__construct(
			self::ID_BASE,
			__( 'Vehicle Slider', 'inventory-presser' ),
			array( 'description' => __( 'A slideshow for all vehicles with at least one photo.', 'inventory-presser' ), )
		);

		add_action( 'invp_delete_all_data', array( $this, 'delete_option' ) );

		//include scripts and styles if widget is used
		if( is_active_widget( false, false, self::ID_BASE ) ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'include_scripts' ) );
		}
	}

	public function delete_option() {
		delete_option( 'widget_' . self::ID_BASE );
	}

	function featured_select_options() {
		return array(
			'featured_priority' => __( 'Priority for Featured Vehicles', 'inventory-presser' ),
			'featured_only'     => __( 'Featured Vehicles Only', 'inventory-presser' ),
			'random'            => __( 'Random', 'inventory-presser' ),
			'newest_first'      => __( 'Newest Vehicles First', 'inventory-presser' ),
		);
	}

	function include_scripts() {
		//This widget uses a jQuery carousel called slick https://plugins.jquery.com/slick/
		wp_enqueue_style( 'jquery-slick-style', '//cdn.jsdelivr.net/jquery.slick/1.6.0/slick.css' );
		wp_enqueue_style( 'invp-slick', plugins_url( 'css/slick.css', dirname( __FILE__ ) ) );
		wp_enqueue_script( 'jquery-slick', '//cdn.jsdelivr.net/jquery.slick/1.6.0/slick.min.js', array('jquery'), '1.6.0' );
		wp_enqueue_script( 'invp-slick-init', plugins_url( 'js/slick.js', dirname( __FILE__ ) ), array( 'jquery-slick' ) );
	}

	// front-end
	public function widget( $args, $instance ) {

		$title = apply_filters( 'widget_title', $instance['title'] );
		$showcount = $instance['showcount'];
		$showtext = $instance['showtext'];
		$featured_select_slugs = array_keys( $this->featured_select_options() );
		$featured_select = isset($instance['featured_select']) ? $instance[ 'featured_select' ] : $featured_select_slugs[0];
		$showtitle = (isset($instance['cb_showtitle']) && $instance['cb_showtitle'] == 'true');
		$showprice = (isset($instance['cb_showprice']) && $instance['cb_showprice'] == 'true');

		$inventory_ids = array();

		$gpargs = array(
			'numberposts' => $showcount * 5, //get 5 sets of the number we'll show at one time
			'post_type'   => Inventory_Presser_Plugin::CUSTOM_POST_TYPE,
			'fields'      => 'ids',
			'orderby'     => 'rand',
			'order'       => 'ASC',
			'meta_query'  => array(
				array(
					'key'     => '_thumbnail_id',
					'compare' => 'EXISTS',
				),
			),
		);

		switch( $featured_select ) {

			case 'random':
				$gpargs['meta_key'] = '_thumbnail_id';
				$inventory_ids = get_posts( apply_filters( 'invp_slider_widget_query_args', $gpargs ) );
				break;

			case 'newest_first':
				$gpargs['meta_key'] = apply_filters( 'invp_prefix_meta_key', 'last_modified' );
				$gpargs['orderby'] = ' STR_TO_DATE( meta1.meta_value, \'%a, %d %b %Y %T\' ) ';
				$gpargs['order']   = 'DESC';
				$inventory_ids = get_posts( apply_filters( 'invp_slider_widget_query_args', $gpargs ) );
				break;

			default:
				$gpargs['meta_query']['relation'] = 'AND';
				$gpargs['meta_query'][] = array(
					'key'     => apply_filters( 'invp_prefix_meta_key', 'featured' ),
					'value'   => 1,
					'compare' => '=',
				);
				$inventory_ids = get_posts( apply_filters( 'invp_slider_widget_query_args', $gpargs ) );

				if (count($inventory_ids) < ($showcount * 5) && $featured_select == 'featured_priority') {

					$gpargs['numberposts'] = ($showcount * 5) - (count($inventory_ids));
					$gpargs['meta_query'] = array(
						'relation' => 'AND',
						array(
							'key'     => apply_filters( 'invp_prefix_meta_key', 'featured' ),
							'value'   => 0,
							'compare' => '='
						),
						array(
							'key'	  => '_thumbnail_id',
							'compare' => 'EXISTS'
						)
					);
					$inventory_ids += get_posts( apply_filters( 'invp_slider_widget_query_args', $gpargs ) );
				}
				//randomize the order, a strange choice we'll maintain
				shuffle( $inventory_ids );

				break;
		}

		if ($inventory_ids) {

			// before and after widget arguments are defined by themes
			echo $args['before_widget'];
			if ( ! empty( $title ) ) {
				echo $args['before_title'] . $title . $args['after_title'];
			}

			printf(
				'<div class="slick-slider-element" data-slick=\'{"slidesToShow": %1$d, "slidesToScroll": %1$d, "easing": "ease", "autoplaySpeed": %2$d, "speed": 4000}\'>',
				$showcount,
				( $showcount * 1000 ) + 1000
			);

			foreach ($inventory_ids as $inventory_id) {

				$vehicle = new Inventory_Presser_Vehicle($inventory_id);
				printf(
					'<div class="widget-inventory-slide-wrap"><a href="%s"><div class="slick-background-image" style="background-image: url(%s);">',
					$vehicle->url,
					wp_get_attachment_image_url( get_post_thumbnail_id( $inventory_id ), 'large' )
				);
				if ($showtext != 'none') {
					printf( '<div class="slick-text slick-text-%s">', $showtext );
					if ($showtitle) {
						printf( '<h3>%s %s %s</h3>', $vehicle->year, $vehicle->make, $vehicle->model );
					}
					if ($showprice) {
						printf( '<h2>%s</h2>', $vehicle->price( __( 'Call For Price', 'inventory-presser' ) ) );
					}
					echo '</div>';
				}
				echo '</div></a></div>';
			}
			echo '</div>' . $args['after_widget'];
		}
	}

	// Widget Backend
	public function form( $instance ) {

		$title = isset($instance[ 'title' ]) ? $instance[ 'title' ] : '';
		$showcount = isset($instance[ 'showcount' ]) ? $instance[ 'showcount' ] : 3;

		$featured_select_slugs = array_keys( $this->featured_select_options() );
		$featured_select = isset($instance['featured_select']) ? $instance[ 'featured_select' ] : $featured_select_slugs[0];

		$text_displays_slugs = array_keys($this->text_displays);
		$showtext = isset($instance['showtext']) ? $instance[ 'showtext' ] : $text_displays_slugs[0];

		// Widget admin form
		?>
		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'inventory-presser' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<p>
		<label for="<?php echo $this->get_field_id( 'showcount' ); ?>"><?php _e( 'Vehicles to show at one time:', 'inventory-presser' ); ?></label>
		<select class="widefat" id="<?php echo $this->get_field_id('showcount'); ?>" name="<?php echo $this->get_field_name('showcount'); ?>">
		<?php
			for ( $i=1; $i < 8; $i++ ) {
				printf(
					'<option value="%1$d"%2$s>%1$d</option>',
					$i,
					selected( $i == $showcount, true, false )
				);
			}
		?>
		</select>
		</p>

		<p>
		<label for="<?php echo $this->get_field_id( 'featured_select' ); ?>"><?php _e( 'Vehicle Selection:', 'inventory-presser' ); ?></label>
		<select class="widefat" id="<?php echo $this->get_field_id('featured_select'); ?>" name="<?php echo $this->get_field_name('featured_select'); ?>">
		<?php
			foreach ( $this->featured_select_options() as $slug => $label ) {
				printf(
					'<option value="%s"%s>%s</option>',
					$slug,
					selected( true, $slug == $featured_select, false ),
					$label
				);
			}
		?>
		</select>
		</p>

		<p>
		<label for="<?php echo $this->get_field_id( 'showtext' ); ?>"><?php _e( 'Text Overlay:', 'inventory-presser' ); ?></label>
		<select class="widefat" id="<?php echo $this->get_field_id('showtext'); ?>" name="<?php echo $this->get_field_name('showtext'); ?>">
		<?php
			foreach ( $this->text_displays as $slug => $label ) {
				printf(
					'<option value="%s"%s>%s</option>',
					$slug,
					selected( $slug == $showtext, true, false ),
					$label
				);
			}
		?>
		</select>
		</p>
		<p>
		<label for="<?php echo $this->get_field_id('cb_showtitle'); ?>"><input type="checkbox" id="<?php echo $this->get_field_id('cb_showtitle'); ?>" name="<?php echo $this->get_field_name('cb_showtitle'); ?>" value="true"<?php checked( true, ( isset( $instance['cb_showtitle'] ) && $instance['cb_showtitle'] == 'true' ) ); ?>> <?php _e( 'Show Vehicle Title', 'inventory-presser' ); ?></label>
		</p>
		<p>
		<label for="<?php echo $this->get_field_id('cb_showprice'); ?>"><input type="checkbox" id="<?php echo $this->get_field_id('cb_showprice'); ?>" name="<?php echo $this->get_field_name('cb_showprice'); ?>" value="true"<?php checked( true, ( isset( $instance['cb_showprice'] ) && $instance['cb_showprice'] == 'true' ) ); ?>> <?php _e( 'Show Vehicle Price', 'inventory-presser' ); ?></label>
		</p>

		<?php
	}

	// Updating widget replacing old instances with new
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['showcount'] = ( ! empty( $new_instance['showcount'] ) ) ? strip_tags( $new_instance['showcount'] ) : 3;
		$instance['featured_select'] = ( ! empty( $new_instance['featured_select'] ) ) ? strip_tags( $new_instance['featured_select'] ) : '';
		$instance['showtext'] = ( ! empty( $new_instance['showtext'] ) ) ? strip_tags( $new_instance['showtext'] ) : '';
		$instance['cb_showtitle'] = ( !empty( $new_instance['cb_showtitle'] ) ) ? $new_instance['cb_showtitle'] : '';
		$instance['cb_showprice'] = ( !empty( $new_instance['cb_showprice'] ) ) ? $new_instance['cb_showprice'] : '';
		return $instance;
	}
}
