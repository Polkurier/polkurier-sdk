<?php

namespace PolkurierWebServiceApi\Methods;

use PolkurierWebServiceApi\Entities\MapToken;
use PolkurierWebServiceApi\Response;

class GetMapToken extends AbstractMethod
{
    /**
     * @return string
     */
    public function getName()
    {
        return 'get_map_token';
    }

    /**
     * @return array
     */
    public function getRequestData()
    {
        return [];
    }

    /**
     * @param Response $response
     * @return $this|GetMapToken
     */
    public function setResponseData(Response $response)
    {
        $token = new MapToken($response->get('response')['token']);
        $this->responseData = $token;
        return $this;
    }
}
