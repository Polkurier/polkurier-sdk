<?php

namespace PolkurierWebServiceApi\Entities;

use DateTime;
use DateTimeImmutable;
use JsonSerializable;
use PolkurierWebServiceApi\Util\Arr;

class CourierPickupDate implements JsonSerializable
{

    /**
     * @var DateTimeImmutable
     */
    private $date;

    /**
     * @var CourierPickupTimeRange[]
     */
    private $time;

    /**
     * @param CourierPickupTimeRange[] $time
     */
    public function __construct(DateTimeImmutable $date, array $time = [])
    {
        Arr::assertInstancesOf($time, CourierPickupTimeRange::class);
        $this->date = $date;
        $this->time = $time;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @return CourierPickupTimeRange[]
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        $timeArray = [];
        foreach ($this->time as $timeRange) {
            $timeArray[] = $timeRange->jsonSerialize();
        }
        return [
            'pickupdate' => $this->date->format(DateTime::ATOM),
            'time' => $timeArray,
        ];
    }

}
