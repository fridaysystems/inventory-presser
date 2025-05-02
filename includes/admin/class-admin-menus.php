<?php
/**
 * Lets users add phone numbers to menus. Creates a meta box at Appearance >
 * Menus titled "Phone Numbers" when at least one phone number is saved in a
 * Locations taxonomy term.
 *
 * @package    inventory-presser
 * @subpackage inventory-presser/includes/admin
 * @author     Corey Salzano <corey@friday.systems>
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Inventory_Presser_Admin_Menus' ) ) {
	/**
	 * Inventory_Presser_Admin_Menus
	 */
	class Inventory_Presser_Admin_Menus {
		/**
		 * __construct
		 *
		 * @return void
		 */
		public function __construct() {
			$this->add_hooks();
		}

		/**
		 * Set up filter and action hooks
		 *
		 * @return void
		 */
		public function add_hooks() {
			// Add phone numbers to nav menus.
			add_action( 'admin_init', array( $this, 'add_add_menu_items' ) );
			// Store meta data with phone number nav menu items so we can update the numbers when the dealerships number changes.
			add_action( 'wp_update_nav_menu_item', array( $this, 'add_nav_menu_meta' ), 10, 3 );
			/**
			 * When a location is updated, make sure changes to this phone
			 * number are made in the menu items. Priority 11 to happen after
			 * term meta updates in class-admin-location-meta.php are complete.
			 */
			add_action( 'edited_location', array( $this, 'update_phone_numbers_in_menus' ), 11, 1 );
		}

		/**
		 * Links a menu item post to a Locations taxonomy term. When a user adds
		 * a phone number to a menu, we stash the location term ID in the list
		 * of CSS classes. This method extracts it, saves it in another post
		 * meta key, and removes the CSS class. The user never sees the class.
		 * Stashing the Location term ID allows us to find this menu item and
		 * edit it when the user changes the phone number in taxonomy term meta.
		 *
		 * @param int   $menu_id         ID of the updated menu.
		 * @param int   $menu_item_db_id ID of the updated menu item.
		 * @param array $args            An array of arguments used to update a menu item.
		 * @return void
		 */
		public function add_nav_menu_meta( $menu_id, $menu_item_db_id, $args ) {
			// Extract location ID from classes?
			if ( is_array( $args['menu-item-classes'] ) ) {
				$classes_to_remove = array();
				foreach ( $args['menu-item-classes'] as $css_class ) {
					// Look for a CSS class like "location-4176".
					preg_match( '/location-([0-9]+)/', $css_class, $matches );
					// Did we find a match?
					if ( ! empty( $matches[1] ) ) {
						// Yes.
						// Store this location term ID in another meta key.
						update_post_meta( $menu_item_db_id, '_invp_location_id', $matches[1] );
						// Remove this CSS class in a moment.
						$classes_to_remove[] = $matches[0];
					}

					// Look for a CSS class like "phone-uid-4x3h1S7E1o6B".
					preg_match( '/phone-uid-([a-zA-Z0-9]+)/', $css_class, $matches );
					// Did we find a match?
					if ( ! empty( $matches[1] ) ) {
						// Yes.
						// Store this location term ID in another meta key.
						update_post_meta( $menu_item_db_id, '_invp_phone_uid', $matches[1] );
						// Remove this CSS class in a moment.
						$classes_to_remove[] = $matches[0];
					}
				}
				if ( ! empty( $matches[0] ) ) {
					// Remove from classes.
					$args['menu-item-classes'] = array_diff( $args['menu-item-classes'], $classes_to_remove );
					update_post_meta( $menu_item_db_id, '_menu_item_classes', $args['menu-item-classes'] );
				}
			}
		}

		/**
		 * Adds a meta box to the Appearance > Menus sidebar list where post
		 * types and taxonomies appear by default.
		 *
		 * @return void
		 */
		public function add_add_menu_items() {
			// Are there any phone numbers saved?
			global $wpdb;
			$term_id = $wpdb->get_var(
				"SELECT		`{$wpdb->prefix}termmeta`.term_id

				FROM		`{$wpdb->prefix}terms`
							LEFT JOIN `{$wpdb->prefix}termmeta` ON `{$wpdb->prefix}termmeta`.term_id = `{$wpdb->prefix}terms`.term_id

				WHERE		meta_key LIKE 'phone_%_number'
							AND meta_value != ''

				LIMIT 1"
			);
			if ( empty( $term_id ) ) {
				// Do not proceed if we do not have any phone numbers.
				return;
			}

			// Add a meta box titled "Phone Numbers" to the Appearance > Menus
			// sidebar list.
			add_meta_box(
				'add-phone-numbers', // ID.
				__( 'Phone Numbers', 'inventory-presser' ), // Title.
				array( $this, 'nav_menus_phone_numbers_meta_box' ), // Callback.
				'nav-menus', // Screen.
				'side', // Context.
				'default', // Priority.
				get_terms(
					array(
						'taxonomy'   => 'location',
						'hide_empty' => false,
					)
				) // Args.
			);
		}

		/**
		 * nav_menus_phone_numbers_meta_box
		 *
		 * @param  mixed $data_object Gets passed to the meta box callback function as the first parameter.
		 *                            Often this is the object that's the focus of the current screen,
		 *                            for example a `WP_Post` or `WP_Comment` object.
		 * @param  array $box An item in the global $wp_meta_boxes array.
		 * @return void
		 */
		public function nav_menus_phone_numbers_meta_box( $data_object, $box ) {

			?><div id="taxonomy-location"><div class="tabs-panel-active"><p><ul>
			<?php

			// For each location term, list phone numbers.
			foreach ( $box['args'] as $term ) {
				$phones = INVP::get_phones( $term->term_id );
				if ( ! empty( $phones ) ) {
					echo '<li><p><b>' . esc_html( $term->name ) . '</b></p>'
						. '<ul class="categorychecklist">';
					$phone_count = count( $phones );
					for ( $p = 0; $p < $phone_count; $p++ ) {
						echo '<li><label class="menu-item-title">'
							. '<input type="checkbox" class="menu-item-checkbox" name="menu-item[' . esc_attr( $p + 999 ) . '][menu-item-object-id]" value="' . esc_attr( $term->term_id ) . '" /> '
							. esc_html( $phones[ $p ]['number'] ) . '</label>'
							. '<input type="hidden" name="menu-item[' . esc_attr( $p + 999 ) . '][menu-item-url]" value="' . esc_attr( $this->phone_link_target( $phones[ $p ]['number'] ) ) . '" />'
							. '<input type="hidden" name="menu-item[' . esc_attr( $p + 999 ) . '][menu-item-title]" value="' . esc_attr( $phones[ $p ]['number'] ) . '" />'
							. '<input type="hidden" name="menu-item[' . esc_attr( $p + 999 ) . '][menu-item-type]" value="custom" />'
							. '<input type="hidden" name="menu-item[' . esc_attr( $p + 999 ) . '][menu-item-classes]" value="location-' . esc_attr( $term->term_id ) . ' phone-uid-' . esc_attr( $phones[ $p ]['uid'] ) . '" />'
							. '</li>';
					}
					echo '</ul></li>';
				}
			}
			?>
			</ul><span class="add-to-menu">
			<input type="submit" 
				class="button submit-add-to-menu right" value="<?php esc_attr_e( 'Add to Menu' ); ?>"
				name="add-taxonomy-menu-item" id="submit-taxonomy-location"
			/>
			<span class="spinner"></span></span></p></div></div>
			<?php
		}

		/**
		 * Creates a link target that dials a phone number given a number. No
		 * support for international numbers.
		 *
		 * @param  string $phone_number A phone number like "717-733-8889" or "1-800-677-7160".
		 * @return string
		 */
		protected function phone_link_target( $phone_number ) {
			$just_number = preg_replace( '/[^0-9]+/', '', $phone_number );
			if ( 10 === strlen( $just_number ) ) {
				$just_number = '1' . $just_number;
			}
			return 'tel:+' . esc_attr( $just_number );
		}

		/**
		 * Updates phone numbers in menu items when phone numbers are updated in
		 * the Locations taxonomy term meta.
		 *
		 * @param  int $term_id Location taxonomy term ID.
		 * @return void
		 */
		public function update_phone_numbers_in_menus( $term_id ) {
			// Do any menu items have term ID in post meta key _invp_location_id?
			$menu_item_posts = get_posts(
				array(
					'post_type'      => 'nav_menu_item',
					'meta_key'       => '_invp_location_id',
					'meta_value'     => strval( $term_id ),
					'posts_per_page' => 50,
				)
			);
			if ( empty( $menu_item_posts ) ) {
				// No.
				return;
			}

			$phones = INVP::get_phones( $term_id );

			foreach ( $menu_item_posts as $post ) {
				// Does this menu item have a phone number?
				$phone_uid = get_post_meta( $post->ID, '_invp_phone_uid', true );
				if ( ! empty( $phone_uid ) ) {
					foreach ( $phones as $phone ) {
						if ( $phone['uid'] !== $phone_uid ) {
							continue;
						}
						// Update the link target.
						update_post_meta( $post->ID, '_menu_item_url', $this->phone_link_target( $phone['number'] ) );
						// Update the menu text.
						$menu_update = array(
							'ID'         => $post->ID,
							'post_title' => $phone['number'],
							'post_name'  => sanitize_title( $phone['number'] ),
						);
						wp_update_post( (object) $menu_update );
					}
				}
			}
		}
	}
	new Inventory_Presser_Admin_Menus();
}
