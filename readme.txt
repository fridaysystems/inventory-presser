=== Inventory Presser ===
Contributors: salzano
Tags: car dealer, inventory management, vehicle, automobile, dealership, lot, motorcycle, rv
Requires at least: 5.0.0
Tested up to: 5.6.0
Stable tag: 12.2.5
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Turn WordPress into a dealership website. Provides vehicle listing templates and a custom post type for automobiles or powersports.

== Description ==

This plugin tranforms WordPress into a powerful dealer website platform that makes displaying a vehicle inventory easy.

* Supports automobiles, motorcycles, ATVs, RVs, and boats
* Manage multiple lot locations and separate phone numbers & hours for each
* VIN-decoding add-on available
* Custom post type `inventory_vehicle` makes importing data easy
* Categorizes inventory by type, body style, fuel, transmission, drive type, availability, and new or used
* Includes many widgets including vehicle sliders and inventory grids

Built the right way and developer-friendly

* Creates a custom post type & 11 taxonomies to store and group vehicles
* Editor sidebar integrates custom fields into the block editor
* Adds columns to the posts list for vehicles for stock number, color, odometer, price, photo count, and thumbnail
* Implements custom taxonomies to group vehicles by year, make, model, type, fuel, transmission, drive type, availability, new or used, and body style
* Hooks in all the right places & four powerful [shortcodes](https://inventorypresser.com/docs/shortcodes/)
* [Template tags](https://inventorypresser.com/docs/template-tags/) make front-end development easy
* [Runs on any theme](https://inventorypresser.com/docs/theme-compatibility-with-vehicle-listings/) & provides shortcodes for the stubborn ones.
* Full feature list & more documentation https://inventorypresser.com/


== Installation ==

1. Upload the `inventory-presser` directory to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Read this plugin's Frequently Asked Question called "Will Inventory Presser work with my theme?" to learn how vehicles can best be displayed


== Frequently Asked Questions ==

= How much does this plugin cost? =
This plugin is free and open source. Free and paid add-ons & services are available at https://inventorypresser.com/.

= Does it decode VINs? =
Not for free. We have a VIN decoder add-on available at https://inventorypresser.com/. If you know of a free VIN decoder that allows many automated queries, please show us.

= Can I import my inventory into this system? =
Yes. We use the [WordPress REST API](https://developer.wordpress.org/rest-api/) to deliver inventory updates. [Read how we do it](https://inventorypresser.com/docs/using-the-rest-api-to-update-vehicle-listings-in-wordpress/). Vehicles in Inventory Presser are stored in a custom post type using meta fields, taxonomy terms, and photo attachments, and many CSV/spreadsheet importer plugins work with custom post types. If imports sound challenging, Friday Systems (that's us) feeds many sites running this plugin, and it may be easier to feed there and let Friday Systems handle the last leg.

= Will Inventory Presser work with my theme? =
Yes. Check out our [Theme Compatibility Guide](https://inventorypresser.com/docs/theme-compatibility-with-vehicle-listings/). If your theme does not include templates to display listing pages and vehicle detail pages, the plugin will provide the markup for those pages automatically. There are shortcodes like `[invp_archive]` and `[invp_single_vehicle]` as a last resort. A number of [Template Tags](https://inventorypresser.com/docs/template-tags/) make designing custom templates easy.


== Screenshots ==

1. This is a list of vehicles in the administrator dashboard that the user sees when she clicks the Vehicles or All Vehicles menu items. Each vehicle is listed by year, make, model, trim, stock number, color, odometer, price, and photo count. If the vehicle has photos, a thumbnail is also shown. This screen shot was taken on version 3.1.0.
2. This is the edit screen that adds or edits a single vehicle. The post content box is demoted to handle only the sentence-form vehicle description, and the entire edit screen is enhanced to support vehicle attributes. This screen shot was taken on version 3.1.0.
3. This screenshot shows a vehicle post in the WordPress block editor. An editor sidebar for the Inventory Presser plugin is expanded and contains vehicle attribute fields. An arrow points to a button with a key on it that reveals the editor sidebar.
4. This screenshot shows a vehicle archive on the Twenty Twenty theme before any customization.


== Changelog ==

= 12.2.5 =
* [Added] Adds a screenshots that show a vehicle being edited in the block editor and vehicle archive output on the Twenty Twenty theme.
* [Changed] Moves image files out of /assets and into /images. The /assets folder must contain wordpress.org icons and banners only.
* [Changed] Updates the npm development dependency @wordpress/scripts to version 12.6.1.

= 12.2.4 =
* [Added] Adds a meta field to vehicle posts to hold URLs for NextGear Digital Vehicle Inspections `inventory_presser_nextgear_inspection_url`
* [Added] Adds a button near vehicles in archives and single pages to "See Digital Inspection Report" when vehicles have a value in the new `inventory_presser_nextgear_inspection_url` meta field.
* [Fixed] Fixes a bug in CSS rules for the output of our [invp_single_vehicle] shortcode that was hiding vehicle buttons output during the `invp_single_buttons` action hook like Carfax.
* [Fixed] Fixes a bug in the template tag `invp_get_the_price()` where our filter on the zero string, `invp_zero_price_string`, would never be applied. The filter allows other developers to change what is shown when a vehicle has zero for a price.

= 12.2.3 =
* [Added] Adds an attribute  `show_titles` to both the [invp_archive] and [invp_archive_vehicle] shortcodes. The default value is true for the former and false for the latter.
* [Added] Adds a link to readme.txt about how we update vehicles running this plugin using the WordPress REST API.
* [Changed] Adds post titles to the [invp_archive] shortcode output, while preventing double titles on post archives in themes that do not contain templates for our custom post type.
* [Changed] Adds blocks created by this plugin to the block editor all the time instead of only when users are editing vehicle posts.
* [Fixed] The [invp_single_vehicle] shortcode now calls the action hook `invp_single_buttons`.
* [Removed] Removes a call to invp_get_the_carfax_icon_html() inside the [invp_single_vehicle] shortcode.


== Upgrade Notice ==

= 12.2.1 =
This release is the one we shipped to wordpress.org and the world as our open source launch.

= 11.8.2 =
The first version with DocBlock comments on all classes and methods.

= 11.7.0 =
This version makes the job of REST API clients that update vehicle data much easier by adding meta fields that overlap and updated all custom taxonomies.


== History ==

= Fall 2015 =
Corey Salzano starts writing this plugin and an inventory importer companion. John Norton begins creating a theme and widgets for a front-end.

= March 2016 =
The first site using the plugin launches.

= March 2018 =
Powering more than 100 websites with version 4.2.0 when REST API endpoints were introduced.

= December 2020 =
Version 12.2.1 is submitted to wordpress.org for inclusion in the Plugin Repository. Github repo is flipped from private to public.