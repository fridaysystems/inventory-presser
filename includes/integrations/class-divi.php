<?php
/**
 * Divi theme integration
 *
 * @package    inventory-presser
 * @since      14.18.0
 * @subpackage inventory-presser/includes/integrations
 * @author     Corey Salzano <corey@friday.systems>
 */

defined( 'ABSPATH' ) || exit;

/**
 * Inventory_Presser_Divi
 *
 * Integrates with Divi theme. Prevents Divi from breaking our Gallery Block in
 * the block editor.
 */
class Inventory_Presser_Divi {
	/**
	 * Adds hooks that power our Divi integration.
	 *
	 * @return void
	 */
	public function add_hooks() {
		/**
		 * Priority 3 to run before a Divi hook at
		 * themes/Divi/includes/builder/feature/BlockEditorIntegration.php line 717
		 */
		add_action( 'enqueue_block_editor_assets', array( $this, 'add_et_builder_post_types_hook' ), 3 );

		// Output a warning near the Listings Pages setting at Vehicles → Options.
		add_action( 'invp_listings_pages_settings', array( $this, 'output_listings_pages_warning' ) );

		// Change the way some of our meta fields are rendered by the Divi Builder.
		add_filter( 'et_builder_resolve_dynamic_content_custom_meta_inventory_presser_options_array', array( $this, 'resolve_dynamic_content_options' ), 10, 3 );
		add_filter( 'et_builder_resolve_dynamic_content', array( $this, 'resolve_dynamic_content' ), 11, 4 );
	}

	/**
	 * Returns formatted data for some of our meta fields. Formats currency and
	 * numeric values.
	 *
	 * @param string  $content Empty string.
	 * @param string  $name Custom field name.
	 * @param array   $settings Array of dynamic content settings.
	 * @param integer $post_id Post Id.
	 * @return string
	 */
	public function resolve_dynamic_content( $content, $name, $settings, $post_id ) {
		$keys_and_callbacks = array(
			'custom_meta_inventory_presser_down_payment' => 'invp_get_the_down_payment',
			'custom_meta_inventory_presser_odometer'     => 'invp_get_the_odometer',
			'custom_meta_inventory_presser_payment'      => 'invp_get_the_payment',
			'custom_meta_inventory_presser_msrp'         => 'invp_get_the_msrp',
			'custom_meta_inventory_presser_price'        => 'invp_get_the_price',
		);
		if ( ! array_key_exists( $name, $keys_and_callbacks ) ) {
			return $content;
		}
		// Odometer takes a different first parameter.
		if ( 'custom_meta_inventory_presser_odometer' === $name ) {
			return $settings['before'] . invp_get_the_odometer( '', $post_id ) . $settings['after'];
		}
		return $settings['before'] . call_user_func( $keys_and_callbacks[ $name ], $post_id ) . $settings['after'];
	}

	/**
	 * Returns unordered list HTML that contains all vehicle options or empty
	 * string.
	 *
	 * @param string  $content Dynamic content string data.
	 * @param array   $settings Array of dynamic content settings.
	 * @param integer $post_id Post Id.
	 * @return string
	 */
	public function resolve_dynamic_content_options( $content, $settings, $post_id ) {
		$options_array = invp_get_the_options( $post_id );
		if ( empty( $options_array ) ) {
			return $content;
		}

		// If we are rendering the options array, we need to return an HTML list.
		$options_html = '<ul class="vehicle-features">';
		foreach ( invp_get_the_options() as $option ) {
			$options_html .= sprintf( '<li>%s</li>', esc_html( $option ) );
		}
		return $options_html . '</ul>';
	}

	/**
	 * Lie to Divi about the builder being enabled for our post type.
	 * We don't want Divi's nag to take over the block editor, "Build Your
	 * Layout Using Divi". It clobbers our gallery block and prevents users
	 * from understanding how to manage photos in the block editor. So, if
	 * we are adding a new vehicle in the block editor, lie to Divi about
	 * our post type being one that it manages with the Builder.
	 */
	public function add_et_builder_post_types_hook() {
		add_filter( 'et_builder_post_types', array( $this, 'remove_post_type_block_editor' ) );
	}

	/**
	 * Warn users that the Listings Pages feature does not work on Divi.
	 *
	 * @return void
	 */
	public function output_listings_pages_warning() {
		// Is Divi or a Divi child theme active?
		if ( in_array( 'Divi', array( get_template(), get_stylesheet() ), true ) ) {
			// Yes. This feature does not work on Divi.
			printf(
				'<div class="invp-notice invp-notice-error"><p>%s %s <a href="https://inventorypresser.com/docs/divi-setup-guide/">%s →</a></p></div>',
				esc_html__( 'Listings Pages feature does not work on Divi.', 'inventory-presser' ),
				esc_html__( 'Visit', 'inventory-presser' ),
				esc_html__( 'Divi Setup Guide', 'inventory-presser' )
			);
		}
	}

	/**
	 * Detects the add new vehicle screen in the dashboard and removes our post
	 * type from the list of post types enabled in Divi Builder.
	 *
	 * @param  string[] $post_types Array of post types enabled in Divi Builder.
	 * @return string[]
	 */
	public function remove_post_type_block_editor( $post_types ) {
		// Is this the block editor creating a new vehicle?
		global $pagenow;
		if ( ! is_admin()
			|| get_post_type() !== INVP::POST_TYPE
			|| 'post-new.php' !== $pagenow ) {
				// No.
			return $post_types;
		}
		// Is our post type managed by the Divi Builder?
		$index = array_search( INVP::POST_TYPE, $post_types, true );
		if ( false === $index ) {
			// No.
			return $post_types;
		}
		// Yes. Remove it from the list of post types to prevent the Divi nag.
		unset( $post_types[ $index ] );
		return array_values( $post_types );
	}
}
