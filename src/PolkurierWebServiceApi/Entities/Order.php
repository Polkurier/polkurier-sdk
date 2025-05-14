<?php

namespace PolkurierWebServiceApi\Entities;

use DateTimeImmutable;
use JsonSerializable;

class Order implements JsonSerializable
{

    /**
     * @var string|null
     */
    private $number;

    /**
     * @var DateTimeImmutable|null
     */
    private $date;

    /**
     * @var string|null
     */
    private $description;

    /**
     * @var string|null
     */
    private $courier;

    /**
     * @var string|null
     */
    private $status;

    /**
     * @var string|null
     */
    private $statusCode;

    /**
     * @var float|null
     */
    private $priceGross;

    /**
     * @var float|null
     */
    private $priceNet;

    /**
     * @var bool|null
     */
    private $isPaid;

    /**
     * @var bool|null
     */
    private $hasInvoice;

    /**
     * @var float|null
     */
    private $unpaidAmount;

    /**
     * @var string|null
     */
    private $url;

    /**
     * @var DateTimeImmutable|null
     */
    private $statusDate;

    /**
     * @var DateTimeImmutable|null
     */
    private $deliveredDate;

    /**
     * @var float|null
     */
    private $insurance;

    /**
     * @var Sender|null
     */
    private $sender;

    /**
     * @var Recipient|null
     */
    private $recipient;

    /**
     * @var CoverAddress|null
     */
    private $coverAddress;

    /**
     * @var COD|null
     */
    private $cod;

    /**
     * @var Pickup|null
     */
    private $pickup;

    /**
     * @var Pack[]
     */
    private $packs = [];

    /**
     * @var CourierServiceInterface[]|array
     * @deprecated Replaced by $items
     */
    private $courierService = [];

    /**
     * @var OrderItem[]
     */
    private $items = [];

    /**
     * @var OrderWaybill[]
     */
    private $waybills = [];

    /**
     * @var string|null
     */
    private $courierName;

    /**
     * @var string|null
     */
    private $shipmentType;

    /**
     * @var string[]
     * @deprecated Replaced by $waybills
     */
    private $label = [];

    /**
     * @var mixed|null
     */
    private $individualPricing;

    /**
     * @param string $number
     * @return $this
     */
    public function setNumber($number)
    {
        $this->number = $number;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * @param DateTimeImmutable $date
     * @return $this
     */
    public function setDate(DateTimeImmutable $date)
    {
        $this->date = $date;
        return $this;
    }

    /**
     * @return DateTimeImmutable|null
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param string $description
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $courier
     * @return $this
     */
    public function setCourier($courier)
    {
        $this->courier = $courier;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getCourier()
    {
        return $this->courier;
    }

    /**
     * @param string $status
     * @return $this
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $statusCode
     * @return $this
     */
    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @param float $priceGross
     * @return $this
     */
    public function setPriceGross($priceGross)
    {
        $this->priceGross = $priceGross;
        return $this;
    }

    /**
     * @return float|null
     */
    public function getPriceGross()
    {
        return $this->priceGross;
    }

    /**
     * @param float $priceNet
     * @return $this
     */
    public function setPriceNet($priceNet)
    {
        $this->priceNet = $priceNet;
        return $this;
    }

    /**
     * @return float|null
     */
    public function getPriceNet()
    {
        return $this->priceNet;
    }

    /**
     * @param bool $isPaid
     * @return $this
     */
    public function setIsPaid($isPaid)
    {
        $this->isPaid = (bool)$isPaid;
        return $this;
    }

    /**
     * @return bool|null
     */
    public function getIsPaid()
    {
        return $this->isPaid;
    }

    /**
     * @param bool $hasInvoice
     * @return $this
     */
    public function setHasInvoice($hasInvoice)
    {
        $this->hasInvoice = (bool)$hasInvoice;
        return $this;
    }

    /**
     * @return bool|null
     */
    public function getHasInvoice()
    {
        return $this->hasInvoice;
    }

    /**
     * @param float $unpaidAmount
     * @return $this
     */
    public function setUnpaidAmount($unpaidAmount)
    {
        $this->unpaidAmount = $unpaidAmount;
        return $this;
    }

    /**
     * @return float|null
     */
    public function getUnpaidAmount()
    {
        return $this->unpaidAmount;
    }

    /**
     * @param string $url
     * @return $this
     */
    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param DateTimeImmutable|null $statusDate
     * @return $this
     */
    public function setStatusDate($statusDate)
    {
        $this->statusDate = $statusDate;
        return $this;
    }

    /**
     * @return DateTimeImmutable|null
     */
    public function getStatusDate()
    {
        return $this->statusDate;
    }

    /**
     * @param DateTimeImmutable|null $deliveredDate
     * @return $this
     */
    public function setDeliveredDate($deliveredDate)
    {
        $this->deliveredDate = $deliveredDate;
        return $this;
    }

    /**
     * @return DateTimeImmutable|null
     */
    public function getDeliveredDate()
    {
        return $this->deliveredDate;
    }

    /**
     * @param float $insurance
     * @return $this
     */
    public function setInsurance($insurance)
    {
        $this->insurance = $insurance;
        return $this;
    }

    /**
     * @return float|null
     */
    public function getInsurance()
    {
        return $this->insurance;
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
     * @return Sender|null
     */
    public function getSender()
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
     * @return Recipient|null
     */
    public function getRecipient()
    {
        return $this->recipient;
    }

    /**
     * @return CoverAddress|null
     */
    public function getCoverAddress()
    {
        return $this->coverAddress;
    }

    /**
     * @param CoverAddress|null $coverAddress
     */
    public function setCoverAddress(CoverAddress $coverAddress = null)
    {
        $this->coverAddress = $coverAddress;
    }

    /**
     * @param COD $cod
     * @return $this
     */
    public function setCod(COD $cod)
    {
        $this->cod = $cod;
        return $this;
    }

    /**
     * @return COD|null
     */
    public function getCod()
    {
        return $this->cod;
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
     * @return Pickup|null
     */
    public function getPickup()
    {
        return $this->pickup;
    }

    /**
     * Dodaje paczkę do zamówienia.
     *
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
    public function getPacks()
    {
        return $this->packs;
    }

    /**
     * @deprecated
     * @param array $courierService
     * @return $this
     */
    public function setCourierService($courierService)
    {
        $this->courierService = $courierService;
        return $this;
    }

    /**
     * @deprecated
     * @return array
     */
    public function getCourierService()
    {
        return $this->courierService;
    }

    /**
     * Dodaje element zamówienia do listy.
     *
     * @param OrderItem $item
     * @return $this
     */
    public function addItem(OrderItem $item)
    {
        $this->items[] = $item;
        return $this;
    }

    /**
     * @return array
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * Dodaje list przewozowy do zamówienia.
     *
     * @param OrderWaybill $waybill
     * @return $this
     */
    public function addWaybill(OrderWaybill $waybill)
    {
        $this->waybills[] = $waybill;
        return $this;
    }

    /**
     * @return array
     */
    public function getWaybills()
    {
        return $this->waybills;
    }

    /**
     * @param string $courierName
     * @return $this
     */
    public function setCourierName($courierName)
    {
        $this->courierName = $courierName;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getCourierName()
    {
        return $this->courierName;
    }

    /**
     * @param string $shipmentType
     * @return $this
     */
    public function setShipmentType($shipmentType)
    {
        $this->shipmentType = $shipmentType;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getShipmentType()
    {
        return $this->shipmentType;
    }

    /**
     * @param array $label
     * @return $this
     * @deprecated
     */
    public function setLabel(array $label)
    {
        $this->label = $label;
        return $this;
    }

    /**
     * @return array
     * @deprecated
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param mixed $individualPricing
     * @return $this
     */
    public function setIndividualPricing($individualPricing)
    {
        $this->individualPricing = $individualPricing;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getIndividualPricing()
    {
        return $this->individualPricing;
    }

    /**
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'number' => $this->number,
            'date' => $this->date,
            'description' => $this->description,
            'courier' => $this->courier,
            'status' => $this->status,
            'status_code' => $this->statusCode,
            'price_gross' => $this->priceGross,
            'price_net' => $this->priceNet,
            'is_paid' => $this->isPaid,
            'has_invoice' => $this->hasInvoice,
            'unpaid_amount' => $this->unpaidAmount,
            'url' => $this->url,
            'status_date' => $this->statusDate,
            'delivered_date' => $this->deliveredDate,
            'insurance' => $this->insurance,
            'sender' => $this->sender,
            'recipient' => $this->recipient,
            'cover_address' => $this->coverAddress ? $this->coverAddress->toArray() : null,
            'COD' => $this->cod ? $this->cod->toArray() : null,
            'pickup' => $this->pickup ? $this->pickup->toArray() : null,
            'packs' => $this->packs,
            'items' => $this->items,
            'waybills' => $this->waybills,
            'courier_name' => $this->courierName,
            'shipmenttype' => $this->shipmentType,
            'individual_pricing' => $this->individualPricing
        ];
    }

}
