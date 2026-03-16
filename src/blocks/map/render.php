<?php
/**
 * Render callback for the Map block.
 *
 * All of the parameters passed to the function where this file is being
 * required are accessible in this scope:
 *
 * @param array    $attributes The array of attributes for this block.
 * @param string   $content    Rendered block output. ie. <InnerBlocks.Content />.
 * @param WP_Block $block      The instance of the WP_Block class that represents
 *                             the block being rendered.
 *
 * @package inventory-presser
 */

defined( 'ABSPATH' ) || exit;

// Abort if we do not have a mapbox.com API token.
$settings = INVP::settings();
if ( empty( $settings['mapbox_public_token'] ) ) {
	return;
}

// Abort if we don't have any locations to show.
if ( empty( $attributes['locationSlug'] ) || ! is_array( $attributes['locationSlug'] ) ) {
	return;
}

// Get all of the selected Location terms.
$location_terms = get_terms(
	array(
		'hide_empty' => false,
		'slug'       => $attributes['locationSlug'],
		'taxonomy'   => 'location',
	)
);

if ( ! $location_terms ) {
	// There are no dealership addresses stored in this site, abort.
	return;
}

/**
 * Create an array that contains the data needed to create the markers
 * and popups: location names, addresses, and lat lon coords.
 */
$popups         = array();
$location_count = count( $location_terms );
for ( $t = 0; $t < $location_count; $t++ ) {
	$popup            = new stdClass();
	$popup->widget_id = ''; // Set later when we know the block instance ID.
	// Location title/dealership name - escape to prevent XSS.
	$popup->name = esc_html( $location_terms[ $t ]->name );
	// Address - allow <br /> tags but escape other HTML to prevent XSS.
	$popup->address = wp_kses_post(
		str_replace(
			"\r",
			'',
			str_replace(
				PHP_EOL,
				'<br />',
				$location_terms[ $t ]->description
			)
		)
	);
	// Get the latitude and longitude coordinates for this address.
	$location = INVP::fetch_latitude_and_longitude( $location_terms[ $t ]->term_id );
	if ( false !== $location ) {
		$popup->coords      = new stdClass();
		$popup->coords->lat = $location->lat;
		$popup->coords->lon = $location->lon;
		$popups[]           = $popup;
	}
}

if ( empty( $popups ) ) {
	/**
	 * We didn't find any latitude & longitude coordinates using the
	 * addresses on openstreetmap.org. It is likely that the addresses
	 * need to be added to the buildings for this dealer's locations.
	 */
	return;
}

// Enqueue leaflet.js scripts and styles.
if ( ! wp_script_is( Inventory_Presser_Map_Widget::SCRIPT_HANDLE_LEAFLET ) ) {
	wp_enqueue_script( Inventory_Presser_Map_Widget::SCRIPT_HANDLE_LEAFLET );
	wp_enqueue_style( Inventory_Presser_Map_Widget::SCRIPT_HANDLE_LEAFLET );
	wp_enqueue_style( Inventory_Presser_Map_Widget::ID_BASE );
}

// Include the JavaScript file that powers the map.
$handle = 'invp-maps';

// Create a unique ID for this Map block instance.
static $invp_map_block_instance = 0;
$widget_id                      = 'invp-map-block-' . ( ++$invp_map_block_instance );

// Set the widget_id on each popup so the JS can match them to this map.
foreach ( $popups as $popup ) {
	$popup->widget_id = $widget_id;
}

/**
 * If there are multiple Map widgets or blocks on the same page, we need
 * to avoid redefining the same invp_maps constant because that will
 * produce a JavaScript error.
 */
if ( ! wp_script_is( $handle ) ) {
	// First instance of a map on the page.
	wp_enqueue_script( $handle );
	// Localize an API key and the popups array for JavaScript.
	wp_add_inline_script(
		$handle,
		'const invp_maps = ' . wp_json_encode(
			array(
				'mapbox_public_token' => $settings['mapbox_public_token'],
				'popups'              => $popups,
			)
		),
		'before'
	);
} else {
	// There is another Map widget or block on this page already.
	foreach ( $popups as $popup ) {
		wp_add_inline_script( $handle, 'invp_maps.popups.push( ' . wp_json_encode( $popup ) . ' );', 'before' );
	}
}

// Output the map container.
printf(
	'<div %s></div>',
	wp_kses_data(
		get_block_wrapper_attributes(
			array(
				'class' => 'invp-map ' . $widget_id,
				'id'    => $widget_id . '-inner',
			)
		)
	)
);
