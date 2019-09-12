<?php
namespace PolkurierWebServiceApi\Entities;

/**
 * Class Carrier
 * @package PolkurierWebServiceApi\Entities
 *
 */
class Carrier implements \JsonSerializable
{

    /**
     * @var string
     */
    private $servicecode;

    /**
     * @var string
     */
    private $name;

    /**
     * @var
     */
    private $additionalData;

    /**
     * @return mixed
     */
    public function getAdditionalData()
    {
        return $this->additionalData;
    }

    /**
     * @param mixed $additionalData
     */
    public function setAdditionalData($additionalData)
    {
        $this->additionalData = $additionalData;
    }

    /**
     * @return mixed
     */
    public function getServicecode()
    {
        return $this->servicecode;
    }

    /**
     * @param mixed $servicecode
     */
    public function setServicecode($servicecode)
    {
        $this->servicecode = $servicecode;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return array|mixed
     */
    function jsonSerialize()
    {
        return [
            'servicecode' => $this->servicecode,
            'name' => $this->name,
            'additional_data' => $this->additionalData
        ];
    }

}