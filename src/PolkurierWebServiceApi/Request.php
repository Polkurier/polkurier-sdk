<?php

namespace PolkurierWebServiceApi;

use PolkurierWebServiceApi\Methods\MethodInterface;

/**
 * Class Request
 * @package PolkurierWebService
 *
 */
class Request
{
    /**
     * @var Auth
     */
    private $auth;
    /**
     * @var MethodInterface
     */
    private $method;

    /**
     * Request constructor.
     * @param MethodInterface $method
     * @param Auth $auth
     */
    public function __construct(MethodInterface $method, Auth $auth)
    {
        $this->method = $method;
        $this->auth = $auth;
    }

    /**
     * @return array
     */
    public function getBody()
    {
        return [
            'authorization' => [
                'login' => $this->auth->getLogin(),
                'token' => $this->auth->getToken()
            ],
            'apimetod' => $this->method->getName(),
            'data' => $this->method->getRequestData()
        ];
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return [
            'Content-Type' => 'application/json'
        ];
    }
}