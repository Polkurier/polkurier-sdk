<?php

namespace PolkurierWebServiceApi\Methods;

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
            $template = [
                'id' => Arr::get($row, 'id'),
                'name' => Arr::get($row, 'name'),
                'shipmenttype' => Arr::get($row, 'shipmenttype'),
                'length' => Arr::get($row, 'length'),
                'width' => Arr::get($row, 'width'),
                'height' => Arr::get($row, 'height'),
                'weight' => Arr::get($row, 'weight'),
                'COD' => Arr::get($row, 'COD'),
                'insurance' => Arr::get($row, 'insurance'),
                'description' => Arr::get($row, 'description'),
                'type' => Arr::get($row, 'type'),
            ];

            $this->responseData[] = $template;
        }
        return $this;
    }
}
