<?php


namespace CSLFW\Includes\CargoAPI;

use CSLFW\Includes\CargoAPI\Helpers;

class CargoAPIV2
{
    use Helpers;

    private $api_key;
    private $headers;

    protected $host = 'https://api-v2.cargo.co.il/api/';

    public function __construct()
    {
        $this->api_key = get_option('cslfw_cargo_api_key');

        if ($this->api_key) {
            $this->headers = [
                "Authorization" => "Bearer {$this->api_key}",
            ];
        }
    }

    /**
     * @param $args
     * @return mixed
     */
    public function createShipment($args)
    {
        $cargoObject = $this->transformFromOldToNew($args['Params']);

        $logs = new \CSLFW_Logs();
        $logs->add_log_message('cargo.apiV2.shipment-create', [
            'request' => $cargoObject
        ]);
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
            "cod_type" => $oldApiParams['CashOnDeliveryType'] ?? 0,
            "carrier_id" => $oldApiParams['CarrierID'],
            "order_id" => $oldApiParams['OrderID'],
            "notes" => $oldApiParams['Note'],
            "website" => $oldApiParams['website'],
            "platform" => "Wordpress",
            "customer_code" => $oldApiParams['customerCode'],
            "to_address" => $oldApiParams['to_address'],
            "from_address" => $oldApiParams['from_address']
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
     * @param int $shipment_id
     * @param int $customer_code
     * @param int $status
     * @return mixed
     */
    public function updateShipmentStatus(int $shipment_id, int $customer_code, int $status)
    {
        $data = [
            "shipment_id" => $shipment_id,
            "customer_code" => $customer_code,
            "status_code" => $status
        ];

        return $this->put( "{$this->host}shipments/update-status", $data, $this->headers);
    }

    /**
     * @return array
     */
    public function getPointsCities()
    {
        $boxPoints = $this->getPickupPoints();

        if (!$boxPoints->errors) {
            return array_unique(array_map(function($point) {
                return $point->CityName;
            }, $boxPoints->data ?? []));
        } else {
            return [];
        }
    }

    /**
     * @param $city
     * @return mixed
     */
    public function checkSuperExpress($city)
    {
        $args = [
            "city" => $city,
        ];

        return $this->post("{$this->host}shipments/allowed-for-super-express", $args);
    }

    /**
     * @return mixed
     */
    public function getPickupPoints()
    {
        // TODO make with cache
        $pickup_points = get_transient('cslfw_pickup_points');

        if (!$pickup_points) {
            $pickup_points = $this->get( "{$this->host}shipments/get-pickup-points", [], $this->headers);

            if (!$pickup_points->errors && !empty($pickup_points->data)) {
                set_transient('cslfw_pickup_points', $pickup_points, 3600);
            }
        }

        return $pickup_points;
    }

    public function getPickupPointsIndexed()
    {
        $indexed = get_transient('cslfw_pickup_points_indexed');

        if (!$indexed) {
            $pickup_points = $this->get( "{$this->host}shipments/get-pickup-points", [], $this->headers);

            if (!$pickup_points->errors && !empty($pickup_points->data)) {
                $indexed = [];

                foreach ($pickup_points->data as $point) {
                    $indexed[$point->DistributionPointID] = $point;
                }

                set_transient('cslfw_pickup_points_indexed', $indexed,  3600);
            }
        }

        return $indexed;
    }

    public function getPickupPointsCityIndexed()
    {
        $indexed = get_transient('cslfw_pickup_points_city_indexed');

        if (!$indexed) {
            $pickup_points = $this->get( "{$this->host}shipments/get-pickup-points", [], $this->headers);

            if (!$pickup_points->errors && !empty($pickup_points->data)) {
                $indexed = [];

                foreach ($pickup_points->data as $point) {
                    if (isset($indexed[$point->CityName])) {
                        $indexed[$point->CityName][] = $point;
                    } else {
                        $indexed[$point->CityName] = [$point];
                    }
                }

                set_transient('cslfw_pickup_points_city_indexed', $indexed,  3600);
            }
        }

        return $indexed;
    }

    /**
     * @param null $pointId
     * @return mixed|null
     */
    public function findPointById($pointId = null)
    {
        $pickup_points = $this->getPickupPointsIndexed();

        if (!empty($pickup_points) && $pointId) {
            $foundPoint = $pickup_points[$pointId] ?? null;

            if ($foundPoint) {
                return (object) ['errors' => false, 'data' => $foundPoint, 'messages' => 'Point found'];
            } else {
                return (object) ['errors' => true, 'data' => [], 'messages' => 'Point not found'];
            }
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
        $pickup_points = $this->getPickupPointsCityIndexed();

        if (!empty($pickup_points) && $city) {
            $logs = new \CSLFW_Logs();
            $logs->add_log_message('cached indexed point', ['pp' => $pickup_points]);
            $foundPoint = $pickup_points[$city] ?? null;


            $logs->add_log_message('cached indexed point', [
                'city' => $city,
                'point' => $foundPoint
            ]);

            if ($foundPoint) {
                return (object) ['errors' => false, 'data' => $foundPoint, 'messages' => 'Point found'];
            } else {
                return (object) ['errors' => true, 'data' => [], 'messages' => 'Point not found'];
            }
        } else {
            return (object) ['errors' => true, 'data' => [], 'messages' => 'Point not found'];
        }
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
