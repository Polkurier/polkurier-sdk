<?php

namespace PolkurierWebServiceApi\Methods;

use PolkurierWebServiceApi\Response;
use PolkurierWebServiceApi\Util\Arr;

class CreateMultiOrderPickup extends AbstractMethod
{
    private $pickupDate;
    private $pickupTimeFrom;
    private $pickupTimeTo;
    private $orders = [];

    public function getName()
    {
        return 'create_multi_order_pickup';
    }

    public function getRequestData()
    {
        return [
            'pickupdate' => $this->pickupDate,
            'pickuptimefrom' => $this->pickupTimeFrom,
            'pickuptimeto' => $this->pickupTimeTo,
            'orders' => $this->orders,
        ];
    }

    public function setPickupDate($pickupDate)
    {
        $this->pickupDate = $pickupDate;
        return $this;
    }

    public function setPickupTimeFrom($pickupTimeFrom)
    {
        $this->pickupTimeFrom = $pickupTimeFrom;
        return $this;
    }

    public function setPickupTimeTo($pickupTimeTo)
    {
        $this->pickupTimeTo = $pickupTimeTo;
        return $this;
    }

    public function setOrders(array $orders)
    {
        $this->orders = $orders;
        return $this;
    }

    public function setResponseData(Response $response)
    {
        $this->responseData = [
            'success' => Arr::get($response->get('response'), 'success', []),
            'failed' => Arr::get($response->get('response'), 'failed', [])
        ];
        return $this;
    }
}
