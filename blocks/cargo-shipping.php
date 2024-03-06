<?php
/**
 * Plugin Name:     Shipping Workshop
 * Version:         1.0
 * Author:          Thomas Roberts and Niels Lange
 * License:         GPL-2.0-or-later
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:     cargo-shipping
 *
 * @package         create-block
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Define SHIPPING_WORKSHOP_VERSION.
$plugin_data = get_file_data( __FILE__, array( 'version' => 'version' ) );
define( 'SHIPPING_WORKSHOP_VERSION', $plugin_data['version'] );

/**
 * Include the dependencies needed to instantiate the block.
 */
add_action('woocommerce_blocks_loaded', function() {
	require_once CSLFW_PATH . 'includes/CargoApi/Helpers.php';
	require_once CSLFW_PATH . 'includes/CargoApi/Cargo.php';
    require_once __DIR__ . '/cargo-shipping-blocks-integration.php';
	add_action(
		'woocommerce_blocks_checkout_block_registration',
		function( $integration_registry ) {
			$integration_registry->register( new Cargo_Shipping_Blocks_Integration() );
		}
	);
});




/**
 * Registers the slug as a block category with WordPress.
 */
function register_Cargo_Shipping_block_category( $categories ) {
    return array_merge(
        $categories,
        [
            [
                'slug'  => 'cargo-shipping',
                'title' => esc_html__( 'Cargo_Shipping Blocks', 'cargo-shipping' ),
            ],
        ]
    );
}
add_action( 'block_categories_all', 'register_Cargo_Shipping_block_category', 10, 2 );
