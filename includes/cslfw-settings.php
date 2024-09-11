<?php
/**
 * Main Settings
 *
 */

use CSLFW\Includes\CSLFW_Helpers;

if ( ! defined( 'ABSPATH' ) ) exit;

if ( class_exists( 'CSLFW_Settings', false ) ) {
    return new CSLFW_Settings();
}

if( !class_exists('CSLFW_Settings') ) {
    class CSLFW_Settings
    {
        public $helpers;

        function __construct()
        {
            $this->helpers = new CSLFW_Helpers();

            add_action('admin_init', [$this, 'cslfw_shipping_api_settings_init']);
            add_filter('plugin_action_links_' . plugin_basename( __FILE__ ), [$this,  'cargo_settings_link']);

            if ( is_admin() ) {
                register_activation_hook(__FILE__, [$this, 'activate']);

                register_deactivation_hook(__FILE__, [$this, 'cslfw_deactivate']);
                // plugin uninstallation
                register_uninstall_hook(__FILE__, 'cslfw_uninstall');
            }
        }

        public function settings(){
            $this->helpers->check_woo();
            $this->helpers->load_template('settings');
        }

        public function cslfw_shipping_api_settings_init() {
            register_setting('cslfw_shipping_api_settings_fg', 'cargo_order_status');
            register_setting('cslfw_shipping_api_settings_fg', 'cslfw_google_api_key');
            register_setting('cslfw_shipping_api_settings_fg', 'cslfw_map_size');
            register_setting('cslfw_shipping_api_settings_fg', 'cslfw_cod_check');
            register_setting('cslfw_shipping_api_settings_fg', 'cslfw_debug_mode');
            register_setting('cslfw_shipping_api_settings_fg', 'cslfw_shipping_methods_all');
            register_setting('cslfw_shipping_api_settings_fg', 'cslfw_auto_shipment_create');
            register_setting('cslfw_shipping_api_settings_fg', 'cslfw_fulfill_all');
            register_setting('cslfw_shipping_api_settings_fg', 'cslfw_complete_orders');
            register_setting('cslfw_shipping_api_settings_fg', 'cslfw_custom_map_size');
            register_setting('cslfw_shipping_api_settings_fg', 'shipping_cargo_express');
            register_setting('cslfw_shipping_api_settings_fg', 'shipping_cargo_box');
            register_setting('cslfw_shipping_api_settings_fg', 'shipping_pickup_code');
            register_setting('cslfw_shipping_api_settings_fg', 'from_street');
            register_setting('cslfw_shipping_api_settings_fg', 'from_street_name');
            register_setting('cslfw_shipping_api_settings_fg', 'from_city');
            register_setting('cslfw_shipping_api_settings_fg', 'phonenumber_from');
            register_setting('cslfw_shipping_api_settings_fg', 'website_name_cargo');
            register_setting('cslfw_shipping_api_settings_fg', 'cslfw_box_info_email');
            register_setting('cslfw_shipping_api_settings_fg', 'bootstrap_enalble');
            register_setting('cslfw_shipping_api_settings_fg', 'cargo_box_style');
            register_setting('cslfw_shipping_api_settings_fg', 'disable_order_status');
            register_setting('cslfw_shipping_api_settings_fg', 'cslfw_shipping_methods');
            register_setting('cslfw_shipping_api_settings_fg', 'cslfw_products_in_label');
        }

        public function cslfw_uninstall() {
            //flush permalinks
            flush_rewrite_rules();
            delete_option('cargo_order_status');
            delete_option('cslfw_google_api_key');
            delete_option('cslfw_map_size');
            delete_option('cslfw_cod_check');
            delete_option('cslfw_debug_mode');
            delete_option('cslfw_shipping_methods_all');
            delete_option('cslfw_auto_shipment_create');
            delete_option('cslfw_fulfill_all');
            delete_option('cslfw_complete_orders');
            delete_option('cslfw_custom_map_size');
            delete_option('shipping_cargo_express');
            delete_option('shipping_cargo_box');
            delete_option('shipping_pickup_code');
            delete_option('website_name_cargo');
            delete_option('cslfw_box_info_email');
            delete_option('bootstrap_enalble');
            delete_option('cargo_box_style');
            delete_option('disable_order_status');
            delete_option('cslfw_shipping_methods');
            delete_option('cslfw_products_in_label');
        }

        public function cargo_settings_link( $links_array ) {
            array_unshift( $links_array, '<a href="' . admin_url( 'admin.php?page=loaction_api_settings' ) . '">' . esc_html_e('Settings') . '</a>' );
            return $links_array;
        }

        public function activate() {
            if( class_exists( 'Awsb_Express_Shipping' ) ) {
                error_log( 'You can Only use only one Plugin Cargo Shipping Location Or Cargo Express Shipping Location' );
                $args = var_export( func_get_args(), true );
                error_log( $args );
                wp_die( 'You can Only use only one Plugin Cargo Shipping Location Or Cargo Express Shipping Location' );
            }
            //flush permalinks
            flush_rewrite_rules();
        }
        public function cslfw_deactivate() {
            //flush permalinks
            flush_rewrite_rules();
        }

    }
}

$settings = new CSLFW_Settings();

