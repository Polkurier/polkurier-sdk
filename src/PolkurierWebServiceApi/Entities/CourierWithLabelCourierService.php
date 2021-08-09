<?php

namespace PolkurierWebServiceApi\Entities;

/**
 * Class CourierWithLabelCourierService
 * @package PolkurierWebServiceApi\Entities
 */
class CourierWithLabelCourierService implements CourierServiceInterface
{
    /**
     * @var bool
     */
    private $courier_with_label;

    /**
     * CourierWithLabelCourierService constructor.
     * @param false $courier_with_label
     */
    public function __construct($courier_with_label = false)
    {
        $this->courier_with_label = $courier_with_label;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'COURIER_WITH_LABEL' => $this->courier_with_label
        ];
    }

    /**
     * @return bool
     */
    public function isCourierWithLabel()
    {
        return $this->courier_with_label;
    }

    /**
     * @param bool $courier_with_label
     */
    public function setCourierWithLabel($courier_with_label)
    {
        $this->courier_with_label = $courier_with_label;
    }
}
