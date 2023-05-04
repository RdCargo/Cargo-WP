<?php
 /**
 * Plugin Name: Cargo Shipping Location for WooCommerce
 * Plugin URI: https://api.cargo.co.il/Webservice/pluginInstruction
 * Description: Location Selection for Shipping Method for WooCommerce
 * Version: 3.2.4
 * Author: Astraverdes
 * Author URI: https://astraverdes.com/
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: cargo-shipping-location-for-woocommerce
 */

if ( !defined( 'ABSPATH' ) ) {
    die;
}

if ( !defined( 'CSLFW_URL' ) ) {
    define( 'CSLFW_URL', plugins_url( '/', __FILE__ ) );
}

if ( !defined( 'CSLFW_PATH' ) ) {
    define( 'CSLFW_PATH', plugin_dir_path( __FILE__ ) );
}

require CSLFW_PATH . '/includes/cslfw-helpers.php';
require CSLFW_PATH . '/includes/cslfw-logs.php';
require CSLFW_PATH . '/includes/cslfw-contact.php';
require CSLFW_PATH . '/includes/cslfw-settings.php';
require CSLFW_PATH . '/includes/cslfw-admin.php';
require CSLFW_PATH . '/includes/cslfw-front.php';
require CSLFW_PATH . '/includes/cslfw-cargo.php';
require CSLFW_PATH . '/includes/cslfw-orders-reindex.php';

if( !class_exists('CSLFW_Cargo') ) {
    class CSLFW_Cargo {

        function __construct() {
            $this->helpers = new CSLFW_Helpers();
            $this->logs = new CSLFW_Logs();
            $this->reindex = new CSLFW_OrdersReindex();

            add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'custom_checkout_field_update_order_meta' ));
            add_action( 'woocommerce_checkout_order_processed', array( $this, 'transfer_order_data_for_shipment' ), 10, 1);

            add_action( 'wp_ajax_getOrderStatus', array( $this, 'getOrderStatusFromCargo' ) );
            add_action( 'wp_ajax_nopriv_getOrderStatus', array( $this, 'getOrderStatusFromCargo' ) );
            add_action( 'wp_ajax_get_delivery_location', array( $this, 'cslfw_ajax_delivery_location' ) );
            add_action( 'wp_ajax_nopriv_get_delivery_location', array( $this, 'cslfw_ajax_delivery_location' ) );
            add_action('wp_ajax_sendOrderCARGO', array( $this, 'send_order_to_cargo' ) );
            add_action('wp_ajax_get_shipment_label', array( $this, 'get_shipment_label' ) );
            add_action('admin_menu', array($this->logs, 'add_menu_link'), 100);
            add_action('admin_menu', array($this->reindex, 'add_menu_link'), 100);

            add_filter( 'woocommerce_order_get_formatted_shipping_address', [$this, 'additional_shipping_details'], 10, 3 );
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
            $shipping_method    = @array_shift($order->get_shipping_methods());
            $cslfw_box_info     = get_option('cslfw_box_info_email');
            if (!$cslfw_box_info) {
                ob_start();
                $cargo_shipping = new CSLFW_Cargo_Shipping( $order->get_id() );

                if ( $shipping_method['method_id'] == 'woo-baldarp-pickup' ) {
                    $box_shipment_type   = get_post_meta($order->get_id(), 'cslfw_box_shipment_type', true);

                    foreach ($cargo_shipping->get_shipment_data() as $shipping_id => $data) {
                        $point = $this->helpers->cargoAPI("https://api.carg0.co.il/Webservice/getPickUpPoints", ['pointId' => intval( $data['box_id'] )]);
                        if ( count($point->PointsDetails) ) {
                            $chosen_point = $point->PointsDetails[0];
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
				    array(
				        "shipmentId" => "",
                        "error_msg" => __('Please enter all details from plugin setting', 'cargo-shipping-location-for-woocommerce')
                    )
                );
				exit;
			}

	    	$order_id           = sanitize_text_field($_POST['orderId']);
            $order              = wc_get_order($order_id);
            $shipping_method    = @array_shift($order->get_shipping_methods() );
            if ( $shipping_method['method_id'] === 'cargo-express' && trim( get_option('shipping_cargo_express') ) === '' ) {
                echo json_encode( array("shipmentId" => "", "error_msg" => __('Cargo Express ID is missing from plugin settings.', 'cargo-shipping-location-for-woocommerce') ) );
                exit;
            }
            if ( $shipping_method['method_id'] === 'woo-baldarp-pickup' && trim( get_option('shipping_cargo_box') ) === '' ) {
                echo json_encode( array("shipmentId" => "", "error_msg" => __('Cargo BOX ID is missing from plugin settings.', 'cargo-shipping-location-for-woocommerce') ) );
                exit;
            }

			$args = array(
                'double_delivery'   => (int) sanitize_text_field($_POST['double_delivery']),
                'shipment_type'     => (int) sanitize_text_field($_POST['shipment_type']),
                'no_of_parcel'      => (int) sanitize_text_field($_POST['no_of_parcel']),
                'cargo_cod'         => (int) sanitize_text_field($_POST['cargo_cod']),
                'cargo_cod_type'    => (int) sanitize_text_field($_POST['cargo_cod_type']),
                'fulfillment'       => (int) sanitize_text_field($_POST['fulfillment'])
            );

            if (isset( $_POST['box_point_id'] )) {
                $point = $this->helpers->cargoAPI("https://api.carg0.co.il/Webservice/getPickUpPoints", ['pointId' => intval( $_POST['box_point_id'] )]);
                if ( count($point->PointsDetails) ) {
                    $chosen_point      = $point->PointsDetails[0];
                    $args['box_point'] = $chosen_point;

                    update_post_meta( $order_id, 'cargo_DistributionPointID', sanitize_text_field($chosen_point->DistributionPointID) );
                }
            }

            $cargo_shipping = new CSLFW_Cargo_Shipping($order_id);
            $response = $cargo_shipping->createShipment($args);

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
        public function transfer_order_data_for_shipment($order_id ) {
        	if ( ! $order_id ) return;

        	$order = wc_get_order( $order_id );
            $shipping_method = @array_shift($order->get_shipping_methods());

        	if ( $shipping_method['method_id'] === 'woo-baldarp-pickup' ) {
    			update_post_meta( $order_id, 'cargo_DistributionPointID', sanitize_text_field($_POST['DistributionPointID']) );
        	}
        }

        /**
         * Update Order meta
         *
         * @param $order_id
         */
        public function custom_checkout_field_update_order_meta($order_id){
            $order          = wc_get_order( $order_id );
            $shippingMethod = explode(':', sanitize_text_field($_POST['shipping_method'][0]) );
            if( reset($shippingMethod) == 'woo-baldarp-pickup') {

                if ( isset($_POST['DistributionPointID']) ) {
                    $order->update_meta_data( 'cargo_DistributionPointID', sanitize_text_field($_POST['DistributionPointID']) );
                }
                if ( isset($_POST['DistributionPointID']) ) {
                    $order->update_meta_data( 'cargo_CityName', sanitize_text_field($_POST['CityName']) );
                }
                if ( get_option('cargo_box_style') ) {
                    $order->update_meta_data( 'cslfw_box_shipment_type', sanitize_text_field(get_option('cargo_box_style')) );
                }
            }

            $order->save();
        }

        /**
         * Function for map
         */
        public function cslfw_ajax_delivery_location() {
            $results = $this->helpers->cargoAPI('https://api.carg0.co.il/Webservice/getPickUpPoints');
            $point = !empty($results->PointsDetails) ? $results->PointsDetails : '';

            $response = array(
                "info"             => "Everything is fine.",
                "data"             => 1,
                "dataval"          => json_encode($point),
                'shippingMethod'   => WC()->session->get('chosen_shipping_methods')[0],
            );

            echo json_encode($response);
            wp_die();
        }

        public function init_plugin() {
            add_action('admin_menu', array($this, 'plugin_menu'));
        }

        public function plugin_menu() {
            add_menu_page('Cargo Shipping Location', 'Cargo Shipping Location', 'manage_options', 'loaction_api_settings', array(new CSLFW_Settings(), 'settings'),plugin_dir_url( __FILE__ ) . 'assets/image/cargo-icon-with-white-bg-svg.svg');
        }
    }
}

$cslfw_shipping = new CSLFW_Cargo();
$cslfw_shipping->init_plugin();
