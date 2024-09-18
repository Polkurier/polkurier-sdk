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
    private $rod;
    /**
     * @var string
     * @deprecated
     */
    private $type;
    /**
     * @var string
     * @deprecated
     */
    private $description;

    /**
     * RodCourierService constructor.
     * @param false $rod
     */
    public function __construct($rod = false)
    {
        $this->rod = $rod;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'ROD' => $this->rod
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
     * @deprecated
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return RodCourierService
     * @deprecated
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return string
     * @deprecated
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return RodCourierService
     * @deprecated
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }
}
