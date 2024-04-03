<?php
/**
 * Helper functions
 *
 */

namespace CSLFW\Includes;
use Automattic\WooCommerce\Utilities\OrderUtil;

class CSLFW_Helpers {
    public function check_woo() {
        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        if (! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
            echo wp_kses_post('<div class="error"><p><strong>Cargo Shipping Location API requires WooCommerce to be installed and active. You can download <a href="https://woocommerce.com/" target="_blank">WooCommerce</a> here.</strong></p></div>');
            die();
        }
    }

    public static function HPOS_enabled() {
        /* HPOS_enabled - flag for data from db, where hpos is enabled or not */
        return class_exists( 'Automattic\WooCommerce\Utilities\OrderUtil' ) &&
            OrderUtil::custom_orders_table_usage_is_enabled();
    }


    public function load_template( $templateName = '', $data = null ) {
        if( $templateName != '' ){
            include CSLFW_PATH . 'templates/'.$templateName.'.php';
        }
    }

    function cargoAPI($url, $data = []) {
        $args = [
            'method'      => 'POST',
            'timeout'     => 45,
            'httpversion' => '1.1',
            'blocking'    => true,
            'headers' => [
                'Content-Type: application/json',
            ],
        ];
        if ( $data ) $args['body'] = wp_json_encode($data);
        $response   = wp_remote_post($url, $args);
        $response   = wp_remote_retrieve_body($response) or die("Error: Cannot create object. <pre>" . $args['body']);
        return json_decode( $response );
    }

    function astra_wc_get_orders($args)
    {
        if ($this->HPOS_enabled()) {
            $orders = wc_get_orders($args);
        } else {
            $addArgs = array_merge($args, [
                'post_type' => 'shop_order',
                'post_status'       => 'any',
                'fields' => 'ids'
            ]);
            $query = new \WP_Query( $addArgs );
            $post_ids = $query->get_posts();

            $orders = wc_get_orders([
                'post__in' => $post_ids,
                'posts_per_page' => $args['posts_per_page'] ?? 10,
                'paged' => $args['pages'] ?? 1,
            ]);
        }

        return $orders;
    }
}


