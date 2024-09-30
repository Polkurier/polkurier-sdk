<?php

namespace PolkurierWebServiceApi\Entities;

use JsonSerializable;

class BankAccount implements JsonSerializable
{

    /**
     * @var string
     */
    private $id = '';

    /**
     * @var string
     */
    private $number = '';

    /**
     * @var string
     */
    private $name = '';

    /**
     * @var bool
     */
    private $default = false;

    /**
     * @param string $id
     * @return BankAccount
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $number
     * @return BankAccount
     */
    public function setNumber($number)
    {
        $this->number = (string)$number;
        return $this;
    }

    /**
     * @return string
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * @param string $name
     * @return BankAccount
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param bool $default
     * @return BankAccount
     */
    public function setDefault($default)
    {
        $this->default = (bool)$default;
        return $this;
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
            'id' => $this->id,
            'number' => $this->number,
            'name' => $this->name,
            'default' => $this->default,
        ];
    }

}
