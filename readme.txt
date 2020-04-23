=== Inventory Presser ===
Contributors: salzano
Tags: car dealer, inventory management, vehicle, automobile, dealership, motorcycle
Requires at least: 5.0.0
Tested up to: 5.4.0
Stable tag: 11.2.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Turn WordPress into a dealership website. Import or directly manage an automobile or powersports dealer inventory.

== Description ==

This plugin tranforms WordPress into a powerful dealer website platform that makes managing a vehicle inventory easy.

* Supports automobiles, motorcycles, ATVs, RVs, and boats
* Accepts bulk inventory feeds and allows manual-entry
* VIN-decoding add-on available
* Categorizes inventory by type, body style, fuel, transmission, drive type, availability, and new or used
* Includes many widgets including vehicle sliders and inventory grids

Built the right way and developer-friendly

* Creates a custom post type that represents a vehicle
* The Add/Edit post screen is enhanced with vehicle attributes as postmeta fields
* The post content editor manages only the long form vehicle description
* Adds columns to the posts list for vehicles for stock number, color, odometer, price, photo count, and thumbnail
* Implements custom taxonomies to group vehicles by type, fuel, transmission, drive type, availability, new or used, and body style
* Puts media counts next to the Add Media button while editing vehicles
* Hooks in all the right places
* Vehicle object and template tags make front-end development easy
* Many add-ons both free and paid available at https://inventorypresser.com/


== Installation ==

1. Upload the `inventory-presser` directory to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Read this plugin's Frequently Asked Question called "Does Inventory Presser work with my theme?" to learn how vehicles can best be displayed


== Frequently Asked Questions ==

= How much does this plugin cost? =
This plugin is free. Free and paid add-ons are available at https://inventorypresser.com/.

= Does it decode VINs? =
Not for free. We have a VIN decoder add-on available at https://inventorypresser.com/. If you know of a free VIN decoder that allows automated, public queries, please show us.

= Can I import my inventory into this system with a bulk feed? =
Yes. The best way to do this to use the [WordPress REST API](https://developer.wordpress.org/rest-api/). Vehicles are stored in a custom post type, so other options are certainly available. Our first importer was based on a fork of the XML-based [WordPress Importer](https://wordpress.org/plugins/wordpress-importer/). Many CSV/spreadsheet importer plugins work with custom post types as well. If this sounds challenging, Friday Systems feeds many site running Inventory Presser, and it may be easier to feed there and let Friday Systems handle the last leg.

= Does Inventory Presser work with my theme? =
This plugin requires a few page templates to display listing pages and vehicle detail pages that contain the many attributes that describe a vehicle. To make creating these templates easy, we've released [Lift Kit](https://github.com/fridaysystems/lift-kit), a free set of files that can be added to any WordPress theme or child theme to quickly achieve compatibility. Themes designed for this plugin can also be seen and bought at https://inventorypresser.com/.


== Screenshots ==

1. This is a list of vehicles in the administrator dashboard that the user sees when she clicks the Vehicles or All Vehicles menu items. Each vehicle is listed by year, make, model, trim, stock number, color, odometer, price, and photo count. If the vehicle has photos, a thumbnail is also shown. This screen shot was taken on version 3.1.0.
2. This is the edit screen that adds or edits a single vehicle. The post content box is demoted to handle only the sentence-form vehicle description, and the entire edit screen is enhanced to support vehicle attributes. This screen shot was taken on version 3.1.0.


== Changelog ==

= 11.2.1 =
* [Fixed] Fixes a bug to account for when connections to carfax.com fail and icons cannot be retrieved. Now falls back to the icons that are bundled with this plugin.
* [Fixed] Fixes a bug that was sending 'auto' into wp_constrain_dimensions() instead of a numeric pixel value of the height of the image.
* [Fixed] Fixes a bug that would cause the Hours widget to only be partially output if there were no sets of hours to display. Now, the widget will vanish entirely.


== Upgrade Notice ==

= 11.2.1 =
Version 11.2.1 is the first polished release after the elimination of serialized meta fields. The plugin is now much more secure when it uses vehicle data written via the REST API because allowing remote writes of serialized data risks PHP Object Injection.

= 8.6.0 =
This is the minimum version required for compatibility with the block editor launched in WordPress 5.0 and a new add-on called [Inventory Presser Elementor Add-on](https://inventorypresser.com/products/plugins/elementor-add-on/). This plugin adds vehicle fields to Elementor's list of Dynamic Tags.

= 4.2.0 =
This version is the first that includes REST API endpoints that allow efficient vehicle updates.

= 3.1.0 =
This is the required minimum version for compatibility with our [VIN decoder add-on](https://inventorypresser.com/products/plugins/vin-decoder/).

= 2.3.1 =
Dashicons were being included by a companion widget until said widget was rewritten. They are used by this plugin but were not enqueued until this version.

= 2.0.0 =
We are still in a private beta, but this version is the new stable tag.

= 1 4.0 =
The new location taxonomy links a vehicle to the address where a vehicle is located, making multi-lot management simple

= 1.2.0 =
This is the first version of the plugin that is part of a launched website, so there is no reason to not have at least this version.

= 0.5 =
Without version 0.5, you will not have this readme.txt file


== History ==

The plan for development in late 2015 was to build a platform on which we could build websites for our independent car dealership customers based on these principles:

* Feed first. The inventory source is primarily a bulk feed and not manual entry.
* Leverage the platform; do not reinvent the wheel. WordPress allows custom post types and taxonomies. WordPress has already designed edit screens for content objects.
* Stay small. This plugin will not contain functionality that is not the minimum set of features that will enable users to comfortably manage inventory. Other features will live other plugins.

The first site using the plugin launched in March 2016.

By March of 2018, the plugin was powering more than 100 websites. This milestone coincided with our launch of version 4.2.0 and the introduction of REST API endpoints. The REST API increased the efficiency of inventory updates by an order of magnitude over a delimited file or XML import, and our migration to updating all sites via REST was complete before June 1st, 2018.

A complete development news feed is available on our website at https://inventorypresser.com/news/
