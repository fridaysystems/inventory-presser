<?php
defined( 'ABSPATH' ) OR exit;

class Inventory_Presser_Business_Day
{
	public $weekday; // 0 - 6

	public $open_hour; // 0 - 23
	public $open_minute; // 0 - 59

	public $close_hour; // 0 - 23
	public $close_minute; // 0 - 59

	function __construct()
	{
		$this->weekday = $this->open_hour = $this->close_hour =
		$this->open_minute = $this->close_minute = 0;
	}

	public function close_string()
	{
		return $this->time_string( $this->close_hour, $this->close_minute );
	}

	//use WordPress current_time() to create a DateTime object
	private function current_datetime()
	{
		return new DateTime( date( DATE_RFC2822, current_time( 'timestamp' ) ) );
	}

	/**
	 * @return boolean
	 */
	public function is_tomorrow()
	{
		$tomorrow = $this->current_datetime();
		$tomorrow->add( new DateInterval( 'P1D' ) );
		return $this->weekday == $tomorrow->format( 'w' );
	}

	/**
	 * Does this day have any open hours?
	 *
	 * @return boolean
	 */
	public function open_in_some_fashion()
	{
		return ( 0 != $this->close_hour && $this->open_hour < $this->close_hour );
	}

	/**
	 * @return boolean
	 */
	public function open_later_today()
	{
		$now = $this->current_datetime();
		$today_open_date = $this->current_datetime();
		$today_open_date->setTime( $this->open_hour, $this->open_minute, 0 );
		return null != $today_open_date && $now < $today_open_date;
	}

	/**
	 * @return boolean
	 */
	public function open_right_now()
	{
		$now = $this->current_datetime();
		$today_open_date = $this->current_datetime();
		$today_open_date->setTime( $this->open_hour, $this->open_minute, 0 );

		$today_close_date = $this->current_datetime();
		$today_close_date->setTime( $this->close_hour, $this->close_minute, 0 );

		return null != $today_open_date && $now >= $today_open_date && $now < $today_close_date;
	}

	public function open_string()
	{
		return $this->time_string( $this->open_hour, $this->open_minute );
	}

	function time_string( $hour, $minute )
	{
		if( ! $this->open_in_some_fashion() ) { return ''; }

		//9:30:00
		$time_string = (string)( $hour ) . ':' . (string)( $minute ) .':00';
		return ( date( 'g:i a', strtotime( $time_string ) ) );
	}
}
