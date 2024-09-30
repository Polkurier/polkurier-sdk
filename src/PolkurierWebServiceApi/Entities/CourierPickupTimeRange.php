<?php

namespace PolkurierWebServiceApi\Entities;

use JsonSerializable;

class CourierPickupTimeRange implements JsonSerializable
{

    /**
     * @var string
     */
    public $from;

    /**
     * @var string
     */
    public $to;

    /**
     * @param string $timeFrom
     * @param string $timeTo
     */
    public function __construct($timeFrom = '', $timeTo = '')
    {
        $this->from = (string)$timeFrom;
        $this->to = (string)$timeTo;
    }

    /**
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'timefrom' => $this->from,
            'timeto' => $this->to,
        ];
    }

}
