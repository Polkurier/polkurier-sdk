<?php

namespace PolkurierWebServiceApi\Methods;

use PolkurierWebServiceApi\Entities\PackTemplate;
use PolkurierWebServiceApi\Response;
use PolkurierWebServiceApi\Util\Arr;

class GetPackTemplates extends AbstractMethod
{
    public function getName()
    {
        return 'get_pack_templates';
    }

    public function getRequestData()
    {
        return [];
    }

    public function setResponseData(Response $response)
    {
        $this->responseData = [];

        foreach ($response->get('response') as $row) {
            $packTemplate = new PackTemplate();
            $packTemplate->setId(Arr::get($row, 'id'))
                ->setName(Arr::get($row, 'name'))
                ->setShipmentType(Arr::get($row, 'shipmenttype'))
                ->setLength(Arr::get($row, 'length'))
                ->setWidth(Arr::get($row, 'width'))
                ->setHeight(Arr::get($row, 'height'))
                ->setWeight(Arr::get($row, 'weight'))
                ->setCod(Arr::get($row, 'COD'))
                ->setInsurance(Arr::get($row, 'insurance'))
                ->setDescription(Arr::get($row, 'description'))
                ->setType(Arr::get($row, 'type'));

            $this->responseData[] = $packTemplate;
        }

        return $this;
    }
}
