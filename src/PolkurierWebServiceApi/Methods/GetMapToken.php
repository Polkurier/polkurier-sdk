<?php

namespace PolkurierWebServiceApi\Methods;

use PolkurierWebServiceApi\Entities\MapToken;
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
        $token = new MapToken();
        $token->setToken($response->get('response')['token']);
        $this->responseData = $token;
        return $this;
    }
}
