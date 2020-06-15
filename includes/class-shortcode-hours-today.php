<?php
defined( 'ABSPATH' ) OR exit;

class Inventory_Presser_Shortcode_Hours_Today
{
	function hooks()
	{
		//Include this class we
		include_once( 'includes/business-day.php' );

		//Allow translations
		add_action( 'plugins_loaded', function() {
			load_plugin_textdomain( 'inventory-presser-hours-today', false, __DIR__ );
		} );

		//add a shortcode that outputs hours today
		add_shortcode( 'invp_hours_today', array( $this, 'driver' ) );

		//add hours today near the "this vehicle is located at" sentence
		add_filter( '_dealer_vehicle_location_sentence', array( $this, 'append_hours_today_by_location_slug' ), 10, 2 );

		//add hours today in the Dealer Hours widget
		add_filter( 'invp_hours_title', array( $this, 'append_hours_today_to_hours_widget'), 10, 2 );
	}

	function append_hours_today_to_hours_widget( $hours_title_html, $hours_uid )
	{
		$hours_set = $this->find_hours_set_by_uid( $hours_uid );
		if( null == $hours_set )
		{
			return $hours_title_html;
		}

		$days = $this->create_days_array_from_hours_array( $hours_set );
		return $hours_title_html . '<p class="invp-hours-today">' . $this->create_sentence( $days ) . '</p>';
	}

	function append_hours_today_by_location_slug( $content, $location_slug )
	{
		$sets = $this->find_hours_sets_by_location_slug( $location_slug );
		if( null == $sets ) { return $content; }

		$hours_set = null;
		if( 0 < sizeof( $sets ) )
		{
			//always take the first, even if there are more than one set
			$hours_set = $sets[0];
		}
		if( null == $hours_set ) { return $content; }

		//remove the period at the end of the $content
		if( '.' == substr( $content, -1 ) )
		{
			$content = substr( $content, 0, strlen( $content ) - 1 );
		}

		return trim( $content . ', which is ' . lcfirst( $this->create_sentence( $this->create_days_array_from_hours_array( $hours_set ) ) ) . '.' );
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
		$weekdays = array(
			'monday',
			'tuesday',
			'wednesday',
			'thursday',
			'friday',
			'saturday',
			'sunday',
		);
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

		$str = '';
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

		//find the next day we are open and the previous day we were open
		$next_open_day;
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
		$next_open_day = ( null == $after_day ? $before_day : $after_day );

		//make DateTime objects of today's open and close times
		$today_open_date = $today_close_date = null;
		if( null != $today )
		{
			$today_open_date = $this->current_datetime();
			$today_close_date = $this->current_datetime();
			$today_open_date->setTime( $today->open_hour, $today->open_minute, 0 );
			$today_close_date->setTime( $today->close_hour, $today->close_minute, 0 );
		}

		//are we open right now?
		$now = $this->current_datetime();
		if( null != $today_open_date && $now >= $today_open_date && $now < $today_close_date )
		{
			//currently open
			$str .= __( 'Open today until ', 'inventory-presser-hours-today' ) . $today->close_string();
		}
		elseif( null != $today_open_date && $now < $today_open_date )
		{
			//not yet open today
			$open_string = $today->open_string();
			if( '' == $open_string ) { return ''; }
			$str .= __( 'Opening at ', 'inventory-presser-hours-today' ) . $open_string . __( ' today', 'inventory-presser-hours-today' );
		}
		else
		{
			//closed today, tell them about the next time we are open
			if( null != $next_open_day )
			{
				$str .= __( 'Closed until ', 'inventory-presser-hours-today' ) . $next_open_day->open_string();

				//is next_open_day tomorrow? make next_open_day into date object
				$tomorrow = $this->current_datetime();
				$tomorrow->add( new DateInterval( 'P1D' ) );
				if( $next_open_day->weekday == $tomorrow->format( 'w' ) )
				{
					$str .= __( ' tomorrow', 'inventory-presser-hours-today' );
				}
				else
				{
					$str .= __( ' on ', 'inventory-presser-hours-today' ) . jddayofweek( $next_open_day->weekday-1, 1 );
				}
			}
		}

		return $str;
	}

	//use WordPress current_time() to create a DateTime object
	function current_datetime()
	{
		return new DateTime( date( DATE_RFC2822, current_time( 'timestamp' ) ) );
	}

	//Find hours, format them into the array we need, send them to create_sentence()
	function driver( $atts )
	{
		//setup default attributes
		$atts = shortcode_atts( array(
			'hours_uid'     => 0,
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
		else if( is_singular( 'inventory_vehicle' ) )
		{
			if( class_exists( 'Inventory_Presser_Vehicle' ) )
			{
				//is there a location attached to this vehicle?
				$vehicle = new Inventory_Presser_Vehicle( get_the_ID() );

				if( isset( $vehicle->location ) )
				{
					$sets = $this->find_hours_sets_by_location_slug( $vehicle->location );
					if( ! empty( $sets ) )
					{
						$hours_set = $sets[0];
					}
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

		if( ! class_exists( 'Inventory_Presser_Taxonomies' ) )
		{
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

		if( ! class_exists( 'Inventory_Presser_Taxonomies' ) )
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
}
