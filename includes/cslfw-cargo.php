<?php
/**
 * Cargo shipping object.
 *
 */
require CSLFW_PATH . '/includes/cslfw-helpers.php';
require CSLFW_PATH . '/includes/cslfw-logs.php';

if( !class_exists('CSLFW_Cargo_Shipping') ) {
    class CSLFW_Cargo_Shipping
    {
        public $deliveries;
        public $order_id;

        function __construct($order_id = 0) {
            $this->helpers = new CSLFW_Helpers();

            $this->order_id  = $order_id;
            $this->deliveries = get_post_meta($order_id, 'cslfw_shipping', true) ? get_post_meta($order_id, 'cslfw_shipping', true) : [];


            add_action('woocommerce_new_order', array($this, 'clean_cookies'), 10, 1);
        }

        public function get_id() {
            return $this->shipping_id;
        }

        function createCargoObject($args = []) {
            $logs = new CSLFW_Logs();
            $order = wc_get_order( $this->order_id );
            if ( $this->deliveries && is_array($this->deliveries) && count($this->deliveries) >= 4 ) {
                return array('shipmentId' => "", 'error_msg' => "Maximum allowed amount of shipments is 4 per order. orderID = $this->order_id");
            }

            $order_data         = $order->get_data();
            $shipping_method    = @array_shift($order->get_shipping_methods());
            $shipping_method_id = $shipping_method['method_id'];
            $cargo_box_style    = get_post_meta($order->get_id(), 'cslfw_box_shipment_type', true);


            $CarrierName     = $shipping_method_id == 'woo-baldarp-pickup' ? 'B0X' : 'EXPRESS';
            $customer_code   = $shipping_method_id == 'woo-baldarp-pickup' ? get_option('shipping_cargo_box') : get_option('shipping_cargo_express');

            $name = $order_data['shipping']['first_name'] ? $order_data['shipping']['first_name']. ' ' . $order_data['shipping']['last_name'] : $order_data['billing']['first_name'] . ' ' . $order_data['billing']['last_name'];

            $notes = '';
            if ( $args['fulfillment'] ) {
                foreach ($order->get_items() as $item) {
                    $product = wc_get_product($item->get_product_id());
                    $notes .= '|' .  $product->get_sku() . '*' . $item->get_quantity();
                }
            }
            $notes = substr($notes, 1);
            $notes .= $order_data['customer_note'];

            $data['Method'] = "ship";
            $data['Params'] = array(
                'shipping_type'         => isset($args['shipping_type']) ? $args['shipping_type'] : 1,
                'doubleDelivery'        => isset($args['double_delivery']) ? $args['double_delivery'] : 1,
                'noOfParcel'            => isset($args['no_of_parcel']) ? $args['no_of_parcel'] : 0,
                'TotalValue'            => $order->get_total(),
                'TransactionID'         => $this->order_id,
                'ContentDescription'    => "",
                'CashOnDelivery'        => isset($args['cargo_cod']) && $args['cargo_cod'] ? floatval($order->get_total()) : 0,
                'CarrierName'           => "CARGO",
                'CarrierService'        => $CarrierName,
                'CarrierID'             => $shipping_method_id == 'woo-baldarp-pickup' ? 0 : 1,
                'OrderID'               => $this->order_id,
                'PaymentMethod'         => $order_data['payment_method'],
                'Note'                  => $notes,
                'customerCode'          => $customer_code,

                'to_address' => array(
                    'name'      => $name,
                    'company'   => $order_data['shipping']['company'] ?? $order_data['billing']['company'],
                    'street1'   => !empty( $order_data['shipping']['address_1'] ) ? $order_data['shipping']['address_1'] : $order_data['billing']['address_1'],
                    'street2'   => !empty( $order_data['shipping']['address_2'] ) ? $order_data['shipping']['address_2'] : $order_data['billing']['address_2'],
                    'city'      =>  !empty( $order_data['shipping']['city'] ) ? $order_data['shipping']['city'] : $order_data['billing']['city'],
                    'state'     =>  !empty( $order_data['shipping']['state'] ) ? $order_data['shipping']['state'] : $order_data['billing']['state'],
                    'zip'       =>  !empty( $order_data['shipping']['postcode'] ) ? $order_data['shipping']['postcode'] : $order_data['billing']['postcode'],
                    'country'   =>  !empty( $order_data['shipping']['country'] ) ? $order_data['shipping']['country'] : $order_data['billing']['country'],
                    'phone'     =>  !empty( $order_data['shipping']['phone'] ) ? $order_data['shipping']['phone'] : $order_data['billing']['phone'],
                    'email'     =>  !empty( $order_data['shipping']['email'] ) ? $order_data['shipping']['email'] : $order_data['billing']['email'],
                    'floor'     => get_post_meta($this->order_id, 'cargo_floor', TRUE),
                    'appartment' => get_post_meta($this->order_id, 'cargo_apartment', TRUE),
                ),

                'from_address' => array(
                    'name'      => $name,
                    'company'   => get_option( 'website_name_cargo' ),
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

            if ( $data['Params']['CashOnDelivery'] ) {
                $data['Params']['CashOnDeliveryType'] = isset($args['cargo_cod_type']) ? $args['cargo_cod_type'] : 0;
            }

            if ( $shipping_method_id == 'woo-baldarp-pickup' ) {
                if ( $cargo_box_style !== 'cargo_automatic' || isset($args['box_point']) ) {
                    $chosen_point = $args['box_point'];
                    $data['Params']['to_address'] = array(
                        'name'      => $name,
                        'company'   => get_option( 'website_name_cargo' ),
                        'street1'   => isset($args['box_point']) ? $chosen_point->StreetNum : get_post_meta($this->order_id, 'StreetNum', TRUE),
                        'street2'   => isset($args['box_point']) ? $chosen_point->StreetName : get_post_meta($this->order_id, 'StreetName', TRUE),
                        'city'      => isset($args['box_point']) ? $chosen_point->CityName : get_post_meta($this->order_id, 'CityName', TRUE),
                        'state'     => "",
                        'zip'       => "",
                        'country'   => "",
                        'phone'     =>  !empty( $order_data['shipping']['phone'] ) ? $order_data['shipping']['phone'] : $order_data['billing']['phone'],
                        'email'     =>  !empty( $order_data['shipping']['email'] ) ? $order_data['shipping']['email'] : $order_data['billing']['email'],
                    );

                    $data['Params']['boxPointId'] = get_post_meta($this->order_id, 'DistributionPointID', TRUE) ?? get_post_meta($this->order_id, 'cargo_DistributionPointID', TRUE);
                    $data['Params']['boxPointId'] = isset($args['box_point']) ? $chosen_point->DistributionPointID : $data['Params']['boxPointId'];
                } else {
                    $address = $data['Params']['to_address']['city'] . ',' . $data['Params']['to_address']['street2'] . ' ' . $data['Params']['to_address']['street1'];
                    $geocoding = $this->helpers->cargoAPI('https://api.carg0.co.il/Webservice/cargoGeocoding', array('address' => $address) );
                    if ( $geocoding->error === false ) {

                        if ( !empty($geocoding->data->results) ) {
                            $geocoding = $geocoding->data->results[0]->geometry->location;
                            $closest_point = $this->helpers->cargoAPI('https://api.carg0.co.il/Webservice/findClosestPoints', array('lat' => $geocoding->lat, 'long' => $geocoding->lng, 'distance' => 10) );

                            if ( $closest_point->error === false ) {
                                if ( !empty($closest_point->closest_points) ) {
                                    // THE SUCCESS FOR DETERMINE CARGO POINT ID IN AUTOMATIC MODE.
                                    $chosen_point = $closest_point->closest_points[0]->point_details;
                                    $data['Params']['boxPointId'] = $chosen_point->DistributionPointID;
                                    $order->save();
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
         * Main create shipment function
         *
         * @param $order_id
         * @param array $args
         * @return array|int|string[]
         */
        public function createShipment($args = []) {
            $logs = new CSLFW_Logs();
            $data = $this->createCargoObject($args);

            if ( !isset($data['Method']) ) return $data;

            $response = $this->passDataToCargo( $data );
            $message = '==============================' . PHP_EOL;

            if ( $response['shipmentId'] != '' ) {
                $response['all_data'] = $this->addShipment($data['Params'], $response);
                $message .= "ORDER ID : $this->order_id | DELIVERY ID  : {$response['shipmentId']} | SENT TO CARGO ON : ".date('Y-m-d H:i:d')." SHIPMENT TYPE : {$data['Params']['CarrierName']} | CUSTOMER CODE : {$data['Params']['customerCode']}" . PHP_EOL;
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
            $this->deliveries[$shipment_data['shipmentId']] = array(
                'driver_name'   => $shipment_data['drivername'],
                'line_number'   => $shipment_data['linetext'],
                'customer_code' => $shipment_params['customerCode'],
                'status'        => array(
                    'number' => $shipment_data['status_number'] ?? 1,
                    'text' => $shipment_data['status_text'] ?? 'Open',
                )
            );

            if ( isset($shipment_params['boxPointId']) ) {
                $this->deliveries[$shipment_data['shipmentId']]['box_id'] = $shipment_params['boxPointId'];
            }
            update_post_meta( $this->order_id, 'cslfw_shipping', $this->deliveries );

            return $this->deliveries;
        }

        public function get_shipment_ids() {

            return array_keys($this->deliveries);
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

                $status = $this->helpers->cargoAPI('https://api.carg0.co.il/Webservice/CreateShipment', $data);
                return (array) $status;
            } else {
                return 0;
            }
        }

        /**
         * Check the Shipping Setting from cargo
         *
         * @param $_POST DATA
         * @return int shipping Status
         */
        function getOrderStatusFromCargo( $shipping_id ) {
            // TODO continue to work here.
            $order = wc_get_order($this->order_id);

            $post_data = array(
                'deliveryId' => (int) $shipping_id,
                'DeliveryType' => (int) $this->deliveries[$shipping_id]['box_id'] ? 'BOX' : 'EXPRESS',
                'customerCode' => (int) $this->deliveries[$shipping_id]['box_id'] ? get_option('shipping_cargo_box') : get_option('shipping_cargo_express'),
            );

            $data = (array) $this->helpers->cargoAPI("https://api.carg0.co.il/Webservice/CheckShipmentStatus",  $post_data);
            if ( $data['errorMsg']  == '' && $shipping_id) {
                if ( (int) $data['deliveryStatus'] === 8 ) {
                    if ( $this->deliveries ) {
                        $index = array_search($shipping_id, $this->deliveries);
                        unset($this->deliveries[$index]);
                        update_post_meta( $this->order_id, 'cslfw_shipping', $this->deliveries );
                    }
                } elseif ( (int) $data['deliveryStatus'] > 0) {
                    $this->deliveries[$shipping_id]['status']['number'] = (int) $data['deliveryStatus'];
                    $this->deliveries[$shipping_id]['status']['text'] = sanitize_text_field( $data['DeliveryStatusText']);
                    update_post_meta( $this->order_id, 'cslfw_shipping', $this->deliveries );

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


        function getShipmentLabel($shipment_ids = false) {
            $args = array(
                'deliveryId' => $shipment_ids ? $shipment_ids : implode(',', array_reverse($this->get_shipment_ids())),
                'shipmentId' => $shipment_ids ? $shipment_ids : implode(',', array_reverse($this->get_shipment_ids()))
            );
            return $this->helpers->cargoAPI("https://api.carg0.co.il/Webservice/generateShipmentLabel", $args);
        }

        /**
         * @param array $order_ids
         * @return array
         */
        function order_ids_to_shipment_ids($order_ids = []) {
            if ( !empty($order_ids) ) {
                $shipment_ids = [];
                foreach ($order_ids as $order_id) {
                    $cargo_delivery_id  = get_post_meta( $order_id, 'cslfw_shipping', true );
                    if ( $cargo_delivery_id ) {
                        $cargo_delivery_id = array_keys($cargo_delivery_id);
                        array_push($shipment_ids,  ...$cargo_delivery_id);
                    }
                }
                return $shipment_ids;
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

        public function get_box_details( $order_id ) {

        }
    }
}
