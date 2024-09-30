<?php

namespace PolkurierWebServiceApi\Entities;

use JsonSerializable;
use PolkurierWebServiceApi\Type\PackType;
use PolkurierWebServiceApi\Type\ShipmentType;

class PackTemplate implements JsonSerializable
{

    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $shipmentType;

    /**
     * @var int
     */
    private $length;

    /**
     * @var int
     */
    private $width;

    /**
     * @var int
     */
    private $height;

    /**
     * @var float
     */
    private $weight;

    /**
     * @var float
     */
    private $cod;

    /**
     * @var float
     */
    private $insurance;

    /**
     * @var string
     */
    private $description;

    /**
     * @var string
     */
    private $type;

    /**
     * @param string $id
     * @param string $name
     * @param string $shipmentType
     * @param int $length
     * @param int $width
     * @param int $height
     * @param float $weight
     * @param float $cod
     * @param float $insurance
     * @param string $description
     * @param string $type
     */
    public function __construct(
        $id = '',
        $name = '',
        $shipmentType = ShipmentType::BOX,
        $length = 0,
        $width = 0,
        $height = 0,
        $weight = 0.0,
        $cod = 0.0,
        $insurance = 0.0,
        $description = '',
        $type = PackType::ST
    )
    {
        $this->id = (string)$id;
        $this->name = (string)$name;
        $this->shipmentType = (string)$shipmentType;
        $this->length = (int)$length;
        $this->width = (int)$width;
        $this->height = (int)$height;
        $this->weight = (float)$weight;
        $this->cod = (float)$cod;
        $this->insurance = (float)$insurance;
        $this->description = (string)$description;
        $this->type = (string)$type;
    }

    /**
     * @return string|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $name
     * @return PackTemplate
     */
    public function setName($name)
    {
        $this->name = (string)$name;
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
     * @param string $shipmentType
     * @return PackTemplate
     */
    public function setShipmentType($shipmentType)
    {
        $this->shipmentType = (string)$shipmentType;
        return $this;
    }

    /**
     * @return string
     */
    public function getShipmentType()
    {
        return $this->shipmentType;
    }

    /**
     * @param int $length
     * @return PackTemplate
     */
    public function setLength($length)
    {
        $this->length = (int)$length;
        return $this;
    }

    /**
     * @return int
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * @param int $width
     * @return PackTemplate
     */
    public function setWidth($width)
    {
        $this->width = (int)$width;
        return $this;
    }

    /**
     * @return int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @param int $height
     * @return PackTemplate
     */
    public function setHeight($height)
    {
        $this->height = (int)$height;
        return $this;
    }

    /**
     * @return int
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @param float $weight
     * @return PackTemplate
     */
    public function setWeight($weight)
    {
        $this->weight = (float)$weight;
        return $this;
    }

    /**
     * @return float
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * @param float $cod
     * @return PackTemplate
     */
    public function setCod($cod)
    {
        $this->cod = (float)$cod;
        return $this;
    }

    /**
     * @return float
     */
    public function getCod()
    {
        return $this->cod;
    }

    /**
     * @param float $insurance
     * @return PackTemplate
     */
    public function setInsurance($insurance)
    {
        $this->insurance = (float)$insurance;
        return $this;
    }

    /**
     * @return float
     */
    public function getInsurance()
    {
        return $this->insurance;
    }

    /**
     * @param string $description
     * @return PackTemplate
     */
    public function setDescription($description)
    {
        $this->description = (string)$description;
        return $this;
    }

    /**
     * @return string|
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $type
     * @return PackTemplate
     */
    public function setType($type)
    {
        $this->type = (string)$type;
        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'shipmenttype' => $this->shipmentType,
            'length' => $this->length,
            'width' => $this->width,
            'height' => $this->height,
            'weight' => $this->weight,
            'cod' => $this->cod,
            'insurance' => $this->insurance,
            'description' => $this->description,
            'type' => $this->type,
        ];
    }

}
