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

    public function __construct($token)
    {
        $this->token = $token;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'token' => $this->token,
        ];
    }
}
