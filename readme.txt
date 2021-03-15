=== Inventory Presser ===
Contributors: salzano
Tags: car dealer, inventory management, vehicle, automobile, dealership, lot, motorcycle, rv
Requires at least: 5.0.0
Tested up to: 5.7.0
Stable tag: 12.2.7
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

= 12.2.7 =
* [Added] Adds a new vehicle field to track the date entered. The meta key is `inventory_presser_date_entered`.
* [Added] Adds a title attribute to the [iframe] shortcode we use to embed credit application forms on web pages for accessibility.
* [Changed] Changes the tested up to version number to 5.7.0
* [Changed] No longer hides the Year, Make, Model, and Body Style taxonomies from showing in editor user interfaces.
* [Fixed] Fixes a bug during meta field registration where both payment and payment frequency had a label of just "payment"
* [Fixed] Fixes a bug in the Hours widget where Monday would be highlighted as the current day during the last day of the prior week a dealership was open. 
* [Fixed] Updates a dependency https://github.com/indutny/elliptic


== Upgrade Notice ==

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
