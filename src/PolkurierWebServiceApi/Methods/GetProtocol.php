<?php

namespace PolkurierWebServiceApi\Methods;

use PolkurierWebServiceApi\File;
use PolkurierWebServiceApi\Response;
use PolkurierWebServiceApi\Util\Arr;

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
     * @param string[] $orderNumbers
     * @return GetProtocol
     */
    public function setOrderNumbers(array $orderNumbers)
    {
        $this->orderNumbers = $orderNumbers;
        return $this;
    }

    /**
     * @param $orderNumber
     * @return GetProtocol
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
     * @return GetProtocol
     */
    public function setResponseData(Response $response)
    {
        $data = $response->get('response');
        $this->responseData = new File(base64_decode(Arr::get($data, 'file')));
        return $this;
    }

}
