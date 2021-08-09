<?php

namespace PolkurierWebServiceApi\Methods;

use PolkurierWebServiceApi\File;
use PolkurierWebServiceApi\Response;
use PolkurierWebServiceApi\Util\Arr;

/**
 * Class GetProtocol
 * @package PolkurierWebServiceApi\Methods
 *
 */
class GetProtocol extends AbstractMethod
{
    /**
     * @var array
     */
    private $orderNumbers = [];

    /**
     * @return string
     */
    public function getName()
    {
        return 'get_protocol';
    }

    /**
     * @param array $orderNumbers
     * @return $this
     */
    public function setOrderNumbers(array $orderNumbers)
    {
        $this->orderNumbers = $orderNumbers;
        return $this;
    }

    /**
     * @param $orderNumber
     * @return $this
     */
    public function addOrderNumber($orderNumber)
    {
        $this->orderNumbers[] = $orderNumber;
        return $this;
    }

    /**
     * @return array
     */
    public function getRequestData()
    {
        return [
            'orderno' => $this->orderNumbers
        ];
    }

    /**
     * @param Response $response
     * @return $this|AbstractMethod
     */
    public function setResponseData(Response $response)
    {
        $data = $response->get('response');
        $this->responseData = new File(base64_decode(Arr::get($data, 'file')));
        return $this;
    }
}
