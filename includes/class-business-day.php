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

	public function open_in_some_fashion()
	{
		return ( 0 != $this->close_hour && $this->open_hour < $this->close_hour );
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
