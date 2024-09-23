<?php

namespace PolkurierWebServiceApi\Entities;

class MapToken implements \JsonSerializable
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
    public function jsonSerialize()
    {
        return [
            'token' => $this->token,
        ];
    }
}
