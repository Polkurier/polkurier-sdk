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
     * @var \PolkurierWebServiceApi\Entities\Sender
     */
    private $sender;
    /**
     * @var \PolkurierWebServiceApi\Entities\Recipient
     */
    private $recipient;

    /**
     * @var array
     */
    private $packs = [];
    /**
     * @var \PolkurierWebServiceApi\Entities\Pickup
     */
    private $pickup;
    /**
     * @var \PolkurierWebServiceApi\Entities\COD
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
            'courierservice' => array_map(function (CourierServiceInterface $service) {
                return $service->toArray();
            }, $this->getCourierService()),
            'description' => $this->getDescription(),
            'sender' => $this->getSender()->toArray(),
            'recipient' => $this->getRecipient()->toArray(),
            'packs' => array_map(function (Pack $pack) {
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
        $this->courier = (string) $courier;
    }

    /**
     * @param $shipmenttype string
     * @return $this
     */
    public function setShipmentType($shipmenttype)
    {
        $this->shipmenttype = (string) $shipmenttype;
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
        $this->description = (string) $description;
        return $this;
    }

    private function getDescription()
    {
        return $this->description;
    }

    /**
     * @param \PolkurierWebServiceApi\Entities\Sender $sender
     * @return $this
     */
    public function setSender(Sender $sender)
    {
        $this->sender = $sender;
        return $this;
    }

    /**
     * @return \PolkurierWebServiceApi\Entities\Sender
     */
    private function getSender()
    {
        return $this->sender;
    }

    /**
     * @param \PolkurierWebServiceApi\Entities\Recipient $recipient
     * @return $this
     */
    public function setRecipient(Recipient $recipient)
    {
        $this->recipient = $recipient;
        return $this;
    }

    /**
     * @return \PolkurierWebServiceApi\Entities\Recipient
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
     * @param \PolkurierWebServiceApi\Entities\Pack $pack
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
     * @param \PolkurierWebServiceApi\Entities\CourierServiceInterface $courierservice
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
     * @param \PolkurierWebServiceApi\Entities\Pickup $pickup
     * @return $this
     */
    public function setPickup(Pickup $pickup)
    {
        $this->pickup = $pickup;
        return $this;
    }

    /**
     * @return \PolkurierWebServiceApi\Entities\Pickup
     */
    private function getPickup()
    {
        return $this->pickup;
    }

    /**
     * @param \PolkurierWebServiceApi\Entities\COD $cod
     * @return $this
     */
    public function setCOD(COD $cod)
    {
        $this->COD = $cod;
        return $this;
    }

    /**
     * @return \PolkurierWebServiceApi\Entities\COD
     */
    private function getCOD()
    {
        return $this->COD;
    }

}