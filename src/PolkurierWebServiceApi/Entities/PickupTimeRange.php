<?php

namespace PolkurierWebServiceApi\Entities;

use JsonSerializable;

class PickupTimeRange implements JsonSerializable
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
    public function jsonSerialize()
    {
        return [
            'timefrom' => $this->timeFrom,
            'timeto' => $this->timeTo,
        ];
    }
}
