<?php
defined( 'ABSPATH' ) || exit;

/**
 * Inventory_Presser_Shortcode_Hours_Today
 *
 * Creates a shortcode that outputs a sentence about whether or not the car lot
 * is open. Also hooks into vehicle singles and tables of hours to
 * automatically deploy the feature.
 */
class Inventory_Presser_Shortcode_Hours_Today {

	const SHORTCODE_TAG = 'invp_hours_today';

	/**
	 * add
	 *
	 * Adds two shortcodes
	 *
	 * @return void
	 */
	function add() {
		// add a shortcode that outputs hours today
		add_shortcode( self::SHORTCODE_TAG, array( $this, 'driver' ) );
		add_shortcode( str_replace( '_', '-', self::SHORTCODE_TAG ), array( $this, 'driver' ) );
	}

	/**
	 * hooks
	 *
	 * Adds hooks that power the shortcode and attach it to other features
	 *
	 * @return void
	 */
	public function add_hooks() {
		add_action( 'init', array( $this, 'add' ) );

		// add hours today near the "this vehicle is located at" sentence
		add_filter( 'invp_vehicle_location_sentence', array( $this, 'append_shortcode' ) );

		// add hours today in the Hours widget
		add_filter( 'invp_hours_title', array( $this, 'append_hours_today_to_hours_widget' ), 10, 2 );
	}

	/**
	 * append_hours_today_to_hours_widget
	 *
	 * Returns the output of the shortcode wrapped in some HTML
	 *
	 * @param  string $hours_title_html
	 * @param  string $hours_uid
	 * @return string HTML that renders a sentence
	 */
	function append_hours_today_to_hours_widget( $hours_title_html, $hours_uid ) {
		$shortcode        = '[' . self::SHORTCODE_TAG . ' hours_uid="' . $hours_uid . '"]';
		$shortcode_output = '';
		if ( function_exists( 'apply_shortcodes' ) ) {
			$shortcode_output = apply_shortcodes( $shortcode );
		} elseif ( function_exists( 'do_shortcode' ) ) {
			$shortcode_output = do_shortcode( $shortcode );
		}

		if ( empty( $shortcode_output ) ) {
			return $hours_title_html;
		}

		return $hours_title_html . '<p class="invp-hours-today">' . $shortcode_output . '</p>';
	}

	/**
	 * append_shortcode
	 *
	 * Filter callback that appends the shortcode to the end of a string of
	 * content.
	 *
	 * @param  string $content
	 * @return string The provided $content with the shortcode appended to the end
	 */
	function append_shortcode( $content ) {
		return trim( $content . ' <p>[' . self::SHORTCODE_TAG . ']</p>' );
	}

	/**
	 * Create a DateTime object from a string like "9:00 AM"
	 *
	 * @param  string $hour_string A string like "9:00 AM"
	 * @return DateTime
	 */
	static function create_date_object_from_hour_string( $hour_string ) {
		return DateTime::createFromFormat( 'g:ia', strtolower( str_replace( ' ', '', $hour_string ?? '' ) ) );
	}

	/**
	 * create_days_array_from_hours_array
	 *
	 * Translate the hours termmeta data structure into
	 * Inventory_Presser_Business_Day objects
	 *
	 * @param  array $hours_arr
	 * @return array And array of Inventory_Presser_Business_Day objects
	 */
	public static function create_days_array_from_hours_array( $hours_arr ) {
		$days     = array();
		$weekdays = array_keys( INVP::weekdays() );
		for ( $d = 0; $d < 7; $d++ ) {
			if ( empty( $hours_arr[ $weekdays[ $d ] . '_open' ] )
				|| empty( $hours_arr[ $weekdays[ $d ] . '_close' ] )
			) {
				continue;
			}

			$day          = new Inventory_Presser_Business_Day();
			$day->weekday = $d + 1;

			// open hour, turn "9:00 AM" into 9 and "4:00 PM" into 15
			$date_obj = self::create_date_object_from_hour_string( $hours_arr[ $weekdays[ $d ] . '_open' ] );
			if ( ! $date_obj ) {
				continue;
			}

			$day->open_hour   = $date_obj->format( 'G' );
			$day->open_minute = $date_obj->format( 'i' );

			// close hour
			$date_obj = self::create_date_object_from_hour_string( $hours_arr[ $weekdays[ $d ] . '_close' ] );
			if ( ! $date_obj ) {
				continue;
			}

			$day->close_hour   = $date_obj->format( 'G' );
			$day->close_minute = $date_obj->format( 'i' );

			array_push( $days, $day );
		}
		return $days;
	}

	/**
	 * create_sentence
	 *
	 * Examines the array of $days and generates the sentence, the core feature
	 * of this shortcode.
	 *
	 * @param  array $days An array of Inventory_Presser_Business_Day objects
	 * @return string A string like "Open today until 5:00pm" or "Closed until 9:00am on Monday"
	 */
	function create_sentence( $days ) {
		if ( null === $days || 0 === count( $days ) ) {
			return '';
		}

		// Find today. It might not be in the array at all.
		$today         = null;
		$today_weekday = gmdate( 'w', current_time( 'timestamp' ) ); // 0 if today is Sunday
		foreach ( $days as $day ) {
			if ( strval( $day->weekday ) === $today_weekday ) {
				$today = $day;
				break;
			}
		}

		// are we open right now?
		if ( null !== $today && $today->open_right_now() ) {
			// currently open
			return __( 'Open today until ', 'inventory-presser' ) . $today->close_string();
		} elseif ( null != $today && $today->open_later_today() ) {
			// not yet open today
			$open_string = $today->open_string();
			if ( '' == $open_string ) {
				return '';
			}
			return __( 'Opening at ', 'inventory-presser' ) . $open_string . __( ' today', 'inventory-presser' );
		}

		// Find the next day we are open.
		$next_open_day = self::find_next_open_day( $days );
		if ( null === $next_open_day ) {
			return '';
		}

		// closed today, tell them about the next time we are open
		$str = __( 'Closed until ', 'inventory-presser' ) . $next_open_day->open_string();

		// If $next_open_day is tomorrow, output "tomorrow" instead of "on Tuesday"
		if ( $next_open_day->is_tomorrow() ) {
			$str .= __( ' tomorrow', 'inventory-presser' );
		} else {
			$str .= __( ' on ', 'inventory-presser' ) . ucfirst( array_keys( INVP::weekdays() )[ $next_open_day->weekday - 1 ] );
		}

		return $str;
	}

	/**
	 * driver
	 *
	 * The shortcode callback method that returns the sentence.
	 *
	 * @param  array $atts
	 * @return string The sentence
	 */
	public function driver( $atts ) {
		// setup default attributes
		$atts = shortcode_atts(
			array(
				'hours_uid' => 0,
			),
			$atts
		);

		/**
		 * Find hours for which we will create sentence(s)
		 */
		$hours_set = $this->find_hours_set( $atts );
		if ( null === $hours_set ) {
			return '';
		}

		$days = $this->create_days_array_from_hours_array( $hours_set );
		return $this->create_sentence( $days );
	}

	/**
	 * find_hours_set
	 *
	 * Uses the shortcode attributes to find the right set of hours
	 *
	 * @param  array $shortcode_atts The shortcode attributes
	 * @return array A set of hours
	 */
	private function find_hours_set( $shortcode_atts ) {
		if ( ! empty( $shortcode_atts['hours_uid'] ) ) {
			// the hours identified by this unique id
			return $this->find_hours_set_by_uid( $shortcode_atts['hours_uid'] );
		} elseif ( is_singular( INVP::POST_TYPE ) ) {
			// is there a location attached to this vehicle?
			$location_terms = wp_get_object_terms( get_the_ID(), 'location' );
			if ( ! empty( $location_terms ) ) {
				$location_slug = $location_terms[0]->slug;
				$sets          = $this->find_hours_sets_by_location_slug( $location_slug );
				if ( ! empty( $sets ) ) {
					return $sets[0];
				}
			}
		} else {
			// no specific set or location identified.

			$location_slugs = get_terms(
				array(
					'fields'     => 'id=>slug',
					'taxonomy'   => 'location',
					'hide_empty' => true,
					'orderby'    => 'term_id',
					'order'      => 'ASC',
				)
			);

			// are there terms in the location taxonomy?
			if ( 0 < sizeof( $location_slugs ) ) {
				// take the first set of hours we find
				foreach ( $location_slugs as $id => $slug ) {
					$sets = $this->find_hours_sets_by_location_slug( $slug );
					if ( ! empty( $sets ) ) {
						return $sets[0];
					}
				}
			}
		}

		return null;
	}

	/**
	 * find_hours_sets_by_location_slug
	 *
	 * Get all sets of hours attached to a term in the location taxonomy
	 *
	 * @param  string $slug The slug of a term in our location taxonomy
	 * @return array A set of hours
	 */
	public function find_hours_sets_by_location_slug( $slug ) {
		if ( ! is_string( $slug ) ) {
			return null;
		}

		$location_term = get_term_by( 'slug', $slug, 'location' );
		if ( ! $location_term ) {
			return null;
		}
		return INVP::get_hours( $location_term->term_id );
	}

	/**
	 * find_hours_set_by_uid
	 *
	 * Get all sets of hours attached to a term in the location taxonomy by
	 * unique ID
	 *
	 * @param  string $uid A unique identifier assigned to a set of hours
	 * @return array A set of hours
	 */
	function find_hours_set_by_uid( $uid ) {
		// get all term ids in the location taxonomy
		$location_term_ids = get_terms(
			array(
				'fields'     => 'ids',
				'orderby'    => 'term_id', // oldest first
				'taxonomy'   => 'location',
				'hide_empty' => false, // Dealers that don't tag each vehicle with a location will have no vehicles
			)
		);

		if ( ! is_array( $location_term_ids ) ) {
			return null;
		}

		foreach ( $location_term_ids as $term_id ) {
			$sets = INVP::get_hours( $term_id );
			if ( empty( $sets ) ) {
				continue;
			}
			foreach ( $sets as $hours ) {
				if ( isset( $hours['uid'] ) && $uid == $hours['uid'] ) {
					// these are the hours we want
					return $hours;
				}
			}
		}
		return null;
	}

	/**
	 * Takes an array of days and finds the next day the business has hours.
	 * Does not check if the business is still open today. Helps make the jump
	 * from Friday to the next open day on Monday.
	 *
	 * @param  array $days An array of Inventory_Presser_Business_Day objects
	 * @return Inventory_Presser_Business_Day The next day that has open hours
	 */
	public static function find_next_open_day( $days ) {
		// find today
		$today_weekday = gmdate( 'w', current_time( 'timestamp' ) ); // 0 if today is Sunday
		$after_day     = null;
		$before_day    = null;
		$open_days     = array_filter(
			$days,
			function ( $day ) {
				return $day->open_in_some_fashion();
			}
		);
		foreach ( $open_days as $day ) {
			if ( $day->weekday > $today_weekday && ( null === $after_day || $day->weekday < $after_day->weekday ) ) {
				$after_day = $day;
			}
			if ( $day->weekday < $today_weekday && ( null === $before_day || $day->weekday < $before_day->weekday ) ) {
				$before_day = $day;
			}
		}
		return ( null === $after_day ? $before_day : $after_day );
	}
}
