<?php

namespace PolkurierWebServiceApi\Methods;

use PolkurierWebServiceApi\Entities\MapToken;
use PolkurierWebServiceApi\Response;
use PolkurierWebServiceApi\Util\Arr;

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
     * @return GetMapToken
     */
    public function setResponseData(Response $response)
    {
        $this->responseData = new MapToken(Arr::get((array)$response->get('response'), 'token'));
        return $this;
    }

}
