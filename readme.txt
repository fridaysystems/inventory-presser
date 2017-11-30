=== Inventory Presser ===
Contributors: salzano
Tags: car dealer, inventory management, vehicle, automobile, dealership, motorcycle
Requires at least: 3.0.1
Tested up to: 4.9.1
Stable tag: 3.6.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

An inventory management plugin for Car Dealers. Import or create an automobile or powersports dealership inventory.

== Description ==

This plugin modifies the WordPress administrator dashboard to make managing a vehicle inventory easy and convenient for users.

* Creates a custom post type that represents a vehicle
* The Add/Edit post screen is redesigned to place emphasis on the individual vehicle data, and WordPress's typical post content is demoted to handling only the long form vehicle description
* Modifies the all posts screen for the custom post type to show stock number, color, odometer, price and photo count columns
* Puts media counts next to the Add Media button while editing vehicles
* Creates custom taxonomies to group vehicles by type, fuel, transmission, drive type, availability, and condition
* Defines a vehicle object (includes/class-inventory-presser-vehicle.php) that makes accessing all of the post meta fields consistent and easy for theme development
* Supports automobiles, motorcycles, ATVs, RVs, and boats

== Installation ==

This section describes how to install the plugin and get it working.

e.g.

1. Upload the `inventory-presser` directory to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= How much does this plugin cost? =

This plugin is free, but there are paid upgrades available.

= Can I import my inventory into this system with a bulk feed? =

Yes. The best way to do this is to use our plugin [Friday Systems Vehicle Importer](https://inventorypresser.com/products/plugins/friday-systems-vehicle-importer/).

== Screenshots ==

1. This is a list of vehicles in the administrator dashboard that the user sees when she clicks the Vehicles or All Vehicles menu items. Each vehicle is listed by year, make, model, trim, stock number, color, odometer, price, and photo count. If the vehicle has photos, a thumbnail is also shown. This screen shot was taken on version 3.1.0.
2. This is the edit screen that adds or edits a single vehicle. The post content box is demoted to handle only the sentence-form vehicle description, and the entire edit screen is enhanced to support vehicle attributes. This screen shot was taken on version 3.1.0.

== Changelog ==

= 3.6.0 =
* [Changed] Do not show options and prices arrays in the list of default vehicle sort keys on the options page, these are structured data fields
* [Removed] Removed an option that belongs in the _dealer theme to affect the way users are redirected after submitting a Contact Form 7 form
* [Removed] Removed an option that belongs in the _dealer theme to customize a particular layout style
* [Removed] Removed an option that belongs in the _dealer theme to append a page's content to each vehicle details page

= 3.5.0 =
* [Added] Added "Wheelchair Access" to the list of default vehicle options
* [Added] Include Carfax buttons in this plugin instead of relying on themes to provide them, similar to how we handle AutoCheck
* [Changed] Updated the Carfax buttons to the newest style provided by Carfax in November 2017
* [Changed] Start using new Instant Carfax Report URLs provided by Carfax in November 2017
* [Changed] Change the Carfax image widget that shows the plain SHOW ME THE CARFAX image to use one of the new buttons instead of a near duplicate image
* [Changed] Add rel="noopener noreferrer" to links to Carfax and AutoCheck to protect against target="_blank" vulnerability
* [Changed] Do not show a Carfax button if there is no report available when the button is clicked
* [Changed] Stop using "../" in URLs to images, wrap __FILE__ in dirname() instead
* [Fixed] Bug fix in the address widget where we were assuming an address would always exist, new check for null prevents warnings
* [Fixed] Prevent the "Sort vehicles by" feature from overriding other meta queries on vehicle archive pages

= 3.4.0 =
* [Added] The vehicle grid widget now includes a setting to show only featured vehicles
* [Added] Add a sitemap URL and crawl-delay to robots.txt on multisite installs when Yoast SEO XML sitemaps are enabled
* [Added] Do not allow our vehicle taxonomies to be included in Yoast SEO XML sitemaps

= 3.3.1 =
* [Fixed] Do not assume we have sets of hours for a location
* [Fixed] Also update vehicle price in prices array when the price is changed via the dashboard
* [Fixed] Make sure a location term query does not return an error before trying to output that term's details
* [Changed] Create the body styles taxonomy before the location taxonomy so they load in that order, purely for appearance and usability

= 3.3.0 =
* [Changed] AutoCheck report fetching is rewritten to use a newer end-point at Friday Systems

= 3.2.0 =
* [Added] When shoppers navigate to vehicles that are no longer on the site, redirect them to an archive page of all vehicles of the same make
* [Added] Make it easy for themes to include Schema.org structured data for vehicles via a new method on our vehicle class
* [Added] Include custom taxonomy names and slugs in searches, so searches for "suv" and "diesel" are now possible
* [Added] A mower vehicle type is now populated in the Type taxonomy
* [Changed] Now prioritizes featured vehicles when filling the slider
* [Changed] Tested up to date to 4.8.1
* [Fixed] A vehicle class member was typoed as drivetrain and changed to drivetype
* [Removed] An unused theme-specific setting that allowed the footer link text to be overriden has been removed from the options page

= 3.1.0 =
* Adds a filter hook to the VIN input box on the edit vehicle screen in the dashboard. This permits our newest add-on plugin, [a VIN decoder](https://inventorypresser.com/products/plugins/vin-decoder/), to place a "Decode VIN" button adjacent to the VIN text box.
* Adds an empty drop down option to the model year selector on the edit vehicle screen in the dashboard. Previously, new vehicle entries would save the current year if the user makes no selection.
* Screenshots are updated

= 3.0.0 =
* Consolidates all settings under the Vehicles custom post type menu submenu Options. This saves the default sort field and order in a different location, so those values will need to be migrated when upgrading.

= 2.3.5 =
* Use save_post_inventory_vehicle instead of save_post and then a check to make sure we are working on our post type
* Bug fix for the parameters passed into the save_post/save_post_{post-type} action handler
* Skip the extra stuff that happens when vehicles are saved if the vehicle was just moved to the trash

= 2.3.4 =
* Bug fix to not modify ORDER BY clauses on term queries for model years and cylinder counts when no order is specified

= 2.3.3 =
* Bug fix in Order By Post Meta widget. A bad string concatenation prevented the output of list items and links.
* Bug fix while editing vehicles in the Dashboard. The last modified date was not being saved with the correct timezone offset.

= 2.3.2 =
* Bug fix when queuing scripts for our shortcodes. Previously, we were using a $post variable before a global $post; call.

= 2.3.1 =
* Include dashicons on the front-end. Previously, this was handled by a widget that we were also using on every site. That widget has been rewritten and this bug was revealed.

= 2.3.0 =
* Delete all inventory feature now ignores post status and deletes any and all
* Delete all inventory method now runs an action hook after the deletions occur, inventory_presser_delete_all_inventory
* Only insert the default terms into our custom taxonomies during plugin activation instead of on every page load when the taxonomies are created

= 2.2.0 =
* New features for boat dealers. We have added a propulsion type taxonomy and vehicle fields for beam, length, and hull material. The edit screen will change to accomodate this data when the vehicle type boat is chosen or loaded.
* Adds a filter to the "Dealer Hours" widget so that our new plugin, Hours Today Shortcode, can add a sentence like "Open until 6:00 pm today" near the table of hours.

= 2.1.0 =
* Now supports child themes
* A taxonomy for body style is added
* Now supports boats and buses
* Vehicle list in the dashboard now has inline thumbnails
* Now supports Cargurus badges, AutoCheck report retrieval, Edmunds.com style IDs and YouTube video IDs
* Delete all data button on options page now deletes all terms in the taxonomies this plugin creates and widget options

= 2.0.0 =
* Stop manipulating imports. All import related code has been moved to another plugin where it belongs. Lots of code has been deleted.

= 1.5.0 =
* Stop pruning the pending attachments folder during every import via the import_end hook.
* Stop copying attachment payloads during imports unless the file date is newer than the payload that was previously imported.
* Stop uniting orphan attachments with their parents during every import via the import_end hook. Use a new hook to find parent post IDs as the attachments are processed.
* A new method set_thumbnails() hooks on import_end to set thumbnails for vehicles with attachments.

= 1.4.2 =
* Obsoleted the postmeta key _inventory_presser_file_name_base now that we live in a world where our photo file name bases are VINs
* Stop relying on post title and guid to decide if a vehicle exists. Always use the VIN.

= 1.4.1 =
* Fixed a bug where function_exists() was not used to always make sure get_current_screen() is defined before using it

= 1.4.0 =
* Location taxonomy added to manage single or multi-lot inventories' addresses, phone numbers and hours
* Update term meta during imports when terms aready exist
* Force term recounts when a post is inserted or updated during an import

= 1.3.0 =
* Add custom fields to searches, so searches for a VIN or the color blue work
* Introduce VIN-based URLs that redirect to proper permalinks so that it's easy to link to vehicles

= 1.2.1 =
* Preserves images when deleting vehicles that no longer appear in import files by dissociating them with their parents. This was a weird bug where a vehicle can come through the import with the same VIN but a different unique post slug. Adding this and deleting the old vehicle will lose the photos until the next import runs because they were in the database but deleted at the end of the import. Dissociating the photos allows the new vehicle post to be united with its photos.
* Bug fixes to allow attachments to live in sub-folders of the uploads folder, or now saving vehicle photos in folders per vehicle instead of polluting the uploads folder.
* Attempt to delete the folder that a pending attachment lives in when it is deleted. This will clean up those per vehicle folders in the pending directory.

= 1.2.0 =
* Now implements custom rewrite rules for filtering our post type. These rules lived in a widget until now.
* Added a widget to allow users to reorder vehicles by post meta keys. This enables sorting by year, make, model, price, odometer, etc. This widget was developed as a separate plugin until being rolled in with this update.
* Added a setting to specify a default sort order and direction for vehicle search result pages
* Added checkboxes to the add/edit vehicle page for adding and removing optional equipment
* Converted the [Delete all Inventory] button on the settings page to use a looping AJAX call that will restart deletions during server timeouts.

= 1.1.0 =
* Added CARFAX support to the vehicle object and added three CARFAX icons to the assets directory

= 1.0.0 =
* Changed the post meta key `piped_options` to `option` with the new intent to no longer be a unique key.
* Changed the place where the options list lives again after realizing a non-unique post meta key would cause more problems than it solved. The vehicle options list now lives in `option_array` as a serialized array.
* John Norton joins me and starts building the first theme to use this plugin

= 0.2.0 =
* Moved all code that modifies imports into its own object and file, `includes/class-modify-imports.php`
* Deeper integration with WXR importers so inventory does not have to be deleted before a new import is run. An option called "When importing a feed, delete units not contained in the new feed" is born. Previously, an option allowed users to delete all inventory before a new feed is run. This is a much more nuanced solution that will not vacate the site of inventory during an import, and also reduce the amount churn on post IDs in the database.
* A post meta key called `_inventory_presser_photo_file_name_base` is introduced to help this plugin mate attachments to their parents after an import. A more conventional WordPress import from a backup would identify post IDs. It is possible to not specify post IDs in a WXR XML file, and let the database assign IDs as items are imported. We love this approach, but it means that the import file cannot provide a parent ID for media attachments. This meta key is used to say "Any attachment that does not have a parent post ID and whose filename starts with this string is a child of this post."

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
* Enhanced imports to mate photo attachments to parent posts, establishing a relationship between this plugin and WXR importers, especially `WordPress Importer for CRON` that I (salzano) have also written. Also sets first photo as featured image. This whole routine operates according to the photo naming convention we employ at Friday Systems.
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

The plan for development in late 2015 was to build a platform on which we could build websites for our independent
car dealership customers based on these principles:

* Feed first. The inventory source is primarily a bulk feed and not manual entry.
* Leverage the platform; do not reinvent the wheel. WordPress allows custom post types and taxonomies.
WordPress has already designed edit screens for content objects. WordPress already has an importer.
* Stay small. This plugin will not contain functionality that is not the minimum set of features
that will enable users to comfortably manage inventory. Other features will live other plugins.