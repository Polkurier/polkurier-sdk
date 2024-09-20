<?php

namespace PolkurierWebServiceApi\Methods;

use PolkurierWebServiceApi\Response;
use PolkurierWebServiceApi\Entities\Pack;
use PolkurierWebServiceApi\Util\Arr;

class IsMultiPickupAvailable extends AbstractMethod
{
    /**
     * @var
     */
    private $senderPostCode;
    /**
     * @var
     */
    private $shipmentType;
    /**
     * @var
     */
    private $courier;
    /**
     * @var array
     */
    private $packs = [];

    /**
     * @return string
     */
    public function getName()
    {
        return 'is_multi_pickup_available';
    }

    /**
     * @return array
     */
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

    /**
     * @param $senderPostCode
     * @return $this
     */
    public function setSenderPostCode($senderPostCode)
    {
        $this->senderPostCode = $senderPostCode;
        return $this;
    }

    /**
     * @param $shipmentType
     * @return $this
     */
    public function setShipmentType($shipmentType)
    {
        $this->shipmentType = $shipmentType;
        return $this;
    }

    /**
     * @param $courier
     * @return $this
     */
    public function setCourier($courier)
    {
        $this->courier = $courier;
        return $this;
    }

    /**
     * @param Pack $pack
     * @return $this
     */
    public function addPack(Pack $pack)
    {
        $this->packs[] = $pack;
        return $this;
    }

    /**
     * @param Response $response
     * @return mixed|IsMultiPickupAvailable|null
     */
    public function setResponseData(Response $response)
    {
        return Arr::get($response->get('response'), 'isMultiPickupAvailable');
    }
}
