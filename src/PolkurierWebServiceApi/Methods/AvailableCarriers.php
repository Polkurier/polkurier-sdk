<?php
namespace PolkurierWebServiceApi\Methods;

use PolkurierWebServiceApi\Response;
use PolkurierWebServiceApi\Entities\Carrier;
use PolkurierWebServiceApi\Util\Arr;

/**
 * Class AvailableCarriers
 * @package PolkurierWebServiceApi\Methods
 *
 */
class AvailableCarriers extends AbstractMethod
{
    /**
     * @return string
     */
    public function getName()
    {
        return 'available_carriers';
    }

    /**
     * @param \PolkurierWebServiceApi\Response $response
     * @return $this|\PolkurierWebServiceApi\Methods\AbstractMethod
     */
    public function setResponseData(Response $response) {
        $this->responseData = [];
        foreach ($response->get('response') as $row) {
            $item = new Carrier();
            $item->setServicecode(Arr::get($row, 'servicecode'));
            $item->setName(Arr::get($row, 'name'));
            $item->setAdditionalData(Arr::get($row, 'additional_data', []));
            $this->responseData[] = $item;
        }
        return $this;
    }

    /**
     * @param $value
     */
    public function setAdditionalData($value)
    {
        $this->requestData['additional_data'] = (bool) $value;
    }

    /**
     * @param $value
     */
    public function setReturnCarrier($value)
    {
        $this->requestData['returncarrier'] = $value;
    }

}