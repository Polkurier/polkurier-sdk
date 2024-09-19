<?php

namespace PolkurierWebServiceApi\Entities;

/**
 * Class Pickup
 * @package PolkurierWebServiceApi\Entities
 *
 */
class Pickup
{
    /**
     * @var string
     */
    private $date;

    /**
     * @var PickupTimeRange[]
     */
    private $timeRanges = [];

    /**
     * @var bool
     */
    private $noCourierOrder;

    /**
     * @param string $date
     * @return $this
     */
    public function setDate($date)
    {
        $this->date = $date;
        return $this;
    }

    /**
     * @return string
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param array $timeRanges
     * @return $this
     */
    public function setTimeRanges(array $timeRanges)
    {
        $this->timeRanges = $timeRanges;
        return $this;
    }

    /**
     * @return array
     */
    public function getTimeRanges()
    {
        return $this->timeRanges;
    }

    /**
     * @param boolean $noCourierOrder
     * @return Pickup
     */
    public function setNoCourierOrder($noCourierOrder)
    {
        $this->noCourierOrder = (bool)$noCourierOrder;
        return $this;
    }

    /**
     * @return bool
     */
    public function getNoCourierOrder()
    {
        return $this->noCourierOrder;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'pickupdate' => $this->getDate(),
            'time_ranges' => array_map(function (PickupTimeRange $timeRange) {
                return $timeRange->toArray();
            }, $this->getTimeRanges()),
            'nocourierorder' => $this->getNoCourierOrder()
        ];
    }
}
