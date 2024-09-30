<?php

namespace PolkurierWebServiceApi\Methods;

use PolkurierWebServiceApi\Response;
use PolkurierWebServiceApi\Util\Arr;

class GetStatus extends AbstractMethod
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
        return 'get_status';
    }

    /**
     * @param $orderNumber
     * @return GetStatus
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
     * @return GetStatus
     */
    public function setResponseData(Response $response)
    {
        $this->responseData = [];
        $response = $response->get('response');
        $this->responseData['url'] = Arr::get($response, 'url');
        $this->responseData['status_date'] = Arr::get($response, 'status_date');
        $this->responseData['status'] = Arr::get($response, 'status');
        $this->responseData['status_code'] = Arr::get($response, 'status_code');
        $this->responseData['delivered_date'] = Arr::get($response, 'delivered_date');
        return $this;
    }

}
