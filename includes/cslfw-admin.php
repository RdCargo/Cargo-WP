<?php
/**
 * Admin adjustments.
 *
 */

use CSLFW\Includes\CargoAPI\Cargo;
use CSLFW\Includes\CargoAPI\CSLFW_Order;

if ( class_exists( 'CSLFW_Admin', false ) ) {
    return new CSLFW_Admin();
}
require CSLFW_PATH . '/includes/cslfw-cargo.php';

if( !class_exists('CSLFW_Admin') ) {
    class CSLFW_Admin
    {
        function __construct()
        {
            $this->helpers = new CSLFW_Helpers();
            $this->cargo = new Cargo();

            add_action('admin_enqueue_scripts', [$this, 'import_assets']);

            add_action('init', [$this, 'register_order_status_for_cargo']);
            add_filter('wc_order_statuses', [$this, 'custom_order_status']);
            add_action('add_meta_boxes', [$this, 'add_meta_box']);
            add_action('add_meta_boxes', [$this, 'remove_shop_order_meta_box'], 90 );
            add_action('woocommerce_admin_order_data_after_billing_address', [$this, 'show_shipping_info']);
            add_action('admin_notices', [$this, 'cargo_bulk_action_admin_notice']);
            add_action('woocommerce_shipping_init', [$this,'shipping_method_classes']);

            add_filter('handle_bulk_actions-edit-shop_order', [$this, 'bulk_order_cargo_shipment'], 10, 3);
            add_filter('bulk_actions-edit-shop_order', [$this, 'custom_dropdown_bulk_actions_shop_order'], 20, 1);
            add_filter('woocommerce_shipping_methods', [$this,'cargo_shipping_methods']);

            add_action('manage_shop_order_posts_custom_column', [$this,'add_custom_column_content']);
            add_filter('manage_edit-shop_order_columns', [$this, 'add_order_delivery_status_column_header'], 2000);

            add_action('wp_ajax_cslfw_change_carrier_id', [$this, 'change_carrier_id']);

            // WC for HPOS
            add_action('woocommerce_shop_order_list_table_custom_column', [$this, 'add_custom_column_content'], 10, 2);
            add_filter('woocommerce_shop_order_list_table_columns', [$this, 'add_order_delivery_status_column_header']);

            add_filter('bulk_actions-woocommerce_page_wc-orders', [$this, 'custom_dropdown_bulk_actions_shop_order'], 20, 1);
            add_filter('handle_bulk_actions-woocommerce_page_wc-orders', [$this, 'bulk_order_cargo_shipment'], 10, 2);

        }
        public function import_assets() {
            $screen       = get_current_screen();
            $screen_id    = $screen ? $screen->id : '';


            if( $screen_id === 'toplevel_page_loaction_api_settings' ||
                $screen_id === 'cargo-shipping-location_page_cargo_shipping_contact' ||
                $screen_id === 'cargo-shipping-location_page_cargo_shipping_webhook' ||
                $screen_id === 'cargo-shipping-location_page_cargo_orders_reindex' ) {
                wp_enqueue_script( 'cargo-libs', CSLFW_URL . 'assets/js/libs.js', ['jquery'], CSLFW_VERSION, true);
            }
            wp_enqueue_style( 'admin-baldarp-styles', CSLFW_URL . 'assets/css/admin-baldarp-styles.css' );
            wp_enqueue_script( 'cargo-global', CSLFW_URL . 'assets/js/global.js', ['jquery'], CSLFW_VERSION, true);

            wp_enqueue_script( 'cargo-admin-script', CSLFW_URL .'assets/js/admin/admin-baldarp-script.js', [], CSLFW_VERSION, true);
            wp_localize_script( 'cargo-admin-script', 'admin_cargo_obj',
                [
                    'ajaxurl' => admin_url( 'admin-ajax.php' ),
                    'ajax_nonce'    => wp_create_nonce( 'cslfw_shipping_nonce' ),
                    'path' => CSLFW_URL,
                ]
            );
        }

        public function change_carrier_id()
        {
            $orderId = sanitize_text_field($_POST['orderId']);
            $order = wc_get_order($orderId);

            if ($order) {
                $cargoOrder = new CSLFW_Order($order);
                $shippingMethod  = $cargoOrder->getShippingMethod();


                $newMethod = $shippingMethod === 'woo-baldarp-pickup' ? 'cargo-express' : 'woo-baldarp-pickup';

                $order->update_meta_data('cslfw_shipping_method', $newMethod);
                $order->save_meta_data();
                $order->save();

                echo json_encode([
                    'error' => false,
                    'test' => $newMethod,
                    'order' => $order->get_meta('cslfw_shipping_method'),
                    'message' => 'successfully updated'
                ]);
                wp_die();
            }

            echo json_encode([
                'error' => true,
                'message' => 'Order not found. Contact support.'
            ]);
            wp_die();
        }

        /**
         * Single order cargo view.
         *
         * @param $post
         */
        public function render_meta_box_content( $post ) {
            $order = wc_get_order($post->ID);
            $cargoOrder = new CSLFW_Order($order);
            $shipping_method = $cargoOrder->getShippingMethod();

            $cargo_debug_mode   = get_option('cslfw_debug_mode');
            $cargo_shipping     = new CSLFW_Cargo_Shipping($order->get_id());

            $cslfw_shiping_methods = get_option('cslfw_shipping_methods') ? get_option('cslfw_shipping_methods') : [];

            $orderStatus = $order->get_status();

            if (!in_array($orderStatus, ['cancelled', 'refunded', 'pending']) && $shipping_method) {
                if ($cargo_debug_mode) {

                    var_dump($shipping_method);
                    print_r($cargo_shipping->deliveries);
                }
                if (
                    ($shipping_method === 'cargo-express')
                    || ($shipping_method === 'woo-baldarp-pickup')
                    || in_array($shipping_method, $cslfw_shiping_methods)
                ) {

                    $shipmentData = $cargo_shipping->get_shipment_data();

                    $data = [
                        'shipmentIds' => $cargo_shipping->get_shipment_ids(),
                        'paymentMethodCheck' => get_option('cslfw_cod_check') ?  get_option('cslfw_cod_check') : 'cod',
                        'fulfillAllShipments' => get_option('cslfw_fulfill_all'),
                        'shippingMethod' => $shipping_method,
                        'order' => $order,
                        'shipmentData' => $shipmentData,
                    ];

                    $boxPointId = !empty($shipmentData) ? @end( $shipmentData)['box_id'] : false;
                    $boxPointId = $boxPointId ? $boxPointId : $order->get_meta('cargo_DistributionPointID', true);

                    if ($boxPointId) {
                        $selectedPoint = $this->cargo->findPointById($boxPointId);

                        $data['cities'] = $this->cargo->getPointsCities();
                        $data['selectedPoint'] = $selectedPoint;
                        $data['points'] = $this->cargo->getPointsByCity($selectedPoint?->CityName);
                    }

                    $this->helpers->load_template('admin/shipment', $data);
                } else {
                    esc_html_e('Shipping type is not related to cargo', 'cargo-shipping-location-for-woocommerce');
                }
            }
        }

        /**
         * Add order status for Cargo
         */
        function register_order_status_for_cargo() {
            register_post_status( 'wc-send-cargo', [
                'label'                     => 'Send to CARGO',
                'public'                    => true,
                'exclude_from_search'       => false,
                'show_in_admin_all_list'    => true,
                'show_in_admin_status_list' => true,
                'label_count'               => _n_noop( 'Send to CARGO <span class="count">(%s)</span>', 'Send to CARGO <span class="count">(%s)</span>' )
            ] );
        }

        /**
         * @param $order_statuses
         * @return mixed
         *
         * Add order status in Array
         */
        function custom_order_status( $order_statuses ) {
            $order_statuses['wc-send-cargo'] = _x( 'Send to CARGO', 'Order status', 'cargo-shipping-location-for-woocommerce' );
            return $order_statuses;
        }
        /**
         * @param $post_type
         *
         * Add Meta box in admin order
         */
        public function add_meta_box( $post_type ) {
            global $post, $pagenow, $typenow;
            $orderId = $_GET['id'] ?? $post->ID ?? null;

            if(
                ($post_type === 'woocommerce_page_wc-orders' && $orderId)
                || ('edit.php' === $pagenow || 'post.php' === $pagenow) && 'shop_order' === $typenow
                && is_admin()
            ) {
                $order = wc_get_order($orderId);
                $cargoOrder = new CSLFW_Order($order);
                $shipping_method = $cargoOrder->getShippingMethod();
                $cslfw_shiping_methods = get_option('cslfw_shipping_methods') ? get_option('cslfw_shipping_methods') : [];


                if (!in_array($order->get_status() , ['cancelled', 'refunded', 'pending']) && $shipping_method !== null) {
                    if ( $shipping_method === 'cargo-express'
                        || $shipping_method === 'woo-baldarp-pickup'
                        || in_array($shipping_method, $cslfw_shiping_methods)
                    ) {
                        add_meta_box(
                            'cslfw_cargo_custom_box',
                            '<img src="'.CSLFW_URL."assets/image/howitworks.png".'" alt="Cargo" width="100" style="width:50px;">CARGO',
                            [$this, 'render_meta_box_content'],
                            null,
                            'side',
                            'core'
                        );
                    }
                }
            }
        }

        /**
         * remove meta box from the admin order page
         */
        public function remove_shop_order_meta_box() {
            remove_meta_box( 'postcustom', 'shop_order', 'normal' );
        }

        /**
         * @param $actions
         * @return array
         *
         * Add Order Status to Admin Order list
         */
        function custom_dropdown_bulk_actions_shop_order($actions ){
            $new_actions = [];

            foreach ($actions as $key => $action) {
                $new_actions[$key] = $action;

                if ('mark_processing' === $key) {
                    $new_actions['mark_send-cargo-shipping'] = __( 'Send to CARGO', 'cargo-shipping-location-for-woocommerce' );
                    $new_actions['mark_send-cargo-dd'] = __( 'Send to CARGO with double delivery', 'cargo-shipping-location-for-woocommerce' );
                    $new_actions['mark_send-cargo-pickup'] = __( 'Send Pickup to CARGO', 'cargo-shipping-location-for-woocommerce' );
                    $new_actions['mark_cargo-print-label'] = __( 'Print CARGO labels', 'cargo-shipping-location-for-woocommerce' );
                }
            }

            return $new_actions;
        }

        /**
         * @param $column
         *
         * Add Custom Column in Admin Order List
         */
        public function add_custom_column_content($column, $orderPost = null ) {
            global $post;
            $order = $orderPost ?? wc_get_order($post->ID);

            if (!$order) return;

            $cargoOrder = new CSLFW_Order($order);
            $shipping_method = $cargoOrder->getShippingMethod();
            $cslfw_shiping_methods = get_option('cslfw_shipping_methods') ? get_option('cslfw_shipping_methods') : [];


            if (!in_array($order->get_status() , ['cancelled', 'refunded', 'pending']) || false) {

                if ( ($shipping_method === 'cargo-express')
                    || ($shipping_method === 'woo-baldarp-pickup')
                    || in_array($shipping_method, $cslfw_shiping_methods)
                ) {

                    $cargo_shipping = new CSLFW_Cargo_Shipping($order->get_id());
                    $deliveries = $cargo_shipping->get_shipment_data();
                    $webhook_installed = get_option('cslfw_webhooks_installed');
                    $box_point_id = $order->get_meta('cargo_DistributionPointID', true);
                    if ( 'cslfw_delivery_status' === $column ) {
                        if ( $deliveries ) {
                            foreach ($deliveries as $key => $value) {
                                echo wp_kses_post('<div class=""><p class="cslfw-status status-' . $value['status']['number'] .'">'. $key .' - ' . $value['status']['text'] . '</p></div>');
                                if ($webhook_installed !== 'yes') {
                                    echo wp_kses_post("<a href='#' class='btn btn-success send-status' style='margin-bottom: 5px;' data-id=" . $order->get_id() .  " data-deliveryid='$key'>$key  בדוק מצב הזמנה</a>");
                                }
                            }
                        }
                    }

                    if ( 'send_to_cargo' === $column ) {
                        if ( $deliveries ) {
                            $deliveries = implode(',', $cargo_shipping->get_shipment_ids()) ;
                            echo wp_kses_post("<p>". $deliveries . "</p>");
                            echo wp_kses_post('<a  href="#" class="btn btn-success label-cargo-shipping" data-order-id="'.$order->get_id().'">הדפס תווית</a>');
                        } else {
                            if ( $box_point_id ) {
                                echo wp_kses_post("<a href='#' class='btn btn-success submit-cargo-shipping' data-box-point-id='$box_point_id' data-id=".$order->get_id()." >שלח  לCARGO</a>");
                            } else {
                                echo wp_kses_post("<a href='#' class='btn btn-success submit-cargo-shipping' data-id=".$order->get_id()." >שלח  לCARGO</a>");
                            }
                        }
                    }
                }
            }
        }

        /**
         * Show Shipping Info in Admin Edit Order
         *
         * @param $order
         */
        function show_shipping_info($order ) {
            $cargo_shipping = new CSLFW_Cargo_Shipping($order->get_id());
            $deliveries = $cargo_shipping->get_shipment_ids();

            if ( ! empty($deliveries) ) {
                $deliveries = is_array( $deliveries ) ? implode(',', $deliveries) : $deliveries;
                echo wp_kses_post('<p><strong>'.__('מזהה משלוח', 'cargo-shipping-location-for-woocommerce').':</strong> ' . $deliveries . '</p>');
            }
        }

        /**
         * @param $columns
         * @return array
         */
        public function add_order_delivery_status_column_header($columns) {
            $new_columns = [];

            foreach ( $columns as $column_name => $column_info ) {
                $new_columns[ $column_name ] = $column_info;

                if ( 'order_status' === $column_name ) {
                    $new_columns['send_to_cargo'] = __( 'שלח משלוח לCARGO', 'cargo-shipping-location-for-woocommerce' );
                    $new_columns['cslfw_delivery_status'] = __( 'סטטוס משלוח', 'cargo-shipping-location-for-woocommerce' );
                }
            }

            return $new_columns;
        }

        /**
         * Add Bulk Action in admin panel.
         */
        function cargo_bulk_action_admin_notice() {
            global $pagenow;
            if ( 'edit.php' === $pagenow ) {
                $args = [
                    'posts_per_page' => -1,
                    'meta_key'      => 'cargo_shipping_id', // Postmeta key field
                    'meta_value'    => ['', '0'],
                    'meta_compare'  => 'NOT IN', // Possible values are ‘=’, ‘!=’, ‘>’, ‘>=’, ‘<‘, ‘<=’, ‘LIKE’, ‘NOT LIKE’, ‘IN’, ‘NOT IN’, ‘BETWEEN’, ‘NOT BETWEEN’, ‘EXISTS’ (only in WP >= 3.5), and ‘NOT EXISTS’ (also only in WP >= 3.5). Values ‘REGEXP’, ‘NOT REGEXP’ and ‘RLIKE’ were added in WordPress 3.7. Default value is ‘=’.
                    'return'        => 'ids' // Accepts a string: 'ids' or 'objects'. Default: 'objects'.
                ];

                $orders = wc_get_orders( $args );
                if ( count($orders) > 0) {
                    echo wp_kses_post( printf( '<div class="notice notice-error fade is-dismissible"><p>' .
                        _n( '%s Order require reindex',
                            '%s Orders require reindex',
                            count($orders),
                            'woocommerce'
                        ) . '<a class="button button-primary" style="margin-left: 10px;" href="%s">REINDEX</a></p></div>', count($orders), admin_url('admin.php?page=cargo_orders_reindex')
                    ) );
                }
            }

            if ( 'edit.php' === $pagenow
                && isset($_GET['post_type'])
                && 'shop_order' === $_GET['post_type']
                && isset( $_GET['cargo_send']) ) {

                $processed_count = intval( sanitize_text_field($_REQUEST['processed_count']) );

                if ( isset($_REQUEST['processed_count']) ) {
                    echo wp_kses_post( printf( '<div class="notice notice-success fade is-dismissible"><p>' .
                        _n( '%s Order Sent for Shipment',
                            '%s Orders Sent For Shipment',
                            $processed_count,
                            'woocommerce'
                        ) . '</p></div>', $processed_count ) );
                }

                $skipped_count = intval( sanitize_text_field($_REQUEST['skipped_count']) );

                if ( isset($_REQUEST['skipped_count']) ) {
                    echo wp_kses_post( printf( '<div class="notice notice-success fade is-dismissible"><p>' .
                        _n( '%s Were skipped because they already have shipment created.',
                            '%s Were skipped because they already have shipment created.',
                            $skipped_count,
                            'woocommerce'
                        ) . '</p></div>', $skipped_count ) );
                }
            }
        }

        /**
         * Init Cargo shipping methods.
         */
        public function shipping_method_classes() {
            require_once CSLFW_PATH . 'includes/woo-baldarp-shipping.php';
            require_once CSLFW_PATH . 'includes/woo-baldarp-express-shipping.php';
        }

        /**
         * Add shipping methods.
         *
         * @param $methods
         * @return mixed
         */
        public function cargo_shipping_methods( $methods ) {
            $methods['woo-baldarp-pickup'] = 'CSLFW_Shipping_Method';
            $methods['cargo-express'] = 'Cargo_Express_Shipping_Method';
            return $methods;
        }

        public function hpos_bulk_order_cargo_shipment($redirect, $action)
        {
//            if ($_GET['id']);
        }

            /**
         * Add bulk actions in order list
         *
         * @param $redirect_to
         * @param $action
         * @param $ids
         * @return mixed
         */
        public function bulk_order_cargo_shipment($redirect_to, $action, $ids = null)
        {
            $orderIds = $ids ?? $_GET['id'] ?? [];
            $processed_count = 0;
            $skipped_count = 0;

            if (str_contains( $action, 'mark_' )) {
                $actionName     = substr( $action, 5 ); // Get the status name from action.

                if ($actionName === 'cargo-print-label') {
                    $cargoShipping = new CSLFW_Cargo_Shipping();
                    $shipmentIds   = $cargoShipping->order_ids_to_shipment_ids($orderIds);
                    $pdfLabel      = $cargoShipping->getShipmentLabel( implode( ',', $shipmentIds ) );

                    if ($pdfLabel->pdfLink) {
                        wp_redirect($pdfLabel->pdfLink);
                        exit;
                    }
                } else if (in_array($actionName, ['send-cargo-shipping', 'send-cargo-dd', 'send-cargo-pickup'])) {
                    foreach ($orderIds as $orderId) {
                        $cargoShipping = new CSLFW_Cargo_Shipping($orderId);
                        $order = wc_get_order($orderId);

                        if (!$cargoShipping->get_shipment_data()) {
                            $args = [
                                'double_delivery' => $actionName === 'send-cargo-dd' ? 2 : 1,
                                'shipping_type' => $actionName === 'send-cargo-pickup' ? 2 : 1
                            ];

                            if ($distribution_point = (int)$order->get_meta('cargo_DistributionPointID', true)) {
                                if ($point = $this->cargo->findPointById($distribution_point)) {
                                    $args['box_point'] = $point;
                                }
                            }

                            $cargoShipping->createShipment($args);
                            $processed_count++;
                        } else {
                            $skipped_count++;
                        }
                    }
                }
            }

            return add_query_arg(
                [
                    'old_stutus'     => '',
                    'processed_count' => $processed_count,
                    'skipped_count' => $skipped_count,
                    'processed_ids'  => implode( ',', $orderIds ),
                ], $redirect_to );
        }
    }
}

$cslfw_admin = new CSLFW_Admin();
