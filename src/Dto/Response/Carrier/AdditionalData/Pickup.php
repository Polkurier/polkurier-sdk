<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response\Carrier\AdditionalData;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Entity\Order\Pickup as OrderPickup;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * A carrier's rules for the collection booked with an order.
 *
 * Each part is null when the carrier's entry leaves it out.
 */
final readonly class Pickup implements MessageInterface
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
         * @var Option|null null if the carrier's entry leaves it out
         */
        #[Serializer\SerializedName(self::FIELD_PICKUP_DATE)]
        public ?Option $pickupDate = null,

        /**
         * @var Option|null null if the carrier's entry leaves it out
         */
        #[Serializer\SerializedName(self::FIELD_PICKUP_TIME_FROM)]
        public ?Option $pickupTimeFrom = null,

        /**
         * @var Option|null null if the carrier's entry leaves it out
         */
        #[Serializer\SerializedName(self::FIELD_PICKUP_TIME_TO)]
        public ?Option $pickupTimeTo = null,

        /**
         * Whether the parcel can be dropped off at a sending point or handed to a courier instead of
         * booking a collection.
         * {@see \Polkurier\Webservice\Dto\Response\Carrier\AdditionalData\Property::$default} says
         * whether that's the usual way for this carrier, as for parcel lockers.
         *
         * @var Property|null null if the carrier's entry leaves it out
         */
        #[Serializer\SerializedName(self::FIELD_NO_COURIER_ORDER)]
        public ?Property $noCourierOrder = null,
    ) {}
}
