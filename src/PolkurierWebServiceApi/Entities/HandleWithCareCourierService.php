<?php

namespace PolkurierWebServiceApi\Entities;

class HandleWithCareCourierService implements CourierServiceInterface
{
    /**
     * @var bool
     */
    private $handleWithCare;

    /**
     * HandleWithCareCourierService constructor.
     * @param bool $handleWithCare
     */
    public function __construct($handleWithCare = false)
    {
        $this->handleWithCare = $handleWithCare;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'HANDLE_WITH_CARE' => $this->handleWithCare
        ];
    }

    /**
     * @return bool
     */
    public function isHandleWithCare()
    {
        return $this->handleWithCare;
    }

    /**
     * @param bool $handleWithCare
     */
    public function setHandleWithCare($handleWithCare)
    {
        $this->handleWithCare = (bool)$handleWithCare;
    }
}
