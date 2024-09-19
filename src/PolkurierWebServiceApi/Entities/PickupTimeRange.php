<?php

namespace PolkurierWebServiceApi\Entities;

/**
 * Class PickupTimeRange
 * @package PolkurierWebServiceApi\Entities
 */
class PickupTimeRange
{
    /**
     * @var string
     */
    public $timeFrom;

    /**
     * @var string
     */
    public $timeTo;

    /**
     * PickupTimeRange constructor.
     * @param string $timeFrom
     * @param string $timeTo
     */
    public function __construct($timeFrom = '', $timeTo = '')
    {
        $this->timeFrom = $timeFrom;
        $this->timeTo = $timeTo;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'timefrom' => $this->timeFrom,
            'timeto' => $this->timeTo,
        ];
    }
}
