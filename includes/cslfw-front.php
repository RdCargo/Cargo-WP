<?php
/**
 * Admin adjustments.
 *
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( class_exists( 'CSLFW_Front', false ) ) {
    return new CSLFW_Front();
}
use CSLFW\Includes\CargoAPI\Cargo;

if( !class_exists('CSLFW_Front') ) {
    class CSLFW_Front
    {
        function __construct()
        {
            $this->helpers = new CSLFW_Helpers();
            $this->cargo = new Cargo();

            add_action( 'wp_enqueue_scripts', [$this, 'import_assets']);

            add_filter( 'woocommerce_account_orders_columns', [$this, 'add_account_orders_column'], 10, 1);
            add_filter( 'woocommerce_locate_template', [$this, 'intercept_wc_template'], 10, 3);

            add_action( 'wp_head', [$this, 'checkout_popups']);
            add_action( 'wp_footer', [$this, 'add_model_footer']);
            add_action( 'woocommerce_order_details_after_order_table', [$this, 'tracking_button']);
            add_action( 'wp_ajax_get_order_tracking_details', [$this,'get_order_tracking_details']);
            add_action( 'woocommerce_after_shipping_rate', [$this, 'checkout_cargo_actions'], 20, 2);
            add_action( 'woocommerce_my_account_my_orders_column_order-track', [$this, 'add_account_orders_column_rows']);
            add_action( 'woocommerce_checkout_process', [$this, 'action_woocommerce_checkout_process'], 10, 1);

            // WC 8+
        }

        function import_assets() {
            if ( is_account_page() ) {
                wp_enqueue_style('badarp-front-css', CSLFW_URL.'assets/css/front.css');

                wp_enqueue_script( 'cargo-order', CSLFW_URL .'assets/js/cargo-order.js', [], CSLFW_VERSION, true );
                wp_localize_script( 'cargo-order', 'cargo_obj',
                    [
                        'ajaxurl' => admin_url( 'admin-ajax.php' ),
                        'ajax_nonce' => wp_create_nonce( 'cslfw_shipping_nonce' ),
                    ]
                );
            }

            if ( is_cart() || is_checkout() ) {
                wp_enqueue_script( 'baldarp-script', CSLFW_URL .'assets/js/baldarp-script.js', [], CSLFW_VERSION, true);
                wp_localize_script( 'baldarp-script', 'baldarp_obj',
                    [
                        'ajaxurl' => admin_url( 'admin-ajax.php' ),
                        'ajax_nonce' => wp_create_nonce( 'cslfw_shipping_nonce' ),
                    ]
                );

                if ( get_option('cslfw_google_api_key') ) {
                    $maps_key = get_option('cslfw_google_api_key');
                    wp_enqueue_script( 'baldarp-map-jquery', "https://maps.googleapis.com/maps/api/js?key=$maps_key&language=he&libraries=places&v=weekly", null, null, true );
                }
                wp_enqueue_style('badarp-front-css', CSLFW_URL.'assets/css/front.css');

                if ( get_option('bootstrap_enalble') == 1 ) {
                    wp_enqueue_script( 'baldarp-bootstrap-jquery',  CSLFW_URL .'assets/js/boostrap_bundle.js', [], CSLFW_VERSION, false );
                    wp_enqueue_style('badarp-bootstrap-css', CSLFW_URL .'assets/css/boostrap_min.css');
                }
            }
        }

        /**
         * Display fields for checkout page, and cargo box selector..
         *
         * @param $method
         * @param $index
         */
        public function checkout_cargo_actions($method, $index) {
            if( is_cart() ) { return; }

            $shippingMethods = WC()->session->get('chosen_shipping_methods')[ $index ];
            $selectedShippingMethod = explode(':', $shippingMethods);
            $selectedShippingMethod = reset($selectedShippingMethod);

            if ( $selectedShippingMethod === 'woo-baldarp-pickup' && $method->method_id === 'woo-baldarp-pickup' ) {
                $pointId = isset($_COOKIE['cargoPointID']) ? sanitize_text_field($_COOKIE['cargoPointID']) : null;
                $coordinates = [
                    'lat' => isset($_COOKIE['cargoLatitude']) ? sanitize_text_field($_COOKIE['cargoLatitude']) : 31.046051,
                    'long' => isset($_COOKIE['cargoLongitude']) ? sanitize_text_field($_COOKIE['cargoLongitude']) : 34.851612,
                    'distance' => 10,
                ];

                $cities = $this->cargo->getPointsCities();
                $points = $this->cargo->findClosestPoints($coordinates);

                $selectedPoint = $this->cargo->findPointById($pointId);

                $cargoBoxStyle = get_option('cargo_box_style');

                $data = [
                    'boxStyle' => $cargoBoxStyle,
                    'cities' => $cities,
                    'selectedCity' => isset($_COOKIE['CargoCityName_dropdown']) ? sanitize_text_field($_COOKIE['CargoCityName_dropdown']) : '',
                    'selectedPointId' => $pointId,
                    'selectedPoint' => $selectedPoint,
                    'points' => $points,
                ];

                $this->helpers->load_template('checkout/box-shipment', $data);
            }
        }

        /**
         * @param $order
         *
         * Add Track order link in my account page in order tab
         */
        function add_account_orders_column_rows( $order ) {
            $order_id           = $order->get_id();
            $cargo_shipping     = new CSLFW_Cargo_Shipping($order_id);
            $deliveries  = $cargo_shipping->get_shipment_ids();

            if( $deliveries ) {
                foreach ( $deliveries as $key => $value ) {
                    echo wp_kses_post('<a href="#" class="btn woocommerce-button js-cargo-track" data-delivery="' . $value . '">' . esc_html_e('Track Order', 'cargo-shipping-location-for-woocommerce') . '</a>');
                }
            }
        }

        /**
         * @param $columns
         * @return mixed
         *
         * Add New column Track order in the my account order tab
         */
        function add_account_orders_column( $columns ) {
            $columns['order-track'] = esc_html_e( 'Track order', 'cargo-shipping-location-for-woocommerce' );

            return $columns;
        }

        /**
         * Add popup to account page.
         */
        function add_model_footer() {
            if( is_account_page() ) {
                $this->helpers->load_template('account-tracking-popup');
            }
        }

        /**
         * Function for `woocommerce_order_details_after_order_table` action-hook.
         *
         * @param  $order
         *
         * @return void
         */
        function tracking_button( $order ) {
            $order_id           = $order->get_id();
            $cargo_shipping     = new CSLFW_Cargo_Shipping($order_id);
            $deliveries  = $cargo_shipping->get_shipment_ids();

            if ( $deliveries ) {
                foreach($deliveries as $key => $value) {
                    echo wp_kses_post('<a href="#" class="btn wp-element-button button woocommerce-button js-cargo-track" data-delivery="'. $value .'">' . esc_html_e('Track shipment', 'cargo-shipping-location-for-woocommerce') . " $value</a>");
                }
            }
        }

        function get_order_tracking_details() {
            if ( !isset($_POST['shipping_id']) || sanitize_text_field($_POST['shipping_id']) === '' ) {
                echo wp_json_encode(
                    [
                        'status'  => 'fail',
                        'message' => esc_html_e('No shipping id provided. Contact support please.', 'cargo-shipping-location-for-woocommerce')
                    ]
                );
                wp_die();
            }

            $data['deliveryId'] = (int) sanitize_text_field($_POST['shipping_id']);
            $result = $this->helpers->cargoAPI('https://api.cargo.co.il/Webservice/CheckShipmentStatusAndTime', $data);
            echo wp_json_encode( $result );
            die();
        }

        /**
         * Display map and info popup.
         */
        public function checkout_popups() {
            if ( is_checkout() || is_cart() ){
                $this->helpers->load_template('checkout-popups');
            }
        }

        /**
         * Filter the thankyou template path to use thankyou.php in this plugin instead of the one in WooCommerce.
         *
         * @param string $template      Default template file path.
         * @param string $template_name Template file slug.
         * @param string $template_path Template file name.
         *
         * @return string The new Template file path.
         */
        function intercept_wc_template($template, $template_name, $template_path) {
            global $woocommerce;
            $_template = $template;
            if ( 'thankyou.php' === basename( $template ) ) {
                if ( ! $template_path ) $template_path = $woocommerce->template_url;

                $plugin_path  = untrailingslashit( plugin_dir_path( __FILE__ ) )  . '/woocommerce/templates/';

                // Look within passed path within the theme - this is priority
                $template = locate_template(
                    [
                        $template_path . $template_name,
                        $template_name
                    ]
                );

                if ( !$template && file_exists( $plugin_path . $template_name ) )
                    $template = $plugin_path . $template_name;

                if ( !$template )
                    $template = $_template;
            }
            return $template;
        }

        public function action_woocommerce_checkout_process($wccs_custom_checkout_field_pro_process) {
            $chosen_shipping_methods = WC()->session->get('chosen_shipping_methods');
            $chosen_method_id = explode(':', $chosen_shipping_methods[0]);
            $chosen_method_id = reset($chosen_method_id);
            $cargo_box_style = get_option('cargo_box_style');

            if ( $chosen_method_id === 'woo-baldarp-pickup' && $cargo_box_style !== 'cargo_automatic') {
                if ( sanitize_text_field($_POST['DistributionPointID']) === '' ) {
                    wc_add_notice( esc_html_e( 'נא לבחור נקודת חלוקה בשיטת משלוח זה או שיטת משלוח אחרת', 'cargo-shipping-location-for-woocommerce' ), 'error' );
                }
            }
        }
    }
}

$CSLFW_Front = new CSLFW_Front();
