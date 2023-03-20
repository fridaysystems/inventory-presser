=== Inventory Presser ===
Contributors: salzano
Tags: car dealer, inventory management, vehicle, automobile, dealership, lot, motorcycle, rv
Requires at least: 5.0.0
Tested up to: 6.1.1
Requires PHP: 7.0.0
Stable tag: 14.6.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Turn WordPress into a dealership website. Provides vehicle listings and photo sliders for automobiles or powersports.

== Description ==

Manage and market dealership inventory with listings and photo sliders built on a vehicle custom post type.

* Supports automobiles, motorcycles, ATVs, RVs, and boats
* [Manage multiple lot locations](https://inventorypresser.com/docs/vehicle-post-type/locations-taxonomy/) and maintain separate phone numbers & hours for each
* [VIN-decoding add-on](https://inventorypresser.com/products/plugins/add-vins-to-vehicle-urls/) available
* Categorizes inventory by type, body style, fuel, transmission, drive type, availability, new or used, location, and more
* Includes blocks, shortcodes, and [widgets](https://inventorypresser.com/docs/feature-list/widgets/) including vehicle sliders and inventory grids

Integrates with other plugins

* Import any CSV file with WP All Import
* Contact Form 7 form tag adds vehicles to lead emails
* Elementor Dynamic Tags add-on available

Built the right way and developer-friendly

* [Custom post type `inventory_vehicle`](https://inventorypresser.com/docs/vehicle-post-type/) enables [vehicle data imports](https://inventorypresser.com/docs/using-the-rest-api-to-update-vehicle-listings-in-wordpress/)
* Editor sidebar integrates custom fields into the block editor
* Adds columns to the posts list for vehicles for stock number, color, odometer, price, photo count, and thumbnail
* Implements 10+ custom taxonomies to group vehicles by year, make, model, type, body style, fuel, transmission, drive type, availability, new or used, location, and more
* [Hooks](https://inventorypresser.com/docs/hooks/) in all the right places & powerful [shortcodes](https://inventorypresser.com/docs/shortcodes/)
* [Template tags](https://inventorypresser.com/docs/template-tags/) make front-end development easy
* [Runs on any theme](https://inventorypresser.com/docs/theme-compatibility-with-vehicle-listings/) & provides shortcodes for the stubborn ones.
* [Full feature list](https://inventorypresser.com/docs/feature-list/) & more documentation [inventorypresser.com](https://inventorypresser.com/)


== Installation ==

1. Upload the `inventory-presser` directory to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Read this plugin's Frequently Asked Question called "Will Inventory Presser work with my theme?" to learn how vehicles can best be displayed


== Frequently Asked Questions ==

= How much does this plugin cost? =
This plugin is free and open source. Free and paid add-ons & services are available at https://inventorypresser.com/.

= Does it decode VINs? =
Not for free. A VIN decoder add-on is available at https://inventorypresser.com/products/plugins/vin-decoder/.

= Can I import my inventory into this system? =
Yes. We use the [WordPress REST API](https://developer.wordpress.org/rest-api/) to deliver inventory updates. [Read how we do it](https://inventorypresser.com/docs/using-the-rest-api-to-update-vehicle-listings-in-wordpress/). Vehicles in Inventory Presser are stored in a custom post type using meta fields, taxonomy terms, and photo attachments, and many CSV/spreadsheet importer plugins like WP All Import work with custom post types. If imports sound challenging, Friday Systems (that's us) feeds hundreds of sites running this plugin. It may be easier to let Friday Systems handle the last leg for a monthly fee.

= Will Inventory Presser work with my theme? =
Yes. Check out our [Theme Compatibility Guide](https://inventorypresser.com/docs/theme-compatibility-with-vehicle-listings/). If your theme does not include templates to display listing pages and vehicle detail pages, the plugin will provide the markup for those pages automatically. There are shortcodes like `[invp_archive]` and `[invp_single_vehicle]` as a last resort. A number of [Template Tags](https://inventorypresser.com/docs/template-tags/) make designing custom templates easy.


== Screenshots ==

1. This is a vehicle post archive showing post title, full size featured image, price, vehicle attribute table, carfax button, and link to vehicle post single.
2. This is an inventory grid showing featured image thumbnails and post titles. A full inventory link takes users to the vehicle post archive.
3. This is a list of vehicles in the administrator dashboard that the user sees when she clicks the Vehicles or All Vehicles menu items. Each vehicle is listed by year, make, model, trim, stock number, color, odometer, price, and photo count. If the vehicle has photos, a thumbnail is also shown. This screen shot was taken on version 3.1.0.
4. This is the edit screen that adds or edits a single vehicle. The post content box is demoted to handle only the sentence-form vehicle description, and the entire edit screen is enhanced to support vehicle attributes. This screen shot was taken on version 3.1.0.
5. This screenshot shows a vehicle post in the WordPress block editor. An editor sidebar for the Inventory Presser plugin is expanded and contains vehicle attribute fields. An arrow points to a button with a key on it that reveals the editor sidebar.
6. This screenshot shows a vehicle archive on the Twenty Twenty theme before any customization.


== Changelog ==

= 14.6.0 =
* [Added] Adds a filter `invp_meta_fields` so other plugins can add meta fields to the vehicle post type.
* [Added] Adds additional meta fields for Carfax "Accident Free" and "Top Condition" badges.
* [Added] Adds a JavaScript filter `invp_editor_sidebar_elements` to let other plugins add to the Editor Sidebar where vehicle attributes are managed.
* [Added] Adds a feature that changes the <title> tag on inventory archives.
* [Fixed] Fixes a bug that showed the Boat Attributes field in the Editor Sidebar for non-boat vehicles.
* [Fixed] Stops hitting inventorypresser.com as often to check for valid add-on licenses.
* [Fixed] Stops showing sold vehicles in the [invp_inventory_grid] shortcode and Inventory Grid widget when the "Include sold vehicles" setting is off.

= 14.5.2 =
* [Added] Adds a filter `invp_get_the_odometer` to filter the value returned by the invp_get_the_odometer() template tag.
* [Fixed] Fixes the way queries are edited during searches to avoid name collisions.

= 14.5.1 =
* [Added] Wraps strings in filters in the was_now_discount pricing display option so "Retail", "Now", and "You Save" strings can be changed by other plugins using the `invp_price_was_now_discount_retail`, `invp_price_was_now_discount_now`, and `invp_price_was_now_discount_save` filters.
* [Fixed] Applies the `invp_price_display` filter to all return paths of the invp_get_the_price() template tag.

= 14.5.0 =
* [Added] Adds a feature enabled by default: Adds a gallery block to new vehicle posts with a specific CSS class that allows users to upload and reorder photos on vehicle posts in the block editor. This is merging the invp-photo-arranger feature plugin into Inventory Presser core.
* [Fixed] Makes more strings translateable. Escapes strings for output on the plugin settings page.
* [Fixed] Bug fix when changing attachment URLs to have cache-busting hash querystrings. If the post ID does not point to an attachment, exit early.
* [Fixed] Bug fixes in the maximum price filter widget. Avoid warnings when no widget settings are saved for the instance.
* [Fixed] CSS tweak for thumbnails in the vehicle posts list in the dashboard.

= 14.4.0 =
* [Added] Adds a minimum PHP version directive to this file of 7.0.0 to support the null coalescing operator.
* [Added] Adds a template tag `invp_get_raw_msrp()` and filter `invp_get_raw_msrp`.
* [Added] Adds a template tag `invp_get_the_dealer_id()`.
* [Added] Adds a postmeta field `inventory_presser_certified_preowned` to the vehicle post type and a template tag `invp_is_certified_preowned()`.
* [Fixed] Fixes a bug in the Maximum Price Filter widget to make it compatible with the new block widgets feature in the dashboard.
* [Fixed] Fixes a bug that caused vehicle thumbnail photos to break out of the posts table at the Vehicles dashboard page.
* [Fixed] Fixes a bug that prevented `invp_get_the_photos()` from including the urls array key when a single size of `large` was passed.
* [Fixed] Fixes bugs when calling `http_build_query()` for compatibility with PHP 8.1.
* [Fixed] Fixes a bug in the uninstaller that prevented the plugin from being deleted.


== Upgrade Notice ==

= 14.6.0 =
Stops showing sold vehicles in the [invp_inventory_grid] shortcode and Inventory Grid widget when the "Include sold vehicles" setting is off. Stops hitting inventorypresser.com as often to check for valid add-on licenses. Fixes a bug that showed the Boat Attributes field in the Editor Sidebar for non-boat vehicles. Adds a feature that changes the <title> tag on inventory archives. Adds a JavaScript filter `invp_editor_sidebar_elements` to let other plugins add to the Editor Sidebar where vehicle attributes are managed. Adds additional meta fields for Carfax "Accident Free" and "Top Condition" badges. Adds a filter `invp_meta_fields` so other plugins can add meta fields to the vehicle post type.

= 14.5.0 =
Adds a feature enabled by default: Adds a gallery block to new vehicle posts with a specific CSS class that allows users to upload and reorder photos on vehicle posts in the block editor. This is merging the invp-photo-arranger feature plugin into Inventory Presser core. Deactivate and delete invp-photo-arranger after upgrading to 14.5.0.

= 14.2.1 =
Adds two template tags `invp_get_the_location_state()` and `invp_get_the_location_zip()`. Wraps vehicle prices in a <span> element in the [invp_inventory_grid] shortcode so they can be more easily styled.

= 14.1.0 =
Adds a Contact Form 7 mail tag [invp_adf_timestamp] to help insert ISO 8601 timestamps into ADF XML lead emails. Adds a Contact Form 7 mail tag [invp_adf_vehicle] to help insert vehicles into ADF XML lead emails. Adds a "No vehicles found." message to the [invp_archive] shortcode when there are no vehicle posts that satisfy the query. Adds a default false value for the use_carfax setting so it shows up in REST at /wp-json/invp/v1/settings consistently. Wraps the vehicle YMM string in a link in email sent using our Contact Form 7 form-tag [invp_vehicle].

= 14.0.0 =
Integrates WP All Import to allow CSV feed imports. Integrates Contact Form 7 to add a [invp_vehicle] form tag. Improves [invp_single_vehicle] output and adds a filter `invp_single_sections` to allow customization. Removes all deprecated methods and constants, all of which have replacements. Removes the [iframe] shortcode that was replaced by [invp_iframe].

= 13.8.2 = 
Changes the slider widget to show only 2 vehicles at a time on smaller devices. Bug fixes. Increases compatibility with inventory photos that were inserted by other services and do not have sequence numbers saved in post meta. Relies on the post_date for photo sequence when our meta key is not found.

= 13.8.1 = 
Adds a minimized version of the leaflet.js CSS file. This file is used by the Map widget. Bug fix when resizing flexslider images to avoid stretching images wider than their full size dimensions.

= 13.8.0 =
Adds template tags `invp_get_the_last_modified()` and `invp_get_raw_last_modified()`. Registers all scripts and styles on the `enqueue_block_editor_assets` hook so blocks can use them. Adds & implements iFrameResizer.js in the [invp_iframe] shortcode instead of relying on the themes like _dealer to provide the library and resize iframes. Bug fix when looking for an SVG file path in the Carfax Widget. Bug fix in the `invp_get_the_transmission_speeds()` template tag. The single `$post_id` argument is now optional, matching all other template tags.

= 13.7.1 = 
Changes tested up to version number 5.9.2. Increases compatibility with more themes out of the box, and specifically themes like GeneratePress that call and filter the_content multiple times when rendering a page. Fixes block category creation so all this plugin's blocks are nicley grouped. Fix in the Sort Vehicles By setting when a date field like last modified is chosen. Fixes plugin update nags in multisite installations to look more like core update nags.

= 13.6.0 =
Upgrades the [invp_archive] shortcode to support taxonomy and querystring filters and sorts. Adds a template tag invp_is_featured(). Changes the "was now discount" price display to fallback to showing the price when the MSRP is empty or the difference is not a positive discount. Previously, the setting would fallback to "Call for Price" even if a price value was available.

= 13.5.0 =
Renames the Google Map widget to Google Map (legacy). It still works for an unknown amount of time. Adds a Google Map widget. This new widget implements the v3 JavaScript API and requires an API key. Adds street address, street address line two, city, state, and zip fields to the Edit form when editing a term in the locations taxonomy. Allows users to specify and save the pieces of the address instead of the whole, multi-line address we store in the term description. This makes latitude and longitude decoding more accessible to users. When a location term is saved, these meta values are used to populate the term description.

= 13.3.0 =
Upgrades the add-on framework to simplify the building and integrating of add-on plugins. Adds a colors taxonomy to help users shop by base color.

= 13.2.1 =
Contains a bug fix in the Map widget to prevent widget titles from sitting underneath the map. Changes tested up to version number to 5.8.0.

= 13.1.1 = 
Now saves latitude and longitude coordinates with location term addresses when they are fetched from OpenStreetMap.org for use in the Map widget. Enables more than one Map widget to appear on the same page. Bug fix in the Hours widget.

= 13.1.0 =
Contains bug fixes and adds a new maps widget called Map that succeeds the Google Map widget. Google deprecated the API upon which the Google Map widget is built, and has indicated it will cease working any day. The new widget is built on leaflet.js and uses map tiles/imagery from MapBox.

= 13.0.0 =
Version 13 is smaller than version 12! Instead of shipping with two sliders, all slideshows are now powered by flexslider. This version drops a dependency on slick.js without losing functionality or changing the look and feel of sliders. Fixes a bug when displaying our placeholder "no photo photo" near vehicles that have zero photos.

= 12.3.0 =
Adds a setting to control whether vehicles use Trash when deleted. Adds a shortcode [invp_photo_slider]. Creates relationship with "For Sale" term in Availabilities taxonomy when vehicles are added with the editor. Fixes a bug that prevented boat fields from appearing in the vehicle attribute table.

= 12.2.7 =
First version that adds a "date entered" field to vehicles posts. Bug fix in the hours widget to no longer highlight Friday and Monday at the same time for some sets of hours. 

= 12.2.6 =
Changes the default button text for NextGear vehicle inspection reports from "See Digital Inspection Report" to "Mechanic's Report." Fixes bugs.

= 12.2.5 =
This release is the one we shipped to wordpress.org and the world as our open source launch.

= 11.8.2 =
The first version with DocBlock comments on all classes and methods.

= 11.7.0 =
This version makes the job of REST API clients that update vehicle data much easier by adding meta fields that overlap and updated all custom taxonomies.
