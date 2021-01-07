<?php
defined( 'ABSPATH' ) OR exit;

/**
 * Inventory_Presser_Badges
 * 
 * This class adds buttons that link to Carfax reports and NextGear vehicle
 * inspections to vehicle archives and single pages. It uses the
 * `invp_single_buttons` and `invp_archive_buttons` action hooks.
 */
class Inventory_Presser_Badges
{
	/**
	 * add_carfax
	 * 
	 * Filter callback that outputs HTML markup that creates a Carfax badge if
	 * $vehicle contains Carfax report data.
	 *
	 * @return void
	 */
	function add_carfax()
	{
		$carfax_html = invp_get_the_carfax_icon_html();
		if( '' != $carfax_html )
		{
			?><div class="carfax-wrapper"><?php
				echo $carfax_html;
			?></div><?php
		}
	}

	public function hooks()
	{
		//If Carfax is enabled, add the badge to pages
		$settings = INVP::settings();
		if ( isset( $settings['use_carfax'] ) && $settings['use_carfax'] )
		{
			add_action( 'invp_archive_buttons', array( $this, 'add_carfax' ) );
			add_action( 'invp_single_buttons',  array( $this, 'add_carfax' ) );
		}
	}
}
