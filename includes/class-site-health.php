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
	 * When the tests defined in this class are performed, do they produce
	 * results with statuses of "recommended"?
	 *
	 * @return bool
	 */
	public function have_recommendations() {
		$tests = $this->site_status_add_tests( array() );
		foreach ( $tests['direct'] as $test => $data ) {
			if ( method_exists( $this, $data['test'][1] ) && is_callable( $data['test'] ) ) {
				$results[] = call_user_func( $data['test'] );
			}
		}
		return in_array( 'recommended', array_column( $results, 'status' ), true );
	}

	/**
	 * Adds our tests to the array of tests.
	 *
	 * @param  array $tests
	 * @return array
	 */
	public function site_status_add_tests( $tests ) {
		// Can the user change options like Media settings and permalinks?
		if ( current_user_can( 'manage_options' ) ) {
			// Yes.

			// Add a test for media folders.
			$tests['direct']['invp_media_folders'] = array(
				'label' => __( 'Inventory Presser', 'inventory-presser' ),
				'test'  => array( $this, 'site_status_media_folders_result' ),
			);

			// Add a test for plain permalinks.
			$tests['direct']['invp_plain_permalinks'] = array(
				'label' => __( 'Inventory Presser', 'inventory-presser' ),
				'test'  => array( $this, 'site_status_plain_permalinks_result' ),
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
		// Are media files organized into year/month folders?
		if ( '1' === get_option( 'uploads_use_yearmonth_folders' ) ) {
			// Yes.
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

	/**
	 * Performs the plain permalinks test and returns the result array.
	 *
	 * @return array
	 */
	public function site_status_plain_permalinks_result() {
		// Is the permalink structure empty?
		if ( '' === get_option( 'permalink_structure' ) ) {
			// Yes.
			return array(
				'label'       => __( 'Consider using pretty permalinks', 'inventory-presser' ),
				'status'      => 'recommended',
				'badge'       => array(
					'label' => __( 'Inventory Presser', 'inventory-presser' ),
					'color' => 'blue',
				),
				'description' => sprintf(
					'<p>%s</p>',
					__( 'No permalink structure has been chosen at Settings → Permalinks. This means the default vehicle archive is located at /?post_type=inventory_vehicle rather than /inventory.', 'inventory-presser' )
				),
				'actions'     => '',
				'test'        => 'invp_plain_permalinks',
			);
		}
		return array(
			'label'       => __( 'Permalink structure allows default vehicle archive at /inventory', 'inventory-presser' ),
			'status'      => 'good',
			'badge'       => array(
				'label' => __( 'Inventory Presser', 'inventory-presser' ),
				'color' => 'blue',
			),
			'description' => sprintf(
				'<p>%s</p>',
				__( 'A permalink structure has been defined at Settings → Permalinks that allows the default vehicle archive to be located at /inventory.', 'inventory-presser' )
			),
			'actions'     => '',
			'test'        => 'invp_plain_permalinks',
		);
	}
}
