<?php

namespace PolkurierWebServiceApi\Methods;

use DateTimeImmutable;
use Exception;
use PolkurierWebServiceApi\Entities\COD;
use PolkurierWebServiceApi\Entities\CourierPoint;
use PolkurierWebServiceApi\Entities\CoverAddress;
use PolkurierWebServiceApi\Entities\Order;
use PolkurierWebServiceApi\Entities\OrderItem;
use PolkurierWebServiceApi\Entities\OrderWaybill;
use PolkurierWebServiceApi\Entities\Pack;
use PolkurierWebServiceApi\Entities\Pickup;
use PolkurierWebServiceApi\Entities\Recipient;
use PolkurierWebServiceApi\Entities\Sender;
use PolkurierWebServiceApi\Exception\ErrorException;
use PolkurierWebServiceApi\Response;
use PolkurierWebServiceApi\Util\Arr;
use PolkurierWebServiceApi\Util\Dates;
use PolkurierWebServiceApi\Util\Validators;

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
     * @param string $orderno
     * @return GetOrders
     */
    public function setOrderno($orderno)
    {
        $this->orderno = $orderno;
        return $this;
    }

    /**
     * @param bool $packs
     * @return GetOrders
     */
    public function setPacks($packs)
    {
        $this->packs = (bool)$packs;
        return $this;
    }

    /**
     * @param $pagesize
     * @return GetOrders
     */
    public function setPageSize($pagesize)
    {
        $this->pagesize = $pagesize;
        return $this;
    }

    /**
     * @param $page
     * @return GetOrders
     */
    public function setPage($page)
    {
        $this->page = $page;
        return $this;
    }

    /**
     * @param $status
     * @return GetOrders
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @param $items
     * @return GetOrders
     */
    public function setItems($items)
    {
        $this->items = (bool)$items;
        return $this;
    }

    /**
     * @param $files
     * @return GetOrders
     */
    public function setFiles($files)
    {
        $this->files = (bool)$files;
        return $this;
    }

    /**
     * @return GetOrders
     * @throws Exception
     * @throws ErrorException
     */
    public function setResponseData(Response $response)
    {
        $this->responseData = [];

        if ($this->orderno) {
            $this->responseData = $this->createOrder((array)$response->get('response'));
        } else {
            $rawData = (array)$response->get('response')['result'];
            foreach ($rawData as $row) {
                $this->responseData[] = $this->createOrder($row);
            }
        }
        return $this;
    }

    /**
     * @return Order
     * @throws Exception
     * @throws ErrorException
     */
    private function createOrder(array $data)
    {
        $order = (new Order())
            ->setNumber((string)Arr::get($data, 'number', ''))
            ->setDate(Dates::ensureDateTime(Arr::get($data, 'date')))
            ->setDescription((string)Arr::get($data, 'description', ''))
            ->setCourier((string)Arr::get($data, 'courier', ''))
            ->setStatus((string)Arr::get($data, 'status', ''))
            ->setStatusCode((string)Arr::get($data, 'status_code', ''))
            ->setPriceGross((float)Arr::get($data, 'price_gross', 0.0))
            ->setPriceNet((float)Arr::get($data, 'price_net', 0.0))
            ->setIsPaid((bool)Arr::get($data, 'is_paid', false))
            ->setHasInvoice((bool)Arr::get($data, 'has_invoice', false))
            ->setUnpaidAmount((float)Arr::get($data, 'unpaid_amount', 0.0))
            ->setUrl((string)Arr::get($data, 'url', ''))
            ->setStatusDate(Dates::dateTimeOrNull(Arr::get($data, 'status_date')))
            ->setDeliveredDate(Dates::dateTimeOrNull(Arr::get($data, 'delivered_date')))
            ->setInsurance((float)Arr::get($data, 'insurance', 0.0))
            ->setCourierName((string)Arr::get($data, 'courier_name', ''))
            ->setShipmentType((string)Arr::get($data, 'shipmenttype', ''))
            ->setLabel((array)Arr::get($data, 'label', ''))
            ->setIndividualPricing(Arr::get($data, 'individual_pricing'))
            ->setSender($this->createSenderAddress((array)Arr::get($data, 'sender', [])))
            ->setRecipient($this->createRecipientAddress((array)Arr::get($data, 'recipient', [])))
            ->setCod($this->createCod((array)Arr::get($data, 'COD', [])))
            ->setPickup($this->createPickup((array)Arr::get($data, 'pickup', [])));

        if (!empty($data['cover_address'])) {
            $order->setCoverAddress(new CoverAddress());
            $this->setAddressProperties($order->getCoverAddress(), (array)$data['cover_address']);
        }

        // Set Packs
        $packsData = (array)Arr::get($data, 'packs', []);
        foreach ($packsData as $packData) {
            $order->addPack($this->createPack((array)$packData));
        }

        // Set Items
        $itemsData = (array)Arr::get($data, 'items', []);
        foreach ($itemsData as $itemData) {
            $order->addItem($this->createOrderItem((array)$itemData));
        }

        // Set Waybills
        $waybillsData = Arr::get($data, 'waybills', []);
        foreach ($waybillsData as $waybillData) {
            $order->addWaybill($this->createOrderWaybill((array)$waybillData));
        }

        return $order;
    }

    /**
     * @return Sender
     */
    private function createSenderAddress(array $data)
    {
        $address = new Sender();
        $this->setAddressProperties($address, $data);
        return $address;
    }

    /**
     * @return Recipient
     */
    private function createRecipientAddress(array $data)
    {
        $address = new Recipient();
        $this->setAddressProperties($address, $data);
        return $address;
    }

    /**
     * @param Sender|Recipient $address
     * @param array $data
     * @return void
     */
    private function setAddressProperties($address, array $data)
    {
        $address->setCompany((string)Arr::get($data, 'company', ''));
        $address->setPerson((string)Arr::get($data, 'person', ''));
        $address->setStreet((string)Arr::get($data, 'streetname', ''));
        $address->setHouseNumber((string)Arr::get($data, 'housenumber', ''));
        $address->setFlatNumber((string)Arr::get($data, 'flatnumber', ''));
        $address->setPostcode((string)Arr::get($data, 'postcode', ''));
        $address->setCity((string)Arr::get($data, 'city', ''));
        $address->setPhone((string)Arr::get($data, 'phone', ''));
        $address->setEmail((string)Arr::get($data, 'email', ''));
        $address->setCountry((string)Arr::get($data, 'country', ''));
        $address->setPointId((string)Arr::get($data, 'point_id', ''));
        $address->setPoint(CourierPoint::fromArray((array)Arr::get($data, 'point', [])));
    }

    /**
     * @return COD
     * @throws ErrorException
     */
    private function createCod(array $data)
    {
        $cod = (new COD())
            ->setType(Arr::get($data, 'codtype'))
            ->setAmount(Arr::get($data, 'codamount'))
            ->setReturnCod(Arr::get($data, 'return_cod'));

        $bankAccount = (string)Arr::get($data, 'codbankaccount');
        if ($bankAccount !== '') {
            $cod->setBankAccount(Arr::get($data, 'codbankaccount'));
        }
        return $cod;
    }

    /**
     * @return Pickup
     */
    private function createPickup(array $data)
    {
        $pickup = new Pickup();
        $pickup->setDate(Arr::get($data, 'pickupdate'));
        $pickup->setNoCourierOrder(Arr::get($data, 'nocourierorder', false));

        try {
            $pickupTimeFrom = (string)Arr::get($data, 'pickuptimefrom');
            if (Validators::validateHourFormat($pickupTimeFrom)) {
                $pickup->setTimeFrom($pickupTimeFrom);
            }
        } catch (Exception $e) {
        }

        try {
            $pickupTimeTo = (string)Arr::get($data, 'pickuptimeto');
            if (Validators::validateHourFormat($pickupTimeTo)) {
                $pickup->setTimeTo($pickupTimeTo);
            }
        } catch (ErrorException $e) {
        }
        return $pickup;
    }

    /**
     * @return Pack
     */
    private function createPack(array $data)
    {
        $pack = new Pack();
        $pack->setType(Arr::get($data, 'type'));

        try {
            $pack->setLength((float)Arr::get($data, 'length'));
        } catch (ErrorException $e) {
        }

        try {
            $pack->setWidth((float)Arr::get($data, 'width'));
        } catch (ErrorException $e) {
        }

        try {
            $pack->setHeight((float)Arr::get($data, 'height'));
        } catch (ErrorException $e) {
        }

        try {
            $pack->setWeight((float)Arr::get($data, 'weight'));
        } catch (ErrorException $e) {
        }

        try {
            $pack->setAmount((int)Arr::get($data, 'amount'));
        } catch (ErrorException $e) {
        }

        return $pack;
    }

    /**
     * @return OrderItem
     */
    private function createOrderItem(array $data)
    {
        return new OrderItem(
            (string)Arr::get($data, 'description', ''),
            (int)Arr::get($data, 'amount', 0),
            (float)Arr::get($data, 'price', 0.0),
            (float)Arr::get($data, 'value_nett', 0.0),
            (float)Arr::get($data, 'value_gross', 0.0),
            (string)Arr::get($data, 'type', '')
        );
    }

    /**
     * @return OrderWaybill
     */
    private function createOrderWaybill(array $data)
    {
        return new OrderWaybill(
            (string)Arr::get($data, 'number'),
            (bool)Arr::get($data, 'is_default')
        );
    }

}
