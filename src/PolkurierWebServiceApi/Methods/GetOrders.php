<?php

namespace PolkurierWebServiceApi\Methods;

use PolkurierWebServiceApi\Entities\Order;
use PolkurierWebServiceApi\Entities\Address;
use PolkurierWebServiceApi\Entities\COD;
use PolkurierWebServiceApi\Entities\Pickup;
use PolkurierWebServiceApi\Entities\Pack;
use PolkurierWebServiceApi\Entities\OrderItem;
use PolkurierWebServiceApi\Entities\OrderWaybill;
use PolkurierWebServiceApi\Entities\PickupDate;
use PolkurierWebServiceApi\Entities\PickupTimeRange;
use PolkurierWebServiceApi\Response;
use PolkurierWebServiceApi\Util\Arr;

class GetOrders extends AbstractMethod
{
    /**
     * @var string
     */
    private $orderno = '';
    /**
     * @var bool
     */
    private $packs = false;
    /**
     * @var int
     */
    private $pagesize = 100;
    /**
     * @var int
     */
    private $page = 1;
    /**
     * @var string
     */
    private $status = '';
    /**
     * @var bool
     */
    private $items = false;
    /**
     * @var bool
     */
    private $files = false;

    /**
     * @return string
     */
    public function getName()
    {
        return 'get_orders';
    }

    /**
     * @return array
     */
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

    /**
     * @param $orderno
     * @return $this
     */
    public function setOrderno($orderno)
    {
        $this->orderno = $orderno;
        return $this;
    }

    /**
     * @param $packs
     * @return $this
     */
    public function setPacks($packs)
    {
        $this->packs = (bool)$packs;
        return $this;
    }

    /**
     * @param $pagesize
     * @return $this
     */
    public function setPageSize($pagesize)
    {
        $this->pagesize = $pagesize;
        return $this;
    }

    /**
     * @param $page
     * @return $this
     */
    public function setPage($page)
    {
        $this->page = $page;
        return $this;
    }

    /**
     * @param $status
     * @return $this
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @param $items
     * @return $this
     */
    public function setItems($items)
    {
        $this->items = (bool)$items;
        return $this;
    }

    /**
     * @param $files
     * @return $this
     */
    public function setFiles($files)
    {
        $this->files = (bool)$files;
        return $this;
    }

    /**
     * @param Response $response
     * @return $this|GetOrders
     * @throws \DateMalformedStringException
     * @throws \PolkurierWebServiceApi\Exception\ErrorException
     */
    public function setResponseData(Response $response)
    {
        $this->responseData = [];

        if ($this->orderno) {
            $orderData = $response->get('response');
            $order = $this->createOrder($orderData);

            $this->responseData = $order;
        } else {
            $rawData = $response->get('response')['result'];
            foreach ($rawData as $row) {
                $order = $this->createOrder($row);
                $this->responseData[] = $order;
            }
        }

        return $this;
    }

    /**
     * @param array $data
     * @return Order
     * @throws \DateMalformedStringException
     * @throws \PolkurierWebServiceApi\Exception\ErrorException
     */
    private function createOrder($data)
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
            $sender = $this->createAddress($senderData);
            $order->setSender($sender);
        }

        // Set Recipient Address
        $recipientData = Arr::get($data, 'recipient', []);
        if (!empty($recipientData)) {
            $recipient = $this->createAddress($recipientData);
            $order->setRecipient($recipient);
        }

        // Set COD
        $codData = Arr::get($data, 'COD', []);
        if (!empty($codData)) {
            $cod = $this->createCod($codData);
            $order->setCod($cod);
        }

        // Set Pickup
        $pickupData = Arr::get($data, 'pickup', []);
        if (!empty($pickupData)) {
            $pickup = $this->createPickup($pickupData);
            $order->setPickup($pickup);
        }

        // Set Packs
        $packsData = Arr::get($data, 'packs', []);
        foreach ($packsData as $packData) {
            $pack = $this->createPack($packData);
            $order->addPack($pack);
        }

        // Set Items
        $itemsData = Arr::get($data, 'items', []);
        foreach ($itemsData as $itemData) {
            $item = $this->createOrderItem($itemData);
            $order->addItem($item);
        }

        // Set Waybills
        $waybillsData = Arr::get($data, 'waybills', []);
        foreach ($waybillsData as $waybillData) {
            $waybill = $this->createOrderWaybill($waybillData);
            $order->addWaybill($waybill);
        }

        return $order;
    }

    /**
     * @param array $data
     * @return Address
     */
    private function createAddress($data)
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

    /**
     * @param array $data
     * @return COD
     * @throws \PolkurierWebServiceApi\Exception\ErrorException
     */
    private function createCod($data)
    {
        $cod = new COD();
        $cod->setType(Arr::get($data, 'codtype'))
            ->setAmount(Arr::get($data, 'codamount'))
            ->setBankAccount(Arr::get($data, 'codbankaccount', ''))
            ->setReturnCod(Arr::get($data, 'return_cod'));
        return $cod;
    }

    /**
     * @param array $data
     * @return Pickup
     */
    private function createPickup($data)
    {
        $pickup = new Pickup();
        $pickup->setDate(Arr::get($data, 'pickupdate'));
        $pickup->setTimeTo(Arr::get($data, 'pickuptimeto'));
        $pickup->setTimeFrom(Arr::get($data, 'pickuptimefrom'));
        $pickup->setNoCourierOrder(Arr::get($data, 'nocourierorder', false));

        return $pickup;
    }


    /**
     * @param array $data
     * @return Pack
     * @throws \PolkurierWebServiceApi\Exception\ErrorException
     */
    private function createPack($data)
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

    /**
     * @param array $data
     * @return OrderItem
     */
    private function createOrderItem($data)
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

    /**
     * @param array $data
     * @return OrderWaybill
     */
    private function createOrderWaybill($data)
    {
        $waybill = new OrderWaybill();
        $waybill->setNumber(Arr::get($data, 'number'))
            ->setIsDefault(Arr::get($data, 'is_default'));
        return $waybill;
    }
}
