<?php

namespace PolkurierWebServiceApi\Methods;

use PolkurierWebServiceApi\Entities\COD;
use PolkurierWebServiceApi\Entities\CourierServiceInterface;
use PolkurierWebServiceApi\Entities\CoverAddress;
use PolkurierWebServiceApi\Entities\Pack;
use PolkurierWebServiceApi\Entities\Pickup;
use PolkurierWebServiceApi\Entities\Recipient;
use PolkurierWebServiceApi\Entities\Sender;

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
     * @var CoverAddress|null
     */
    private $coverAddress;

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
            'cover_address' => $this->coverAddress !== null ? $this->coverAddress->toArray() : null,
            'packs' => $this->packs,
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
     * @param string $shipmenttype
     * @return CreateOrder
     */
    public function setShipmentType($shipmenttype)
    {
        $this->shipmenttype = (string)$shipmenttype;
        return $this;
    }

    /**
     * @param string $description
     * @return CreateOrder
     */
    public function setDescription($description)
    {
        $this->description = (string)$description;
        return $this;
    }

    /**
     * @param Sender $sender
     * @return CreateOrder
     */
    public function setSender(Sender $sender)
    {
        $this->sender = $sender;
        return $this;
    }

    /**
     * @param Recipient $recipient
     * @return CreateOrder
     */
    public function setRecipient(Recipient $recipient)
    {
        $this->recipient = $recipient;
        return $this;
    }

    /**
     * @param CoverAddress|null $coverAddress
     */
    public function setCoverAddress(CoverAddress $coverAddress)
    {
        $this->coverAddress = $coverAddress;
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
     * @return CreateOrder
     */
    public function addPack(Pack $pack)
    {
        $this->packs[] = $pack;
        return $this;
    }

    /**
     * @param CourierServiceInterface $courierservice
     * @return CreateOrder
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
     * @return CreateOrder
     */
    public function setPickup(Pickup $pickup)
    {
        $this->pickup = $pickup;
        return $this;
    }

    /**
     * @param COD $cod
     * @return CreateOrder
     */
    public function setCOD(COD $cod)
    {
        $this->COD = $cod;
        return $this;
    }

}
