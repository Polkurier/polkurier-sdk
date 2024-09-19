<?php

namespace PolkurierWebServiceApi\Methods;

use PolkurierWebServiceApi\Entities\Order;
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
            ->setDate(Arr::get($data, 'date'))
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
            ->setStatusDate(Arr::get($data, 'status_date'))
            ->setDeliveredDate(Arr::get($data, 'delivered_date'))
            ->setInsurance(Arr::get($data, 'insurance'))
            ->setSender(Arr::get($data, 'sender'))
            ->setRecipient(Arr::get($data, 'recipient'))
            ->setCod(Arr::get($data, 'COD'))
            ->setPickup(Arr::get($data, 'pickup'))
            ->setPacks(Arr::get($data, 'packs'))
            ->setCourierService(Arr::get($data, 'courierservice'))
            ->setItems(Arr::get($data, 'items'))
            ->setWaybills(Arr::get($data, 'waybills'));

        return $order;
    }
}
