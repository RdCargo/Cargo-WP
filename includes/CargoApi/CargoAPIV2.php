<?php


namespace CSLFW\Includes\CargoAPI;

use CSLFW\Includes\CargoAPI\Helpers;

class CargoAPIV2
{
    use Helpers;

    private $api_key;
    private $headers;

    protected String $host = 'https://api-v2.cargo.co.il/api/';

    public function __construct()
    {
        $this->api_key = get_option('cslfw_cargo_api_key');

        $this->headers = [
            "Authorization" => "Bearer {$this->api_key}",
        ];
    }

    /**
     * @param $args
     * @return mixed
     */
    public function createShipment($args)
    {
        $cargoObject = $this->transformFromOldToNew($args['Params']);

        return $this->post("{$this->host}shipments/create", $cargoObject, $this->headers);
    }

    /**
     * @param $oldApiParams
     * @return array
     */
    public function transformFromOldToNew($oldApiParams)
    {
        $params = [
            "shipping_type" => $oldApiParams['shipping_type'],
            "number_of_parcels" => $oldApiParams['noOfParcel'],
            "double_delivery" => $oldApiParams['doubleDelivery'],
            "total_value" => $oldApiParams['TotalValue'],
            "transaction_id" => $oldApiParams['TransactionID'],
            "cash_on_delivery" => $oldApiParams['CashOnDelivery'],
            "carrier_id" => $oldApiParams['CarrierID'],
            "order_id" => $oldApiParams['OrderID'],
            "notes" => $oldApiParams['Note'],
            "website" => $oldApiParams['website'],
            "platform" => "Wordpress",
            "customer_code" => $oldApiParams['customerCode'],
            "to_address" => $oldApiParams['shipping_type'] === 2 ? $oldApiParams['from_address'] : $oldApiParams['to_address'],
            "from_address" => $oldApiParams['shipping_type'] === 2 ? $oldApiParams['to_address'] : $oldApiParams['from_address']
        ];

        if (isset($oldApiParams['boxPointId']) && $oldApiParams['boxPointId']) {
            $params['box_point_id'] = $oldApiParams['boxPointId'];
        }

        return $params;
    }

    /**
     * @param int $shipping_id
     * @param int $customer_code
     * @return mixed
     */
    public function checkShipmentStatus(int $shipping_id, int $customer_code)
    {
        $args = [
            "shipment_id" => $shipping_id,
            "customer_code" => $customer_code
        ];

        return $this->post("{$this->host}shipments/get-status", $args, $this->headers);
    }

    /**
     * @param $args
     * @return mixed
     */
    public function generateShipmentLabel($args)
    {
        $newArgs = [
            'shipment_ids' => !is_array($args['deliveryId']) ? $args['deliveryId'] : implode(',', $args['deliveryId']),
        ];

        if (isset($args['shipmentsData'])) {
            $newArgs['shipments_data'] = $args['shipmentsData'];
        }
        return $this->post("{$this->host}shipments/print-label", $newArgs, $this->headers);
    }

    /**
     * @param array $deliveryId
     * @return mixed
     */
    public function generateMultipleLabel(array $deliveryId, array $shipmentsData = [])
    {
        $args = [
            'deliveryId' => $deliveryId
        ];

        if ($shipmentsData) {
            $args['shipmentsData'] = $shipmentsData;
        }

        return $this->generateShipmentLabel($args);
    }

    /**
     * @param array $deliveryId
     * @return mixed
     */
    public function generateMultipleLabelsA4(array $deliveryId, $startingPoint = 1)
    {
        $args = [
            'shipment_ids' => !is_array($deliveryId) ? $deliveryId : implode(',', $deliveryId),
            'starting_point' => $startingPoint
        ];

        return $this->post("{$this->host}shipments/print-label-a4", $args, $this->headers);
    }

    /**
     * @param int $shipmentId
     * @param int $customerCode
     * @param int $status
     * @return mixed
     */
    public function updateShipmentStatus(int $shipmentId, int $customerCode, int $status)
    {
        $data = [
            "shipment_id" => $shipmentId,
            "customer_code" => $customerCode,
            "status_code" => $status
        ];

        return $this->put( 'shipments/update-status', $data, $this->headers);
    }

    /**
     * @return array
     */
    public function getPointsCities()
    {
        $boxPoints = $this->getPickupPoints();

        if (!$boxPoints->errors) {
            $cities = array_unique(array_map(function($point) {
                return $point->CityName;
            }, $boxPoints->data));

            return $cities ?? [];
        } else {
            return [];
        }

    }

    /**
     * @return mixed
     */
    public function getPickupPoints()
    {
        return $this->get( "{$this->host}shipments/get-pickup-points", [], $this->headers);
    }

    /**
     * @param null $pointId
     * @return mixed|null
     */
    public function findPointById($pointId = null)
    {
        if ($pointId) {
            $point = $this->post("{$this->host}shipments/get-point-details", ['point_id' => $pointId], $this->headers);

            return $point;
        } else {
            return (object) ['errors' => true, 'data' => [], 'messages' => 'Point not found'];
        }
    }

    /**
     * @param $city
     * @return mixed
     */
    public function getPointsByCity($city)
    {
        $args = [
            'city' => $city
        ];

        return $this->post( "{$this->host}shipments/get-points-by-city", $args, $this->headers);
    }

    /**
     * @param $coordinates
     * @return array
     */
    public function findClosestPoints($latitude, $longitude, $radius = 10)
    {
        $coordinates = [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'radius' => $radius
        ];

        return $this->post("{$this->host}shipments/find-closest-pickup-points", $coordinates, $this->headers);
    }

    /**
     * @param $address
     * @return mixed
     */
    public function cargoGeocoding($address)
    {
        $args = [
            'address' => $address
        ];

        $result = $this->post("{$this->host}shipments/cargo-geocoding", $args, $this->headers);

        return $result;
    }
}
