<?php
/**
 * Contact class
 *
 */

use CSLFW\Includes\CSLFW_Helpers;

if ( ! defined( 'ABSPATH' ) ) exit;

if ( class_exists( 'CSLFW_Contact', false ) ) {
    return new CSLFW_Contact();
}

if( !class_exists('CSLFW_Contact') ) {
    class CSLFW_Contact
    {
        /**
         * @var CSLFW_Helpers
         */
        private $helpers;

        /**
         * CSLFW_Contact constructor.
         */
        function __construct()
        {
            $this->helpers = new CSLFW_Helpers();
            add_action('admin_menu', [$this, 'add_menu_link'], 100);
            add_action('wp_ajax_cslfw_send_email', [$this, 'send_email']);
            add_action('admin_enqueue_scripts', [$this, 'import_assets'] );

        }

        public function import_assets() {
            wp_enqueue_script( 'cargo-contact', CSLFW_URL . 'assets/js/cslfw-contact.js', ['jquery'], CSLFW_VERSION, true);
        }

        public function render() {
            $this->helpers->load_template('contact');
        }

        function add_menu_link() {
            add_submenu_page('loaction_api_settings', 'Contact Us', 'Contact Us', 'manage_options', 'cargo_shipping_contact', [$this, 'render'] );
        }

        function send_email() {
            parse_str(sanitize_text_field($_POST['form_data']), $data);
            if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field($_POST['_wpnonce']), "cslfw-sent-email")) {
                echo wp_json_encode([
                    'error' => true,
                    'message' => 'Bad request, try again later.',
                ]);
                wp_die();
            }

            $site_url   = get_site_url();
            $to         = 'rd@cargo.co.il';
            $subject    = 'BUG REPORT';
            $body       = "<p><strong>REASON: </strong>{$data['reason']}</p></br>";
            $body      .= "<p><strong>MESSAGE: </strong>{$data['content']}</p></br>";
            $body      .= "<p><strong>URL: </strong>{$site_url}</p></br>";
            $headers    = ['Content-Type: text/html; charset=UTF-8'];
            $headers[]  = "From: CARGO PLUGIN <{$data['email']}>";
            $response   = wp_mail( $to, $subject, $body, $headers );
            if ( $response ) {
                echo wp_json_encode(
                    [
                        'error' => false,
                        'message' => 'Email successfully sent'
                    ]
                );
            } else {
                echo wp_json_encode(
                    [
                        'error' => true,
                        'message' => 'Something went wrong.'
                    ]
                );

            }
            wp_die();
        }
    }

    $contact = new CSLFW_Contact();
}
