<?php
 /**
 * Plugin Name: Cargo Shipping Location for WooCommerce
 * Plugin URI: https://api.cargo.co.il/Webservice/pluginInstruction
 * Description: Location Selection for Shipping Method for WooCommerce
 * Version: 4.0.0
 * Author: Astraverdes
 * Author URI: https://astraverdes.com/
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: cargo-shipping-location-for-woocommerce
  *
  * WC requires at least: 6.0.0
  * WC tested up to: 7.6.1
 */

use CSLFW\Includes\CargoAPI\Cargo;
use CSLFW\Includes\CargoAPI\CSLFW_Order;
use CSLFW\Includes\CargoAPI\Webhook;

if ( !defined( 'ABSPATH' ) ) {
    die;
}

if ( !defined( 'CSLFW_URL' ) ) {
    define( 'CSLFW_URL', plugins_url( '/', __FILE__ ) );
}

if ( !defined( 'CSLFW_PATH' ) ) {
    define( 'CSLFW_PATH', plugin_dir_path( __FILE__ ) );
}

if ( !defined( 'CSLFW_VERSION' ) ) {
    define( 'CSLFW_VERSION', '4.0.0' );
}

require CSLFW_PATH . '/includes/CargoApi/Helpers.php';
require CSLFW_PATH . '/includes/CargoApi/CSLFW_Order.php';
require CSLFW_PATH . '/includes/CargoApi/Cargo.php';
require CSLFW_PATH . '/includes/CargoApi/Webhook.php';
require CSLFW_PATH . '/includes/cslfw-helpers.php';
require CSLFW_PATH . '/includes/cslfw-logs.php';
require CSLFW_PATH . '/includes/cslfw-contact.php';
require CSLFW_PATH . '/includes/cslfw-settings.php';
require CSLFW_PATH . '/includes/cslfw-admin.php';
require CSLFW_PATH . '/includes/cslfw-front.php';
require CSLFW_PATH . '/includes/cslfw-cargo.php';
//include_once __DIR__ . '/blocks/cargo-shipping.php';

if( !class_exists('CSLFW_Cargo') ) {
    class CSLFW_Cargo {

        function __construct() {
            $this->helpers = new CSLFW_Helpers();
            $this->logs = new CSLFW_Logs();
            $this->cargo = new Cargo();
            $this->webhook = new Webhook();

            add_action('before_woocommerce_init', [$this, 'hpos_compability']);

            add_action('woocommerce_checkout_update_order_meta', [$this, 'custom_checkout_field_update_order_meta']);
            add_action('woocommerce_checkout_order_processed', [$this, 'transfer_order_data_for_shipment'], 10, 1);

            add_action('wp_ajax_getOrderStatus', [$this, 'getOrderStatusFromCargo']);
            add_action('wp_ajax_nopriv_getOrderStatus', [$this, 'getOrderStatusFromCargo']);
            add_action('wp_ajax_get_delivery_location', [$this, 'cslfw_ajax_delivery_location']);
            add_action('wp_ajax_nopriv_get_delivery_location', [$this, 'cslfw_ajax_delivery_location']);
            add_action('wp_ajax_sendOrderCARGO', [$this, 'send_order_to_cargo']);
            add_action('wp_ajax_get_shipment_label', [$this, 'get_shipment_label']);
            add_action('admin_menu', [$this->logs, 'add_menu_link'], 100);

            add_filter('woocommerce_order_get_formatted_shipping_address', [$this, 'additional_shipping_details'], 10, 3 );

            add_action('woocommerce_thankyou', [$this, 'auto_create_shipment'], 10, 1);
        }

        public function hpos_compability()
        {
            if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
                \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
            }
        }

        /**
         * Add shipment details to an email and frontend
         *
         * @param $address
         * @param $raw_address
         * @param $order
         * @return mixed|string
         */
        function additional_shipping_details( $address, $raw_address, $order ) {
            $cargoOrder = new CSLFW_Order($order);
            $shipping_method    = $cargoOrder->getShippingMethod();
            $cslfw_box_info     = get_option('cslfw_box_info_email');

            if (!$cslfw_box_info && $shipping_method !== null) {
                ob_start();
                $cargo_shipping = new CSLFW_Cargo_Shipping( $order->get_id() );
                $shipmentsData = $cargo_shipping->get_shipment_data();

                if ( $shipping_method === 'woo-baldarp-pickup' && $shipmentsData ) {
                    $box_shipment_type = $order->get_meta('cslfw_box_shipment_type', true);

                    foreach ($shipmentsData as $shipping_id => $data) {
                        if ($point = $this->cargo->findPointById($data['box_id'])) {
                            $chosen_point = $point;
                            echo __("Cargo Point Details", 'cargo-shipping-location-for-woocommerce') . PHP_EOL;
                            if ( $box_shipment_type === 'cargo_automatic' && !$chosen_point ) {
                                echo __('Details will appear after sending to cargo.', 'cargo-shipping-location-for-woocommerce'). PHP_EOL;
                            } else {
                                echo wp_kses_post( $chosen_point->DistributionPointName ) ?> : <?php echo wp_kses_post($chosen_point->DistributionPointID ) . PHP_EOL;
                                echo wp_kses_post( $chosen_point->StreetNum.' '.$chosen_point->StreetName.' '. $chosen_point->CityName ) . PHP_EOL;
                                echo wp_kses_post( $chosen_point->Comment ) . PHP_EOL;
                                echo wp_kses_post( $chosen_point->Phone ) . PHP_EOL;
                            }
                        }
                    }
                 }
                $cargo_details = ob_get_clean();
                $address .= $cargo_details;
            }
            return $address;
        }

	    /**
		* Send Order to CARGO (Baldar) For Shipping Process.
		*
		* @param string $_POST data
		*
		* @return Return Success Msg and store Cargo Shipping ID in Meta.
		*/
	    function send_order_to_cargo() {

			if( trim( get_option('from_street') ) == ''
                || trim( get_option('from_street_name') ) == ''
                || trim( get_option('from_city') ) == ''
                || trim( get_option('phonenumber_from') ) == '' ) {
				echo json_encode(
				    [
				        "shipmentId" => "",
                        "error_msg" => __('Please enter all details from plugin setting', 'cargo-shipping-location-for-woocommerce')
                    ]
                );
				exit;
			}

	    	$order_id = sanitize_text_field($_POST['orderId']);
            $order = wc_get_order($order_id);
            $cargoOrder = new CSLFW_Order($order);
            $shipping_method  = $cargoOrder->getShippingMethod();

            if ($shipping_method === null) {
                echo json_encode(
                    [
                        "shipmentId" => "",
                        "error_msg" => __('No shipping methods found. Contact support please.', 'cargo-shipping-location-for-woocommerce')
                    ]
                );
                exit;
            }
            if ( ($shipping_method === 'cargo-express') && trim( get_option('shipping_cargo_express') ) === '' ) {
                echo json_encode(
                    [
                        "shipmentId" => "",
                        "error_msg" => __('Cargo Express ID is missing from plugin settings.', 'cargo-shipping-location-for-woocommerce')
                    ]
                );
                exit;
            }

            if (in_array($order->get_status(), ['cancelled', 'refunded', 'pending'])) {
                echo json_encode(
                    [
                        "shipmentId" => "",
                        "error_msg" => __('Cancelled, pending, or refunded order can\'t be processed.', 'cargo-shipping-location-for-woocommerce')
                    ]
                );
                exit;
            }

            if ( ($shipping_method === 'woo-baldarp-pickup') && trim( get_option('shipping_cargo_box') ) === '' ) {
                echo json_encode(
                    [
                        "shipmentId" => "",
                        "error_msg" => __('Cargo BOX ID is missing from plugin settings.', 'cargo-shipping-location-for-woocommerce')
                    ]
                );
                exit;
            }

			$args = [
                'double_delivery'   => (int) sanitize_text_field($_POST['double_delivery']),
                'shipping_type'     => (int) sanitize_text_field($_POST['shipment_type']),
                'no_of_parcel'      => (int) sanitize_text_field($_POST['no_of_parcel']),
                'cargo_cod'         => (int) sanitize_text_field($_POST['cargo_cod']),
                'cargo_cod_type'    => (int) sanitize_text_field($_POST['cargo_cod_type']),
                'fulfillment'       => (int) sanitize_text_field($_POST['fulfillment'])
            ];

            if (isset( $_POST['box_point_id'] )) {
                if ($point = $this->cargo->findPointById($_POST['box_point_id'])) {
                    $args['box_point'] = $point;

                    $order->update_meta_data('cargo_DistributionPointID', sanitize_text_field($point->DistributionPointID));
                }
                $order->update_meta_data('cslfw_shipping_method', 'woo-baldarp-pickup');
            } else {
                $order->update_meta_data('cslfw_shipping_method', 'cargo-express');
            }

            $cargo_shipping = new CSLFW_Cargo_Shipping($order_id);
            $response = $cargo_shipping->createShipment($args);

            $order->save();
            echo json_encode($response);
			exit();
	    }

        /**
         * Get label from cargo.
         *
         * @param false $shipmentId
         * @return mixed
         */
		function get_shipment_label() {
		    $cargo_shipping = new CSLFW_Cargo_Shipping($_POST['orderId']);

            $response = $cargo_shipping->getShipmentLabel();

            echo json_encode($response);
            exit;
		}

		/**
		* Check the Shipping Setting from cargo
		*
		* @param $_POST DATA
		* @return int shipping Status
		*/
		function getOrderStatusFromCargo() {
		    $order_id       = (int) $_POST['orderId'];
		    $shipping_id    = (int) $_POST['deliveryId'];
            $cargo_shipping = new CSLFW_Cargo_Shipping($order_id);

			echo json_encode( $cargo_shipping->getOrderStatusFromCargo($shipping_id) );
			exit;
		}

        /**
         * Update order on status change.
         *
         * @param $order_id
         */
        public function transfer_order_data_for_shipment($order_id) {
        	if ( ! $order_id ) return;

        	$order = wc_get_order( $order_id );
            $cargoOrder = new CSLFW_Order($order);
            $shipping_method = $cargoOrder->getShippingMethod();

            if ($shipping_method !== null) {
                if ($shipping_method === 'woo-baldarp-pickup') {
                    $order->update_meta_data('cargo_DistributionPointID', sanitize_text_field($_POST['DistributionPointID']));
                    $order->update_meta_data('cslfw_shipping_method', $shipping_method);
                }
            } else if ($shipping_method === 'cargo-express') {
                $order->update_meta_data('cslfw_shipping_method', $shipping_method);
            }

            $order->save();
        }

        function auto_create_shipment( $order_id )
        {
            $order = wc_get_order( $order_id );

            $autoShipmentCreate = get_option('cslfw_auto_shipment_create');
            if ($autoShipmentCreate === 'on') {
                $cargo_shipping = new CSLFW_Cargo_Shipping($order_id);
                $response = $cargo_shipping->createShipment();
                $order->update_meta_data('custom_checkout_field_update_order_meta', $response);
            }

            $order->save();
        }

            /**
         * Update Order meta
         *
         * @param $order_id
         */
        public function custom_checkout_field_update_order_meta($order_id){
            $order          = wc_get_order($order_id);
            $shippingMethod = explode(':', sanitize_text_field($_POST['shipping_method'][0]) );
            if(reset($shippingMethod) === 'woo-baldarp-pickup') {
                if ( isset($_POST['DistributionPointID']) ) {
                    $order->update_meta_data('cargo_DistributionPointID', sanitize_text_field($_POST['DistributionPointID']));
                }
                if ( isset($_POST['DistributionPointID']) ) {
                    $order->update_meta_data('cargo_CityName', sanitize_text_field($_POST['CityName']) );
                }
                if ( get_option('cargo_box_style') ) {
                    $order->update_meta_data('cslfw_box_shipment_type', sanitize_text_field(get_option('cargo_box_style')));
                }

                $order->update_meta_data('cslfw_shipping_method', 'woo-baldarp-pickup');
            }

            if(reset($shippingMethod) === 'cargo-express') {
                $order->update_meta_data('cslfw_shipping_method', 'cargo-express');
            }

            $order->save();
        }

        /**
         * Function for map
         */
        public function cslfw_ajax_delivery_location() {
            if ( WC()->session->get('chosen_shipping_methods') !== null) {
                $results = $this->helpers->cargoAPI('https://api.cargo.co.il/Webservice/getPickUpPoints');
                $point = !empty($results->PointsDetails) ? $results->PointsDetails : '';

                $response = [
                    "info"             => "Everything is fine.",
                    "data"             => 1,
                    "dataval"          => json_encode($point),
                    'shippingMethod'   => WC()->session->get('chosen_shipping_methods')[0],
                ];
            } else {
                $response = [
                    "info"             => "Error",
                    "data"             => 0,
                    "dataval"          => '',
                    'shippingMethod'   => ''
                ];
            }
            echo json_encode($response);
            wp_die();
        }

        public function init_plugin() {
            add_action('admin_menu', [$this, 'plugin_menu']);
        }

        public function plugin_menu() {
            add_menu_page('Cargo Shipping Location', 'Cargo Shipping Location', 'manage_options', 'loaction_api_settings', [new CSLFW_Settings(), 'settings'], plugin_dir_url( __FILE__ ) . 'assets/image/cargo-icon-with-white-bg-svg.svg');
        }
    }
}

$cslfw_shipping = new CSLFW_Cargo();
$cslfw_shipping->init_plugin();
