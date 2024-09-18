<?php

namespace PolkurierWebServiceApi\Methods;

use PolkurierWebServiceApi\Response;
use PolkurierWebServiceApi\Util\Arr;

class GetMapToken extends AbstractMethod
{
    public function getName()
    {
        return 'get_map_token';
    }

    public function getRequestData()
    {
        return [];
    }

    public function setResponseData(Response $response)
    {
        $this->responseData = [
            'token' => Arr::get($response->get('response'), 'token')
        ];
        return $this;
    }
}
