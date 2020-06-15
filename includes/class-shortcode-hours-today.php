<?php
defined( 'ABSPATH' ) OR exit;

class Inventory_Presser_Shortcode_Hours_Today
{
	function hooks()
	{
		//add a shortcode that outputs hours today
		add_shortcode( 'invp_hours_today', array( $this, 'driver' ) );

		//add hours today near the "this vehicle is located at" sentence
		add_filter( 'invp_vehicle_location_sentence', array( $this, 'append_shortcode' ) );

		//add hours today in the Hours widget
		add_filter( 'invp_hours_title', array( $this, 'append_hours_today_to_hours_widget'), 10, 2 );
	}

	function append_hours_today_to_hours_widget( $hours_title_html, $hours_uid )
	{
		if( function_exists( 'apply_shortcodes' ) )
		{
			$hours_title_html .= '<p class="invp-hours-today">'
				. apply_shortcodes( '[invp_hours_today hours_uid="' . $hours_uid . '"]' )
				. '</p>';
		}
		return $hours_title_html;
	}

	function append_shortcode( $content )
	{
		return trim( $content . ' [invp_hours_today]' );
	}

	/**
	 * Create a DateTime object from a string like "9:00 AM"
	 */
	function create_date_object_from_hour_string( $hour_string )
	{
		return DateTime::createFromFormat("g:ia", strtolower( str_replace( ' ', '', $hour_string ) ) );
	}

	/**
	 * Translate the hours termmeta data structure into
	 * Inventory_Presser_Business_Day objects
	 */
	function create_days_array_from_hours_array( $hours_arr )
	{
		$days = array();
		$weekdays = array_keys( INVP::weekdays() );
		for( $d=0; $d<7; $d++ )
		{
			if( empty( $hours_arr[$weekdays[$d] . '_open'] )
				|| empty( $hours_arr[$weekdays[$d] . '_close'] ) )
			{
				continue;
			}

			$day = new Inventory_Presser_Business_Day();
			$day->weekday = $d+1;

			//open hour, turn "9:00 AM" into 9 and "4:00 PM" into 15
			$date_obj = $this->create_date_object_from_hour_string( $hours_arr[$weekdays[$d] . '_open'] );
			if( ! $date_obj ) { continue; }

			$day->open_hour = $date_obj->format('G');
			$day->open_minute = $date_obj->format('i');

			//close hour
			$date_obj = $this->create_date_object_from_hour_string( $hours_arr[$weekdays[$d] . '_close'] );
			if( ! $date_obj ) { continue; }

			$day->close_hour = $date_obj->format('G');
			$day->close_minute = $date_obj->format('i');

			array_push( $days, $day );
		}
		return $days;
	}

	/**
	 * @param Array $days An array of Inventory_Presser_Business_Day objects
	 */
	function create_sentence( $days )
	{
		if( null == $days || 0 == sizeof( $days ) ) { return ''; }

		//find today
		$today = null;
		$today_weekday = date('w', current_time( 'timestamp' )); //0 if today is Sunday
		foreach( $days as $day )
		{
			if ( $today_weekday == $day->weekday )
			{
				$today = $day;
				break;
			}
		}

		//are we open right now?
		if( null != $today && $today->open_right_now() )
		{
			//currently open
			return __( 'Open today until ', 'inventory-presser' ) . $today->close_string();
		}
		elseif( null != $today && $today->open_later_today() )
		{
			//not yet open today
			$open_string = $today->open_string();
			if( '' == $open_string ) { return ''; }
			return __( 'Opening at ', 'inventory-presser' ) . $open_string . __( ' today', 'inventory-presser' );
		}

		//find the next day we are open
		$next_open_day = $this->find_next_open_day( $days );
		if( null == $next_open_day )
		{
			return '';
		}

		//closed today, tell them about the next time we are open
		$str = __( 'Closed until ', 'inventory-presser' ) . $next_open_day->open_string();

		//If $next_open_day is tomorrow, output "tomorrow" instead of "on Tuesday"
		if( $next_open_day->is_tomorrow() )
		{
			$str .= __( ' tomorrow', 'inventory-presser' );
		}
		else
		{
			$str .= __( ' on ', 'inventory-presser' ) . jddayofweek( $next_open_day->weekday-1, 1 );
		}
		return $str;
	}

	function driver( $atts )
	{
		//setup default attributes
		$atts = shortcode_atts( array(
			'hours_uid' => 0,
		), $atts );

		/**
		 * Find hours for which we will create sentence(s)
		 */
		$hours_set = null;

		if( 0 !== $atts['hours_uid'] )
		{
			//the hours identified by this unique id
			$hours_set = $this->find_hours_set_by_uid( $atts['hours_uid'] );
		}
		else if( is_singular( Inventory_Presser_Plugin::CUSTOM_POST_TYPE ) )
		{
			//is there a location attached to this vehicle?
			$location_terms = wp_get_object_terms( get_the_ID(), 'location' );
			if( ! empty( $location_terms ) )
			{
				$location_slug = $location_terms[0]->slug;
				$sets = $this->find_hours_sets_by_location_slug( $location_slug );
				if( ! empty( $sets ) )
				{
					$hours_set = $sets[0];
				}
			}
		}
		else
		{
			//no specific set or location identified.

			$location_slugs = get_terms( array(
				'fields'     => 'id=>slug',
				'taxonomy'   => 'location',
				'hide_empty' => true,
				'orderby'    => 'term_id',
				'order'      => 'ASC',
			) );

			//are there terms in the location taxonomy?
			if( 0 < sizeof( $location_slugs ) )
			{
				//take the first set of hours we find
				foreach( $location_slugs as $id => $slug )
				{
					$sets = $this->find_hours_sets_by_location_slug( $slug );
					if( ! empty( $sets ) )
					{
						$hours_set = $sets[0];
						break;
					}
				}
			}
		}

		if( null == $hours_set ) { return ''; }

		$days = $this->create_days_array_from_hours_array( $hours_set );
		return $this->create_sentence( $days );
	}

	//Get all sets of hours attached to a term in the location taxonomy
	function find_hours_sets_by_location_slug( $slug )
	{
		if( ! is_string( $slug ) )
		{
			error_log( 'find_hours_sets_by_location_slug() was passed something other than string: ' . print_r( $slug, true ) );
			return null;
		}

		$location_term = get_term_by( 'slug', $slug, 'location' );
		if( ! $location_term ) { return null; }
		return Inventory_Presser_Taxonomies::get_hours( $location_term->term_id );
	}

	function find_hours_set_by_uid( $uid )
	{
		//get all term ids in the location taxonomy
		$location_term_ids = get_terms( array(
			'fields'   => 'ids',
			'orderby'  => 'term_id', //oldest first
			'taxonomy' => 'location',
		) );

		if( ! is_array( $location_term_ids ) )
		{
			return null;
		}

		foreach( $location_term_ids as $term_id )
		{
			$sets = Inventory_Presser_Taxonomies::get_hours( $term_id );
			if( empty( $sets ) )
			{
				continue;
			}
			foreach( $sets as $hours )
			{
				if( isset( $hours['uid'] ) && $uid == $hours['uid'] )
				{
					//these are the hours we want
					return $hours;
				}
			}
		}
		return null;
	}

	public function find_next_open_day( $days )
	{
		//find today
		$today_weekday = date( 'w', current_time( 'timestamp' ) ); //0 if today is Sunday
		$after_day = null;
		$before_day = null;
		$open_days = array_filter( $days, function( $day ) {
			return $day->open_in_some_fashion();
		});
		foreach( $open_days as $day )
		{
			if( $day->weekday > $today_weekday && ( null == $after_day || $day->weekday < $after_day->weekday ) )
			{
				$after_day = $day;
			}
			if( $day->weekday < $today_weekday && ( null == $before_day || $day->weekday < $before_day->weekday ) )
			{
				$before_day = $day;
			}
		}
		return ( null == $after_day ? $before_day : $after_day );
	}
}
