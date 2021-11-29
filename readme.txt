=== Inventory Presser ===
Contributors: salzano
Tags: car dealer, inventory management, vehicle, automobile, dealership, lot, motorcycle, rv
Requires at least: 5.0.0
Tested up to: 5.8.2
Stable tag: 13.5.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Turn WordPress into a dealership website. Provides vehicle listing templates and a custom post type for automobiles or powersports.

== Description ==

This plugin tranforms WordPress into a powerful dealer website platform that makes displaying a vehicle inventory easy.

* Supports automobiles, motorcycles, ATVs, RVs, and boats
* [Manage multiple lot locations](https://inventorypresser.com/docs/vehicle-post-type/locations-taxonomy/) and separate phone numbers & hours for each
* [VIN-decoding add-on](https://inventorypresser.com/products/plugins/add-vins-to-vehicle-urls/) available
* Categorizes inventory by type, body style, fuel, transmission, drive type, availability, new or used, location, and more
* Includes [more than 10 widgets](https://inventorypresser.com/docs/feature-list/widgets/) including vehicle sliders and inventory grids

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
Yes. We use the [WordPress REST API](https://developer.wordpress.org/rest-api/) to deliver inventory updates. [Read how we do it](https://inventorypresser.com/docs/using-the-rest-api-to-update-vehicle-listings-in-wordpress/). Vehicles in Inventory Presser are stored in a custom post type using meta fields, taxonomy terms, and photo attachments, and many CSV/spreadsheet importer plugins work with custom post types. If imports sound challenging, Friday Systems (that's us) feeds hundreds of sites running this plugin. It may be easier to let Friday Systems handle the last leg for a monthly fee.

= Will Inventory Presser work with my theme? =
Yes. Check out our [Theme Compatibility Guide](https://inventorypresser.com/docs/theme-compatibility-with-vehicle-listings/). If your theme does not include templates to display listing pages and vehicle detail pages, the plugin will provide the markup for those pages automatically. There are shortcodes like `[invp_archive]` and `[invp_single_vehicle]` as a last resort. A number of [Template Tags](https://inventorypresser.com/docs/template-tags/) make designing custom templates easy.


== Screenshots ==

1. This is a list of vehicles in the administrator dashboard that the user sees when she clicks the Vehicles or All Vehicles menu items. Each vehicle is listed by year, make, model, trim, stock number, color, odometer, price, and photo count. If the vehicle has photos, a thumbnail is also shown. This screen shot was taken on version 3.1.0.
2. This is the edit screen that adds or edits a single vehicle. The post content box is demoted to handle only the sentence-form vehicle description, and the entire edit screen is enhanced to support vehicle attributes. This screen shot was taken on version 3.1.0.
3. This screenshot shows a vehicle post in the WordPress block editor. An editor sidebar for the Inventory Presser plugin is expanded and contains vehicle attribute fields. An arrow points to a button with a key on it that reveals the editor sidebar.
4. This screenshot shows a vehicle archive on the Twenty Twenty theme before any customization.


== Changelog ==

= 13.5.0 =
* [Added] Adds street address, street address line two, city, state, and zip fields to the Edit form when editing a term in the locations taxonomy. Allows users to specify and save the pieces of the address instead of the whole, multi-line address we store in the term description. This makes latitude and longitude decoding more accessible to users. When a location term is saved, these meta values are used to populate the term description.
* [Added] Adds a Google Map widget. This new widget implements the v3 JavaScript API and requires an API key. 
* [Changed] Makes the term description box on the edit term screen readonly when editing a term in the locations taxonomy.
* [Changed] Renames the Google Map widget to Google Map (legacy). It still works for an unknown amount of time.
* [Changed] Updates node JS packages
* [Removed] Removes a weekly cron job that tabulated term relationship counts. This was a legacy bug fix that is no longer needed.

= 13.4.1 =
* [Added] Adds photo sequence numbers like "(Photo 2 of 44)" to post titles in the Media Library
* [Fixed] Finds a few places to use the template tag invp_get_the_photo_number() instead of a unique get_children() call.
* [Fixed] Fixes a typo that prevented highway MPG from displaying in the EPA Fuel Economy widget
* [Fixed] Assigns photos uploaded in the dashboard an accurate photo sequence number instead of zero
* [Fixed] Sets the first photo uploaded in the dashboard as the vehicle's featured image
* [Fixed] Fixes the add-on license activator class to properly examine API responses and activate licenses

= 13.4.0 =
* [Added] Adds the ability for add-ons to store their license key in a key with a name that is not `license_key`. A few add-ons already use `_license_key`.
* [Added] Adds a filter `invp_vehicle_attribute_table_items` that allows the vehicle attribute table items to be manipulated just before they are parsed into HTML.
* [Added] Upgrades the taxonomy overlapper class to allow the updating of the transmission speeds meta key when term relationships in the transmission taxonomy are changed.
* [Added] Adds car ID, dealer ID, leads ID, Edmunds Style ID, title status, and Next Gear Inspection URL to the editor sidebar.
* [Changed] Changes the taxonomy overlapper to make sure the meta value matches a term name with which a vehicle has a relationship when relationships are deleted. For example, if a vehicle is erroneously assigned both "Convertible" and "Sedan" body styles, this change makes sure Sedan is saved in the meta field when Convertible is removed.
* [Changed] Re-implements the archive "View Details" button as a hook on `invp_archive_buttons` rather than expecting all templates to implement it. Adds a filter `invp_css_classes_view_details_button` on the CSS classes because that is a feature we need on day one for the two different themes we have on production sites.
* [Fixed] Fixes bugs in the `invp_get_the_odometer()` template tag and the vehicle attribute table to avoid outputting odometer and engine attributes that do not actually contain values.
* [Removed] Removes fields from the editor sidebar that are managed via taxonomies and the taxonomy overlapper: year, make, model, and body style.

= 13.3.1 =
* [Fixed] Fixes a bug in the taxonomy overlapper that was causing sold vehicles to show as available. Meta keys were not always being updated when the overlapping taxonomy term relationships were changed.
* [Changed] Changes the add-on updater to not show a plugin update nag unless a download link is available.

= 13.3.0 =
* [Added] Adds a colors taxonomy to help users shop by vehicle color. This taxonomy is designed to hold the base color. That means the value might be "Red" rather than "Ruby Mist Metallic".
* [Added] Adds new classes Inventory_Presser_Addon and Inventory_Presser_Addon_Updater to help add-on plugins store license keys and obtain updates from inventorypresser.com.
* [Added] Adds two API methods INVP::option_group() and INVP::option_page() to help add-ons avoid hard-coding string slugs for the page and group where this plugin's settings are manipulated.
* [Added] Adds an action hook `invp_loaded` that runs on the `plugins_loaded` hook but after Inventory Presser has finished loading. This hook is designed as an add-on entry point.

= 13.2.2 = 
* [Fixed] Bug fix when registering a term meta key `dealer_id` in locations taxonomy. Sanitize callback of `intval()` prevent updates to the terms in the locations taxonomy. Changed to use `sanitize_text_field()` instead.

= 13.2.1 =
* [Changed] Changes the tested up to version number to 5.8.0.
* [Fixed] Fixes a bug in the Map widget that was hiding the widget title underneath the map. 


== Upgrade Notice ==

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
