<?php

namespace PolkurierWebServiceApi\Methods;

use PolkurierWebServiceApi\Response;
use PolkurierWebServiceApi\Util\Arr;

/**
 * Class CancelOrder
 * @package PolkurierWebServiceApi\Methods
 *
 */
class CancelOrder extends AbstractMethod
{
    /**
     * @var string
     */
    private $orderNumber;

    /**
     * @return string
     */
    public function getName()
    {
        return 'cancel_order';
    }

    /**
     * @param $orderNumber
     * @return $this
     */
    public function setOrderNumber($orderNumber)
    {
        $this->orderNumber = $orderNumber;
        return $this;
    }

    /**
     * @return array
     */
    public function getRequestData()
    {
        return [
            'orderno' => $this->orderNumber
        ];
    }

    /**
     * @param Response $response
     * @return $this|AbstractMethod
     */
    public function setResponseData(Response $response)
    {
        $response = $response->get('response');
        $this->responseData = Arr::get($response, 'cancellation', false);
        return $this;
    }
}
