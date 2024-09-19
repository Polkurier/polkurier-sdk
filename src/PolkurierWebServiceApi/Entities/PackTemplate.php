<?php

namespace PolkurierWebServiceApi\Entities;

/**
 * Class Template
 * @package PolkurierWebServiceApi\Entities
 */
class PackTemplate
{
    private $id;
    private $name;
    private $shipmentType;
    private $length;
    private $width;
    private $height;
    private $weight;
    private $cod;
    private $insurance;
    private $description;
    private $type;

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

    public function setShipmentType($shipmentType)
    {
        $this->shipmentType = $shipmentType;
        return $this;
    }

    public function getShipmentType()
    {
        return $this->shipmentType;
    }

    public function setLength($length)
    {
        $this->length = $length;
        return $this;
    }

    public function getLength()
    {
        return $this->length;
    }

    public function setWidth($width)
    {
        $this->width = $width;
        return $this;
    }

    public function getWidth()
    {
        return $this->width;
    }

    public function setHeight($height)
    {
        $this->height = $height;
        return $this;
    }

    public function getHeight()
    {
        return $this->height;
    }

    public function setWeight($weight)
    {
        $this->weight = $weight;
        return $this;
    }

    public function getWeight()
    {
        return $this->weight;
    }

    public function setCod($cod)
    {
        $this->cod = $cod;
        return $this;
    }

    public function getCod()
    {
        return $this->cod;
    }

    public function setInsurance($insurance)
    {
        $this->insurance = $insurance;
        return $this;
    }

    public function getInsurance()
    {
        return $this->insurance;
    }

    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    public function getType()
    {
        return $this->type;
    }

    public function toArray()
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'shipmenttype' => $this->getShipmentType(),
            'length' => $this->getLength(),
            'width' => $this->getWidth(),
            'height' => $this->getHeight(),
            'weight' => $this->getWeight(),
            'cod' => $this->getCod(),
            'insurance' => $this->getInsurance(),
            'description' => $this->getDescription(),
            'type' => $this->getType(),
        ];
    }
}
