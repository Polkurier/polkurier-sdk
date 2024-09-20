<?php

namespace PolkurierWebServiceApi\Entities;

/**
 * Class OrderWaybill
 * @package PolkurierWebServiceApi\Entities
 */
class OrderWaybill
{
    /**
     * @var
     */
    private $number;
    /**
     * @var
     */
    private $isDefault;

    /**
     * @param string $number
     * @return $this
     */
    public function setNumber($number)
    {
        $this->number = $number;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * @param bool $isDefault
     * @return $this
     */
    public function setIsDefault($isDefault)
    {
        $this->isDefault = (bool)$isDefault;
        return $this;
    }

    /**
     * @return bool|null
     */
    public function getIsDefault()
    {
        return $this->isDefault;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'number' => $this->number,
            'is_default' => $this->isDefault,
        ];
    }
}
