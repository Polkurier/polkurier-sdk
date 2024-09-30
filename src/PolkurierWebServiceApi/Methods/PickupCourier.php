<?php

namespace PolkurierWebServiceApi\Methods;

use PolkurierWebServiceApi\Response;
use PolkurierWebServiceApi\Type\ShipmentType;
use PolkurierWebServiceApi\Util\Arr;

class PickupCourier extends AbstractMethod
{

    /**
     * @var string
     */
    private $pickupdate;

    /**
     * @var string
     */
    private $courier;

    /**
     * @var string
     */
    private $shipfrom;

    /**
     * @var string
     */
    private $parcel = ShipmentType::BOX;

    /**
     * @return string
     */
    public function getName()
    {
        return 'pickup_courier';
    }

    /**
     * @return string
     */
    private function getPickupdate()
    {
        return $this->pickupdate;
    }

    /**
     * @param string $pickupdate
     * @return PickupCourier
     */
    public function setPickupdate($pickupdate)
    {
        $this->pickupdate = $pickupdate;
        return $this;
    }

    /**
     * @return string
     */
    private function getCourier()
    {
        return $this->courier;
    }

    /**
     * @param string $courier
     * @return PickupCourier
     */
    public function setCourier($courier)
    {
        $this->courier = $courier;
        return $this;
    }

    /**
     * @return string
     */
    private function getShipfrom()
    {
        return $this->shipfrom;
    }

    /**
     * @param string $shipfrom
     * @return PickupCourier
     */
    public function setShipfrom($shipfrom)
    {
        $this->shipfrom = $shipfrom;
        return $this;
    }

    /**
     * @return string
     */
    private function getParcel()
    {
        return $this->parcel;
    }

    /**
     * @param string $parcel
     * @return PickupCourier
     */
    public function setParcel($parcel)
    {
        $this->parcel = $parcel;
        return $this;
    }

    /**
     * @return array
     */
    public function getRequestData()
    {
        return [
            'pickupdate' => $this->getPickupdate(),
            'courier' => $this->getCourier(),
            'shipfrom' => $this->getShipfrom(),
            'parcel' => $this->getParcel()
        ];
    }

    /**
     * @return PickupCourier
     */
    public function setResponseData(Response $response)
    {
        $this->responseData = [];
        $response = $response->get('response');
        $this->responseData['pickupdate'] = Arr::get($response, 'pickupdate', false);
        $this->responseData['time'] = Arr::get($response, 'time', []);
        return $this;
    }

}
