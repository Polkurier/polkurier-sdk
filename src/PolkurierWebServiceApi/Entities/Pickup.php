<?php

namespace PolkurierWebServiceApi\Entities;

use PolkurierWebServiceApi\Exception\ErrorException;
use PolkurierWebServiceApi\Util\Validators;

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
     * @var string
     */
    private $timeFrom;

    /**
     * @var string
     */
    private $timeTo;

    /**
     * @var bool
     */
    private $noCourierOrder;

    /**
     * @param $date
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
    private function getDate()
    {
        return $this->date;
    }

    /**
     * @param $hour
     * @return $this
     * @throws ErrorException
     */
    public function setTimeFrom($hour)
    {
        Validators::validateHourFormatAndThrowOnInvalid($hour);
        $this->timeFrom = $hour;
        return $this;
    }


    /**
     * @return string
     */
    private function getTimeFrom()
    {
        return $this->timeFrom;
    }


    /**
     * @param $hour
     * @return $this
     * @throws ErrorException
     */
    public function setTimeTo($hour)
    {
        Validators::validateHourFormatAndThrowOnInvalid($hour);
        $this->timeTo = $hour;
        return $this;
    }

    /**
     * @return string
     */
    private function getTimeTo()
    {
        return $this->timeTo;
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
    private function getNoCourierOrder()
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
            'pickuptimefrom' => $this->getTimeFrom(),
            'pickuptimeto' => $this->getTimeTo(),
            'nocourierorder' => $this->getNoCourierOrder(),
        ];
    }
}