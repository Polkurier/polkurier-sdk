<?php
namespace PolkurierWebServiceApi\Methods;

use PolkurierWebServiceApi\Response;
use PolkurierWebServiceApi\Entities\Carrier;
use PolkurierWebServiceApi\Util\Arr;

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
     * @param Response $response
     * @return AvailableCarriers
     */
    public function setResponseData(Response $response) {
        $this->responseData = [];
        foreach ($response->get('response') as $row) {
            $item = new Carrier();
            $item->setServicecode((string)Arr::get($row, 'servicecode', ''));
            $item->setName((string)Arr::get($row, 'name', ''));
            $item->setAdditionalData((array)Arr::get($row, 'additional_data', []));
            $item->setForeignShipments(Arr::get($row, 'foreign_shipments', false));
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
