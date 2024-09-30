<?php

namespace PolkurierWebServiceApi\Methods;

use PolkurierWebServiceApi\Entities\PackTemplate;
use PolkurierWebServiceApi\Response;
use PolkurierWebServiceApi\Util\Arr;

class GetPackTemplates extends AbstractMethod
{

    /**
     * @return string
     */
    public function getName()
    {
        return 'get_pack_templates';
    }

    /**
     * @return array
     */
    public function getRequestData()
    {
        return [];
    }

    /**
     * @return GetPackTemplates
     */
    public function setResponseData(Response $response)
    {
        $this->responseData = [];
        foreach ($response->get('response') as $row) {
            $this->responseData[] = new PackTemplate(
                (string)Arr::get($row, 'id', ''),
                (string)Arr::get($row, 'name', ''),
                (string)Arr::get($row, 'shipmenttype', ''),
                (int)Arr::get($row, 'length', 0),
                (int)Arr::get($row, 'width', 0),
                (int)Arr::get($row, 'height', 0),
                (float)Arr::get($row, 'weight', 0.0),
                (float)Arr::get($row, 'COD', 0.0),
                (float)Arr::get($row, 'insurance', 0.0),
                (string)Arr::get($row, 'description', ''),
                (string)Arr::get($row, 'type', '')
            );
        }
        return $this;
    }

}
