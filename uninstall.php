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
		'cargo_consumer_secret_key',
		'cargo_google_api_key',
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
