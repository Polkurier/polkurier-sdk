<?php

namespace PolkurierWebServiceApi\Methods;

use PolkurierWebServiceApi\Response;
use PolkurierWebServiceApi\Util\Arr;

class IsMultiPickupAvailable extends AbstractMethod
{
    private $senderPostCode;
    private $courier;
    private $shipmentType;
    private $packs = [];

    public function getName()
    {
        return 'is_multi_pickup_available';
    }

    public function getRequestData()
    {
        return [
            'senderPostCode' => $this->senderPostCode,
            'courier' => $this->courier,
            'shipmentType' => $this->shipmentType,
            'packs' => $this->packs,
        ];
    }

    public function setSenderPostCode($senderPostCode)
    {
        $this->senderPostCode = $senderPostCode;
        return $this;
    }

    public function setCourier($courier)
    {
        $this->courier = $courier;
        return $this;
    }

    public function setShipmentType($shipmentType)
    {
        $this->shipmentType = $shipmentType;
        return $this;
    }

    public function setPacks(array $packs)
    {
        $this->packs = $packs;
        return $this;
    }

    public function setResponseData(Response $response)
    {
        $this->responseData = [
            'isMultiPickupAvailable' => Arr::get($response->get('response'), 'isMultiPickupAvailable', false)
        ];
        return $this;
    }
}
