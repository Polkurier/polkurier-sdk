<?php

namespace PolkurierWebServiceApi\Entities;

/**
 * Class MapToken
 * @package PolkurierWebServiceApi\Entities
 */
class MapToken
{
    /**
     * @var string
     */
    private $token;

    /**
     * @param string $token
     * @return $this
     */
    public function setToken($token)
    {
        $this->token = $token;
        return $this;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'token' => $this->getToken(),
        ];
    }
}
