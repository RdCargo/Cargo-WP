<?php
/**
 * Helper functions
 *
 */
if( !class_exists('CSLFW_Helpers') ) {
    class CSLFW_Helpers {
        public function checkWooCommerce() {
            include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            if (! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
                echo '<div class="error"><p><strong>Cargo Shipping Location API requires WooCommerce to be installed and active. You can download <a href="https://woocommerce.com/" target="_blank">WooCommerce</a> here.</strong></p></div>';
                die();
            }
        }
        public function loadTemplate($templateName = ''){
            if( $templateName != '' ){
                require_once CSLFW_PATH . 'templates/'.$templateName.'.php';
            }
        }
    }
}

