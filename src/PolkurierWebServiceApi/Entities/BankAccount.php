<?php

namespace PolkurierWebServiceApi\Entities;

/**
 * Class BankAccount
 * @package PolkurierWebServiceApi\Entities
 */
class BankAccount
{
    private $id;
    private $number;
    private $name;
    private $isDefault;

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setNumber($number)
    {
        $this->number = $number;
        return $this;
    }

    public function getNumber()
    {
        return $this->number;
    }

    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setIsDefault($isDefault)
    {
        $this->isDefault = (bool)$isDefault;
        return $this;
    }

    public function getIsDefault()
    {
        return $this->isDefault;
    }

    public function toArray()
    {
        return [
            'id' => $this->getId(),
            'number' => $this->getNumber(),
            'name' => $this->getName(),
            'default' => $this->getIsDefault(),
        ];
    }
}
