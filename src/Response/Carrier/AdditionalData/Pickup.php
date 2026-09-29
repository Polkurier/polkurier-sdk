<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\Carrier\AdditionalData;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Response\Order\Pickup as OrderPickup;
use Symfony\Component\Serializer\Attribute as Serializer;

readonly class Pickup implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_PICKUP_DATE = OrderPickup::FIELD_PICKUP_DATE;
    public const string FIELD_PICKUP_TIME_FROM = OrderPickup::FIELD_PICKUP_TIME_FROM;
    public const string FIELD_PICKUP_TIME_TO = OrderPickup::FIELD_PICKUP_TIME_TO;
    public const string FIELD_NO_COURIER_ORDER = OrderPickup::FIELD_NO_COURIER_ORDER;
    /**-@#*/

    /**
     * @param Option|null $pickupDate
     * @param Option|null $pickupTimeFrom
     * @param Option|null $pickupTimeTo
     * @param Property|null $noCourierOrder
     */
    public function __construct(
        /**
         * @var Option|null
         */
        #[Serializer\SerializedName(self::FIELD_PICKUP_DATE)]
        private ?Option $pickupDate = null,

        /**
         * @var Option|null
         */
        #[Serializer\SerializedName(self::FIELD_PICKUP_TIME_FROM)]
        private ?Option $pickupTimeFrom = null,

        /**
         * @var Option|null
         */
        #[Serializer\SerializedName(self::FIELD_PICKUP_TIME_TO)]
        private ?Option $pickupTimeTo = null,

        /**
         * @var Property|null
         */
        #[Serializer\SerializedName(self::FIELD_NO_COURIER_ORDER)]
        private ?Property $noCourierOrder = null,
    ) {}

    /**
     * @return Option|null
     */
    public function getPickupDate(): ?Option
    {
        return $this->pickupDate;
    }

    /**
     * @return Option|null
     */
    public function getPickupTimeFrom(): ?Option
    {
        return $this->pickupTimeFrom;
    }

    /**
     * @return Option|null
     */
    public function getPickupTimeTo(): ?Option
    {
        return $this->pickupTimeTo;
    }

    /**
     * @return Property|null
     */
    public function getNoCourierOrder(): ?Property
    {
        return $this->noCourierOrder;
    }
}
