<?php

namespace PolkurierWebServiceApi;

use PolkurierWebServiceApi\Exception\ErrorException;
use PolkurierWebServiceApi\Exception\FatalException;
use PolkurierWebServiceApi\Methods\MethodInterface;
use PolkurierWebServiceApi\Type\ResponseStatus;

/**
 * Class PolkurierWebService
 * @package PolkurierWebServiceApi
 */
class PolkurierWebService
{
    /**
     * @var Auth
     */
    private $auth;

    /**
     * @var HTTPClient HTTPClient
     */
    private $HTTPClient;

    /**
     * @var Config
     */
    private $config;

    /**
     * PolkurierWebService constructor.
     * @param Auth $auth
     * @param Config $config
     */
    public function __construct(Auth $auth, Config $config)
    {
        $this->HTTPClient = new HTTPClient($config);
        $this->config = $config;
        $this->auth = $auth;
    }

    /**
     * @param MethodInterface $method
     * @return Response
     * @throws ErrorException
     * @throws FatalException
     */
    public function requestMethod(MethodInterface $method)
    {
        $request = new Request($method, $this->auth);
        $response = $this->HTTPClient->request($request);
        if ($response->get('status') !== ResponseStatus::SUCCESS) {
            throw new ErrorException($response->get('response'));
        }
        $method->setResponseData($response);
        return $response;
    }
}
