<?php
/**
 * Adds tests to the Site Health Status API.
 *
 * @package inventory-presser
 * @since   14.13.1
 */

defined( 'ABSPATH' ) || exit;

/**
 * Inventory_Presser_Site_Health
 */
class Inventory_Presser_Site_Health {
	/**
	 * Adds hooks that power the feature.
	 *
	 * @return void
	 */
	public function add_hooks() {
		add_filter( 'site_status_tests', array( $this, 'site_status_add_tests' ) );
	}

	/**
	 * Adds our tests to the array of tests.
	 *
	 * @param  array $tests
	 * @return array
	 */
	public function site_status_add_tests( $tests ) {
		// Are media files organized into year/month folders?
		if ( current_user_can( 'manage_options' ) ) {
			// Yes.
			$tests['direct']['invp_media_folders'] = array(
				'label' => __( 'Inventory Presser', 'inventory-presser' ),
				'test'  => array( $this, 'site_status_media_folders_result' ),
			);
		}
		return $tests;
	}

	/**
	 * Performs the media folders test and returns the result array.
	 *
	 * @return array
	 */
	public function site_status_media_folders_result() {
		if ( '1' === get_option( 'uploads_use_yearmonth_folders' ) ) {
			return array(
				'label'       => __( 'Consider storing vehicle photos in a single folder', 'inventory-presser' ),
				'status'      => 'recommended',
				'badge'       => array(
					'label' => __( 'Inventory Presser', 'inventory-presser' ),
					'color' => 'blue',
				),
				'description' => sprintf(
					'<p>%s</p>',
					__( 'Media uploads are organized into month- and year-based folders. This reveals photo upload dates to users, and changes vehicle photo URLs when they are updated seasonally. Change the setting at Settings → Media.', 'inventory-presser' )
				),
				'actions'     => '',
				'test'        => 'invp_media_folders',
			);
		}
		return array(
			'label'       => __( 'Vehicle photos are stored in a predictable way', 'inventory-presser' ),
			'status'      => 'good',
			'badge'       => array(
				'label' => __( 'Inventory Presser', 'inventory-presser' ),
				'color' => 'blue',
			),
			'description' => sprintf(
				'<p>%s</p>',
				__( 'Media uploads are not organized into month- and year-based folders. Photo upload dates are not revealed to users, and vehicle photo URLs will not change even if the photos themselves change.', 'inventory-presser' )
			),
			'actions'     => '',
			'test'        => 'invp_media_folders',
		);
	}
}
