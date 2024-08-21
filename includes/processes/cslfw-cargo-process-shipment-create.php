<?php

use CSLFW\Includes\CargoAPI\Cargo;
use CSLFW\Includes\CargoAPI\CargoAPIV2;

class CSLFW_Cargo_Process_Shipment_Create extends CSLFW_Cargo_Job
{
    private $cargo;
    public $action_name;
    public $last_order_id;
    public $id;

    public function __construct($id = null, $action_name = '', $last_order_id = null)
    {
        if (!empty($id)) $this->id = $id;
        if (!empty($args)) $this->args = $args;
        $this->action_name = $action_name;
        $this->last_order_id = $last_order_id;

        $api_key = get_option('cslfw_cargo_api_key');

        if ($api_key) {
            $this->cargo = new CargoAPIV2();
        } else {
            $this->cargo = new Cargo();
        }
    }

    /**
     * @param null $id
     * @return MailChimp_WooCommerce_Single_Order
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    public function handle()
    {
        $logs = new CSLFW_Logs();
        $logs->add_debug_message("CARGO QUEUE:: started job handle", ['order_id' => $this->id, 'action_name' => $this->action_name]);
        $progress = get_transient( 'cslfw_bulk_shipment_process');

        // Loop through the array of objects
        if ($progress) {
            foreach ($progress as &$singleProgress) {
                if ($singleProgress['orderId'] == $this->id) {
                    $singleProgress['status'] = 'Processing ...';
                    break;
                }
            }
        }

        $cargoShipping = new CSLFW_Cargo_Shipping($this->id);
        $order = wc_get_order($this->id);

        if (!$cargoShipping->get_shipment_data()) {
            $args = [
                'double_delivery' => $this->action_name === 'send-cargo-dd' ? 2 : 1,
                'shipping_type' => $this->action_name === 'send-cargo-pickup' ? 2 : 1
            ];

            $autoCashOnDeliveryMethod = get_option('cslfw_cod_check') ?  get_option('cslfw_cod_check') : 'cod';

            if ($autoCashOnDeliveryMethod === $order->get_payment_method()) {
                $args['cargo_cod'] = $order->get_total();
            }

            if ($distribution_point = (int)$order->get_meta('cargo_DistributionPointID', true)) {
                $point = $this->cargo->findPointById($distribution_point);
                if (!$point->errors) {
                    $args['box_point'] = $point->data;
                }
            }

            $shipment = $cargoShipping->createShipment($args);

            if (!$shipment->errors) {
                $shipment_id = $shipment->data->shipment_id;
                $newProgressStatus = "ShipmentID: $shipment_id";
            } else {
                $newProgressStatus = "ERROR:: {$shipment->message}";
                $logs->add_debug_message("CARGO QUEUE:: processed order", ['order_id' => $this->id, 'action_name' => $this->action_name, 'shipment' => $shipment]);
            }

            $logs->add_debug_message("CARGO QUEUE:: processed order", ['order_id' => $this->id, 'action_name' => $this->action_name, 'shipment' => $shipment]);

        } else {
            $newProgressStatus = 'Order skipped because shipment already exist';
            $logs->add_debug_message("CARGO QUEUE:: skip the order because shipment already exist", ['order_id' => $this->id, 'action_name' => $this->action_name]);
        }

        if ($progress) {
            foreach ($progress as &$singleProgress) {
                if ($singleProgress['orderId'] == $this->id) {
                    $singleProgress['status'] = $newProgressStatus;
                    break;
                }
            }
        }
        set_transient( 'cslfw_bulk_shipment_process', $progress, 300);

        if (!is_null($this->last_order_id) && $this->id === $this->last_order_id) {
            delete_transient('bulk_shipment_create');

            sleep(5);
            delete_transient( 'cslfw_bulk_shipment_process');
        }
    }


    public function toArgsArray()
    {
        return [
            'obj_id' => $this->id,
            'action_name' => $this->action_name,
            'last_order_id' => $this->last_order_id
        ];
    }
}
