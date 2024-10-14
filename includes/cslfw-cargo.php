<?php
/**
 * Cargo shipping object.
 *
 */
if ( ! defined( 'ABSPATH' ) ) exit;

use CSLFW\Includes\CargoAPI\Cargo;
use CSLFW\Includes\CargoAPI\CargoAPIV2;
use CSLFW\Includes\CargoAPI\CSLFW_Order;
use CSLFW\Includes\CSLFW_Helpers;

require CSLFW_PATH . '/includes/cslfw-logs.php';
if( !class_exists('CSLFW_Cargo_Shipping') ) {
    class CSLFW_Cargo_Shipping
    {
        public $deliveries;
        public $order_id;
        public $order;
        public $cargo;
        public $cargoOrder;
        /**
         * @var CSLFW_Helpers
         */
        private $helpers;

        function __construct($order_id = 0) {
            $api_key = get_option('cslfw_cargo_api_key');

            if ($api_key) {
                $this->cargo = new CargoAPIV2();
            } else {
                $this->cargo = new Cargo();
            }

            $this->helpers = new CSLFW_Helpers();

            if ($order_id) {
                $this->order = wc_get_order($order_id);
                $this->order_id = $order_id;
                $this->deliveries = $this->order->get_meta('cslfw_shipping', true) ?? [];
                $this->cargoOrder = new CSLFW_Order($this->order);
            }

            add_action('init', [$this, 'add_cors_http_header']);

            add_action('woocommerce_new_order', [$this, 'clean_cookies'], 10, 1);
        }

        /**
         *
         */
        function add_cors_http_header(){
            header("Access-Control-Allow-Origin: *");
        }

        /**
         * Making object for cargo API
         *
         * @param array $args
         * @return string[]
         */
        function createCargoObject($args = []) {
            if ( $this->deliveries && is_array($this->deliveries) && count($this->deliveries) >= 4 ) {
                return [
                    'shipmentId' => "",
                    'error_msg' => "Maximum allowed amount of shipments is 4 per order. orderID = $this->order_id"
                ];
            }

            $order_data         = $this->order->get_data();
            $shipping_method_id = $this->cargoOrder->getShippingMethod();

            if ($shipping_method_id === null || empty($shipping_method_id)) {
                return [
                    'shipmentId' => "",
                    'shipping_method' => $shipping_method_id,
                    'error_msg' => "No shipping methods found. Contact support please."
                ];
            }
            if (in_array($this->order->get_status(), ['cancelled', 'refunded', 'pending'])) {
                return [
                    'shipmentId' => "",
                    'error_msg' => "Cancelled, pending or refunded order can\'t be processed."
                ];
            }

            $isBoxShipment = $shipping_method_id === 'woo-baldarp-pickup';
            $isExpress24 = $shipping_method_id === 'cargo-express-24';

            $cargo_box_style    = $this->order->get_meta('cslfw_box_shipment_type', true) ?? 'cargo_automatic';
            $cargo_box_style    = empty($cargo_box_style) ? 'cargo_automatic' : $cargo_box_style;

            $pickupCustomerCode = get_option('shipping_pickup_code');

            $customer_code = $isBoxShipment ? get_option('shipping_cargo_box') : get_option('shipping_cargo_express');
            $customer_code = $isExpress24 ? get_option('shipping_cargo_express_24') : $customer_code;

            $shipping_type = (int) $args['shipping_type'] ?? 1;
            if ($shipping_type === 2 ) {
                $customer_code = $pickupCustomerCode ? $pickupCustomerCode :  get_option('shipping_cargo_express');
            }

            $name = $order_data['shipping']['first_name'] ? $order_data['shipping']['first_name']. ' ' . $order_data['shipping']['last_name'] : $order_data['billing']['first_name'] . ' ' . $order_data['billing']['last_name'];

            $notes = '';
            $cslfw_fulfill_all = get_option('cslfw_fulfill_all');
            if ( (isset($args['fulfillment']) && $args['fulfillment']) || $cslfw_fulfill_all ) {
                foreach ($this->order->get_items() as $item) {
                    $product = $item['variation_id'] ? wc_get_product($item['variation_id']) : wc_get_product($item['product_id']);
                    $notes .= '|' .  $product->get_sku() . '*' . $item->get_quantity();
                }
            }
            $notes = substr($notes, 1);
            $notes .= $order_data['customer_note'];

            $website = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
            $website.= sanitize_text_field($_SERVER['HTTP_HOST']);

            $toPhone = isset($order_data['shipping']['phone']) && !empty($order_data['shipping']['phone']) ? $order_data['shipping']['phone'] : $order_data['billing']['phone'];
            $toPhone = apply_filters( 'cslfw_change_recipient_phone', $toPhone, $this->order );

            $data['Method'] = "ship";
            $data['Params'] = [
                'shipping_type'         => $shipping_type,
                'doubleDelivery'        => $args['double_delivery'] ?? 1,
                'noOfParcel'            => $args['no_of_parcel'] ?? 0,
                'TransactionID'         => $this->order_id,
                'CashOnDelivery'        => isset($args['cargo_cod']) && $args['cargo_cod'] ? floatval($this->order->get_total()) : 0,
                'TotalValue'            => floatval($this->order->get_total()),
                'CarrierID'             => $isBoxShipment ? 0 : 1,
                'OrderID'               => $this->order_id,
                'PaymentMethod'         => $order_data['payment_method'],
                'Note'                  => $notes,
                'customerCode'          => $customer_code,
                'website'               => $website,
                'Platform'              => 'Wordpress',

                'to_address' => [
                    'name'      => $name,
                    'company'   => $order_data['shipping']['company'] ?? $name,
                    'street1'   => $order_data['shipping']['address_1'] ?? $order_data['billing']['address_1'],
                    'street2'   => isset($order_data['shipping']['address_2']) && !empty($order_data['shipping']['address_2']) ? $order_data['shipping']['address_2'] : $order_data['billing']['address_2'],
                    'city'      => isset($order_data['shipping']['city']) && !empty($order_data['shipping']['city']) ? $order_data['shipping']['city'] : $order_data['billing']['city'],
                    'country'   => $order_data['shipping']['country'] ?? $order_data['billing']['country'],
                    'phone'     => $toPhone,
                    'email'     => $order_data['shipping']['email'] ?? $order_data['billing']['email'],
                    'floor'     => $this->order->get_meta('cargo_floor', true),
                    'appartment' => $this->order->get_meta('cargo_apartment', true),
                ],
                'from_address' => [
                    'name'      => apply_filters('cslfw_from_address_name', get_option('website_name_cargo'), $this->order_id),
                    'company'   => apply_filters('cslfw_from_address_name', get_option('website_name_cargo'), $this->order_id),
                    'street1'   => get_option('from_street'),
                    'street2'   => get_option('from_street_name'),
                    'city'      => get_option('from_city'),
                    'country'   => $order_data['shipping']['country'] ?? $order_data['billing']['country'],
                    'phone'     => get_option('phonenumber_from'),
                    'email'     => $order_data['shipping']['email'] ?? $order_data['billing']['email'],
                ]
            ];

            if ($shipping_type === 2) {
                $tmp_from_address = $data['Params']['from_address'];
                $data['Params']['from_address'] = $data['Params']['to_address'];
                $data['Params']['to_address'] = $tmp_from_address;
                $data['Params']['CarrierID'] = 1;

                if (isset($data['Params']['boxPointId'])) {
                    unset($data['Params']['boxPointId']);
                }
            }

            if ( $data['Params']['CashOnDelivery'] ) {
                $data['Params']['CashOnDeliveryType'] = $args['cargo_cod_type'] ?? 0;
            }

            if ($isBoxShipment && $shipping_type !== 2) {
                if ( $cargo_box_style !== 'cargo_automatic' || isset($args['box_point']) ) {
                    $chosen_point = $args['box_point'] ?? null;

                    $data['Params']['boxPointId'] = $this->order->get_meta('cargo_DistributionPointID', true);
                    $data['Params']['boxPointId'] = $chosen_point->DistributionPointID ?? $data['Params']['boxPointId'];
                }
            }

            return $data;
        }

        /**
         * Updating wc order status based on option select
         */
        public function update_wc_status() {
            $cargo_status = get_option('cargo_order_status');
            if ($cargo_status) {
                $this->order->update_status($cargo_status);
                $this->order->save();
            }
        }

        /**
         * Main create shipment function
         *
         * @param array $args
         * @return mixed|string[]
         */
        public function createShipment($args = []) {
            $logs = new CSLFW_Logs();
            $data = $this->createCargoObject($args);

            if ( !isset($data['Params']) ) return $data;

            $response = $this->cargo->createShipment($data);
            $message = '==============================' . PHP_EOL;

            if (!$response->errors) {
                $response->all_data = $this->addShipment($data['Params'], $response->data);
                $this->update_wc_status();
                $message .= "ORDER ID : $this->order_id | DELIVERY ID  : {$response->data->shipment_id} | SENT TO CARGO ON : ".date('Y-m-d H:i:d')." CarrierID : {$data['Params']['CarrierID']} | CUSTOMER CODE : {$data['Params']['customerCode']}" . PHP_EOL;
                if( $data['Params']['CarrierID'] === 0) {
                    $message    .= "CARGO BOX POINT ID : {$data['Params']['boxPointId']}". PHP_EOL;
                }
            }

            $message .= "Cargo.shipment.create response::";
            $logs->add_log_message($message, [
                'response' => $response
            ]);
            return $response;
        }

        /**
         * @param $shipment_params
         * @param $shipment_data
         * @return array
         */
        public function addShipment( $shipment_params, $shipment_data ) {
            $delivery = is_array($this->deliveries) ? $this->deliveries : [];
            if ($shipment_data->shipment_id > 0) {
                $delivery[$shipment_data->shipment_id] = [
                    'driver_name'   => $shipment_data->driver_name,
                    'line_number'   => $shipment_data->line_text,
                    'customer_code' => $shipment_params['customerCode'],
                    'created_at' => date('Y-m-d H:i:s'),
                    'status'        => [
                        'number' => $shipment_data->status_number ?? 1,
                        'text' => $shipment_data->status_text ?? 'Open',
                    ]
                ];

                if (isset($shipment_params['boxPointId']) && $shipment_params['boxPointId']) {
                    $delivery[$shipment_data->shipment_id]['box_id'] = $shipment_params['boxPointId'];
                }

                $this->deliveries = $delivery;

                $this->order->update_meta_data('cslfw_shipping', $this->deliveries);
                $this->order->save();
            }


            return $this->deliveries;
        }

        /**
         * @return array|int[]|string[]
         */
        public function get_shipment_ids() {
            return $this->deliveries ? array_keys($this->deliveries) : [];
        }

        /**
         * @return array
         */
        public function get_shipment_data() {
            return $this->deliveries;
        }

        /**
         * @param $shipping_id
         * @param $order_id
         * @return array|string[]
         */
        function getOrderStatusFromCargo($shipping_id) {
            if (in_array($this->order->get_status(), ['cancelled', 'refunded', 'pending'])) {
                return [
                    "type" => "failed",
                    "data" => "Can't process order with cancelled, pending or refunded status"
                ];
            }

            if ($this->deliveries) {
                $shipment = $this->deliveries[$shipping_id];
                $customer_code = $shipment['customer_code'];
            } else {
                $shipping_method_id = $this->cargoOrder->getShippingMethod();

                if (is_null($shipping_method_id)) {
                    return [
                        "type" => "failed",
                        "order_id" => $this->order_id,
                        "shipping_method_id" => $shipping_method_id,
                        "data" => 'No shipping methods found. Contact Support please.'
                    ];
                }

                $isBoxShipment = $shipping_method_id === 'woo-baldarp-pickup';
                $isExpress24 = $shipping_method_id === 'cargo-express-24';

                $customer_code = $isBoxShipment ? get_option('shipping_cargo_box') : get_option('shipping_cargo_express');
                $customer_code = $isExpress24 ? get_option('shipping_cargo_express_24') : $customer_code;
            }

            $shipment_status = $this->cargo->checkShipmentStatus($shipping_id, $customer_code);

            if (!$shipment_status->errors && $shipping_id) {
                $data = $shipment_status->data;
                if ($data->status_code === 8) {
                    if ($this->deliveries) {
                        unset($this->deliveries[$shipping_id]);
                        $this->order->update_meta_data('cslfw_shipping', $this->deliveries );
                        $this->order->save();
                    }
                    $response = [
                        "type" => "success",
                        "data" => $data->status_text,
                        "orderStatus" => $data->status_code
                    ];
                } elseif ($data->status_code > 0) {
                    $this->deliveries[$shipping_id]['status']['number'] = $data->status_code;
                    $this->deliveries[$shipping_id]['status']['text'] = sanitize_text_field($data->status_text);

                    $this->order->update_meta_data('cslfw_shipping', $this->deliveries);

                    $cslfw_complete_orders = get_option('cslfw_complete_orders');

                    if ($data->status_code === 3 && $cslfw_complete_orders) {
                        $this->order->update_status('completed');
                    }

                    $this->order->save();

                    $response = [
                        "type" => "success",
                        "data" => $data->status_text,
                        "orderStatus" => $data->status_code
                    ];
                } else {
                    $response =  [
                        "type" => "failed",
                        "data" => 'Not Getting Data'
                    ];
                }
            } else {
                $response = [
                    "type" => "failed",
                    "data" => $shipment_status->message,
                    "shipping_id" => $shipping_id
                ];
            }

            return $response;
        }

        /**
         * @param $shipping_id
         * @param $status_code
         * @return array|string[]
         */
        function updateShipmentStatus($shipping_id, $status_code)
        {
            if (in_array($this->order->get_status(), ['cancelled', 'refunded', 'pending'])) {
                return [
                    "type" => "failed",
                    "data" => "Can't process order with cancelled, pending or refunded status"
                ];
            }

            if ($this->deliveries) {
                $shipment = $this->deliveries[$shipping_id];
                $customer_code = $shipment['customer_code'];
            } else {
                $shipping_method_id = $this->cargoOrder->getShippingMethod();

                if (is_null($shipping_method_id)) {
                    return [
                        "type" => "failed",
                        "order_id" => $this->order_id,
                        "shipping_method_id" => $shipping_method_id,
                        "data" => 'No shipping methods found. Contact Support please.'
                    ];
                }

                $isBoxShipment = $shipping_method_id === 'woo-baldarp-pickup';
                $isExpress24 = $shipping_method_id === 'cargo-express-24';

                $customer_code = $isBoxShipment ? get_option('shipping_cargo_box') : get_option('shipping_cargo_express');
                $customer_code = $isExpress24 ? get_option('shipping_cargo_express_24') : $customer_code;
            }
            $shipment_status = $this->cargo->updateShipmentStatus($shipping_id, $customer_code, $status_code);

            if (!$shipment_status->errors && $shipping_id) {
                if ($status_code === 8) {
                    if ($this->deliveries) {
                        unset($this->deliveries[$shipping_id]);
                        $this->order->update_meta_data('cslfw_shipping', $this->deliveries );
                        $this->order->delete_meta_data('cslfw_printed_label');
                        $this->order->save();
                    }
                }
            }

            echo json_encode($shipment_status);
            exit;
        }

            /**
         * @param false $shipment_ids
         * @return mixed
         */
        function getShipmentLabel($shipment_ids = null, $orderIds = []) {
            $shipmentIds = $shipment_ids ?? implode(',', array_reverse($this->get_shipment_ids()));

            $args = [
                'deliveryId' => $shipmentIds,
                'shipmentId' => $shipmentIds
            ];

            $orderIds = $orderIds ? $orderIds : [$this->order_id];
            $shipmentsData = $this->helpers->getProductsForLabels($shipmentIds, $orderIds);
            $withProducts = get_option('cslfw_products_in_label');

            if ($withProducts && $shipmentsData) {
                $args['shipmentsData'] = $shipmentsData;
            }

            $cargoLabel = $this->cargo->generateShipmentLabel($args);

            if (!$cargoLabel->errors) {
                if ($orderIds) {
                    foreach ($orderIds as $orderId) {
                        $order = wc_get_order($orderId);
                        $order->update_meta_data('cslfw_printed_label', date('Y-m-d H:i:d'));
                        $order->save();
                    }
                } else {
                    $this->order->update_meta_data('cslfw_printed_label', date('Y-m-d H:i:d'));
                    $this->order->save();
                }
            }
            return $cargoLabel;
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

        /**
         * @param $order_id
         */
        public function clean_cookies($order_id) {
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
