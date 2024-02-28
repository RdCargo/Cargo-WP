<?php
/**
 * Cargo shipping object.
 *
 */

use CSLFW\Includes\CargoAPI\Cargo;

require CSLFW_PATH . '/includes/cslfw-helpers.php';
require CSLFW_PATH . '/includes/cslfw-logs.php';
if( !class_exists('CSLFW_Cargo_Shipping') ) {
    class CSLFW_Cargo_Shipping
    {
        public $deliveries;
        public $order_id;
        public $order;
        public $cargo;

        function __construct($order_id = 0) {
            $this->cargo = new Cargo();
            $this->helpers = new CSLFW_Helpers();

            if ($order_id) {
                $this->order = wc_get_order($order_id);
                $this->order_id  = $order_id;
                $this->deliveries = $this->order->get_meta('cslfw_shipping', true) ?? [];
            }

            add_action('init', array($this, 'add_cors_http_header') );

            add_action('woocommerce_new_order', array($this, 'clean_cookies'), 10, 1);
        }

        function add_cors_http_header(){
            header("Access-Control-Allow-Origin: *");
        }

        public function get_id() {
            return $this->shipping_id;
        }

        /**
         * Making object for cargo API
         *
         * @param array $args
         * @return string[]
         */
        function createCargoObject($args = []) {
            $logs = new CSLFW_Logs();

            if ( $this->deliveries && is_array($this->deliveries) && count($this->deliveries) >= 4 ) {
                return array('shipmentId' => "", 'error_msg' => "Maximum allowed amount of shipments is 4 per order. orderID = $this->order_id");
            }

            $order_data         = $this->order->get_data();
            $shipping_method    = @array_shift($this->order->get_shipping_methods());
            $cslfw_shipping_methods_all = get_option('cslfw_shipping_methods_all');

            if ( !$shipping_method && !(bool)$cslfw_shipping_methods_all ) {
                return array('shipmentId' => "", 'error_msg' => "No shipping methods found. Contact support please.");
            }
            if ( $this->order->get_status() === 'cancelled' || $this->order->get_status() === 'refunded' || $this->order->get_status() === 'pending') {
                return array('shipmentId' => "", 'error_msg' => "Cancelled, pending or refunded order can\'t be processed.");
            }


            $shipping_method_id = $shipping_method ? $shipping_method['method_id'] : 'cargo-express';
            $shipping_method_id = !$shipping_method && (bool) $cslfw_shipping_methods_all ? 'cargo-express' : $shipping_method_id;
            $isBoxShipment = $shipping_method_id === 'woo-baldarp-pickup';

            $cargo_box_style    = $this->order->get_meta('cslfw_box_shipment_type', true) ?? 'cargo_automatic';
            $cargo_box_style    = empty($cargo_box_style) ? 'cargo_automatic' : $cargo_box_style;

            $pickupCustomerCode = get_option('shipping_pickup_code');

            $customer_code   = $isBoxShipment ? get_option('shipping_cargo_box') : get_option('shipping_cargo_express');
            $customer_code   = (int)$args['shipping_type'] === 2 && $pickupCustomerCode ? $pickupCustomerCode : $customer_code;

            $name = $order_data['shipping']['first_name'] ? $order_data['shipping']['first_name']. ' ' . $order_data['shipping']['last_name'] : $order_data['billing']['first_name'] . ' ' . $order_data['billing']['last_name'];

            $notes = '';
            $cslfw_fulfill_all = get_option('cslfw_fulfill_all');
            if ( $args['fulfillment'] || $cslfw_fulfill_all ) {
                foreach ($this->order->get_items() as $item) {
                    $product = $item['variation_id'] ? wc_get_product($item['variation_id']) : wc_get_product($item['product_id']);
                    $notes .= '|' .  $product->get_sku() . '*' . $item->get_quantity();
                }
            }
            $notes = substr($notes, 1);
            $notes .= $order_data['customer_note'];

            $website = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
            $website.= $_SERVER['HTTP_HOST'];

            $data['Method'] = "ship";
            $data['Params'] = array(
                'shipping_type'         => isset($args['shipping_type']) ? $args['shipping_type'] : 1,
                'doubleDelivery'        => isset($args['double_delivery']) ? $args['double_delivery'] : 1,
                'noOfParcel'            => isset($args['no_of_parcel']) ? $args['no_of_parcel'] : 0,
                'TotalValue'            => $this->order->get_total(),
                'TransactionID'         => $this->order_id,
                'CashOnDelivery'        => isset($args['cargo_cod']) && $args['cargo_cod'] ? floatval($this->order->get_total()) : 0,
                'CarrierID'             => $isBoxShipment ? 0 : 1,
                'OrderID'               => $this->order_id,
                'PaymentMethod'         => $order_data['payment_method'],
                'Note'                  => $notes,
                'customerCode'          => $customer_code,
                'website'               => $website,
                'Platform'              => 'Wordpress',

                'to_address' => array(
                    'name'      => $name,
                    'company'   => !empty($order_data['shipping']['company']) ? $order_data['shipping']['company'] : $name,
                    'street1'   => !empty($order_data['shipping']['address_1'] ) ? $order_data['shipping']['address_1'] : $order_data['billing']['address_1'],
                    'street2'   => !empty($order_data['shipping']['address_2'] ) ? $order_data['shipping']['address_2'] : $order_data['billing']['address_2'],
                    'city'      =>  !empty($order_data['shipping']['city'] ) ? $order_data['shipping']['city'] : $order_data['billing']['city'],
                    'state'     =>  !empty($order_data['shipping']['state'] ) ? $order_data['shipping']['state'] : $order_data['billing']['state'],
                    'zip'       =>  !empty($order_data['shipping']['postcode'] ) ? $order_data['shipping']['postcode'] : $order_data['billing']['postcode'],
                    'country'   =>  !empty($order_data['shipping']['country'] ) ? $order_data['shipping']['country'] : $order_data['billing']['country'],
                    'phone'     =>  !empty($order_data['shipping']['phone'] ) ? $order_data['shipping']['phone'] : $order_data['billing']['phone'],
                    'email'     =>  !empty($order_data['shipping']['email'] ) ? $order_data['shipping']['email'] : $order_data['billing']['email'],
                    'floor'     => $this->order->get_meta('cargo_floor', true),
                    'appartment' => $this->order->get_meta('cargo_apartment', true),
                ),

                'from_address' => array(
                    'name'      => get_option('website_name_cargo'),
                    'company'   => get_option('website_name_cargo'),
                    'street1'   => get_option('from_street'),
                    'street2'   => get_option('from_street_name'),
                    'city'      => get_option('from_city'),
                    'state'     => !empty( $order_data['shipping']['state'] ) ? $order_data['shipping']['state'] : $order_data['billing']['state'],
                    'zip'       => !empty( $order_data['shipping']['postcode'] ) ? $order_data['shipping']['postcode'] : $order_data['billing']['postcode'],
                    'country'   => !empty( $order_data['shipping']['country'] ) ? $order_data['shipping']['country'] : $order_data['billing']['country'],
                    'phone'     => get_option('phonenumber_from'),
                    'email'     => !empty( $order_data['shipping']['email'] ) ? $order_data['shipping']['email'] : $order_data['billing']['email'],
                )
            );

            if ((int)$args['shipping_type'] === 2) {
                $tmp_from_address = $data['Params']['from_address'];
                $data['Params']['from_address'] = $data['Params']['to_address'];
                $data['Params']['to_address'] = $tmp_from_address;
            }

            if ( $data['Params']['CashOnDelivery'] ) {
                $data['Params']['CashOnDeliveryType'] = isset($args['cargo_cod_type']) ? $args['cargo_cod_type'] : 0;
            }

            if ($isBoxShipment) {
                if ( $cargo_box_style !== 'cargo_automatic' || isset($args['box_point']) ) {
                    $chosen_point = $args['box_point'];

                    $data['Params']['boxPointId'] = $this->order->get_meta('cargo_DistributionPointID', true);
                    $data['Params']['boxPointId'] = isset($args['box_point']) ? $chosen_point->DistributionPointID : $data['Params']['boxPointId'];
                } else {
                    $address = $data['Params']['to_address']['street1'] . ' ' . $data['Params']['to_address']['street2'] . ',' . $data['Params']['to_address']['city'];
                    $geocoding = $this->helpers->cargoAPI('https://api.cargo.co.il/Webservice/cargoGeocoding', array('address' => $address) );
                    if ( $geocoding->error === false ) {
                        if ( !empty($geocoding->data->results) ) {
                            $geocoding = $geocoding->data->results[0]->geometry->location;
                            $closest_point = $this->helpers->cargoAPI('https://api.cargo.co.il/Webservice/findClosestPoints', array('lat' => $geocoding->lat, 'long' => $geocoding->lng, 'distance' => 10) );

                            if ( $closest_point->error === false ) {
                                if ( !empty($closest_point->closest_points) ) {
                                    // THE SUCCESS FOR DETERMINE CARGO POINT ID IN AUTOMATIC MODE.
                                    $chosen_point = $closest_point->closest_points[0]->point_details;
                                    $data['Params']['boxPointId'] = $chosen_point->DistributionPointID;

                                    $this->order->save();
                                } else {
                                    $logs->add_log_message("ERROR.FAIL: 'No closest points found by the radius." . PHP_EOL );
                                    return array('shipmentId' => "", 'error_msg' => 'No closest points found by the radius.'); die();
                                }
                            } else {
                                $logs->add_log_message("ERROR.FAIL: 'Failed to find closest points." . PHP_EOL );
                                return array('shipmentId' => "", 'error_msg' => 'Failed to find closest points. Contact support please.'); die();
                            }
                        } else {
                            $logs->add_log_message("ERROR.FAIL: Empty geocoding data." . PHP_EOL );
                            return array('shipmentId' => "", 'error_msg' => 'Empty geocoding data.'); die();
                        }

                    } else {
                        $logs->add_log_message("ERROR.FAIL: Address geocoding fail for address $address" . PHP_EOL );
                        return array('shipmentId' => "", 'error_msg' => 'Failed to create geocoding. Contact support please.'); die();
                    }
                }
            }

            return apply_filters('cslfw_cargo_order_array', $data, $this->order_id); die();
        }

        /**
         * Updating wc order status based on option select
         */
        public function update_wc_status() {
            $cargo_status = get_option('cargo_order_status');
            if ($cargo_status) {
                $this->order->update_status($cargo_status);
            }
        }

        /**
         * Main create shipment function
         *
         * @param $order_id
         * @param array $args
         * @return array|int|string[]
         */
        public function createShipment($args = []) {
            $logs = new CSLFW_Logs();
            $data = $this->createCargoObject($args);

            if ( !isset($data['Params']) ) return $data;

            $response = $this->cargo->createShipment($data);
            $message = '==============================' . PHP_EOL;

            if ($response->shipmentId != '' ) {
                $response->all_data = $this->addShipment($data['Params'], $response);
                $this->update_wc_status();
                $message .= "ORDER ID : $this->order_id | DELIVERY ID  : {$response->shipmentId} | SENT TO CARGO ON : ".date('Y-m-d H:i:d')." SHIPMENT TYPE : {$data['Params']['CarrierName']} | CUSTOMER CODE : {$data['Params']['customerCode']}" . PHP_EOL;
                if( $data['Params']['CarrierName'] === 'BOX' ) {
                    $message    .= "CARGO BOX POINT ID : {$data['Params']['boxPointId']}". PHP_EOL;
                }
            }

            $message .= "Shipment Data : ".json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES). PHP_EOL;
            $message .= "Response : ".json_encode($response,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES). PHP_EOL;
            $logs->add_log_message($message);
            return $response;
        }

        public function addShipment( $shipment_params, $shipment_data ) {
            $delivery = is_array($this->deliveries) ? $this->deliveries : [];
            $delivery[$shipment_data->shipmentId] = [
                'driver_name'   => $shipment_data->drivername,
                'line_number'   => $shipment_data->linetext,
                'customer_code' => $shipment_params['customerCode'],
                'status'        => [
                    'number' => $shipment_data->status_number ?? 1,
                    'text' => $shipment_data->status_text ?? 'Open',
                ]
            ];

            if ($shipment_params['boxPointId']) {
                $delivery[$shipment_data->shipmentId]['box_id'] = $shipment_params['boxPointId'];
            }

            $this->deliveries = $delivery;

            $this->order->update_meta_data('cslfw_shipping', $this->deliveries);
            $this->order->save();
            return $this->deliveries;
        }

        public function get_shipment_ids() {
            return $this->deliveries ? array_keys($this->deliveries) : [];
//            return $this->deliveries
//                ? array_map(fn($item) => $item['shipment_id'], $this->deliveries)
//                : [];
        }

        public function get_shipment_data() {
            return $this->deliveries;
        }

        /**
         * Pass data to cargo API
         *
         * @param array $data
         * @return array|int
         */
        public function passDataToCargo($data = []) {
            if ( !empty($data) ) {

                $status = $this->helpers->cargoAPI('https://api.cargo.co.il/Webservice/CreateShipment', $data);
                return (array) $status;
            } else {
                return 0;
            }
        }


        /**
         * @param $shipping_id
         * @return array|string[]
         */
        function getOrderStatusFromCargo( $shipping_id ) {
            $shipping_method    = @array_shift($this->order->get_shipping_methods());
            $cslfw_shipping_methods_all = get_option('cslfw_shipping_methods_all');

            if (!$shipping_method && !(bool)$cslfw_shipping_methods_all) {
                return array("type" => "failed","data" => 'No shipping methods found. Contact Support please.');
            }
            if ( $this->order->get_status() === 'cancelled' || $this->order->get_status() === 'refunded' || $this->order->get_status() === 'pending') {
                return array("type" => "failed","data" => 'Can\'t process order with cancelled, pending or refunded status');
            }
            $shipping_method_id = $shipping_method ?  $shipping_method['method_id'] : 'cargo-express';
            $shipping_method_id = (bool) $cslfw_shipping_methods_all ? 'cargo-express' : $shipping_method_id;

            $post_data = array(
                'deliveryId' => (int) $shipping_id,
                'DeliveryType' => $shipping_method_id === 'woo-baldarp-pickup' ? 'BOX' : 'EXPRESS',
                'customerCode' => $shipping_method_id === 'woo-baldarp-pickup' ? get_option('shipping_cargo_box') : get_option('shipping_cargo_express'),
            );

            $data = (array) $this->helpers->cargoAPI("https://api.cargo.co.il/Webservice/CheckShipmentStatus",  $post_data);

            if ( $data['errorMsg']  == '' && $shipping_id) {
                if ( (int) $data['deliveryStatus'] === 8 ) {
                    if ( $this->deliveries ) {
                        unset($this->deliveries[$shipping_id]);
                        $this->order->update_meta_data('cslfw_shipping', $this->deliveries );
                    }
                    $response = array(
                        "type" => "success",
                        "data" => $data['DeliveryStatusText'],
                        "orderStatus" => (int)$data['deliveryStatus']
                    );
                } elseif ((int) $data['deliveryStatus'] > 0) {
                    $this->deliveries[$shipping_id]['status']['number'] = (int) $data['deliveryStatus'];
                    $this->deliveries[$shipping_id]['status']['text'] = sanitize_text_field( $data['DeliveryStatusText']);

                    $this->order->update_meta_data('cslfw_shipping', $this->deliveries);

                    $cslfw_complete_orders = get_option('cslfw_complete_orders');

                    if ((int) $data['deliveryStatus'] === 3 && $cslfw_complete_orders) {
                        $this->order->update_status('completed');
                    }

                    $response = array(
                        "type" => "success",
                        "data" => $data['DeliveryStatusText'],
                        "orderStatus" => (int)$data['deliveryStatus']
                    );
                } else {
                    $response =  array("type" => "failed","data" => 'Not Getting Data');
                }
            } else {
                $response = array("type" => "failed","data" => 'something went wrong');
            }

            return $response;
        }

        /**
         * @param false $shipment_ids
         * @return mixed
         */
        function getShipmentLabel($shipment_ids = false) {
            $args = array(
                'deliveryId' => $shipment_ids ?? implode(',', array_reverse($this->get_shipment_ids())),
                'shipmentId' => $shipment_ids ?? implode(',', array_reverse($this->get_shipment_ids()))
            );
            return $this->helpers->cargoAPI("https://api.cargo.co.il/Webservice/generateShipmentLabel", $args);
        }

        /**
         * @param array $order_ids
         * @return array
         */
        function order_ids_to_shipment_ids($order_ids = []) {
            if ( !empty($order_ids) ) {
                $shipmentIds = [];

                foreach ($order_ids as $order_id) {
                    $cargo_shipping = new CSLFW_Cargo_Shipping($order_id);
                    $cargo_delivery_id  = $cargo_shipping->get_shipment_ids();

                    if ( $cargo_delivery_id ) {
                        $shipmentIds = array_merge($shipmentIds, $cargo_delivery_id);
                    }
                }
                return $shipmentIds;
            } else {
                wp_die('Empty order_ids array passed to order_ids_to_shipment_ids');
            }
        }

        public function clean_cookies( $order_id ) {
            setcookie("cargoPointID", "", time()-3600);
            setcookie("CargoCityName", "", time()-3600);
            setcookie("cargoPhone", "", time()-3600);
            setcookie("cargoLongitude", "", time()-3600);
            setcookie("cargoLatitude", "", time()-3600);
            setcookie("fullAddress", "", time()-3600);
            setcookie("cargoStreetNum", "", time()-3600);
            setcookie("CargoCityName", "", time()-3600);
            setcookie("cargoPointName", "", time()-3600);
        }
    }
}
