=== Inventory Presser - Car Dealer Listings ===
Contributors: salzano
Tags: car dealer, car dealership, car listings, auto dealer, car sales
Requires at least: 5.0.0
Tested up to: 6.8.3
Requires PHP: 7.0.0
Stable tag: 15.2.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Vehicle inventory management for dealerships. Supports multiple car lot locations. Provides listing templates & photo sliders. Multisite compatible.

== Description ==

Adds listings and vehicle details pages to any theme. Comes with templates and photo sliders for automobile and power sports dealerships. Supports multiple lot locations & separate hours for each. This plugin is free software. Visit the Installation tab for download links.

* Store & display cars, trucks, vans, SUVs, motorcycles, ATVs, RVs, and boats
* [Manage multiple lot locations](https://inventorypresser.com/docs/vehicle-post-type/locations-taxonomy/) and maintain separate phone numbers & hours for each
* [VIN-decoding add-on](https://inventorypresser.com/products/plugins/add-vins-to-vehicle-urls/) available
* Categorizes inventory by type, body style, fuel, transmission, drive type, availability, new or used, location, and more
* Includes blocks, [shortcodes](https://inventorypresser.com/docs/shortcodes/), and [widgets](https://inventorypresser.com/docs/feature-list/widgets/) including vehicle sliders and inventory grids

### Get Started in Minutes

* Install the plugin and [load the sample vehicles](https://inventorypresser.com/docs/settings/load-sample-vehicles/)
* Visit yoursite.com/inventory to see the built-in templates
* Design your own vehicle templates using [post meta fields](https://inventorypresser.com/docs/vehicle-post-type/), Elementor, Avada Builder, or [Divi Builder](https://inventorypresser.com/docs/divi-setup-guide/)
* Decide how to best [manage inventory updates](https://inventorypresser.com/docs/adding-or-importing-inventory/)
* [Add vehicles to lead forms](https://inventorypresser.com/docs/add-vehicles-to-lead-forms/)

### Integration Guides

* [Setup a Dealership Website](https://inventorypresser.com/docs/setup-a-dealership-website/)
* [Theme Compatibility Guide](https://inventorypresser.com/docs/theme-compatibility-with-vehicle-listings/)
* [Adding or Importing Inventory](https://inventorypresser.com/docs/adding-or-importing-inventory/)

### Developer-friendly Best Practices

* Multisite Compatible
* Supports Classic Editor
* Supports Block Editor
* [Custom post type `inventory_vehicle`](https://inventorypresser.com/docs/vehicle-post-type/) enables [vehicle data imports](https://inventorypresser.com/docs/using-the-rest-api-to-update-vehicle-listings-in-wordpress/)
* Posts list columns for stock number, color, odometer, price, photo count, and thumbnail
* Custom taxonomies group vehicles by year, make, model, type, body style, fuel, transmission, drive type, availability, new or used, location, and more
* [Hooks](https://inventorypresser.com/docs/hooks/) in all the right places & powerful [shortcodes](https://inventorypresser.com/docs/shortcodes/)
* [Template tags](https://inventorypresser.com/docs/template-tags/) for easy template builds
* [Runs on any theme](https://inventorypresser.com/docs/theme-compatibility-with-vehicle-listings/) & provides shortcodes for the stubborn ones.
* [Full feature list](https://inventorypresser.com/docs/feature-list/) & more documentation [inventorypresser.com](https://inventorypresser.com/)


== Installation ==

1. Upload the `inventory-presser` directory to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. [Load the sample vehicles](https://inventorypresser.com/docs/settings/load-sample-vehicles/)
1. Visit yoursite.com/inventory to see the built-in templates
1. Design your own pages using [post meta fields](https://inventorypresser.com/docs/vehicle-post-type/) or [Elementor Dynamic Tags](https://inventorypresser.com/products/plugins/elementor-add-on/)
1. Decide how to [manage inventory updates](https://inventorypresser.com/docs/adding-or-importing-inventory/)

### Downloads

 * [https://downloads.wordpress.org/plugin/inventory-presser.15.2.1.zip](https://downloads.wordpress.org/plugin/inventory-presser.15.2.1.zip)
 * [https://inventorypresser.com/wp-content/uploads/inventory-presser-v15.2.1.zip](https://inventorypresser.com/wp-content/uploads/inventory-presser-v15.2.1.zip)


### Requires 5.0.0

This plugin uses a few features that were launched in WordPress 5.0.0, including an Editor Sidebar and `wp_add_inline_script()` calls without sources.


== Frequently Asked Questions ==

= How much does this plugin cost? =
This plugin is free and open source. Free and paid add-ons & services are available at https://inventorypresser.com/.

= Does it decode VINs? =
Not for free. A VIN decoder add-on is available at https://inventorypresser.com/products/plugins/vin-decoder/.

= Can I import my inventory? =
Yes. Visit [Adding or Importing Inventory](https://inventorypresser.com/docs/adding-or-importing-inventory/) to determine what is best for your client.

= Will Inventory Presser work with my theme? =
Yes. Check out our [Theme Compatibility Guide](https://inventorypresser.com/docs/theme-compatibility-with-vehicle-listings/). If your theme does not include templates to display listing pages and vehicle detail pages, the plugin provides those pages automatically.

= Can I change the dollar sign to my local currency? Miles to kilometers? =
Yes. Visit our [Internationalization Guide](https://inventorypresser.com/docs/internationalization/).

== Screenshots ==

1. This is a vehicle post archive showing post title, full size featured image, price, vehicle attribute table, carfax button, and link to vehicle post single.
2. This is an inventory grid showing featured image thumbnails and post titles. A full inventory link takes users to the vehicle post archive.
3. This is a list of vehicles in the administrator dashboard that the user sees when she clicks the Vehicles or All Vehicles menu items. Each vehicle is listed by year, make, model, trim, stock number, color, odometer, price, and photo count. If the vehicle has photos, a thumbnail is also shown. This screen shot was taken on version 3.1.0.
4. This is the edit screen that adds or edits a single vehicle. The post content box is demoted to handle only the sentence-form vehicle description, and the entire edit screen is enhanced to support vehicle attributes. This screen shot was taken on version 3.1.0.
5. This screenshot shows a vehicle post in the WordPress block editor. An editor sidebar for the Inventory Presser plugin is expanded and contains vehicle attribute fields. An arrow points to a button with a key on it that reveals the editor sidebar.
6. This screenshot shows a vehicle archive on the Twenty Twenty theme before any customization.


== Changelog ==

= 15.2.1 =
* [Added] Adds a shortcode [invp_price] to display a vehicle's price.
* [Fixed] Provides the post content as the inventory_presser_description meta field when "Provide single and archive templates" is enabled.
* [Fixed] Adds a style attribute to the [invp_archive] shortcode and passes it into [invp_archive_vehicle] to make the style parameter accessible to archive shortcode users.
* [Fixed] Bug fix in [invp_archive] shortcode that prevented the before and after filters from working.
* [Changed] Changes tested up to version number to 6.8.3.

= 15.2.0 =
* [Added] Adds querystring filters for minimum and maximum down payments. Use `min_down` and `max_down` to filter vehicles by down payment amounts in URLs like https://example.com/inventory/?max_down=600.
* [Fixed] Removes CSS max-width from vehicle attribute table.
* [Changed] Limits the number of simultaneous taxonomy filters in an archive to five in URLs like https://example.com/inventory/make/acura/make/audi/make/honda/make/gmc/make/subaru/.

= 15.1.4 =
* [Fixed] Bug fix in Divi integration. Stops putting the post ID near the odometer value returned by invp_get_the_odometer().

= 15.1.3 =
* [Added] Adds a Block Editor event handler for the VIN Decoder add-on. Detects and runs a VIN decode input handler for Inventory Presser VIN Decoder version 3.0.0 and up.

= 15.1.2 =
* [Added] Adds a `style` parameter to the [invp_archive_vehicle] shortcode. Values can be a or b. Defaults to a.
* [Fixed] Allow all shortcode attributes to be filtered by other developers. Corrects some shortcode filter names to add invp_ prefix.
* [Fixed] Fixes the way the vehicle attribute table styles are loaded. Solves the problem of attribute table CSS vanishing when the shortcode is used outside the provided templates.
* [Fixed] Bug fix in archive CSS. Avoid using a pixel value. Replaces it with a percentage.
* [Fixed] Fixes a bug when saving changes to vehicles made in the dashboard editors. Stops assigning taxonomy term relationships based on the meta data inputs. Starts using only the tax_input array to populate term relationships.
* [Changed] Deprecates Inventory_Presser_Taxonomies::save_taxonomy_term(). Use wp_set_object_terms() instead. This method will be removed in a future release.
* [Changed] Revisions to the Maximum Price Filter. Changes default price amounts to put the larger amounts first. Changes the amounts themselves. Changes the default title to "Shop by Price".

= 15.1.1 =
* [Added] Adds an API method INVP::have_new_and_used_vehicles().
* [Fixed] Bug fix in [invp_attribute_table] shortcode. Avoids repeating a fuel type like "Diesel" in the engine descriptor.
* [Fixed] Bug fixes in flexslider CSS for more widespread compatibility.
* [Fixed] Bug fix in Avada integration. Allows taxonomy filters in vehicle archives using Avada post cards layout.
* [Fixed] Bug fix in Avada integration. Do not assume before and after content object members exist before using them.
* [Changed] Changes to the Type taxonomy terms that are loaded for Car and SUV. Removes "Sport Utility Vehicle" and "Passenger Car" in favor of SUV and Car.

= 15.1.0 =
* [Added] Adds a "Phone Numbers" meta box to the Appearance > Menus page, allowing phone numbers managed by the Locations taxonomy to be added to menus.
* [Added] Adds a filter `invp_phone_number_widget_html` to make it easy to edit Phone Number widget HTML before it is output.
* [Added] Adds two filters `invp_archive_shortcode_before` and `invp_archive_shortcode_after`. These filters make it easy to add content above and below the archive output.
* [Fixed] Fixes a bug in the [invp_sort_by] shortcode.
* [Fixed] Fixes a bug in Gravity Forms integration. Do not use a Gravity Forms class GF_Fields before making sure it exists. When the active_plugins option contains Gravity Forms but the site does not have the Gravity Forms plugin files, this condition would cause a fatal error.
* [Changed] Changes inventory_presser_car_id meta field type from integer to string.
* [Changed] Changes the cron job to delete unused terms from daily to weekly.
* [Changed] Changes the filter priority on the View Details button on archives from 10 to 20.

= 15.0.0 =
* [Added] Adds a Description block that displays and edits the rich text describing the vehicle in the inventory_presser_description meta field.
* [Added] Adds Base Color drop down to the Attributes panel in the Classic Editor.
* [Added] Adds a template tag invp_get_the_youtube_embed().
* [Added] Adds a meta field inventory_presser_tag_line to hold a descriptive sentence that differs from the post title.
* [Fixed] Fixes a bug that blanked vehicle post titles in REST API responses.
* [Fixed] Fixes bugs in Divi integration. Output more than one option when the user chooses the Options Array dynamic content value. Format price, payment, and MSRP as currency when used as dynamic content values.. Format odometer as a number when used as a dynamic content value.
* [Fixed] Fixes bugs in Avada integration. Formats MSRP, payment, and down payment as currency when used as Custom Fields in Text Modules.
* [Fixed] Fixes bugs around the "Sale Pending" availability term and copying changes involving this term to the overlapping meta field.
* [Fixed] Fixes an XML syntax bug in Contact Form 7 integration when creating ADF XML with the [invp_adf_vendor] mail tag. Adds a `post_id` hidden field to the form tag output of [invp_vehicle] to make attaching vehicles to lead submissions more efficient than using the stock number.
* [Changed] Stops using _post capabilities. Changes the registration of the inventory_vehicle post type to use its own capabilities. Grants all capabilities to administrator users, but all non-administrators will lose the ability to read, edit, and delete vehicle posts with this update.
* [Changed] Changes tested up to version number to 6.7.2.

= 14.18.3 =
* [Fixed] Fixes a bug in the [invp_archive] shortcode preventing pagination from working as expected.

= 14.18.2 =
* [Fixed] Fixes a bug in the [invp_archive] shortcode. Stops adding a meta_key parameter to the posts query when found in the orderby query variable when the value found in orderby is "meta_value".
* [Fixed] Fixes a bug in the [invp_archive_vehicle] shortcode. Stops escaping the return value of invp_get_the_price(). The value is already escaped for HTML.
* [Fixed] Fixes a bug in the [invp_single_vehicle] shortcode. Allows the invp_no_photo_url filter to work on vehicle singles.

= 14.18.1 =
* [Added] Adds translation files that were previously only hosted at wordpress.org.
* [Fixed] Fixes bugs when numbering and rearranging photos in the Block Editor.
* [Fixed] Bug fixes in WP All Import integration. Assign photo meta data to attachments to vehicles as they are imported.
* [Fixed] Do not let the Vehicles > Taxonomies settings prevent the Categories taxonomy from working if someone adds it to the vehicles post type.

= 14.18.0 =
* [Added] Adds the description field to the Attributes meta box in the Classic Editor.
* [Fixed] Fixes a bug that prevented transients holding vehicle photo arrays from being deleted.
* [Fixed] Stops adding photo HTML to the post body in the Classic Editor when the Add Media button is used to attach a photo to a vehicle.
* [Fixed] Fixes a bug when renumbering photos during uploads.
* [Fixed] Fixes a bug that would remove a newly-added Featured Image when users press Save draft in the Classic Editor.
* [Fixed] Prevents Divi from clobbering our Gallery Block in new vehicle posts in the Block Editor with their "Build Your Layout Using Divi" nag.

= 14.17.6 =
* [Fixed] Fixes a bug that prevented the plugin from uninstalling properly.
* [Changed] Changes tested up to version number to 6.7.1.

= 14.17.5 =
* [Fixed] Fixes a bug where adding a new vehicle in Classic Editor would not show all appropriate passenger vehicle meta boxes.
* [Fixed] Fixes a bug that caused two plugin update notices for Inventory Presser add-ons in the plugins list.

= 14.17.4 =
* [Fixed] Fixes a bug that prevented the plugin from deleting data during uninstallation.
* [Changed] Changes tested up to version number to 6.7.

= 14.17.3 =
* [Fixed] Fixes bug introduced in 14.17.2. Stops escaping HTML in invp_get_the_location_sentence() twice.

= 14.17.2 =
* [Fixed] Bug fix. Makes strings in blocks translatable.
* [Fixed] Bug fix. Add default block editor toolbar to all blocks to help users move and remove them.
* [Fixed] Bug fix when loading editor sidebar.
* [Changed] YouTube Video ID block now embeds the video in the block editor.
* [Changed] Changes the way most blocks are built so they each have their own block.json file. This will help directories correctly count how many blocks are provided in the plugin.

= 14.17.1 =
* [Fixed] Helps new users understand where to find the list of vehicles by adding a line to the Listings Pages table on the Settings page for the default post type archive.
* [Fixed] Detects an empty permalink structure and adds a Site Health test to recommend a change. With default/no permalinks, slash inventory is not added to rewrite rules and the default vehicle archive is /?post_type=inventory_vehicle instead of slash vehicle. Adds an admin notice on the plugin settings page to draw user attention to Site Health when there are recommendations.
* [Fixed] Bug fix in invp_get_the_last_modified() template tag for support outside the loop. Starts passing the post ID when getting the raw meta value.
* [Fixed] Bug fix in invp_get_the_carfax_url_report() template tag for support outside the loop. Starts passing the post ID when getting the VIN.
* [Fixed] Bug fix in invp_get_the_odometer() template tag. Check if the post ID is available from the loop when the passed value is empty or not passed.

= 14.17.0 =
* [Added] Adds blocks for Down Payment, Make, Model, MSRP, Odometer, Payment, Price, Stock Number, Title Status, Transmission Speeds, Trim Level, VIN, Year, and YouTube Video ID.
* [Added] Adds a block called Year, Make, Model, and Trim that outputs those meta fields in an H1.
* [Fixed] Fixes a number of blocks that were broken, including Beam, Body Style, Color, Engine, Interior Color, Last Modified, Length, and Odometer.
* [Fixed] Fixes bugs in handling input and output. Escapes more strings before output. Sanitizes more input values before they are used.

= 14.16.3 =
* [Fixed] Fixes a bug in the [invp_archive] shortcode that prevented orderby and order query parameters from changing the order of vehicles.
* [Fixed] Fixes a bug in the [invp_sort_by] shortcode that prevented it from working on any page other than an inventory_vehicle post type archive.
* [Fixed] Bug fixes in the REST API class. Stops assuming posts_per_page and paged will be defined in the query params.
* [Changed] Changes tested up to version number to 6.6.2.

= 14.16.2 =
* [Fixed] Bug fixes in the Add Media and Delete All Media buttons shown in the Classic Editor.
* [Fixed] Bug fix. Provide default taxonomy settings when a user has never used the Manage Taxonomies button to configure their site. Preserves backwards compatibility to before the setting existed.
* [Changed] Changes the [invp_archive] shortcode to allow a location parameter that takes a term slug.

= 14.16.1 =
* [Fixed] Fixes the price and odometer range filters not working outside the context of the [invp_archive] shortcode.
* [Fixed] Fixes a bug in the Avada Builder integration added in 14.16.0 to prevent a warning being logged when processing Text Block layout elements that do not have a meta key configured.

= 14.16.0 =
* [Added] Adds an integration with Gravity Forms. Provides a Vehicle field type that allows users to add vehicles to leads captured with Gravity Forms.
* [Fixed] Achieves compatibility with Avada Builder by adding all vehicle photos as Featured Images and changing the way some meta fields display when used in Text Block layout elements.
* [Fixed] Redesigns the Listings Pages settings table to fit on narrower screens. Adds a warning to Divi users that the feature does not work on Divi.

= 14.15.0 =
* [Added] Adds a setting Provide Templates at Vehicles > Options. Lets users toggle the provided templates for archive and single vehicles.
* [Added] Adds WS Form to the list of recognized form builders in the Singles Contact Form setting.
* [Fixed] Bug fix. Fixes the Add Listings Page button in the settings. This button stopped adding a new row to the Listings Pages table in 14.14.0.
* [Changed] Edits WP All Import integration to detect semicolon as an option delimiter in addition to pipes and commas.
* [Changed] Changes tested up to version number to 6.5.5.

= 14.14.2 =
* [Fixed] Adds a meta noindex nofollow tag to inventory archives that are filtered by 2 or more of our taxonomies. Prevent /inventory/make/toyota/make/lexus/fuel/gas/ from being indexed or followed by bots.
* [Fixed] Bug fix. Register scripts in the dashboard. The location taxonomy add phone and add hours buttons were broken because the script powering them stopped loading.
* [Fixed] Bug fix. Restores VIN to vehicle attribute table. VIN was erroneously removed in 14.14.0.

= 14.14.0 =
* [Added] Adds a setting "Singles Contact Form" at Vehicles → Options to embed a lead form on vehicle single pages. Supports Contact Form 7, Gravity Forms, and WP Forms.
* [Added] Adds "make" and "model" attributes to the Inventory Grid and Inventory Slider.
* [Added] Changes the "Show All Taxonomies" option to a "Manage Taxonomies" button that leads to another options page. Users can now toggle which taxonomies are active, show in the admin menu, and display in editors for each vehicle type. This makes the plugin much more friendly to boat dealers and anyone managing vehicles that are not passenger cars.
* [Added] Adds better support for the Classic Editor.
* [Added] Adds more boat fields for condition, draft, number of engines, engine make, engine model, and horsepower.
* [Added] Adds template tags for boats including invp_is_boat(), invp_get_the_condition_boat(), invp_get_the_draft(), invp_get_the_engine_count(), invp_get_the_engine_make(), invp_get_the_engine_model(), and invp_get_the_horsepower().
* [Fixed] Stops showing an admin notice on this plugin's settings page. Adds the information to the Site Health page under the Tools menu instead.
* [Fixed] Makes more strings translateable, including default boat styles and hull materials.
* [Fixed] Bug fix. Prevent invp_get_the_photos() from returning a list of urls containing empty strings. Vehicles with broken images would prevent the flexslider from starting on details pages.
* [Changed] Stops removing the odometer field from the vehicle attribute table for boats.
* [Changed] Changes the fields for boats in the attribute table shortcode, and therefore the default archive and single templates.

= 14.13.0 =
* [Added] Updates WP All Import integration to detect comma-delimited options. Previously, only pipe-delimited options were split into the meta field during imports.
* [Fixed] Fixes a bug in the "hours today" sentence. Stops using the PHP function jddayofweek().

= 14.12.7 =
* [Fixed] Fixes a bug introduced in 14.12.6 that broke this plugin's Block Editor sidebar.

= 14.12.6 =
* [Added] Adds an ID parameter to the [invp_photo_slider] shortcode so it can be used on any page.
* [Fixed] Fixes a bug that caused an intermittent error in the Block editor "Updating failed. The response is not a valid JSON response."
* [Fixed] Stops showing the Vehicles admin bar item to logged in users who cannot edit posts.
* [Fixed] Fixes bugs in the [invp_inventory_slider] shortcode so it operates more closely like the widget. Adds a showcount parameter
* [Fixed] Fixes a bug that redirected 404 vehicle requests in the dashboard to the front end error page instead of the empty posts list.
* [Changed] Changes tested up to version to 6.5.2.

= 14.12.5 =
* [Added] Adds support for marking vehicles "Sale pending". Adds a term to the Availability taxonomy during plugin activation. Adds a template tag invp_is_pending(). Shows "Sale pending" instead of any price.
* [Fixed] Removes valid html title opening tags from readme.txt and changelog.txt. These files can more easily be more easily embedded in web pages.
* [Changed] Changes tested up to version to 6.5.0.

= 14.12.4 =
* [Fixed] Optimizes the way sliders are resized when the browser window is resized.
* [Fixed] Fixes a bug when prefixing taxonomy term URLs with /inventory to avoid extraneous database queries. This was breaking the menu administration page.
* [Fixed] Updates the install instructions in readme.txt.

= 14.12.3 =
* [Fixed] Fixes a CSS bug in the inventory grid shortcode and widget.
* [Fixed] Fixes a bug causing duplicate search results since 14.12.2.

= 14.12.2 =
* [Fixed] Fixes a bug in the photo rearranger Gallery Block when comparing photo numbers.
* [Fixed] Fixes a bug when saving vehicles to prevent duplicate meta values for meta registered as singles.
* [Fixed] Fixes a bug where all search queries sitewide were joined to the taxonomy term tables. Vehicle data in terms is mirrored in post meta, so this was no longer necessary.
* [Fixed] Fixes a JavaScript bug when loading photo sliders on vehicle singles where the vehicle only has one photo and a carousel is not displayed.
* [Removed] Stops adding a querystring containing the photo hash to photo link URLs.

= 14.12.1 =
* [Fixed] Fixes a bug introduced in 14.12.0 in the Grid widget when Show Captions is enabled.

= 14.12.0 =
* [Added] Adds an API method INVP::currency_symbol() and a filter `invp_currency_symbol` to allow the default US dollar sign to be changed.
* [Added] Adds a `show_odometers` parameter to the Grid shortcode and widget.

= 14.11.5 =
* [Fixed] Fixes a bug in the photo numberer class. Makes the post id parameter optional in a callback on the the_title hook.
* [Changed] Updates icon and banner art.
* [Changed] Changes the tested up to version number 6.4.2.

= 14.11.4 =
* [Fixed] Updates demo site URL to https://demo.inventorypresser.com/.
* [Fixed] Fixes bugs in flexslider spin-up script when used outside single vehicle context.
* [Fixed] Makes more strings translateable.
* [Changed] Changes the tested up to version number 6.3.2.


== Upgrade Notice ==

= 15.2.1 =
Adds a shortcode [invp_price] to display a vehicle's price. Provides the post content as the inventory_presser_description meta field when "Provide single and archive templates" is enabled. Adds a style attribute to the [invp_archive] shortcode and passes it into [invp_archive_vehicle] to make the style parameter accessible to archive shortcode users. Bug fix in [invp_archive] shortcode that prevented the before and after filters from working. Changes tested up to version number to 6.8.3.

= 15.2.0 =
Adds querystring filters for minimum and maximum down payments. Use `min_down` and `max_down` to filter vehicles by down payment amounts in URLs like https://example.com/inventory/?max_down=600. Removes CSS max-width from vehicle attribute table. Limits the number of simultaneous taxonomy filters in an archive to five in URLs like https://example.com/inventory/make/acura/make/audi/make/honda/make/gmc/make/subaru/.

= 15.1.4 =
Bug fix in Divi integration. Stops putting the post ID near the odometer value returned by invp_get_the_odometer().

= 15.1.3 =
Adds a Block Editor event handler for the VIN Decoder add-on. Detects and runs a VIN decode input handler for Inventory Presser VIN Decoder version 3.0.0 and up.

= 15.1.2 =
Adds a `style` parameter to the [invp_archive_vehicle] shortcode. Values can be a or b. Defaults to a. Allow all shortcode attributes to be filtered by other developers. Corrects some shortcode filter names to add invp_ prefix. Fixes the way the vehicle attribute table styles are loaded. Solves the problem of attribute table CSS vanishing when the shortcode is used outside the provided templates. Bug fix in archive CSS. Avoid using a pixel value. Replaces it with a percentage. Fixes a bug when saving changes to vehicles made in the dashboard editors. Stops assigning taxonomy term relationships based on the meta data inputs. Starts using only the tax_input array to populate term relationships. Deprecates Inventory_Presser_Taxonomies::save_taxonomy_term(). Use wp_set_object_terms() instead. This method will be removed in a future release. Revisions to the Maximum Price Filter. Changes default price amounts to put the larger amounts first. Changes the amounts themselves. Changes the default title to "Shop by Price".

= 15.1.1 =
Adds an API method INVP::have_new_and_used_vehicles(). Bug fix in [invp_attribute_table] shortcode. Avoids repeating a fuel type like "Diesel" in the engine descriptor. Bug fixes in flexslider CSS for more widespread compatibility. Bug fix in Avada integration. Allows taxonomy filters in vehicle archives using Avada post cards layout. Bug fix in Avada integration. Do not assume before and after content object members exist before using them. Changes to the Type taxonomy terms that are loaded for Car and SUV. Removes "Sport Utility Vehicle" and "Passenger Car" in favor of SUV and Car.

= 15.1.0 =
Adds a "Phone Numbers" meta box to the Appearance > Menus page, allowing phone numbers managed by the Locations taxonomy to be added to menus. Adds a filter `invp_phone_number_widget_html` to make it easy to edit Phone Number widget HTML before it is output. Adds two filters `invp_archive_shortcode_before` and `invp_archive_shortcode_after`. These filters make it easy to add content above and below the archive output. Fixes a bug in the [invp_sort_by] shortcode. Fixes a bug in Gravity Forms integration. Do not use a Gravity Forms class GF_Fields before making sure it exists. When the active_plugins option contains Gravity Forms but the site does not have the Gravity Forms plugin files, this condition would cause a fatal error. Changes inventory_presser_car_id meta field type from integer to string. Changes the cron job to delete unused terms from daily to weekly. Changes the filter priority on the View Details button on archives from 10 to 20.

= 15.0.0 =
Adds a Description block that displays and edits the rich text describing the vehicle in the inventory_presser_description meta field. Adds Base Color drop down to the Attributes panel in the Classic Editor. Adds a template tag invp_get_the_youtube_embed(). Adds a meta field inventory_presser_tag_line to hold a descriptive sentence that differs from the post title. Fixes a bug that blanked vehicle post titles in REST API responses. Fixes bugs in Divi integration. Output more than one option when the user chooses the Options Array dynamic content value. Format price, payment, and MSRP as currency when used as dynamic content values.. Format odometer as a number when used as a dynamic content value. Fixes bugs in Avada integration. Formats MSRP, payment, and down payment as currency when used as Custom Fields in Text Modules. Fixes bugs around the "Sale Pending" availability term and copying changes involving this term to the overlapping meta field. Fixes an XML syntax bug in Contact Form 7 integration when creating ADF XML with the [invp_adf_vendor] mail tag. Adds a `post_id` hidden field to the form tag output of [invp_vehicle] to make attaching vehicles to lead submissions more efficient than using the stock number. Stops using _post capabilities. Changes the registration of the inventory_vehicle post type to use its own capabilities. Grants all capabilities to administrator users, but all non-administrators will lose the ability to read, edit, and delete vehicle posts with this update. Changes tested up to version number to 6.7.2.

= 14.18.3 =
Fixes a bug in the [invp_archive] shortcode preventing pagination from working as expected.

= 14.18.2 =
Fixes a bug in the [invp_archive] shortcode. Stops adding a meta_key parameter to the posts query when found in the orderby query variable when the value found in orderby is "meta_value". Fixes a bug in the [invp_archive_vehicle] shortcode. Stops escaping the return value of invp_get_the_price(). The value is already escaped for HTML. Fixes a bug in the [invp_single_vehicle] shortcode. Allows the invp_no_photo_url filter to work on vehicle singles.

= 14.18.1 =
Adds translation files that were previously only hosted at wordpress.org. Fixes bugs when numbering and rearranging photos in the Block Editor. Bug fixes in WP All Import integration. Assign photo meta data to attachments to vehicles as they are imported. Do not let the Vehicles > Taxonomies settings prevent the Categories taxonomy from working if someone adds it to the vehicles post type.

= 14.18.0 =
Adds the description field to the Attributes meta box in the Classic Editor. Fixes a bug that prevented transients holding vehicle photo arrays from being deleted. Stops adding photo HTML to the post body in the Classic Editor when the Add Media button is used to attach a photo to a vehicle. Fixes a bug when renumbering photos during uploads. Fixes a bug that would remove a newly-added Featured Image when users press Save draft in the Classic Editor. Prevents Divi from clobbering our Gallery Block in new vehicle posts in the Block Editor with their "Build Your Layout Using Divi" nag.

= 14.17.6 =
Fixes a bug that prevented the plugin from uninstalling properly. Changes tested up to version number to 6.7.1.

= 14.17.5 =
Fixes a bug where adding a new vehicle in Classic Editor would not show all appropriate passenger vehicle meta boxes. Fixes a bug that caused two plugin update notices for Inventory Presser add-ons in the plugins list.

= 14.17.4 =
Fixes a bug that prevented the plugin from deleting data during uninstallation. Changes tested up to version number to 6.7.

= 14.17.3 =
Fixes bug introduced in 14.17.2. Stops escaping HTML in invp_get_the_location_sentence() twice.

= 14.17.2 =
Bug fix. Makes strings in blocks translatable. Bug fix. Add default block editor toolbar to all blocks to help users move and remove them. Bug fix when loading editor sidebar. YouTube Video ID block now embeds the video in the block editor. Changes the way most blocks are built so they each have their own block.json file. This will help directories correctly count how many blocks are provided in the plugin.

= 14.17.1 = 
Helps new users understand where to find the list of vehicles by adding a line to the Listings Pages table on the Settings page for the default post type archive. Detects an empty permalink structure and adds a Site Health test to recommend a change. With default/no permalinks, slash inventory is not added to rewrite rules and the default vehicle archive is /?post_type=inventory_vehicle instead of slash vehicle. Adds an admin notice on the plugin settings page to draw user attention to Site Health when there are recommendations. Bug fix in invp_get_the_last_modified() template tag for support outside the loop. Starts passing the post ID when getting the raw meta value. Bug fix in invp_get_the_carfax_url_report() template tag for support outside the loop. Starts passing the post ID when getting the VIN. Bug fix in invp_get_the_odometer() template tag. Check if the post ID is available from the loop when the passed value is empty or not passed.

= 14.17.0 =
Adds blocks for Down Payment, Make, Model, MSRP, Odometer, Payment, Price, Stock Number, Title Status, Transmission Speeds, Trim Level, VIN, Year, and YouTube Video ID. Adds a block called Year, Make, Model, and Trim that outputs those meta fields in an H1. Fixes a number of blocks that were broken, including Beam, Body Style, Color, Engine, Interior Color, Last Modified, Length, and Odometer. Fixes bugs in handling input and output. Escapes more strings before output. Sanitizes more input values before they are used.

= 14.16.3 =
Fixes a bug in the [invp_archive] shortcode that prevented orderby and order query parameters from changing the order of vehicles. Fixes a bug in the [invp_sort_by] shortcode that prevented it from working on any page other than an inventory_vehicle post type archive. Bug fixes in the REST API class. Stops assuming posts_per_page and paged will be defined in the query params. Changes tested up to version number to 6.6.2.

= 14.16.2 =
Bug fixes in the Add Media and Delete All Media buttons shown in the Classic Editor. Bug fix. Provide default taxonomy settings when a user has never used the Manage Taxonomies button to configure their site. Preserves backwards compatibility to before the setting existed. Changes the [invp_archive] shortcode to allow a location parameter that takes a term slug.

= 14.16.1 =
Fixes the price and odometer range filters not working outside the context of the [invp_archive] shortcode. Fixes a bug in the Avada Builder integration added in 14.16.0 to prevent a warning being logged when processing Text Block layout elements that do not have a meta key configured.

= 14.16.0 =
Adds an integration with Gravity Forms. Provides a Vehicle field type that allows users to add vehicles to leads captured with Gravity Forms. Achieves compatibility with Avada Builder by adding all vehicle photos as Featured Images and changing the way some meta fields display when used in Text Block layout elements. Redesigns the Listings Pages settings table to fit on narrower screens. Adds a warning to Divi users that the feature does not work on Divi.

= 14.15.0 =
Adds a setting Provide Templates at Vehicles > Options. Lets users toggle the provided templates for archive and single vehicles. Adds WS Form to the list of recognized form builders in the Singles Contact Form setting. Bug fix. Fixes the Add Listings Page button in the settings. This button stopped adding a new row to the Listings Pages table in 14.14.0. Edits WP All Import integration to detect semicolon as an option delimiter in addition to pipes and commas.

= 14.14.2 =
Adds a meta noindex nofollow tag to inventory archives that are filtered by 2 or more of our taxonomies. Prevent /inventory/make/toyota/make/lexus/fuel/gas/ from being indexed or followed by bots. Bug fix. Register scripts in the dashboard. The location taxonomy add phone and add hours buttons were broken because the script powering them stopped loading. Bug fix. Restores VIN to vehicle attribute table. VIN was erroneously removed in 14.14.0.

= 14.14.1 =
Bug fix. Restores VIN to vehicle attribute table. VIN was erroneously removed in 14.14.0.

= 14.14.0 =
Adds a setting "Singles Contact Form" at Vehicles → Options to embed a lead form on vehicle single pages. Supports Contact Form 7, Gravity Forms, and WP Forms. Adds "make" and "model" attributes to the Inventory Grid and Inventory Slider. Changes the "Show All Taxonomies" option to a "Manage Taxonomies" button that leads to another options page. Users can now toggle which taxonomies are active, show in the admin menu, and display in editors for each vehicle type. This makes the plugin much more friendly to boat dealers and anyone managing vehicles that are not passenger cars. Adds better support for the Classic Editor. Adds more boat fields for condition, draft, number of engines, engine make, engine model, and horsepower. Adds template tags for boats including invp_is_boat(), invp_get_the_condition_boat(), invp_get_the_draft(), invp_get_the_engine_count(), invp_get_the_engine_make(), invp_get_the_engine_model(), and invp_get_the_horsepower(). Stops showing an admin notice on this plugin's settings page. Adds the information to the Site Health page under the Tools menu instead. Makes more strings translateable, including default boat styles and hull materials. Bug fix. Prevent invp_get_the_photos() from returning a list of urls containing empty strings. Vehicles with broken images would prevent the flexslider from starting on details pages. Stops removing the odometer field from the vehicle attribute table for boats. Changes the fields for boats in the attribute table shortcode, and therefore the default archive and single templates.

= 14.13.0 =
Updates WP All Import integration to detect comma-delimited options. Previously, only pipe-delimited options were split into the meta field during imports. Fixes a bug in the "hours today" sentence. Stops using the PHP function jddayofweek().

= 14.12.7 =
Fixes a bug introduced in 14.12.6 that broke this plugin's Block Editor sidebar.

= 14.12.6 =
Fixes a bug that caused an intermittent error in the Block editor "Updating failed. The response is not a valid JSON response." Stops showing the Vehicles admin bar item to logged in users who cannot edit posts. Adds an ID parameter to the [invp_photo_slider] shortcode so it can be used on any page. Fixes bugs in the [invp_inventory_slider] shortcode so it operates more closely like the widget. Adds a showcount parameter. Fixes a bug that redirected 404 vehicle requests in the dashboard to the front end error page instead of the empty posts list. Changes tested up to version to 6.5.2.

= 14.12.5 =
Adds support for marking vehicles "Sale pending". Adds a term to the Availability taxonomy during plugin activation. Adds a template tag invp_is_pending(). Shows "Sale pending" instead of any price. Removes valid html title opening tags from readme.txt and changelog.txt. These files can more easily be more easily embedded in web pages. Changes tested up to version to 6.5.0.

= 14.12.4 =
Optimizes the way sliders are resized when the browser window is resized. Fixes a bug when prefixing taxonomy term URLs with /inventory to avoid extraneous database queries. This was breaking the menu administration page. Updates the install instructions in readme.txt.

= 14.12.3 =
Fixes a CSS bug in the inventory grid shortcode and widget. Fixes a bug causing duplicate search results in some themes since 14.12.2.

= 14.12.2 =
Fixes a bug in the photo rearranger Gallery Block when comparing photo numbers. Fixes a bug when saving vehicles to prevent duplicate meta values for meta registered as singles. Fixes a bug where all search queries sitewide were joined to the taxonomy term tables. Vehicle data in terms is mirrored in post meta, so this was no longer necessary. Fixes a JavaScript bug when loading photo sliders on vehicle singles where the vehicle only has one photo and a carousel is not displayed. Stops adding a querystring containing the photo has to photo link URLs.

= 14.12.1 =
Fixes a bug introduced in 14.12.0 in the Grid widget when Show Captions is enabled.

= 14.12.0 =
Adds an API method INVP::currency_symbol() and a filter `invp_currency_symbol` to allow the default US dollar sign to be changed. Adds a `show_odometers` parameter to the Grid shortcode and widget.

= 14.11.4 =
Updates demo site URL to https://demo.inventorypresser.com/. Fixes bugs in flexslider spin-up script when used outside single vehicle context. Makes more strings translateable. Changes the tested up to version number 6.3.2.

= 14.11.3 =
Adds a REST endpoint `/wp-json/invp/v1/feed-complete` to help inventory clients run an action hook `invp_feed_complete` after an inventory update has completed. Adds a filter `invp_query_limit` around the number 1000 where it is used as the maximum posts_per_page argument in post queries. Prevent prev and next buttons in flexslider carousels from covering the width of an entire thumbnail. Fixes in flexslider spin up script for slow-loading pages. Prevent YouTube embeds from interfering with photo sliders. Reverses a change in 14.11.2 and uses the `enqueue_block_editor_assets` again to avoid front-end JavaScript errors. Prevents scripts and styles from being registered more than once. Prevents the Load Sample Vehicles button from inserting duplicate VINs. Enables the Load Sample Vehicles button to insert more than 10 vehicles. Stops telling dashboard users when the aspect ratio of thumbnails is not that of a common smartphone camera. Allows a location term street address to be entered on the add term form instead of requiring users to save a location term first.

= 14.11.2 =
Compatibility fixes for WordPress 6.3. Stops using the `enqueue_block_editor_assets` hook in favor of the new `enqueue_block_assets`. Stops using the `post__not_in` query variable on the `parse_query` hook. Compatibility fixes for PHP 8.1. Stop sending null to the first parameter of strpos(). Stop sending a non-string file name to wp_get_image_mime(). Prevent queries from returning an unlimited number of posts. Caps queries for vehicle photos to 1000. Fixes the Grid Widget to obey the "Newest vehicles first" setting. Fixes in the "down & payment" price display option that prevented down payments and payment frequencies from displaying. Fixes in database queries to always use the proper table prefix.

= 14.11.1 =
Reduces the number of database queries required when adding cache-busting querystrings to photo URLs. Adds caching in invp_get_the_photos() for 5 minutes to reduce database queries. Makes sure invp_get_the_photos() always populates URLs regardless of what sizes are requested. Bug fix in INVP::sluggify(). Do not allow a string that starts with a symbol to create a slug that starts with a hyphen.

= 14.11.0 =
Adds a fullscreen mode to flexsliders so large vehicle photos can bust out of their containers when tapped by users. Cleans up flexslider JavaScript to use much less jQuery and wait for slow images to load before attempting resizes. Fixes a bug where paging was broken for Listings Pages defined at Vehicles → Options. Makes more strings translateable, adds more taxonomy term labels so the Block Editor stops showing "category" instead of "make", "model", etc. Allows more than 30 meta keys in the Custom Fields editor panel when editing vehicles. Adds a Contact Form 7 mail tag [invp_adf_vendor] to integrate the location taxonomy into ADF XML leads. More details at https://inventorypresser.com/docs/contact-form-7-integration/how-to-send-adf-xml-leads-using-contact-form-7/. Fixes a CSS issue that caused the Annual fuel consumption line to overlap the combined, city, and highway figures in the Fuel Economy widget.

= 14.10.2 =
Adds a template tag `invp_get_the_condition()` that returns "Used", "New", or empty string. Adds an API method `INVP::get_paging_html()` that previously lived inside the [invp_archive] shortcode class. Creates better pagination HTML for listings archives. Fixes a bug in the Block Editor sidebar. Stops attempting to save empty strings in numeric meta fields when their text boxes are emptied. Makes more strings translateable. Stops lying about whether vehicle posts have thumbnails unless our archive shortcodes are run or the Divi Blog Module is detected. The lie helps us work on most themes by avoiding the theme and our shortcode from both outputing a thumbnail. There's no reason to do this unless the shortcode is used.

= 14.10.1 =
Fixes a bug introduced in 14.10.0 that prevented inventory archives from being sorted according to the saved setting.

= 14.10.0 =
Adds a WPForms integration. Adds a Vehicle field type. This field will produce a dropdown on forms not embedded on vehicle singles. On vehicle singles, a hidden input will identify the vehicle the user is looking at. Adds two Smart Tags {invp_adf_vehicle} and {invp_site_url} to make creating ADF XML lead emails easy when following [these instructions](https://inventorypresser.com/docs/capturing-vehicle-leads-with-wpforms/). Adds a parameter to the [invp_inventory_grid] shortcode `priced_first` to put vehicles with prices first. Adds the list of taxonomy links to the settings page near the checkbox to toggle them in the menu. Performance improvements by avoiding several get_option() calls until they are necesary. Removes a call to load_plugin_textdomain() because it is obsolete. Stop warning users about thumbnail size aspect ratios if they are not 4:3. 16:9 is the new mainstream ratio, so check for either. Fixes CSS in the Listings Pages table of the settings page. Stops loading Classic Editor features when the Classic Editor plugin is not active and other dashboard performance improvements around not loading features until they are necessary. Adds support for SCRIPT_DEBUG in all script and style registrations. Adds plugin version number to script and style registrations so they can be cached appropriately. Changes link labels in the dashboard when editing taxonomy terms for specificity like "model years" instead of "tags".

= 14.9.0 =
Adds a function invp_get_the_inventory_sentence() that returns an HTML string containing links like, "Browse Car, SUV, Truck, or all 10 vehicles for sale." Wraps the "/" that separates prices and down payments with a filter `invp_price_display_separator` so it can be changed by other developers. Adds a parameter to the [invp_sort_by label=""] shortcode `label` to let users customize or remove the label text next to the dropdown. Enables the JavaScript filter `invp_editor_sidebar_elements` that was disabled in 14.8.0. The filter was not the problem. Fixes a bug in the [invp_sort_by] shortcode that prevented any sort from happening when a choice was made in the dropdown. JavaScript was missing from this plugin that powers the feature.

= 14.7.1 =
Adds postmeta fields `inventory_presser_rate` and `inventory_presser_term` to the vehicle post type for dealers outside the USA where displaying financing terms without a disclosure may be legal. Adds a "No filter" option to the Additional Listings Pages feature to allow the creation of full, unfiltered archive pages at any URL. Adds a toggle switch to Additional Listings Pages so rules can be saved in an inactive state. Adds a "contains" comparison operator and "Options" to the list of comparison fields in Additional Listings Pages filter rules. Adds a parameter to the [invp_inventory_grid] shortcode to suppress "Call for price" if the sitewide price display setting is ${Price}. This restores behavior produced by a bug described below. Fixes a bug in the [invp_inventory_grid] shortcode that prevented "Call for price" from displaying if show_captions and show_prices were both true and the price display setting sitewide is ${Price}. Fixes syntax errors that impact old versions of PHP including 7.2. Fixes bugs in multiple shortcodes that prevented true/false parameter values from working the way users expect. "true", "1", and 1 are all now working. Likewise with false values "false", "0", and 0. Renames the Additional Listings Pages feature to Listings Pages.

= 14.6.0 =
Stops showing sold vehicles in the [invp_inventory_grid] shortcode and Inventory Grid widget when the "Include sold vehicles" setting is off. Stops hitting inventorypresser.com as often to check for valid add-on licenses. Fixes a bug that showed the Boat Attributes field in the Editor Sidebar for non-boat vehicles. Adds a feature that changes the &lt;title&gt; tag on inventory archives. Adds a JavaScript filter `invp_editor_sidebar_elements` to let other plugins add to the Editor Sidebar where vehicle attributes are managed. Adds additional meta fields for Carfax "Accident Free" and "Top Condition" badges. Adds a filter `invp_meta_fields` so other plugins can add meta fields to the vehicle post type.

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
Adds template tags `invp_get_the_last_modified()` and `invp_get_raw_last_modified()`. Registers all scripts and styles on the `enqueue_block_editor_assets` hook so blocks can use them. Adds & implements iFrameResizer.js in the [invp_iframe] shortcode. Bug fix when looking for an SVG file path in the Carfax Widget. Bug fix in the `invp_get_the_transmission_speeds()` template tag. The single `$post_id` argument is now optional, matching all other template tags.

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
