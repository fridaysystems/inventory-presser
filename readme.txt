=== Inventory Presser ===
Contributors: salzano
Tags: car dealer, inventory management, vehicle, automobile, dealership, motorcycle
Requires at least: 3.0.1
Tested up to: 4.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

An inventory management plugin for Car Dealers. Import or create an automobile or powersports dealership inventory.

== Description ==

This plugin modifies the WordPress administrator dashboard to make managing a vehicle inventory easy and convenient
for users.

* Creates a custom post type that represents a vehicle
* The Add/Edit post screen is redesigned to place emphasis on the individual vehicle data, and WordPress's typical
post content is demoted to handling only the long form vehicle description
* Modifies the all posts screen for the custom post type to show stock number, color, odometer, price and photo count columns
* Puts media counts next to the Add Media button while editing vehicles
* Creates custom taxonomies to group vehicles by type, fuel, transmission, drive type, availability, and condition
* Affects the way WordPress importers function to work optimally with WordPress Importer For CRON, also written by salzano
* Creates an options page to allow the bulk deletion of vehicles and plugin data
* Defines a vehicle object (includes/class-inventory-presser-vehicle.php) that makes accessing all of the post meta fields consistent and easy for theme development
* Creates a shortcode [vehicle_field] that simplifies getting one piece of vehicle data

== Installation ==

This section describes how to install the plugin and get it working.

e.g.

1. Upload the `inventory-presser` directory to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= How much does this plugin cost? =

Dollars. TODO

= Is this plugin everything I need to run a car dealer website? =

No, but it's close.

= Can I import my inventory into this system with a bulk feed overnight? =

Yes. The best way to do this is to use a plugin I have also written called WordPress Importer For CRON.

== Screenshots ==

1. This is a list of vehicles in the administrator dashboard that the user sees when she clicks the
Vehicles or All Vehicles menu items. This screen shot was taken on version 0.4.
2. This is the edit screen that adds or edits a single vehicle. This screen shot was taken on version 0.4,
and at this time there is no VIN decoder or support for vehicle options.

== Changelog ==

= 1.2.0 =
* Added a widget to allow users to reorder vehicles by post meta keys. This enables sorting by year, make, model,
price, odometer, etc. This widget was developed as a separate plugin until being rolled in with this update.
* Added a setting to specify a default sort order and direction for vehicle search result pages

= 1.1.0 =
* Added CARFAX support to the vehicle object and added three CARFAX icons to the assets directory

= 1.0.0 =
* Changed the post meta key `piped_options` to `option` with the new intent
to no longer be a unique key.
* Changed the place where the options list lives again after realizing a non-unique post meta key would cause
more problems than it solved. The vehicle options list now lives in `option_array` as a
serialized array.
* John Norton joins me and starts building the first theme to use this plugin

= 0.2.0 =
* Moved all code that modifies imports into its own object and file, `includes/class-modify-imports.php`
* Deeper integration with WXR importers so inventory does not have to be deleted before a new import is run. An
option called "When importing a feed, delete units not contained in the new feed" is born. Previously, an option
allowed users to delete all inventory before a new feed is run. This is a much more nuanced solution that will
not vacate the site of inventory during an import, and also reduce the amount churn on post IDs in the database.
* A post meta key called `_inventory_presser_photo_file_name_base` is introduced to help this plugin mate attachments to
their parents after an import. A more conventional WordPress import from a backup would identify post IDs. It is
possible to not specify post IDs in a WXR XML file, and let the database assign IDs as items are imported. We
love this approach, but it means that the import file cannot provide a parent ID for media attachments. This meta
key is used to say "Any attachment that does not have a parent post ID and whose filename starts with this string
is a child of this post."

= 0.1.0 =
* Implemented semantic versioning
* An object Inventory_Presser_Vehicle has been defined in includes/class-inventory-presser-vehicle.php to aid theme development
* Custom taxonomies enhanced with `labels` values
* Added filter hook `inventory_presser_post_type` so the post type can be renamed
* Added filter hook `inventory_presser_post_type_args` so the post type can be manipulated before the `register_post_type` call
* Added filter hook `inventory_presser_default_options` to manipulate the default option values
* Added filter hook `inventory_presser_taxonomy_data` to filter the built-in taxonomy data

= 0.5 =
* Created a shortcode [vehicle_field] to make it easy for theme developers to get custom fields out of our custom post type
* Enhanced imports to mate photo attachments to parent posts, establishing a relationship between this plugin and WXR importers,
especially `WordPress Importer for CRON` that I (salzano) have also written. Also sets first photo as featured image. This whole
routine operates according to the photo naming convention we employ at Friday Systems.
* Options page created to hold data deletion buttons
* `Delete all Vehicles` button implemented on the Options page
* `Delete all Plugin Data & Deactivate` button implemented on the Options page
* Attachment count added near Add Media button text
* This readme file was created

= 0.4 =
* A pile of code from 2012 is reread and rewritten in September and October of 2015
* Custom post type & taxonomies completely defined and Add/Edit post screen designed
* Edit.php is customized for our custom post type to list stock number, color, odometer, price & photo count

== Upgrade Notice ==

= 0.5 =
Without version 0.5, you will not have this readme.txt file

== History ==

The plan for development in late 2015 was to build a platform on which we could build websites for our independent
car dealership customers based on these principles:

* Feed first. The inventory source is primarily a bulk feed and not manual entry.
* Leverage the platform; do not reinvent the wheel. WordPress allows custom post types and taxonomies.
WordPress has already designed edit screens for content objects. WordPress already has an importer.
* Stay small. This plugin will not contain functionality that is not the minimum set of features
that will enable users to comfortably manage inventory. Other features will live other plugins.