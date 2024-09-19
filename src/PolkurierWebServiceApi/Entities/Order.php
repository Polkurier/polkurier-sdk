<?php

namespace PolkurierWebServiceApi\Entities;

/**
 * Class Order
 * @package PolkurierWebServiceApi\Entities
 */
class Order
{
    private $number;
    private $date;
    private $description;
    private $courier;
    private $status;
    private $statusCode;
    private $priceGross;
    private $priceNet;
    private $isPaid;
    private $hasInvoice;
    private $unpaidAmount;
    private $url;
    private $statusDate;
    private $deliveredDate;
    private $insurance;
    private $sender;
    private $recipient;
    private $cod;
    private $pickup;
    private $packs = [];
    private $courierService = [];
    private $items = [];
    private $waybills = [];

    public function setNumber($number)
    {
        $this->number = $number;
        return $this;
    }

    public function getNumber()
    {
        return $this->number;
    }

    public function setDate($date)
    {
        $this->date = $date;
        return $this;
    }

    public function getDate()
    {
        return $this->date;
    }

    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setCourier($courier)
    {
        $this->courier = $courier;
        return $this;
    }

    public function getCourier()
    {
        return $this->courier;
    }

    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function setPriceGross($priceGross)
    {
        $this->priceGross = $priceGross;
        return $this;
    }

    public function getPriceGross()
    {
        return $this->priceGross;
    }

    public function setPriceNet($priceNet)
    {
        $this->priceNet = $priceNet;
        return $this;
    }

    public function getPriceNet()
    {
        return $this->priceNet;
    }

    public function setIsPaid($isPaid)
    {
        $this->isPaid = (bool)$isPaid;
        return $this;
    }

    public function getIsPaid()
    {
        return $this->isPaid;
    }

    public function setHasInvoice($hasInvoice)
    {
        $this->hasInvoice = (bool)$hasInvoice;
        return $this;
    }

    public function getHasInvoice()
    {
        return $this->hasInvoice;
    }

    public function setUnpaidAmount($unpaidAmount)
    {
        $this->unpaidAmount = $unpaidAmount;
        return $this;
    }

    public function getUnpaidAmount()
    {
        return $this->unpaidAmount;
    }

    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function setStatusDate($statusDate)
    {
        $this->statusDate = $statusDate;
        return $this;
    }

    public function getStatusDate()
    {
        return $this->statusDate;
    }

    public function setDeliveredDate($deliveredDate)
    {
        $this->deliveredDate = $deliveredDate;
        return $this;
    }

    public function getDeliveredDate()
    {
        return $this->deliveredDate;
    }

    public function setInsurance($insurance)
    {
        $this->insurance = $insurance;
        return $this;
    }

    public function getInsurance()
    {
        return $this->insurance;
    }

    public function setSender($sender)
    {
        $this->sender = $sender;
        return $this;
    }

    public function getSender()
    {
        return $this->sender;
    }

    public function setRecipient($recipient)
    {
        $this->recipient = $recipient;
        return $this;
    }

    public function getRecipient()
    {
        return $this->recipient;
    }

    public function setCod($cod)
    {
        $this->cod = $cod;
        return $this;
    }

    public function getCod()
    {
        return $this->cod;
    }

    public function setPickup($pickup)
    {
        $this->pickup = $pickup;
        return $this;
    }

    public function getPickup()
    {
        return $this->pickup;
    }

    public function setPacks($packs)
    {
        $this->packs = $packs;
        return $this;
    }

    public function getPacks()
    {
        return $this->packs;
    }

    public function setCourierService($courierService)
    {
        $this->courierService = $courierService;
        return $this;
    }

    public function getCourierService()
    {
        return $this->courierService;
    }

    public function setItems($items)
    {
        $this->items = $items;
        return $this;
    }

    public function getItems()
    {
        return $this->items;
    }

    public function setWaybills($waybills)
    {
        $this->waybills = $waybills;
        return $this;
    }

    public function getWaybills()
    {
        return $this->waybills;
    }

    public function toArray()
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
            'COD' => $this->cod,
            'pickup' => $this->pickup,
            'packs' => $this->packs,
            'courierservice' => $this->courierService,
            'items' => $this->items,
            'waybills' => $this->waybills
        ];
    }
}
