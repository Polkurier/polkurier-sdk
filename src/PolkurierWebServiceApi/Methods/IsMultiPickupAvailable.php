<?php

namespace PolkurierWebServiceApi\Methods;

use PolkurierWebServiceApi\Entities\Pack;
use PolkurierWebServiceApi\Response;
use PolkurierWebServiceApi\Util\Arr;

class IsMultiPickupAvailable extends AbstractMethod
{

    /**
     * @var string
     */
    private $senderPostCode;

    /**
     * @var string
     */
    private $shipmentType;

    /**
     * @var string
     */
    private $courier;

    /**
     * @var Pack[]
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
            'packs' => $this->packs
        ];
    }

    /**
     * @param $senderPostCode
     * @return IsMultiPickupAvailable
     */
    public function setSenderPostCode($senderPostCode)
    {
        $this->senderPostCode = $senderPostCode;
        return $this;
    }

    /**
     * @param $shipmentType
     * @return IsMultiPickupAvailable
     */
    public function setShipmentType($shipmentType)
    {
        $this->shipmentType = $shipmentType;
        return $this;
    }

    /**
     * @param $courier
     * @return IsMultiPickupAvailable
     */
    public function setCourier($courier)
    {
        $this->courier = $courier;
        return $this;
    }

    /**
     * @param Pack $pack
     * @return IsMultiPickupAvailable
     */
    public function addPack(Pack $pack)
    {
        $this->packs[] = $pack;
        return $this;
    }

    /**
     * @param Response $response
     * @return IsMultiPickupAvailable
     */
    public function setResponseData(Response $response)
    {
        $this->responseData = Arr::get($response->get('response'), 'isMultiPickupAvailable');
        return $this;
    }

}
