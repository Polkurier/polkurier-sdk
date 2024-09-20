<?php

namespace PolkurierWebServiceApi\Entities;

use JsonSerializable;

class PickupDate implements JsonSerializable
{
    /**
     * @var \DateTimeImmutable
     */
    private $pickupdate;

    /**
     * @var PickupTimeRange[]
     */
    private  $time = [];

    /**
     * @return \DateTimeImmutable
     */
    public function getPickupdate()
    {
        return $this->pickupdate;
    }

    /**
     * @param \DateTimeImmutable $pickupdate
     */
    public function setPickupdate($pickupdate)
    {
        $this->pickupdate = $pickupdate;
    }

    /**
     * @return PickupTimeRange[]
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * @param PickupTimeRange[] $time
     */
    public function setTime($time)
    {
        $this->time = $time;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        $timeArray = [];

        foreach ($this->time as $timeRange) {
            $timeArray[] = [
                'timefrom' => $timeRange->timeFrom,
                'timeto' => $timeRange->timeTo,
            ];
        }

        return [
            'pickupdate' => $this->pickupdate,
            'time' => $timeArray,
        ];
    }
}