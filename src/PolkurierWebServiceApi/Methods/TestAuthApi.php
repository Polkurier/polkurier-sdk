<?php

namespace PolkurierWebServiceApi\Methods;

use PolkurierWebServiceApi\Response;

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
     * @return AbstractMethod
     */
    public function setResponseData(Response $response)
    {
        $responseData = $response->get('response');
        $this->responseData = [
            'status' => $response->get('status'),
            'authorization' => isset($responseData['authorization']) ? $responseData['authorization'] : false
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
