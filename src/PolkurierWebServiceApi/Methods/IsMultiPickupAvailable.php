<?php

namespace PolkurierWebServiceApi\Methods;

use PolkurierWebServiceApi\Response;
use PolkurierWebServiceApi\Entities\Pack;
use PolkurierWebServiceApi\Util\Arr;

class IsMultiPickupAvailable extends AbstractMethod
{
    private $senderPostCode;
    private $shipmentType;
    private $courier;
    private $packs = [];

    public function getName()
    {
        return 'is_multi_pickup_available';
    }

    public function getRequestData()
    {
        return [
            'senderPostCode' => $this->senderPostCode,
            'shipmentType' => $this->shipmentType,
            'courier' => $this->courier,
            'packs' => array_map(static function (Pack $pack) {
                return $pack->toArray();
            }, $this->packs)
        ];
    }

    public function setSenderPostCode($senderPostCode)
    {
        $this->senderPostCode = $senderPostCode;
        return $this;
    }

    public function setShipmentType($shipmentType)
    {
        $this->shipmentType = $shipmentType;
        return $this;
    }

    public function setCourier($courier)
    {
        $this->courier = $courier;
        return $this;
    }

    public function addPack(Pack $pack)
    {
        $this->packs[] = $pack;
        return $this;
    }

    public function setResponseData(Response $response)
    {
        return Arr::get($response->get('response'), 'isMultiPickupAvailable');
    }
}
