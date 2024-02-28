<?php
/**
 * Helper functions
 *
 */
if( !class_exists('CSLFW_Helpers') ) {
    class CSLFW_Helpers {
        public function check_woo() {
            include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            if (! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
                echo '<div class="error"><p><strong>Cargo Shipping Location API requires WooCommerce to be installed and active. You can download <a href="https://woocommerce.com/" target="_blank">WooCommerce</a> here.</strong></p></div>';
                die();
            }
        }

        public function load_template( $templateName = '', $data = null ) {
            if( $templateName != '' ){
                include CSLFW_PATH . 'templates/'.$templateName.'.php';
            }
        }

        function cargoAPI($url, $data = []) {
            $args = array(
                'method'      => 'POST',
                'timeout'     => 45,
                'httpversion' => '1.1',
                'blocking'    => true,
                'headers' => array(
                    'Content-Type: application/json',
                ),
            );
            if ( $data ) $args['body'] = json_encode($data);
            $response   = wp_remote_post($url, $args);
            $response   = wp_remote_retrieve_body($response) or die("Error: Cannot create object. <pre>" . $args['body']);
            return json_decode( $response );
        }
    }
}

