<?php

namespace PolkurierWebServiceApi\Methods;

use PolkurierWebServiceApi\Entities\Order;
use PolkurierWebServiceApi\Entities\Address;
use PolkurierWebServiceApi\Entities\COD;
use PolkurierWebServiceApi\Entities\Pickup;
use PolkurierWebServiceApi\Entities\Pack;
use PolkurierWebServiceApi\Entities\OrderItem;
use PolkurierWebServiceApi\Entities\OrderWaybill;
use PolkurierWebServiceApi\Response;
use PolkurierWebServiceApi\Util\Arr;

class GetOrders extends AbstractMethod
{
    private $orderno = '';
    private $packs = false;
    private $pagesize = 100;
    private $page = 1;
    private $status = '';
    private $items = false;
    private $files = false;

    public function getName()
    {
        return 'get_orders';
    }

    public function getRequestData()
    {
        $data = [];

        if ($this->orderno) {
            $data['orderno'] = $this->orderno;
        } else {
            $data['packs'] = $this->packs;
            $data['pagesize'] = $this->pagesize;
            $data['page'] = $this->page;
            if ($this->status) {
                $data['status'] = $this->status;
            }
            $data['items'] = $this->items;
            $data['files'] = $this->files;
        }

        return $data;
    }

    public function setOrderno($orderno)
    {
        $this->orderno = $orderno;
        return $this;
    }

    public function setPacks($packs)
    {
        $this->packs = (bool)$packs;
        return $this;
    }

    public function setPageSize($pagesize)
    {
        $this->pagesize = $pagesize;
        return $this;
    }

    public function setPage($page)
    {
        $this->page = $page;
        return $this;
    }

    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    public function setItems($items)
    {
        $this->items = (bool)$items;
        return $this;
    }

    public function setFiles($files)
    {
        $this->files = (bool)$files;
        return $this;
    }

    public function setResponseData(Response $response)
    {
        $this->responseData = [];

        if ($this->orderno) {
            $orderData = $response->get('response');
            $order = $this->createOrderObject($orderData);
            $this->responseData = $order;
        } else {
            $rawData = $response->get('response')['result'];
            foreach ($rawData as $row) {
                $order = $this->createOrderObject($row);
                $this->responseData[] = $order;
            }
        }

        return $this;
    }

    private function createOrderObject($data)
    {
        $order = new Order();

        $order->setNumber(Arr::get($data, 'number'))
            ->setDate(new \DateTimeImmutable(Arr::get($data, 'date')))
            ->setDescription(Arr::get($data, 'description'))
            ->setCourier(Arr::get($data, 'courier'))
            ->setStatus(Arr::get($data, 'status'))
            ->setStatusCode(Arr::get($data, 'status_code'))
            ->setPriceGross(Arr::get($data, 'price_gross'))
            ->setPriceNet(Arr::get($data, 'price_net'))
            ->setIsPaid(Arr::get($data, 'is_paid'))
            ->setHasInvoice(Arr::get($data, 'has_invoice'))
            ->setUnpaidAmount(Arr::get($data, 'unpaid_amount'))
            ->setUrl(Arr::get($data, 'url'))
            ->setStatusDate(new \DateTimeImmutable(Arr::get($data, 'status_date')))
            ->setDeliveredDate(new \DateTimeImmutable(Arr::get($data, 'delivered_date')))
            ->setInsurance(Arr::get($data, 'insurance'))
            ->setCourierName(Arr::get($data, 'courier_name'))
            ->setShipmentType(Arr::get($data, 'shipmenttype'))
            ->setLabel(Arr::get($data, 'label'))
            ->setIndividualPricing(Arr::get($data, 'individual_pricing'));

        // Set Sender Address
        $senderData = Arr::get($data, 'sender', []);
        if (!empty($senderData)) {
            $sender = $this->createAddressObject($senderData);
            $order->setSender($sender);
        }

        // Set Recipient Address
        $recipientData = Arr::get($data, 'recipient', []);
        if (!empty($recipientData)) {
            $recipient = $this->createAddressObject($recipientData);
            $order->setRecipient($recipient);
        }

        // Set COD
        $codData = Arr::get($data, 'COD', []);
        if (!empty($codData)) {
            $cod = $this->createCodObject($codData);
            $order->setCod($cod);
        }

        // Set Pickup
        $pickupData = Arr::get($data, 'pickup', []);
        if (!empty($pickupData)) {
            $pickup = $this->createPickupObject($pickupData);
            $order->setPickup($pickup);
        }

        // Set Packs
        $packsData = Arr::get($data, 'packs', []);
        foreach ($packsData as $packData) {
            $pack = $this->createPackObject($packData);
            $order->addPack($pack);
        }

        // Set Items
        $itemsData = Arr::get($data, 'items', []);
        foreach ($itemsData as $itemData) {
            $item = $this->createOrderItemObject($itemData);
            $order->addItem($item);
        }

        // Set Waybills
        $waybillsData = Arr::get($data, 'waybills', []);
        foreach ($waybillsData as $waybillData) {
            $waybill = $this->createOrderWaybillObject($waybillData);
            $order->addWaybill($waybill);
        }

        return $order;
    }

    private function createAddressObject($data)
    {
        $address = new Address();
        $address->setId(Arr::get($data, 'id'))
            ->setName(Arr::get($data, 'name'))
            ->setCompany(Arr::get($data, 'company'))
            ->setPerson(Arr::get($data, 'person'))
            ->setStreet(Arr::get($data, 'street'))
            ->setPostcode(Arr::get($data, 'postcode'))
            ->setCity(Arr::get($data, 'city'))
            ->setPhone(Arr::get($data, 'phone'))
            ->setEmail(Arr::get($data, 'email'))
            ->setCountry(Arr::get($data, 'country'))
            ->setProvince(Arr::get($data, 'province'))
            ->setType(Arr::get($data, 'type'))
            ->setDefault(Arr::get($data, 'default'))
            ->setIsCoverAddress(Arr::get($data, 'is_cover_address'));
        return $address;
    }

    private function createCodObject($data)
    {
        $cod = new COD();
        $cod->setType(Arr::get($data, 'codtype'))
            ->setAmount(Arr::get($data, 'codamount'))
            ->setBankAccount(Arr::get($data, 'codbankaccount'))
            ->setReturnCod(Arr::get($data, 'return_cod'));
        return $cod;
    }

    private function createPickupObject($data)
    {
        $pickup = new Pickup();
        $pickup->setDate(Arr::get($data, 'pickupdate'))
            ->setTimeFrom(Arr::get($data, 'pickuptimefrom'))
            ->setTimeTo(Arr::get($data, 'pickuptimeto'))
            ->setNoCourierOrder(Arr::get($data, 'nocourierorder'));
        return $pickup;
    }

    private function createPackObject($data)
    {
        $pack = new Pack();
        $pack->setLength(Arr::get($data, 'length'))
            ->setWidth(Arr::get($data, 'width'))
            ->setHeight(Arr::get($data, 'height'))
            ->setWeight(Arr::get($data, 'weight'))
            ->setAmount(Arr::get($data, 'amount'))
            ->setType(Arr::get($data, 'type'));
        return $pack;
    }

    private function createOrderItemObject($data)
    {
        $item = new OrderItem();
        $item->setDescription(Arr::get($data, 'description'))
            ->setAmount(Arr::get($data, 'amount'))
            ->setPrice(Arr::get($data, 'price'))
            ->setValueNett(Arr::get($data, 'value_nett'))
            ->setValueGross(Arr::get($data, 'value_gross'))
            ->setType(Arr::get($data, 'type'));
        return $item;
    }

    private function createOrderWaybillObject($data)
    {
        $waybill = new OrderWaybill();
        $waybill->setNumber(Arr::get($data, 'number'))
            ->setIsDefault(Arr::get($data, 'is_default'));
        return $waybill;
    }
}
