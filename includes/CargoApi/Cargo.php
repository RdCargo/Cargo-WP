<?php

namespace CSLFW\Includes\CargoAPI;

use CSLFW\Includes\CargoAPI\Helpers;

class Cargo
{
    use Helpers;
    protected $host = 'https://api.cargo.co.il/Webservice/';

    public function getPointsCities()
    {
        return $this->post("{$this->host}getCitiesForPlugin");
    }

    public function getPickupPoints()
    {
        return $this->post("{$this->host}getPickUpPoints");
    }

    public function sendToCargo($method, $data = []) {
        return $this->post("{$this->host}/$method", $data);
    }
}
