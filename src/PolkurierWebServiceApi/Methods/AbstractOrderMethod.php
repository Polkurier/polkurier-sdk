<?php

namespace PolkurierWebServiceApi\Methods;

use PolkurierWebServiceApi\Entities\COD;
use PolkurierWebServiceApi\Entities\CourierServiceInterface;
use PolkurierWebServiceApi\Entities\CoverAddress;
use PolkurierWebServiceApi\Entities\Pack;
use PolkurierWebServiceApi\Entities\Pickup;
use PolkurierWebServiceApi\Entities\Recipient;
use PolkurierWebServiceApi\Entities\Sender;

abstract class AbstractOrderMethod extends AbstractMethod
{

    /**
     * @var string
     */
    protected $shipmenttype;

    /**
     * @var string
     */
    protected $courier;

    /**
     * @var array
     */
    protected $courierservice = [];

    /**
     * @var string
     */
    protected $description;

    /**
     * @var Sender
     */
    protected $sender;

    /**
     * @var Recipient
     */
    protected $recipient;

    /**
     * @var CoverAddress|null
     */
    protected $coverAddress;

    /**
     * @var array
     */
    protected $packs = [];

    /**
     * @var Pickup
     */
    protected $pickup;

    /**
     * @var COD
     */
    protected $COD;

    /**
     * @var float
     */
    protected $insurance;

    public function __construct()
    {
        $this->sender = new Sender();
        $this->recipient = new Recipient();
        $this->COD = new COD();
        $this->pickup = new Pickup();
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
     * @return AbstractOrderMethod
     */
    public function setShipmentType($shipmenttype)
    {
        $this->shipmenttype = (string)$shipmenttype;
        return $this;
    }

    /**
     * @param string $description
     * @return AbstractOrderMethod
     */
    public function setDescription($description)
    {
        $this->description = (string)$description;
        return $this;
    }

    /**
     * @param Sender $sender
     * @return AbstractOrderMethod
     */
    public function setSender(Sender $sender)
    {
        $this->sender = $sender;
        return $this;
    }

    /**
     * @param Recipient $recipient
     * @return AbstractOrderMethod
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
     * @return AbstractOrderMethod
     */
    public function setInsurance($insurance)
    {
        $this->insurance = (float)$insurance;
        return $this;
    }

    /**
     * @param Pack $pack
     * @return AbstractOrderMethod
     */
    public function addPack(Pack $pack)
    {
        $this->packs[] = $pack;
        return $this;
    }

    /**
     * @param CourierServiceInterface $courierservice
     * @return AbstractOrderMethod
     */
    public function addCourierService(CourierServiceInterface $courierservice)
    {
        $this->courierservice[] = $courierservice;
        return $this;
    }

    /**
     * @return array
     */
    protected function getCourierServiceMap()
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
     * @return AbstractOrderMethod
     */
    public function setPickup(Pickup $pickup)
    {
        $this->pickup = $pickup;
        return $this;
    }

    /**
     * @param COD $cod
     * @return AbstractOrderMethod
     */
    public function setCOD(COD $cod)
    {
        $this->COD = $cod;
        return $this;
    }

}
