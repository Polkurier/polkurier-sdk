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

    public function __toString()
    {
        return $this->token;
    }

    /**
     * @return string
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return $this->token;
    }

}
