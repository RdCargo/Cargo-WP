<?php

// If uninstall not called from WordPress, then exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

if (!defined('ABSPATH')) exit;


/**
 * Uninstall operations
 */
function cslfw_single_uninstall() {
	$options_to_delete = array(
        'cargo_order_status',
        'cslfw_google_api_key',
        'cslfw_map_size',
        'cslfw_cod_check',
        'cslfw_debug_mode',
        'cslfw_shipping_methods_all',
        'cslfw_auto_shipment_create',
        'cslfw_fulfill_all',
        'cslfw_complete_orders',
        'cslfw_custom_map_size',
        'shipping_cargo_express',
        'shipping_cargo_express_24',
        'shipping_cargo_box',
        'shipping_pickup_code',
        'from_street',
        'from_street_name',
        'from_city',
        'phonenumber_from',
        'website_name_cargo',
        'cslfw_box_info_email',
        'bootstrap_enalble',
        'cargo_box_style',
        'disable_order_status',
        'cslfw_shipping_methods',
        'cslfw_products_in_label'
	);

	foreach ( $options_to_delete as $option ) {
		delete_option( $option );
	}

	// delete dismissed notices meta key
}

// Let's do it!
if ( is_multisite() ) {
	cslfw_single_uninstall();
} else {
	cslfw_single_uninstall();
}
