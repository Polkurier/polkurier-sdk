<?php
namespace PolkurierWebServiceApi\Methods;

use PolkurierWebServiceApi\File;
use PolkurierWebServiceApi\Response;
use PolkurierWebServiceApi\Util\Arr;

class GetLabel extends AbstractMethod
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
        return 'get_label';
    }

    /**
     * @param string[] $orderNumbers
     * @return GetLabel
     */
    public function setOrderNumbers(array $orderNumbers)
    {
        $this->orderNumbers = $orderNumbers;
        return $this;
    }

    /**
     * @param string $orderNumber
     * @return GetLabel
     */
    public function addOrderNumber($orderNumber)
    {
        $this->orderNumbers[] = (string)$orderNumber;
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
     * @return GetLabel
     */
    public function setResponseData(Response $response)
    {
        $data = $response->get('response');
        $this->responseData = new File(base64_decode(Arr::get($data, 'file')));
        return $this;
    }
}
