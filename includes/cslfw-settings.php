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
            $fields = [

                // 📝 Text fields
                'cargo_order_status'        => 'sanitize_text_field',
                'cslfw_google_api_key'      => 'sanitize_text_field',
                'from_street'               => 'sanitize_text_field',
                'from_street_name'          => 'sanitize_text_field',
                'from_city'                 => 'sanitize_text_field',
                'website_name_cargo'        => 'sanitize_text_field',
                'cslfw_box_info_email'      => 'sanitize_email',
                'phonenumber_from'          => 'sanitize_text_field',
                'cslfw_map_size'            => 'sanitize_text_field',
                'cslfw_custom_map_size'     => 'sanitize_text_field',
                'cslfw_cod_check'           => 'sanitize_text_field',
                'shipping_cargo_express'    => 'sanitize_text_field',
                'shipping_cargo_express_24' => 'sanitize_text_field',
                'shipping_cargo_box'        => 'sanitize_text_field',
                'shipping_pickup_code'      => 'sanitize_text_field',

                // ✅ Checkboxes / booleans
                'cslfw_debug_mode'          => [$this, 'sanitize_checkbox'],
                'cslfw_shipping_methods_all'=> [$this, 'sanitize_checkbox'],
                'cslfw_auto_shipment_create'=> [$this, 'sanitize_checkbox'],
                'cslfw_fulfill_all'         => [$this, 'sanitize_checkbox'],
                'cslfw_complete_orders'     => [$this, 'sanitize_checkbox'],
                'bootstrap_enalble'         => [$this, 'sanitize_checkbox'],
                'cslfw_products_in_label'   => [$this, 'sanitize_checkbox'],
                'cslfw_queued_bulk_labels'  => [$this, 'sanitize_checkbox'],
                'disable_order_status'      => [$this, 'sanitize_checkbox'],

                // 🎨 Style / select fields
                'cargo_box_style'           => 'sanitize_text_field',

                // 📦 Arrays (multi-select / complex settings)
                'cslfw_shipping_methods'    => [$this, 'sanitize_array'],
                'cslfw_bulk_actions'        => [$this, 'sanitize_array'],
            ];


            foreach ($fields as $field => $callback) {
                register_setting('cslfw_shipping_api_settings_fg', $field, [
                    'sanitize_callback' => $callback,
                ]);
            }
        }

        public function sanitize_checkbox($value) {
            return $value ? 1 : 0;
        }

        public function sanitize_array($value) {
            return is_array($value)
                ? array_map('sanitize_text_field', $value)
                : [];
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
            delete_option('shipping_cargo_express_24');
            delete_option('shipping_cargo_box');
            delete_option('shipping_pickup_code');
            delete_option('website_name_cargo');
            delete_option('cslfw_box_info_email');
            delete_option('bootstrap_enalble');
            delete_option('cargo_box_style');
            delete_option('disable_order_status');
            delete_option('cslfw_shipping_methods');
            delete_option('cslfw_bulk_actions');
            delete_option('cslfw_products_in_label');
            delete_option('cslfw_queued_bulk_labels');
        }

        public function cargo_settings_link( $links_array ) {
            array_unshift( $links_array, '<a href="' . admin_url( 'admin.php?page=loaction_api_settings' ) . '">' . esc_html_e('Settings', 'cargo-shipping-location-for-woocommerce') . '</a>' );
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

