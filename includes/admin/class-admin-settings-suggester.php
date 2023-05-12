<?php
defined( 'ABSPATH' ) || exit;

/**
 * Inventory_Presser_Admin_Settings_Suggester
 *
 * This class creates admin notices on this plugin's settings page at Vehicles >
 * Options in the dashboard. The notices recommend users make changes to
 * WordPress settings to better support an automobile inventory.
 *
 * @since 12.0.0
 */
class Inventory_Presser_Admin_Settings_Suggester {

	/**
	 * Creates HTML that renders an admin notice.
	 *
	 * @param  string $message
	 * @param  string $color   A value of 'red', 'yellow', or 'green' that appears as a left border on the notice box
	 * @return string HTML that renders an admin notice
	 */
	protected function get_admin_notice_html( $message, $color ) {
		switch ( $color ) {
			case 'red':
				$type = 'error';
				break;
			case 'yellow':
				$type = 'update-nag no-pad';
				break;
			case 'green':
				$type = 'updated';
				break;
		}
		return sprintf(
			'<div class="%s notice"><p><strong>%s</strong></p></div>',
			$type,
			$message
		);
	}

	/**
	 * Adds hooks
	 *
	 * @return void
	 */
	public function hooks() {
		/**
		 * If we are looking at our Options page, run the settings suggester. It
		 * will create admin notices for the user to tweak WordPress settings.
		 */
		add_action( 'admin_init', array( $this, 'scan_for_recommended_settings_and_create_warnings' ) );
	}

	/**
	 * Outputs an admin notice to warn a user that they have attachment multiple
	 * aspect ratios of vehicle photos to a single vehicle.
	 *
	 * @return void
	 */
	public function output_thumbnail_size_error_html() {
		echo $this->get_admin_notice_html(
			sprintf(
				'%s <a href="options-media.php">%s</a>.',
				__( 'At least one of your thumbnail sizes does not have an aspect ratio of 4:3, which is the most common smartphone and digital camera aspect ratio. You can change thumbnail sizes ', 'inventory-presser' ),
				__( 'here', 'inventory-presser' )
			),
			'yellow'
		);
	}

	/**
	 * Outputs an admin notice to warn the user if uploads are saved in month-
	 * and year-based folders.
	 *
	 * @return void
	 */
	public function output_upload_folder_error_html() {
		echo $this->get_admin_notice_html(
			sprintf(
				'%s <a href="options-media.php">%s</a>.',
				__( 'Your media settings are configured to organize uploads into month- and year-based folders. This is not optimal for Inventory Presser, and you can turn this setting off ', 'inventory-presser' ),
				__( 'here', 'inventory-presser' )
			),
			'yellow'
		);
	}

	/**
	 * Suggest values for WordPress internal settings if the user has values
	 * we do not prefer
	 *
	 * @return void
	 */
	public function scan_for_recommended_settings_and_create_warnings() {
		// Can this user even change options?
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Is this our options page?
		if ( empty( $_GET['page'] ) || 'dealership-options' != $_GET['page'] ) {
			return;
		}

		if ( '1' === get_option( 'uploads_use_yearmonth_folders' ) ) {
			// Organize uploads into yearly and monthly folders is turned on. Recommend otherwise.
			add_action( 'admin_notices', array( $this, 'output_upload_folder_error_html' ) );
		}

		// Are thumbnail sizes not 4:3 or 16:9 aspect ratios?
		$ratios = array(
			( get_option( 'thumbnail_size_w' ) / get_option( 'thumbnail_size_h' ) ),
			( get_option( 'medium_size_w' ) / get_option( 'medium_size_h' ) ),
			( get_option( 'large_size_w' ) / get_option( 'large_size_h' ) ),
		);
		foreach ( $ratios as $ratio ) {
			if ( ( 4/3 ) !== $ratio && ( 16/9 ) !== $ratio ) {
				// At least one thumbnail size is not 4:3 or 16:9.
				add_action( 'admin_notices', array( $this, 'output_thumbnail_size_error_html' ) );
				break;
			}
		}
	}
}
