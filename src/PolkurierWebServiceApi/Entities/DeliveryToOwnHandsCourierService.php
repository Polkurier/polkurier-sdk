<?php

namespace PolkurierWebServiceApi\Entities;

class DeliveryToOwnHandsCourierService implements CourierServiceInterface
{
    /**
     * @var bool
     */
    private $deliveryToOwnHands;

    /**
     * @param bool $deliveryToOwnHands
     */
    public function __construct($deliveryToOwnHands = false)
    {
        $this->deliveryToOwnHands = (bool)$deliveryToOwnHands;
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
