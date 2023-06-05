<?php
defined( 'ABSPATH' ) || exit;

/**
 * Inventory_Presser_Badges
 *
 * This class adds buttons that link to Carfax reports and NextGear vehicle
 * inspections to vehicle archives and single pages. It uses the
 * `invp_single_buttons` and `invp_archive_buttons` action hooks.
 */
class Inventory_Presser_Badges {

	/**
	 * Filter callback that outputs HTML markup that creates a Carfax badge if
	 * $vehicle contains Carfax report data.
	 *
	 * @return void
	 */
	public function add_carfax() {
		$carfax_html = invp_get_the_carfax_icon_html();
		if ( '' != $carfax_html ) {
			?><div class="carfax-wrapper">
			<?php
				echo $carfax_html;
			?>
</div>
			<?php
		}
	}

	/**
	 * Outputs an HTML button to open NextGear Mechanic's Reports.
	 *
	 * @return void
	 */
	public function add_nextgear() {
		$url = INVP::get_meta( 'nextgear_inspection_url' );
		if ( ! empty( $url ) ) {
			// CSS classes on the <a>.
			$classes = apply_filters( 'invp_button_classes_nextgear_inspection_url', 'wp-block-button__link button _button _button-small' );

			?>
			<div class="nextgear-wrapper">
				<a class="<?php echo $classes; ?>" href="<?php echo $url; ?>">
									<?php

										echo apply_filters( 'invp_button_text_nextgear_inspection_url', __( 'Mechanic\'s Report', 'inventory-presser' ) );

									?>
				</a>
			</div>
			<?php
		}
	}

	/**
	 * Adds hooks that power the feature.
	 *
	 * @return void
	 */
	public function add_hooks() {
		// If Carfax is enabled, add the badge to pages
		$settings = INVP::settings();
		if ( isset( $settings['use_carfax'] ) && $settings['use_carfax'] ) {
			add_action( 'invp_archive_buttons', array( $this, 'add_carfax' ) );
			add_action( 'invp_single_buttons', array( $this, 'add_carfax' ) );
		}

		// NextGear vehicle inspections.
		add_action( 'invp_archive_buttons', array( $this, 'add_nextgear' ) );
		add_action( 'invp_single_buttons', array( $this, 'add_nextgear' ) );
	}
}
