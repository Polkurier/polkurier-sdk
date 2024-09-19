<?php

namespace PolkurierWebServiceApi\Entities;

/**
 * Class Province
 * @package PolkurierWebServiceApi\Entities
 */
class Province
{
    private $id;
    private $name;

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function getId()
    {
        return $this->id;
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

    public function toArray()
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
        ];
    }
}
