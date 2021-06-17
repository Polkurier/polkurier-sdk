<?php

namespace PolkurierWebServiceApi\Methods;

use PolkurierWebServiceApi\Entities\COD;
use PolkurierWebServiceApi\Entities\CourierServiceInterface;
use PolkurierWebServiceApi\Entities\Pack;
use PolkurierWebServiceApi\Entities\Pickup;
use PolkurierWebServiceApi\Entities\Recipient;
use PolkurierWebServiceApi\Entities\Sender;

/**
 * Class CreateOrder
 * @package PolkurierWebServiceApi\Methods
 */
class CreateOrder extends AbstractMethod
{
    /**
     * @var string
     */
    private $shipmenttype;
    /**
     * @var string
     */
    private $courier;
    /**
     * @var array
     */
    private $courierservice = [];
    /**
     * @var string
     */
    private $description;
    /**
     * @var Sender
     */
    private $sender;
    /**
     * @var Recipient
     */
    private $recipient;
    /**
     * @var array
     */
    private $packs = [];
    /**
     * @var Pickup
     */
    private $pickup;
    /**
     * @var COD
     */
    private $COD;
    /**
     * @var float
     */
    private $insurance;

    /**
     * CreateOrder constructor.
     */
    public function __construct()
    {
        $this->sender = new Sender();
        $this->recipient = new Recipient();
        $this->COD = new COD();
        $this->pickup = new Pickup();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'create_order';
    }

    /**
     * @return array
     */
    public function getRequestData()
    {
        return [
            'shipmenttype' => $this->getShipmentType(),
            'courier' => $this->getCourier(),
            'courierservice' => array_map(static function (CourierServiceInterface $service) {
                return $service->toArray();
            }, $this->getCourierService()),
            'description' => $this->getDescription(),
            'sender' => $this->getSender()->toArray(),
            'recipient' => $this->getRecipient()->toArray(),
            'packs' => array_map(static function (Pack $pack) {
                return $pack->toArray();
            }, $this->getPack()),
            'pickup' => $this->getPickup()->toArray(),
            'COD' => $this->getCOD()->toArray(),
            'insurance' => $this->getInsurance()
        ];
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
     */
    public function setCourier($courier)
    {
        $this->courier = (string)$courier;
    }

    /**
     * @param $shipmenttype string
     * @return $this
     */
    public function setShipmentType($shipmenttype)
    {
        $this->shipmenttype = (string)$shipmenttype;
        return $this;
    }

    /**
     * @return string
     */
    private function getShipmentType()
    {
        return $this->shipmenttype;
    }

    /**
     * @param string $description
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = (string)$description;
        return $this;
    }

    private function getDescription()
    {
        return $this->description;
    }

    /**
     * @param Sender $sender
     * @return $this
     */
    public function setSender(Sender $sender)
    {
        $this->sender = $sender;
        return $this;
    }

    /**
     * @return Sender
     */
    private function getSender()
    {
        return $this->sender;
    }

    /**
     * @param Recipient $recipient
     * @return $this
     */
    public function setRecipient(Recipient $recipient)
    {
        $this->recipient = $recipient;
        return $this;
    }

    /**
     * @return Recipient
     */
    private function getRecipient()
    {
        return $this->recipient;
    }

    /**
     * @param mixed $insurance
     * @return CreateOrder
     */
    public function setInsurance($insurance)
    {
        $this->insurance = (float)$insurance;
        return $this;
    }

    /**
     * @return float
     */
    private function getInsurance()
    {
        return $this->insurance;
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
     * @return array
     */
    private function getPack()
    {
        return $this->packs;
    }

    /**
     * @param CourierServiceInterface $courierservice
     * @return $this
     */
    public function addCourierService(CourierServiceInterface $courierservice)
    {
        $this->courierservice[] = $courierservice;
        return $this;
    }

    /**
     * @return array
     */
    private function getCourierService()
    {
        return $this->courierservice;
    }


    /**
     * @param Pickup $pickup
     * @return $this
     */
    public function setPickup(Pickup $pickup)
    {
        $this->pickup = $pickup;
        return $this;
    }

    /**
     * @return Pickup
     */
    private function getPickup()
    {
        return $this->pickup;
    }

    /**
     * @param COD $cod
     * @return $this
     */
    public function setCOD(COD $cod)
    {
        $this->COD = $cod;
        return $this;
    }

    /**
     * @return COD
     */
    private function getCOD()
    {
        return $this->COD;
    }

}