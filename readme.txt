=== Cargo Shipping Location for WooCommerce ===
Contributors: Astraverdes
Tags: woo-commerce, woocommerce, delivery, shipment, cargo
Requires at least: 5.0.0
Tested up to: 6.6.1
Requires PHP: 7.4
Stable tag: 5.1.0
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

== 4.0.11 ===
* Change script to prevent default styles for map.

== 4.1 ===
* Remove shipment methods for cases when settings are not completed.
* Fix map display.

== 4.1.1 ===
* Quickfix of fatal error in checkout.

== 4.2.0 ===
* Fixed some HPOS problems, add table with shipments, with search, pagination, and ability to print shipments in bulk, with a4 format.
* Improve map with customer pin
* Update to print labels with products.
* Fix pickup shipments.

== 4.2.1 ===
* Fixed weight limit.

== 4.2.2 ==
* Fix Cash on delivery.
* Fix status webhooks.

== 4.2.3 ==
* Fix Webhooks Bugs

== 4.2.4 ==
* Fix Shipments page. added search by phone number.

== 4.2.5 ==
* Fix settings page box settings.
* Fix box points when switching from express to box.

== 4.2.6 ==
* Fix checkout box point validation.

== 4.2.7 ==
* Map fixes

== 4.2.8 ==
* Fix order status complete when shipment is completed.

== 4.2.9 ==
* Fix checkout dropdowns.

== 4.2.10 ==
* Fix issue with duplicate shipments create.
* Add cslfw_change_recipient_phone hook to modify recipient phone number.

== 5.0 ==
* Added new cargo api support with api tokens. Much faster, much accurate, much secured.
* Added action scheduler for creating bulk shipments in a background.
* Add Action Scheduler and Make queues for bulk shipment create
* Improved debug logging
* Cancel shipment feature.

== 5.0.1 ==
* Add progress bar to bulk shipments with auto refresh

== 5.0.2 ==
* Fix progress bar issue.

== 5.0.3 ==
* fix cash on delivery type
* fix pickup shipments

== 5.0.4 ==
* bulk print labels in a new tab
* bulk shipment create with applied cash on delivery settings

== 5.0.5 ==
* fix cargo send button in orders summary page to account cash on delivery settings

== 5.0.6 ==
* fix bulk print labels in a new tab with transient

== 5.1.0 ==
* Fix deprecated issue on latest php versions
* Fix phone number for boxes

== 5.1.1 ==
* Add filter for from address name and company
* Fix webhook removal
