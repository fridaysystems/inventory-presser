<?php
class Inventory_Presser_Option_Manager {

	const OPTION_NAME = 'inventory_presser_options';

	function delete_options() {
		delete_option( self::OPTION_NAME );
	}

	function get_default_options( ) {
		$options = array(
			'default-sort-key'                 => apply_filters( 'translate_meta_field_key', 'make' ),
			'default-sort-order'               => 'ASC',
		);
		return apply_filters( 'inventory_presser_default_options', $options );
	}

	function get_options( ) {
		$options = get_option( self::OPTION_NAME );
		if( !$options ) {
			$options = $this->get_default_options( );
			$this->save_options( $options );
			return $options;
		}
		return $options;
	}

	function save_options( $arr ) {
		update_option( self::OPTION_NAME, $arr );
	}
}