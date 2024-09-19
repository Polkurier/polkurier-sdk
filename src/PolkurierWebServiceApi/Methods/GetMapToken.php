<?php

namespace PolkurierWebServiceApi\Methods;

use PolkurierWebServiceApi\Entities\MapToken;
use PolkurierWebServiceApi\Response;

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
        $token = new MapToken($response->get('response')['token']);
        $this->responseData = $token;
        return $this;
    }
}
