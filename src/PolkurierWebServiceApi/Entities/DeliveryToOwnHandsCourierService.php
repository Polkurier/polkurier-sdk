<?php

namespace PolkurierWebServiceApi\Entities;

/**
 * Class DeliveryToOwnHandsCourierService
 * @package PolkurierWebServiceApi\Entities
 */
class DeliveryToOwnHandsCourierService implements CourierServiceInterface
{
    /**
     * @var bool
     */
    private $deliveryToOwnHands;

    /**
     * DeliveryToOwnHandsCourierService constructor.
     * @param bool $deliveryToOwnHands
     */
    public function __construct($deliveryToOwnHands = false)
    {
        $this->deliveryToOwnHands = $deliveryToOwnHands;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'DOSTAWA_DO_RAK_WLASNYCH' => $this->deliveryToOwnHands
        ];
    }

    /**
     * @return bool
     */
    public function isDeliveryToOwnHands()
    {
        return $this->deliveryToOwnHands;
    }

    /**
     * @param bool $deliveryToOwnHands
     */
    public function setDeliveryToOwnHands($deliveryToOwnHands)
    {
        $this->deliveryToOwnHands = (bool)$deliveryToOwnHands;
    }
}
