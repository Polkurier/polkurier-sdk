<?php

namespace PolkurierWebServiceApi\Methods;

use PolkurierWebServiceApi\Response;
use PolkurierWebServiceApi\Util\Arr;

class CreateMultiOrderPickup extends AbstractMethod
{

    /**
     * @var string|null
     */
    private $pickupDate;

    /**
     * @var string|null
     */
    private $pickupTimeFrom;

    /**
     * @var string|null
     */
    private $pickupTimeTo;

    /**
     * @var array
     */
    private $orders = [];

    /**
     * @return string
     */
    public function getName()
    {
        return 'create_multi_order_pickup';
    }

    /**
     * @return array
     */
    public function getRequestData()
    {
        return [
            'pickupdate' => $this->pickupDate,
            'pickuptimefrom' => $this->pickupTimeFrom,
            'pickuptimeto' => $this->pickupTimeTo,
            'orders' => $this->orders,
        ];
    }

    /**
     * @param string|null $pickupDate
     * @return CreateMultiOrderPickup
     */
    public function setPickupDate($pickupDate)
    {
        $this->pickupDate = $pickupDate;
        return $this;
    }

    /**
     * @param string|null $pickupTimeFrom
     * @return CreateMultiOrderPickup
     */
    public function setPickupTimeFrom($pickupTimeFrom)
    {
        $this->pickupTimeFrom = $pickupTimeFrom;
        return $this;
    }

    /**
     * @param string|null $pickupTimeTo
     * @return CreateMultiOrderPickup
     */
    public function setPickupTimeTo($pickupTimeTo)
    {
        $this->pickupTimeTo = $pickupTimeTo;
        return $this;
    }

    /**
     * @param array $orders
     * @return CreateMultiOrderPickup
     */
    public function setOrders(array $orders)
    {
        $this->orders = $orders;
        return $this;
    }

    /**
     * @param Response $response
     * @return CreateMultiOrderPickup
     */
    public function setResponseData(Response $response)
    {
        $this->responseData = [
            'success' => Arr::get($response->get('response'), 'success', []),
            'failed' => Arr::get($response->get('response'), 'failed', [])
        ];
        return $this;
    }

}
