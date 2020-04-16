<?php
defined( 'ABSPATH' ) or exit;

class Inventory_Presser_Location_Phones extends WP_Widget
{
	const ID_BASE = '_invp_phone';
	const MAX_PHONES = 10; //the maximum number of phones a single address holds

	// formats for widget display.  To add more, just follow the pattern
	function formats()
	{
		return array(
			'small_left_label' => array(
				'selector'    => __( 'Small, left label', 'inventory-presser' ),
				'uses_labels' => true,
				'before'      => '<table>',
				'repeater'    => '<tr><th>%1$s</th><td class="phone-link"><a href="tel:%2$s">%2$s</a></td><tr>',
				'after'       => '</table>',
			),
			'large_no_label' => array(
				'selector'    => __( 'Large, no label', 'inventory-presser' ),
				'uses_labels' => false,
				'before'      => '',
				'repeater'    => '<h2><a href="tel:%1$s">%1$s</a></h2>',
				'after'       => '',
			),
			'large_table_left' => array(
				'selector'    => __( 'Large tabled, left label', 'inventory-presser' ),
				'uses_labels' => true,
				'before'      => '<table>',
				'repeater'    => '<tr><th>%1$s</th><td class="phone-link"><a href="tel:%2$s">%2$s</a></td><tr>',
				'after'       => '</table>',
			),
			'large_left_label' => array(
				'selector'    => __( 'Large, small left label', 'inventory-presser' ),
				'uses_labels' => true,
				'before'      => '<table>',
				'repeater'    => '<tr><th>%1$s</th><td><h2><a href="tel:%2$s">%2$s</a></h2></td><tr>',
				'after'       => '</table>',
			),
			'large_right_label' => array(
				'selector'    => __( 'Large, small right label', 'inventory-presser' ),
				'uses_labels' => true,
				'before'      => '<table>',
				'repeater'    => '<tr><td><h2><a href="tel:%2$s">%2$s</a></h2></td><th>%1$s</th><tr>',
				'after'       => '</table>',
			),
			'single_line_labels' => array(
				'selector'    => __( 'Single line with labels', 'inventory-presser' ),
				'uses_labels' => true,
				'before'      => '',
				'repeater'    => '<span>%1$s:</span> <a href="tel:%2$s">%2$s</a>',
				'after'       => '',
			),
			'single_line_no_labels' => array(
				'selector'    => __( 'Single line no labels', 'inventory-presser' ),
				'uses_labels' => false,
				'before'      => '',
				'repeater'    => '<span><a href="tel:%1$s">%1$s</a></span>',
				'after'       => '',
			),
		);
	}

	function __construct()
	{
		parent::__construct(
			self::ID_BASE,
			__( 'Phone Number', 'inventory-presser' ),
			array( 'description' => __( 'Display one or more phone numbers.', 'inventory-presser' ), )
		);

		add_action( 'invp_delete_all_data', array( $this, 'delete_option' ) );
	}

	public function delete_option()
	{
		delete_option( 'widget_' . self::ID_BASE );
	}

	// widget front-end
	public function widget( $args, $instance )
	{
		if( ! is_array( $instance['cb_display'] ) || empty( $instance['cb_display'] ) )
		{
			return;
		}

		// before and after widget arguments are defined by themes
		echo $args['before_widget'];

		$title = apply_filters( 'widget_title', $instance['title'] );
		if( ! empty( $title ) )
		{
			echo $args['before_title'] . $title . $args['after_title'];
		}

		$format_slugs = array_keys( $this->formats() );
		$format = in_array($instance['format'], $format_slugs) ? $instance['format'] : $format_slugs[0];

		printf(
			'<div class="invp-%s">%s',
			$format,
			$this->formats()[$format]['before']
		);

		// loop through each location
		$location_info = get_terms( 'location', array( 'fields' => 'id=>name', 'hide_empty' => false ) );
		foreach( $location_info as $term_id => $name )
		{
			//Does this address even have a phone number displayed by this instance of this widget?
			if( empty( $instance['cb_display'][$term_id] ) )
			{
				//No
				continue;
			}

			for( $p=1; $p<=self::MAX_PHONES; $p++ )
			{
				$phone_uid = get_term_meta( $term_id, 'phone_' . $p . '_uid', true );
				if( ! $phone_uid )
				{
					break;
				}

				//There is a phone number is slot $p, has the user configured this widget to display it?
				if( in_array( $phone_uid, $instance['cb_display'][$term_id] ) )
				{
					//Yes, output this number
					$number = get_term_meta( $term_id, 'phone_' . $p . '_number', true );
					if( $this->formats()[$format]['uses_labels'] )
					{
						$description = get_term_meta( $term_id, 'phone_' . $p . '_description', true );
						printf( $this->formats()[$format]['repeater'], $description, $number );
					}
					else
					{
						printf( $this->formats()[$format]['repeater'], $number );
					}
				}
			}
		}

		echo $this->formats()[$format]['after']
			. '</div>'
			. $args['after_widget'];
	}

	// Widget Backend
	public function form( $instance )
	{
		$title = isset($instance['title']) ? $instance['title'] : '';
		$format = isset($instance['format']) ? $instance['format'] : current( array_keys( $this->formats() ) );
		$cb_display = isset($instance['cb_display']) ? $instance['cb_display'] : array();

		// get all locations
		$location_info = get_terms('location', array('fields'=>'id=>name', 'hide_empty'=>false));

		$phones_table = '<table><tbody>';

		// loop through each location, set up form
		foreach ($location_info as $term_id => $name)
		{
			$location_meta = get_term_meta( $term_id, 'location-phone-hours', true );
			if (isset($location_meta['phones']) && count($location_meta['phones']) > 0)
			{
				$phones_table .= sprintf('<tr><td colspan="3"><strong>%s</strong></td></tr>', $name);
				foreach ($location_meta['phones'] as $index => $phoneset)
				{
					$uid = isset( $phoneset['uid'] ) ? $phoneset['uid'] : '';

					$phoneset_number = ($phoneset['phone_number']) ? $phoneset['phone_number'] : __( 'No number entered', 'inventory-presser' );

					$cb_display_text = sprintf(
						'<input type="checkbox" id="%s" name="%s" value="%s"%s />',
						$this->get_field_id('cb_display'),
						$this->get_field_name('cb_display['.$term_id.'][]'),
						$uid,
						checked( (isset($cb_display[$term_id]) && is_array($cb_display[$term_id]) && in_array($uid, $cb_display[$term_id])), true, false )
					);

					$phones_table .= sprintf(
						'<tr><td>%s</td><td>%s</td><td>%s</td></tr>',
						$cb_display_text,
						$phoneset['phone_description'],
						$phoneset_number
					);
				}
			}

		}

		$phones_table .= '</tbody></table>';

		// Widget admin form
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title (optional):', 'inventory-presser' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('format'); ?>"><?php _e( 'Display Format:', 'inventory-presser' ); ?></label>
			<select class="widefat" id="<?php echo $this->get_field_id('format'); ?>" name="<?php echo $this->get_field_name('format'); ?>">
			<?php
			foreach ($this->formats() as $key => $format_array) {
				printf(
					'<option value="%s"%s>%s</option>',
					$key,
					selected( $format == $key, true, false ),
					$format_array['selector']
				);
			}
			?>
			</select>
		</p>
		<p><?php echo $phones_table; ?></p>
		<?php
	}

	// Updating widget replacing old instances with new
	public function update( $new_instance, $old_instance )
	{
		$instance = array();
		$instance['title'] = ( !empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['format'] = ( !empty( $new_instance['format'] ) ) ? $new_instance['format'] : current( array_keys( $this->formats() ) );
		$instance['cb_display'] = ( !empty( $new_instance['cb_display'] ) ) ? $new_instance['cb_display'] : array();
		return $instance;
	}
}
