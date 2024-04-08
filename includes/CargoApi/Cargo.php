<?php

namespace CSLFW\Includes\CargoAPI;

use CSLFW\Includes\CargoAPI\Helpers;

class Cargo
{
    use Helpers;
    protected $host = 'https://api.cargo.co.il/Webservice/';

    public function getPointsCities()
    {
        $boxPoints = $this->getPickupPoints();
        $cities = array_unique(array_map(function($point) {
            return $point->CityName;
        }, $boxPoints));

        return $cities ?? [];
    }

    public function getPickupPoints()
    {
        $points = $this->post("{$this->host}getPickUpPoints");

        if (empty($points->error_msg)) {
            return $points->PointsDetails;
        } else {
            return [];
        }
    }

    /**
     * @param $args
     * @return mixed
     */
    public function createShipment($args)
    {
        return $this->post("{$this->host}CreateShipment", $args);
    }

    /**
     * @param $args
     * @return mixed
     */
    public function checkShipmentStatus($args)
    {
        return $this->post("{$this->host}CheckShipmentStatus", $args);
    }

    /**
     * @param $args
     * @return mixed
     */
    public function generateShipmentLabel($args)
    {
        return $this->post("{$this->host}generateShipmentLabel", $args);
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

        return $this->post("{$this->host}generateMultipleLabel", $args);
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

        return $this->post("{$this->host}generateMultipleLabelsA4", $args);
    }

    /**
     * @param null $pointId
     * @return mixed|null
     */
    public function getPointsByCity($city = null)
    {
        if (!$city) return [];
        $points = $this->post("{$this->host}getPickUpPoints", ['city' => $city]);

        if (empty($points->error_msg)) {
            return $points->PointsDetails;
        } else {
            return [];
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

            return $points->PointsDetails[0];
        } else {
            return null;
        }
    }

    /**
     * @param $coordinates
     * @return array
     */
    public function findClosestPoints($coordinates)
    {
        $points = $this->post("{$this->host}findClosestPoints", $coordinates);

        if ( $points->error === false && !empty($points->closest_points) ) {
            return $points->closest_points;
        } else {
            return [];
        }
    }
}
