<?php

namespace PolkurierWebServiceApi\Entities;

/**
 * Class LabelLessCourierService
 * @package PolkurierWebServiceApi\Entities
 */
class LabelLessCourierService implements CourierServiceInterface
{
    /**
     * @var bool
     */
    private $labelless;

    /**
     * LabelLessCourierService constructor.
     * @param bool $labelless
     */
    public function __construct($labelless = false)
    {
        $this->labelless = $labelless;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'LABELLESS' => $this->labelless
        ];
    }

    /**
     * @return bool
     */
    public function isLabelLess()
    {
        return $this->labelless;
    }

    /**
     * @param bool $labelless
     */
    public function setLabelLess($labelless)
    {
        $this->labelless = (bool)$labelless;
    }
}
