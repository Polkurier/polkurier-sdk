<?php

namespace PolkurierWebServiceApi\Entities;

use JsonSerializable;

class Carrier implements JsonSerializable
{

    /**
     * @var string
     */
    private $servicecode = '';

    /**
     * @var string
     */
    private $name = '';

    /**
     * @var array
     */
    private $additionalData = [];

    /**
     * @var bool
     */
    private $foreignShipments = false;

    /**
     * @return array
     */
    public function getAdditionalData()
    {
        return $this->additionalData;
    }

    /**
     * @param array $additionalData
     */
    public function setAdditionalData(array $additionalData)
    {
        $this->additionalData = $additionalData;
    }

    /**
     * @return string
     */
    public function getServicecode()
    {
        return $this->servicecode;
    }

    /**
     * @param string $servicecode
     */
    public function setServicecode($servicecode)
    {
        $this->servicecode = (string)$servicecode;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = (string)$name;
    }

    /**
     * @return bool
     */
    public function isForeignShipments()
    {
        return $this->foreignShipments;
    }

    /**
     * @param bool $foreignShipments
     * @return self
     */
    public function setForeignShipments($foreignShipments)
    {
        $this->foreignShipments = (bool)$foreignShipments;
        return $this;
    }

    /**
     * @return array
     */
    #[\ReturnTypeWillChange]
    function jsonSerialize()
    {
        return [
            'servicecode' => $this->servicecode,
            'name' => $this->name,
            'additional_data' => $this->additionalData
        ];
    }

}
