<?php
namespace PolkurierWebServiceApi\Entities;

/**
 * Class RodCourierService
 * @package PolkurierWebServiceApi\Entities
 *
 */
class RodCourierService implements CourierServiceInterface
{
    /**
     * @var bool
     */
    private $rod = false;
    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $description;

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'ROD' => (bool)$this->getRod(),
            'rodtype' => $this->getType(),
            'roddescription' => $this->getDescription()
        ];
    }

    /**
     * @return bool
     */
    public function getRod()
    {
        return $this->rod;
    }

    /**
     * @param bool $rod
     */
    public function setRod($rod)
    {
        $this->rod = $rod;
    }


    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return RodCourierService
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return RodCourierService
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }
}
