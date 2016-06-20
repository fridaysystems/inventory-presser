<?php
/**
 * An object that modifies the behavior of WordPress Importers
 *
 * This file defines hooks that are called by WXR importers like
 * the WordPress Importer and WordPress Importer for Cron.
 *
 * @since      0.2.0
 * @package    Inventory_Presser
 * @subpackage Inventory_Presser/includes
 * @author     Corey Salzano <corey.salzano@gmail.com>
 */
class Inventory_Presser_Modify_Imports {

	const PENDING_DIR_NAME = '_pending_import';

	var $post_type;
	var $upload_dir;

	function __construct( $post_type ) {
		$this->post_type = $post_type;
		$this->upload_dir = wp_upload_dir();
	}

}
