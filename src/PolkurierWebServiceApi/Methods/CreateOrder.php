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
            'shipmenttype' => $this->shipmenttype,
            'courier' => $this->courier,
            'courierservice' => $this->getCourierServiceMap(),
            'description' => $this->description,
            'sender' => $this->sender->toArray(),
            'recipient' => $this->recipient->toArray(),
            'packs' => array_map(static function (Pack $pack) {
                return $pack->toArray();
            }, $this->packs),
            'pickup' => $this->pickup->toArray(),
            'COD' => $this->COD->toArray(),
            'insurance' => $this->insurance
        ];
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
     * @param string $description
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = (string)$description;
        return $this;
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
     * @param Recipient $recipient
     * @return $this
     */
    public function setRecipient(Recipient $recipient)
    {
        $this->recipient = $recipient;
        return $this;
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
     * @param Pack $pack
     * @return $this
     */
    public function addPack(Pack $pack)
    {
        $this->packs[] = $pack;
        return $this;
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
    private function getCourierServiceMap()
    {
        $servicemap = [];
        foreach ($this->courierservice as $item) {
            $itemarray = $item->toArray();
            $servicemap[key($itemarray)] = current($itemarray);
        }
        return $servicemap;
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
     * @param COD $cod
     * @return $this
     */
    public function setCOD(COD $cod)
    {
        $this->COD = $cod;
        return $this;
    }
}