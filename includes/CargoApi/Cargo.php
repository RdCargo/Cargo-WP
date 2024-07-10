<?php

namespace CSLFW\Includes\CargoAPI;

use CSLFW\Includes\CargoAPI\Helpers;

class Cargo
{
    use Helpers;
    protected $host = 'https://api.cargo.co.il/Webservice/';

    /**
     * @return array
     */
    public function getPointsCities()
    {
        $boxPoints = $this->getPickupPoints();
        $cities = array_unique(array_map(function($point) {
            return $point->CityName;
        }, $boxPoints->data));

        return $cities ?? [];
    }

    /**
     * @return object
     */
    public function getPickupPoints()
    {
        $points = $this->post("{$this->host}getPickUpPoints");

        if (empty($points->error_msg)) {
            return (object)['errors' => false, 'data' => $points->PointsDetails, 'message' => 'Success.'];
        } else {
            return (object)['errors' => true, 'data' => [], 'message' => $points->error_msg];
        }
    }

    /**
     * @param $args
     * @return mixed
     */
    public function createShipment($args)
    {
        $logs = new \CSLFW_Logs();
        $logs->add_log_message('cargo.api.shipment-create:: ', [
            'request' => $args
        ]);
        $response = $this->post("{$this->host}CreateShipment", $args);

        if (empty($response->shipmentId)) {
            return (object)[
                'errors' => true,
                'data' => (object)[],
                'message' => $response->error_msg
            ];
        } else {
            return (object)[
                'errors' => false,
                'data' => (object)[
                    'shipment_id' => $response->shipmentId,
                    'line_text' => $response->linetext,
                    'driver_name' => $response->drivername,
                ],
                'message' => 'Successfully create shipment'
            ];
        }

    }

    /**
     * @param int $shipment_id
     * @param int $customer_code
     * @return object
     */
    public function checkShipmentStatus(int $shipment_id, int $customer_code)
    {
        $args = [
            'deliveryId' => $shipment_id,
            'customerCode' => $customer_code
        ];
        $shipmentStatus =  $this->post("{$this->host}CheckShipmentStatus", $args);
        $data = (object)[
            "shipment_id" => $shipment_id,
            "status_code" => (int) $shipmentStatus->deliveryStatus,
            "status_text" => $shipmentStatus->DeliveryStatusText
        ];
        $logs = new \CSLFW_Logs();
        $logs->add_debug_message("checkShipmentStatus: {$this->host}CheckShipmentStatus." . wp_json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) . PHP_EOL);

        return (object) ['errors' => false, 'data' => $data, 'messages' => 'Successfully got the status'];
    }

    /**
     * @param int $shipment_id
     * @param int $customer_code
     * @param int $status
     * @return mixed
     */
    public function updateShipmentStatus(int $shipment_id, int $customer_code, int $status)
    {
        $args = [
            "deliveryId" => $shipment_id,
            "CustomerCode" => $customer_code,
            "UpdateStatus" => $status
        ];

        $shipmentStatus =  $this->post("{$this->host}updateStatus", $args);

        $logs = new \CSLFW_Logs();
        $logs->add_debug_message("updateStatus: {$this->host}updateStatus.", ['request' => $args, 'response' => $shipmentStatus]);

        if (empty($shipmentStatus->error_code)) {
            $data = (object)[
                "shipment_id" => $shipment_id,
                "updated" => empty($shipmentStatus->error_code),
                "status_code" => $status,
                "status_text" => ''
            ];

            return (object) ['errors' => false, 'data' => $data, 'messages' => 'Successfully got the status'];
        } else {
            return (object) ['errors' => false, 'data' => [], 'messages' => $shipmentStatus->error_msg];
        }

    }


    /**
     * @param $args
     * @return mixed
     */
    public function generateShipmentLabel($args)
    {
        $labelData = $this->post("{$this->host}generateShipmentLabel", $args);

        if (empty($labelData->error_msg) && $labelData->pdfLink) {
            return (object) ['errors' => false, 'data' => $labelData->pdfLink, 'message' => 'Successfully returned pdf label'];
        } else {
            return (object) ['errors' => true, 'data' => [], 'message' => $labelData->error_msg];
        }
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

        $labelData = $this->post("{$this->host}generateMultipleLabel", $args);

        if (empty($labelData->error_msg) && $labelData->pdfLink) {
            return (object) ['errors' => false, 'data' => $labelData->pdfLink, 'message' => 'Successfully returned pdf label'];
        } else {
            return (object) ['errors' => true, 'data' => [], 'message' => $labelData->error_msg];
        }
    }

    /**
     * @param array $deliveryId
     * @return mixed
     */
    public function generateMultipleLabelsA4(array $deliveryId, $startingPoint = 1)
    {
        $args = [
            'deliveryId' => $deliveryId,
            'startingPoint' =>$startingPoint
        ];

        $labelData = $this->post("{$this->host}generateMultipleLabelsA4", $args);

        if (empty($labelData->error_msg) && $labelData->pdfLink) {
            return (object) ['errors' => false, 'data' => $labelData->pdfLink, 'message' => 'Successfully returned pdf label'];
        } else {
            return (object) ['errors' => true, 'data' => [], 'message' => $labelData->error_msg];
        }
    }

    /**
     * @param null $pointId
     * @return mixed|null
     */
    public function findPointById($pointId = null)
    {
        if ($pointId) {
            $points = $this->post("{$this->host}getPickUpPoints", ['pointId' => $pointId]);

            return (object) ['errors' => false, 'data' => $points->PointsDetails[0], 'message' => 'Success.'];
        } else {
            return (object) ['errors' => true, 'data' => [], 'message' => 'Point not found'];
        }
    }

    /**
     * @param $latitude
     * @param $longitude
     * @param int $radius
     * @return object
     */
    public function findClosestPoints($latitude, $longitude, $radius = 20)
    {
        $coordinates = [
            'lat' => $latitude,
            'long' => $longitude,
            'distance' => $radius
        ];

        $points = $this->post("{$this->host}findClosestPoints", $coordinates);

        if ( $points->error === false && !empty($points->closest_points) ) {
            $data = array_map(function($point) {
                $new_point = $point->point_details;
                $new_point->distance = $point->km;

                return $new_point;
            }, $points->closest_points);

            return (object) ['errors' => false, 'data' => $data, 'message' => 'Success.'];
        } else {
            return (object) ['errors' => true, 'data' => [], 'message' => $points->error];
        }
    }

    /**
     * @param $city
     * @return object
     */
    public function getPointsByCity($city)
    {
        $args = [
            'city' => $city
        ];

        $points = $this->post("{$this->host}getPickUpPoints", $args);

        if (empty($points->error_msg)) {
            return (object) ['errors' => false, 'data' => $points->PointsDetails, 'message' => 'Successfully got the points'];
        } else {
            return (object) ['errors' => true, 'data' => [], 'message' => $points->error_msg];
        }
    }

    /**
     * @param $address
     * @return object
     */
    public function cargoGeocoding($address)
    {
        $args = [
            'address' => $address
        ];

        $result = $this->post("{$this->host}cargoGeocoding", $args);

        if (!$result->error) {
            return (object) ['errors' => false, 'data' => $result->data, 'message' => 'Successfully geocode'];
        } else {
            return (object) ['errors' => true, 'data' => [], 'message' => 'Something went wrong'];
        }
    }
}
