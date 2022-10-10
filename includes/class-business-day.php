<?php
defined( 'ABSPATH' ) || exit;

/**
 * Inventory_Presser_Business_Day
 */
class Inventory_Presser_Business_Day {

	public $weekday; // 0 - 6

	public $open_hour; // 0 - 23
	public $open_minute; // 0 - 59

	public $close_hour; // 0 - 23
	public $close_minute; // 0 - 59

	/**
	 * __construct
	 *
	 * Initializes all member variables to 0
	 *
	 * @return void
	 */
	function __construct() {
		$this->weekday     = $this->open_hour = $this->close_hour =
		$this->open_minute = $this->close_minute = 0;
	}

	/**
	 * close_string
	 *
	 * @return void
	 */
	public function close_string() {
		return $this->time_string( $this->close_hour, $this->close_minute );
	}

	/**
	 * current_datetime
	 *
	 * Use WordPress current_time() to create a DateTime object
	 *
	 * @return void
	 */
	private function current_datetime() {
		return new DateTime( date( DATE_RFC2822, current_time( 'timestamp' ) ) );
	}

	/**
	 * is_tomorrow
	 *
	 * Is this day tomorrow?
	 *
	 * @return bool
	 */
	public function is_tomorrow() {
		$tomorrow = $this->current_datetime();
		$tomorrow->add( new DateInterval( 'P1D' ) );
		return $this->weekday == $tomorrow->format( 'w' );
	}

	/**
	 * open_in_some_fashion
	 *
	 * Does this day have any open hours?
	 *
	 * @return bool
	 */
	public function open_in_some_fashion() {
		return ( 0 != $this->close_hour && $this->open_hour < $this->close_hour );
	}

	/**
	 * open_later_today
	 *
	 * Is the business open later today even though it's closed now?
	 *
	 * @return bool
	 */
	public function open_later_today() {
		$now             = $this->current_datetime();
		$today_open_date = $this->current_datetime();
		$today_open_date->setTime( $this->open_hour, $this->open_minute, 0 );
		return null != $today_open_date && $now < $today_open_date;
	}

	/**
	 * open_right_now
	 *
	 * Is the business open right now?
	 *
	 * @return bool
	 */
	public function open_right_now() {
		$now             = $this->current_datetime();
		$today_open_date = $this->current_datetime();
		$today_open_date->setTime( $this->open_hour, $this->open_minute, 0 );

		$today_close_date = $this->current_datetime();
		$today_close_date->setTime( $this->close_hour, $this->close_minute, 0 );

		return null != $today_open_date && $now >= $today_open_date && $now < $today_close_date;
	}

	/**
	 * open_string
	 *
	 * A string that describes when the business opens on this day
	 *
	 * @return void
	 */
	public function open_string() {
		return $this->time_string( $this->open_hour, $this->open_minute );
	}

	/**
	 * time_string
	 *
	 * If the business is open at all on this day, what is that opening time in
	 * a human-friendly format?
	 *
	 * @param  int $hour
	 * @param  int $minute
	 * @return string
	 */
	function time_string( $hour, $minute ) {
		if ( ! $this->open_in_some_fashion() ) {
			return '';
		}

		// 9:30:00
		$time_string = (string) ( $hour ) . ':' . (string) ( $minute ) . ':00';
		return ( date( 'g:i a', strtotime( $time_string ) ) );
	}
}
