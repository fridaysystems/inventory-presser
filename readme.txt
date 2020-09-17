=== Inventory Presser ===
Contributors: salzano
Tags: car dealer, inventory management, vehicle, automobile, dealership, motorcycle
Requires at least: 5.0.0
Tested up to: 5.5.1
Stable tag: 12.0.0
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
This plugin is free and open source. Free and paid add-ons and services are available at https://inventorypresser.com/.

= Does it decode VINs? =
Not for free. We have a VIN decoder add-on available at https://inventorypresser.com/. If you know of a free VIN decoder that allows many automated queries, please show us.

= Can I import my inventory into this system with a bulk feed? =
Yes. The way we do it is with the [WordPress REST API](https://developer.wordpress.org/rest-api/). Vehicles in Inventory Presser are stored in a custom post type using meta fields, taxonomy terms, and photo attachments, and many CSV/spreadsheet importer plugins work with custom post types. Our first importer was based on a fork of the XML-based [WordPress Importer](https://wordpress.org/plugins/wordpress-importer/). If imports sound challenging, Friday Systems (that's us) feeds many sites running this plugin, and it may be easier to feed there and let Friday Systems handle the last leg.

= Will Inventory Presser work with my theme? =
Yes. If your theme does not include templates to display listing pages and vehicle detail pages, the plugin will provide the markup for those pages automatically. You may certainly design custom templates, too. To make creating templates easy, we released [Lift Kit](https://github.com/fridaysystems/lift-kit), a free set of files that can be added to any WordPress theme or child theme to quickly achieve compatibility.


== Screenshots ==

1. This is a list of vehicles in the administrator dashboard that the user sees when she clicks the Vehicles or All Vehicles menu items. Each vehicle is listed by year, make, model, trim, stock number, color, odometer, price, and photo count. If the vehicle has photos, a thumbnail is also shown. This screen shot was taken on version 3.1.0.
2. This is the edit screen that adds or edits a single vehicle. The post content box is demoted to handle only the sentence-form vehicle description, and the entire edit screen is enhanced to support vehicle attributes. This screen shot was taken on version 3.1.0.


== Changelog ==

= 12.0.0 =
* [Fixed] Fixes a feature that placed a Settings link near Deactivate on the plugins page
* [Changed] Adjusts all `add_shortcode()` calls to occur on the `init` hook rather than when the plugin is loaded.
* [Removed] Removes features that detected the Yoast SEO plugin and added sitemaps URLs to robots.txt and hid Yoast features from the list of vehicles in the dashboard.
* [Removed] No longer registers a meta key `inventory_presser_options` to hold a CSV string of vehicle options. The multi-valued meta key `inventory_presser_options_array` is now the only place to store and retrieve options.

== Upgrade Notice ==

= 12.0.0 = 
This release is the one we shipped to wordpress.org for publication.

= 11.8.2 =
The first version with DocBlock comments on all classes and methods.

= 11.7.0 =
This version makes the job of REST API clients that update vehicle data much easier by adding meta fields that overlap and updated all custom taxonomies.

= 11.6.0 =
This is the first version that provides a multi-valued meta field to hold vehicle options called `inventory_presser_options_array`.

= 11.3.1 =
This version is just like 11.3.0, but it works on PHP versions less than 7.0.0 without producing warnings.

= 11.3.0 =
Version 11.3.0 is a stable & polished release after the elimination of serialized meta fields.

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

Fall 2015: Corey Salzano starts writing this plugin and an inventory importer companion. John Norton begins creating a theme and widgets for a front-end.
March 2016: The first site using the plugin launches.
March 2018: Powering more than 100 websites with version 4.2.0 when REST API endpoints were introduced.