<?php

namespace PolkurierWebServiceApi\Entities;

/**
 * Class BankAccount
 * @package PolkurierWebServiceApi\Entities
 */
class BankAccount
{
    /**
     * @var
     */
    private $id;
    /**
     * @var
     */
    private $number;
    /**
     * @var
     */
    private $name;
    /**
     * @var
     */
    private $isDefault;

    /**
     * @param $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param $number
     * @return $this
     */
    public function setNumber($number)
    {
        $this->number = $number;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * @param $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param $isDefault
     * @return $this
     */
    public function setIsDefault($isDefault)
    {
        $this->isDefault = (bool)$isDefault;
        return $this;
    }

    /**
     * @return mixed
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
            'id' => $this->id,
            'number' => $this->number,
            'name' => $this->name,
            'default' => $this->isDefault,
        ];
    }
}
