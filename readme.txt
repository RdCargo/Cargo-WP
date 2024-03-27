=== Cargo Shipping Location for WooCommerce ===
Contributors: Astraverdes
Tags: woo-commerce, woocommerce, delivery, shipment, cargo
Requires at least: 5.0.0
Tested up to: 6.4.3
Requires PHP: 7.4
Stable tag: 4.0.9
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

The new plugin for Cargo express & pickups delivery orders from WooCommerce.

== Description ==

Cargo Deliveries and Pickups plugin you can connect your WooCommerce store to Cargo & Cargo BOX. The plugin allows users to create, assign shipments to orders, and receive real-time delivery locations to your user checkout. This helps businesses to streamline their shipping processes and provide better customer success.

Our plugin functions by integrating with a third-party service, CARGO, which specializes in local deliveries management and tracking. This integration allows the plugin to offer features such as creating new shipments, checking the status of existing shipments, and providing real-time updates.

We use [CARGO API](https://cargo11.docs.apiary.io/) in order to integrate woocommerce to cargo. Our [PRIVACY POLICY](https://cargo.co.il/privacy-policy-2/)

== Frequently Asked Questions ==

== Screenshots ==


== Changelog ==
= 2.0 =
* Stable version, with Cargo BOX and Express shipment.


= 2.1.0 =
* Added map customisation.
* fixes for Cargo BOX shipment when using more than 1 package.
* Cash on delivery improvement.
* Printing multiple labels for cargo box.
* Map settings and presets.
* Map Cargo Box search improvement.
* BUGFIXES for Cargo BOX checkout.

= 2.1.1 =
* Added map customisation.

= 2.1.2 =
* Fixes on checkout update.

= 2.1.3 =
* Fixed the automatic detect of cargo box.
* Remove wc_sent-cargo status.
* Removed COD for box shipment.

== 2.2 =
* Contact form for reporting the bugs.
* Bulk shipment with double delivery.
* Ability to create additional shipment for order.
* Added shipping info to an email.

== 2.2.1 =
* Bug fix for shipping location thank you page.

== 2.3 =
* fixes to cities dropdown. (show only cities with the point)
* Disable cargo point details in email settings.
* Bulk Shipment option in cargo summary page.

== 3.0 ==
* Ability to change pickup point from admin.
* Add reindex for moving from plugin versions lower than 3.0 (REQUIRED REINDEX AFTER PLUGIN UPDATE)
* Added filters to edit cargo parameters array.
* Added fulfillment checkbox.
* fix to make the cargo block display in the free shipping and flat rate shipping methods.

== 3.1 ==
* Fixed the cargopoint logic on admin page.
* Added ability to change point for automatic box point choice.

== 3.1.1 ==
* Debug mode (display data on order admin page)
* Make default automatic choice of pickup point by customer address.

== 3.2 ==
* Cargo box fix.

== 3.2.2 ==
* Added cargo send status.
* Option to change status after shipment creat.
* Bugfixes for check status button in order admin page.

== 3.2.3 ==
* Fix the check status button from orders summary page.
* Fix send cargo box from orders summary page.
* Disabled second create shipment from orders summary page.

== 3.2.4 ==
* bugfix to distribution point default
* bugfix to shipping method in order single page.

== 3.3 ==
* bugfix with warning about shipping method.
* Added checkboxes to select which methods should have cargo block. (by default only cargo methods.)

== 3.3.1 ==
* Added variation SKU to shipment labels for fulfillment orders with cargo
* Removed cargo actions for orders with cancelled, pending payment and refunded orders.
* Checkout dropdown flyaway bugfix.

== 3.4 ==
* Update base api url
* added cors policy headers to the request.

== 3.5 ==
* Added settings to complete order on cargo status check if completed.
* Fixed wrong box point id on automatic choice

== 3.5.1 ==
* Fix cargo send button double click.

== 3.6 ==
* Cargo box, changed to_address to be customers address.
* Added weight limit to shipping settings.
* Fixed the Pickup shipment type.
* Fixed cargo box so it takes customers address.

== 3.7 ==
* Update plugin with setting to work for all shipments, even if there is no shipment set.

== 3.7.1 ===
* Added website and platform parameters to request.
* fix for checkout dropdowns.
* fix unused dependency.

== 3.8 ===
* Added Cargo pickup separate customer code.

== 3.8.1 ===
* Restoring the plugin.

== 4.0.0 ===
* Adding support for latest wordpress, woocommerce and HPOS compatibility.
* Added new features, automatic shipment create.

== 4.0.1 ===
* Fix critical error in order page.

== 4.0.2 ===
* Fix the issue with to address phone number.

== 4.0.3 ===
* Quickfixes to support php 7.4

== 4.0.4 ===
* Quickfixes critical error and phone removal from cargo points.

== 4.0.5 ===
* fix status check bug.

== 4.0.6 ===
* Fix shipment status update.

== 4.0.7 ===
* Fix the bug with double shipment create when autoshipment enabled.

== 4.0.8 ===
* Fixed custom fields box. fixed some warnings.

== 4.0.9 ===
* Fix map display.
