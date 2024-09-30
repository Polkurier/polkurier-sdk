<?php

namespace PolkurierWebServiceApi\Methods;

use PolkurierWebServiceApi\Response;
use PolkurierWebServiceApi\Util\Arr;

class CancelOrder extends AbstractMethod
{
    /**
     * @var string
     */
    private $orderNumber = '';

    /**
     * @return string
     */
    public function getName()
    {
        return 'cancel_order';
    }

    /**
     * @param string $orderNumber
     * @return CancelOrder
     */
    public function setOrderNumber($orderNumber)
    {
        $this->orderNumber = (string)$orderNumber;
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
     * @return CancelOrder
     */
    public function setResponseData(Response $response)
    {
        $response = $response->get('response');
        $this->responseData = Arr::get($response, 'cancellation', false);
        return $this;
    }

}
