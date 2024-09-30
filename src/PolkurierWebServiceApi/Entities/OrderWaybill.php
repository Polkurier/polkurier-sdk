<?php

namespace PolkurierWebServiceApi\Entities;

use JsonSerializable;

class OrderWaybill implements JsonSerializable
{

    /**
     * @var string
     */
    private $number;

    /**
     * @var bool
     */
    private $default;

    /**
     * @param string $number
     * @param bool $default
     */
    public function __construct($number = '', $default = false)
    {
        $this->number = (string)$number;
        $this->default = (bool)$default;
    }

    /**
     * @return string|null
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * @return bool
     */
    public function isDefault()
    {
        return $this->default;
    }

    /**
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'number' => $this->number,
            'is_default' => $this->default,
        ];
    }

}
