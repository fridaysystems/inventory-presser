=== Inventory Presser ===
Contributors: salzano
Tags: car dealer, inventory management, vehicle, automobile, dealership, motorcycle
Requires at least: 5.0.0
Tested up to: 5.1.0
Stable tag: 8.6.0
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


== Frequently Asked Questions ==

= How much does this plugin cost? =

This plugin is free. Free and paid add-ons are available at https://inventorypresser.com/.

= Does it decode VINs? =

Not for free. We have a VIN decoder add-on available at https://inventorypresser.com/. If you know of a free VIN decoder that allows automated, public queries, please show us.

= Can I import my inventory into this system with a bulk feed? =

Yes. The best way to do this to use the [WordPress REST API](https://developer.wordpress.org/rest-api/). Vehicles are stored in a custom post type, so other options are certainly available. Our first importer was based on a fork of the XML-based [WordPress Importer](https://wordpress.org/plugins/wordpress-importer/). Many CSV/spreadsheet importer plugins work with custom post types as well.


== Screenshots ==

1. This is a list of vehicles in the administrator dashboard that the user sees when she clicks the Vehicles or All Vehicles menu items. Each vehicle is listed by year, make, model, trim, stock number, color, odometer, price, and photo count. If the vehicle has photos, a thumbnail is also shown. This screen shot was taken on version 3.1.0.
2. This is the edit screen that adds or edits a single vehicle. The post content box is demoted to handle only the sentence-form vehicle description, and the entire edit screen is enhanced to support vehicle attributes. This screen shot was taken on version 3.1.0.


== Changelog ==

= 9.0.0 =
* [Changed] Changes the "Requires at least" version number to 5.0.0 because this version adds editor blocks that do not work on older versions of WordPress

= 8.6.0 =
* [Added] Adds methods to the vehicle class to output down payment and payment in dollar-formatted strings like the price.
* [Added] Now provides a type argument when registering meta keys for the Vehicle post type.
* [Changed] Add and refactor some methods in the vehicle class to make the Carfax report URL and YouTube video URL available as methods.
* [Changed] Tweak the registration of vehicle attribute taxonomies so they will be visible in the block editor.
* [Fixed] Fixes a bug in a `the_content` filter when there are no templates in the theme to display vehicles.

= 8.5.0 =
* [Added] Added `show_ui` arguments when creating vehicle attribute taxonomies so a new add-on plugin, Show All Taxonomies, can filter this data and reveal all our taxonomies to dashboard users.

= 8.4.0 =
* [Added] Allows updating of serialized meta fields via the REST API via an update callback.
* [Changed] No longer defines a few functions statically to avoid throwing warnings when updating term meta via the REST API.

= 8.3.2 =
* [Fixed] Fixes a bug where a misspelled variable name would prevent an instance of our vehicle class from returning the requested size thumbnails.

= 8.3.1 =
* [Fixed] Fixes a bug where when updating taxonomy terms via the REST API, the old value passed to update_term_meta() would be unnecessarily serialized.

= 8.3.0 =
* [Added] Built-in taxonomies can now accept multiple filters. For example, a URL such as foo.com/inventory/model/accent/model/grand-cherokee/ will list all Hyundai Accents and Jeep Grand Cherokees.
* [Removed] Removed a feature that redirects search results that include only one vehicle to that vehicle's details page. This feature is at odds with allowing multiple taxonomies filters and especially with Vehicle Filters Widget version 4.0.0.

= 8.2.0 =
* [Added] Adds a Google Map widget that points at an address stored in this plugin's location taxonomy of the user's choosing
* [Fixed] Changes to achieve compatibility with PHP 7.2: a few create_function() calls were rewritten to use anonymous functions

= 8.1.2 =
* [Fixed] Fixes a bug where a static member was not called statically when updating vehicle taxonomy terms via the REST API

= 8.1.1 =
* [Changed] Only include stylesheets and scripts for the Vehicle Slider widget if that widget is active.
* [Removed] Removes a script file and stylesheet for noUiSlider, a JavaScript range slider that was originally added for a price range widget that was never finished.

= 8.1.0 =
* [Added] Adds a list of full size photo URLs to the response array of the vehicle class' get_images_html_array() method. These accompany the <img> elements that are still part of the response.
* [Added] Adds JavaScript and CSS payloads and hooks for jQuery.slick, a carousel we've built into our Vehicle Slider widget. This further decouples this plugin from the first theme we built called _dealer.

= 8.0.0 =
* [Changed] Changed the custom post type definition to always has_archive, despite whether or not the theme has a proper template to show vehicles.

= 7.1.0 =
* [Added] Added a filter `shortcode_atts_inventory_slider` to the [invp-inventory-slider] shortcode so that the attributes can be filtered. Also added the orderby and order parameters of the queries inside this shortcode to the shortcode attributes so they can be filtered.
* [Added] Added a filter `invp_slider_widget_query_args` that wraps the arguments that find vehicles that will populate the widget version of the slider.
* [Fixed] Fix some bugs in the KBB Widget where we were assuming the widget's settings contain values.

= 7.0.0 =
* [Added] Save a unique ID with phone numbers and sets of hours that are created in the dashboard. These IDs allow numbers or hours to be unique identified even if their labels change.
* [Changed] Changed the permalink behavior for the vehicles post type to ignore any prefix by setting `with_front` to false. This means sites can have blogs at site.com/blog and vehicles at site.com/inventory instead of site.com/blog/inventory.
* [Changed] Stop using a deprecated hook `media_buttons_context` and start using `media_buttons` instead.
* [Fixed] Fixes a bug that prevented our location taxonomy from outputting the phone number and hours array in REST API responses
* [Removed] Support for AutoCheck reports has been removed from this plugin and now lives in an add-on called AutoCheck Buttons.
* [Removed] Management of a Friday System's customer index as a meta field in our location taxonomy. In preparation of a public release, this company-specific code has no place in this plugin.

= 6.1.1 =
* [Fixed] Fixes multiple bugs in the way we redirect requests for vehicles that no longer exist in inventory (instead of returning a 404 error)

= 6.1.0 =
* [Added] Register term meta for our location taxonomy. This code was commented out since WP version 4.9.4 where term meta via the REST appeared to be broken or unfinished.
* [Changed] Change the style of phone number and hours inputs in the locations taxonomy terms found at Vehicles > Locations. These controls now more closely match the WordPress dashboard style.

= 6.0.1 =
* [Changed] The description, FAQ, and History sections of this readme.txt file are updated
* [Fixed] Fixes a bug where the wrong text-domain was provided for a translatable string

= 6.0.0 =
* [Changed] Changes the vehicle object to return it's list of attachments ordered by their photo number meta value instead of the order that they were uploaded.
* [Removed] Removed a member of the vehicle object that was populated with lists of attachment URLs when the get_images_html_array() method executed.

= 5.4.0 =
* [Added] Help our suite of add-on plugins activate licenses at inventorypresser.com and receive automatic updates by adding a license class
* [Added] Pass the vehicle object into the invp_zero_price_string filter so the zero price string can be easily changed based on vehicle attributes

= 5.3.1 =
* [Fixed] Fix a bug introduced in the previous version where only part of a query in the Media Library was corrected
* [Fixed] Fix a bug by checking that a term ID exists before trying to use it
* [Fixed] Fix a bug that excluded sold vehicles outside searches and the post_type archives for our custom post type (when the option to hide sold vehicles was set)
* [Fixed] Fix a bug that prevented filters like min_price and max_odometer from working when no other meta query was present in the request

= 5.3.0 =
* [Added] Make vehicle thumbnails on edit.php links to the edit page just like the post title
* [Fixed] Fix a bad query that prevented filtering the Media Library to unattached photos

= 5.2.0 =
* [Added] Register a meta key to track an MD5 image hash with media attachments.

= 5.1.0 =
* [Added] REST API fields to allow reading of three serialized meta fields in our vehicle object: epa_fuel_economy, option_array, and prices, which is also an array.

= 5.0.0 =
* [Added] Created a workaround to save term meta via the REST API, WordPress 4.9.4 seems a bit broken here.
* [Changed] Do not show sold units in inventory listings by default, and add an option to include them.
* [Changed] Use lowercase meta field names all the time, and change the vehicle class to not capitalize the id in car_id.
* [Fixed] Make sure serialized meta values (like vehicle option lists, EPA fuel economy data, and the prices array) are unserialized before saving. Edits via the REST API were resulting in twice-serialized values.
* [Fixed] Fixed a bug in our logic that redirects users who are trying to view deleted vehicles.

= 4.2.0 =
* [Added] Include term IDs for each of our custom taxonomies in the REST API responses for our posts.
* [Added] Register all postmeta fields for our custom post type so that they may be exposed in the REST API.
* [Added] Expose all custom taxonomies that enable vehicle searches to the REST API.
* [Fixed] Fixed a bug where a meta key was not being prefixed with the filter and instead had a hard-coded prefix.

= 4.1.0 =
* [Added] When a search or set of taxonomy filters returns a single vehicle, redirect to that vehicle instead of an archive or results page.
* [Added] Add two template tags, `invp_get_the_vin()` and `invp_get_the_price()` to make access easy for themes and other items in the loop.
* [Fixed] Change links to terms in our taxonomies to contain /inventory, so one of our rewrite rules is used to guide the request.

= 4.0.0 =
* [Changed] Changed a filter hook from 'translate_meta_field_key' to 'invp_prefix_meta_key'
* [Changed] Changed a filter hook from 'untranslate_meta_field_key' to 'invp_unprefix_meta_key'
* [Changed] Changed a filter hook from 'order_by_post_meta_widget_ignored_fields' to 'invp_sort_by_widget_ignored_fields'
* [Changed] Changed an action hook from 'inventory_presser_delete_all_data' to 'invp_delete_all_data'
* [Changed] Changed an action hook from 'inventory_presser_delete_all_inventory' to 'invp_delete_all_inventory'
* [Changed] Changed a filter hook from 'inventory_presser_edit_control_vin' to 'invp_edit_control_vin'
* [Changed] Changed a filter hook from 'inventory_presser_default_options' to 'invp_default_options'
* [Changed] Changed a filter hook from 'inventory_presser_taxonomy_data' to 'invp_taxonomy_data'
* [Changed] Changed a filter hook from 'inventory_presser_meta_value_or_meta_value_num' to 'invp_meta_value_or_meta_value_num'
* [Changed] Changed a filter hook from 'inventory_presser_post_type_args' to 'invp_post_type_args'
* [Removed] Moved Friday Systems features out of this plugin and into another. No longer save the IDs of contact and financing pages with plugin settings so that menu links to those pages can have querystring variables that identify vehicles appended.

= 3.9.0 =
* [Added] Modifications to searches to include vehicle data like stock numbers and VINs is extended to searches conducted in the admin dashboard.
* [Changed] Do not allow non-numeric characters to be entered into the price inputs while editing vehicles.
* [Changed] Vehicles will no longer go to Trash upon deletion and instead will be permanently deleted.

= 3.8.0 =
* [Added] Merged a feature plugin into core that enables an "Email a Friend" button on VDP.
* [Changed] Tested up to 4.9.4

= 3.7.0 =
* [Added] This plugin is now translation-ready.
* [Added] A new attribute, size, is added to the `[invp-inventory-grid size="one-fourth"]` shortcode. The size specifies how big each thumbnail in a row will be rendered.
* [Added] Hide some columns that Yoast SEO adds to the dashboard list of vehicles.
* [Changed] Renamed almost every widget to remove "Dealer" prefixes and rewrote the descriptions to be less redundant.
* [Changed] Changed some <td>s to <th>s in widgets to increase accessibility of this tabled information.
* [Changed] The Order By Post Meta widget now has a prettier form without ugly post meta key names. The widget should still be renamed to something like Vehicle Sort Controls in the future.
* [Removed] The list of fields by which vehicles can be sorted in listings page by default has been pruned to remove fields that don't make sense as sort fields.

= 3.6.1 =
* [Changed] Updated the AutoCheck button to the latest SVG version
* [Fixed] A bug where the order by field is passed via GET but the sort order is not
* [Removed] A wrapping <div> element around the AutoCheck button HTML

= 3.6.0 =
* [Added] Added two filters, `invp_sold_string` and `invp_zero_price_string` to wrap the output of the vehicle price when the value is zero or the vehicle is sold
* [Added] Added filters `invp_default_payment_frequencies`, `invp_default_boat_styles`, and `invp_default_hull_materials` to wrap arrays of default values baked into this plugin
* [Added] Added some instructional text to the options page to help users understand the features of this plugin
* [Added] All the values in a vehicle's prices array are now editable in the dashboard. This includes MSRP, down payment, payment, and payment frequency
* [Changed] Do not show options and prices arrays in the list of default vehicle sort keys on the options page, these are structured data fields
* [Changed] Move the options meta box to a higher position so it appears directly below the traditional edit box if there are other plugins creating meta boxes
* [Removed] Removed an option that is implemented in the _dealer theme to affect the way users are redirected after submitting a Contact Form 7 form
* [Removed] Removed an option that is implemented in the _dealer theme to customize a particular layout style
* [Removed] Removed an option that is implemented in the _dealer theme to append a page's content to each vehicle details page
* [Removed] Removed an option that is implemented in the _dealer theme to display vehicle prices in various ways to show discounts
* [Removed] Removed an option that is implemented in the _dealer theme to change the MSRP label to custom text
* [Removed] Removed an option that is implemented in the _dealer theme to change the way a Flexslider works
* [Removed] Removed an option that is implemented in the _dealer theme to control where vehicle descriptions are shown
* [Removed] Removed an option that is implemented in the _dealer theme to control where CarGurus badges are shown
* [Removed] Removed an option that is implemented in the _dealer theme to hide footer links

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
