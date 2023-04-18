<?php
/**
 * Logs class
 *
 */
if ( class_exists( 'CSLFW_OrdersReindex', false ) ) {
    return new CSLFW_OrdersReindex();
}

if( !class_exists('CSLFW_OrdersReindex') ) {
    class CSLFW_OrdersReindex
    {
        function __construct()
        {
            $this->helpers = new CSLFW_Helpers();
            $this->logs = new CSLFW_Logs();

            add_action( 'wp_ajax_reindex_orders', array( $this, 'reindex_process' ) );
            add_action( 'admin_enqueue_scripts', [$this, 'import_assets'] );
        }

        public function add_menu_link() {
            add_submenu_page('loaction_api_settings', 'Orders Reindex', 'Orders Reindex', 'manage_options', 'cargo_orders_reindex', array($this, 'reindex_page'));
        }

        public function import_assets() {
            wp_enqueue_script( 'cargo-reindex-orders', CSLFW_URL . 'assets/js/cslfw-reindex-orders.js', array('jquery', 'cargo-libs'), '', true);
        }
        public function reindex_page() {
            $this->helpers->check_woo();
            $this->helpers->load_template('old_plugin_reindex');
        }

        public function reindex_process() {
            $args = array(
                'posts_per_page' => -1,
                'meta_key'      => 'cargo_shipping_id', // Postmeta key field
                'meta_compare'  => 'EXISTS', // Possible values are ‘=’, ‘!=’, ‘>’, ‘>=’, ‘<‘, ‘<=’, ‘LIKE’, ‘NOT LIKE’, ‘IN’, ‘NOT IN’, ‘BETWEEN’, ‘NOT BETWEEN’, ‘EXISTS’ (only in WP >= 3.5), and ‘NOT EXISTS’ (also only in WP >= 3.5). Values ‘REGEXP’, ‘NOT REGEXP’ and ‘RLIKE’ were added in WordPress 3.7. Default value is ‘=’.
                'return'        => 'ids' // Accepts a string: 'ids' or 'objects'. Default: 'objects'.
            );

            $orders = wc_get_orders( $args );
            $orders_processed = 0;
            $last_order_id = 0;

            try {
                foreach ($orders as $order_id) {
                    $shippings      = get_post_meta($order_id, 'cargo_shipping_id', true);
                    $new_shippings  = get_post_meta($order_id, 'cslfw_shipping', true);
                    $new_deliveries = [];
                    if ( $shippings && !$new_shippings ) {
                        $driver_name    = get_post_meta($order_id, 'drivername'. true);
                        $line_number    = get_post_meta($order_id, 'lineNumber'. true);
                        $customer_code  = get_post_meta($order_id, 'customerCode'. true);
                        $status_number  = get_post_meta($order_id, 'get_status_cargo', true) ?? 1;
                        $status_text    = get_post_meta($order_id, 'get_status_cargo_text', true) ?? 'Open';

                        if ( is_array($shippings) ) {
                            foreach ($shippings as $shipping_id) {
                                $new_deliveries[$shipping_id] = array(
                                    'driver_name'   => $driver_name,
                                    'line_number'   => $line_number,
                                    'customer_code' => $customer_code,
                                    'status'        => array(
                                        'number'    =>  $status_number,
                                        'text'      =>  $status_text,
                                    )
                                );

                                if ( get_post_meta($order_id, 'cargo_DistributionPointID') ) {
                                    $new_deliveries[$shipping_id]['box_id'] = get_post_meta($order_id, 'cargo_DistributionPointID');
                                }
                            }
                        } else {
                            $new_deliveries[$shippings] = array(
                                'driver_name'   => $driver_name,
                                'line_number'   => $line_number,
                                'customer_code' => $customer_code,
                                'status'        => array(
                                    'number'    =>  $status_number,
                                    'text'      =>  $status_text,
                                )
                            );

                            if ( get_post_meta($order_id, 'cargo_DistributionPointID') ) {
                                $new_deliveries[$shippings]['box_id'] = get_post_meta($order_id, 'cargo_DistributionPointID');
                            }
                        }

                        if ( update_post_meta( $order_id, 'cslfw_shipping', $new_deliveries ) ) {
                            $orders_processed++;
                            $last_order_id = $order_id;
                            delete_post_meta( $order_id, 'cargo_DistributionPointName' );
                            delete_post_meta( $order_id, 'cargo_StreetName' );
                            delete_post_meta( $order_id, 'cargo_StreetNum' );
                            delete_post_meta( $order_id, 'cargo_cargoPhone' );
                            delete_post_meta( $order_id, 'cargo_Comment' );
                            delete_post_meta( $order_id, 'cargoPhone' );
                            delete_post_meta( $order_id, 'cargo_Latitude' );
                            delete_post_meta( $order_id, 'cargo_Longitude' );
                            delete_post_meta( $order_id, 'get_status_cargo' );
                            delete_post_meta( $order_id, 'get_status_cargo_text' );
                            delete_post_meta( $order_id, 'lineNumber' );
                            delete_post_meta( $order_id, 'drivername' );
                            delete_post_meta( $order_id, 'customerCode' );
                            delete_post_meta( $order_id, 'cargo_shipping_id' );
                        }
                    }
                }
                echo json_encode( array('success' => true, 'orders_processed' => $orders_processed) );
            } catch (Throwable $e) {
                $this->logs->add_log_message("ERROR.REINDEX_FAIL: " . $e->getMessage() . PHP_EOL );
                $this->logs->add_log_message("ORDERS PROCESSED " . $orders_processed . PHP_EOL );
                $this->logs->add_log_message("last order id = " . $last_order_id . PHP_EOL );
                echo json_encode(array('success' => false));
            }

            wp_die();
        }
    }
}
