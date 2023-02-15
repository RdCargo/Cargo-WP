<?php

// If uninstall not called from WordPress, then exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

/**
 * Uninstall operations
 */
function single_uninstall() {
	$options_to_delete = array(
		'shipping_api_username',
		'shipping_api_pwd',
		'shipping_api_int1',
		'cargo_order_status',
		'cargo_consumer_key',
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
	single_uninstall();
} else {
	single_uninstall();
}
