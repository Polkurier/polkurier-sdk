<?php

namespace PolkurierWebServiceApi\Entities;

use PolkurierWebServiceApi\Exception\ErrorException;
use PolkurierWebServiceApi\Util\Validators;

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
     * @return Pickup
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
     * @param string $hour
     * @return Pickup
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
     * @param string $hour
     * @return Pickup
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
            'pickupdate' => $this->date,
            'pickuptimefrom' => $this->timeFrom,
            'pickuptimeto' => $this->timeTo,
            'nocourierorder' => $this->noCourierOrder,
        ];
    }
}
