<?php

namespace PolkurierWebServiceApi\Methods;

use PolkurierWebServiceApi\Response;

/**
 * Class TestAuthApi
 * @package PolkurierWebServiceApi\Methods
 */
class TestAuthApi extends AbstractMethod
{
    /**
     * @return string
     */
    public function getName()
    {
        return 'test_auth_api';
    }

    /**
     * @param Response $response
     * @return $this|AbstractMethod
     */
    public function setResponseData(Response $response)
    {
        $responseData = $response->get('response');
        $this->responseData = [
            'status' => $response->get('status'),
            'authorization' => $responseData['authorization'] ?? false
        ];
        return $this;
    }

    /**
     * @return array
     */
    public function getRequestData()
    {
        return [];
    }
}
