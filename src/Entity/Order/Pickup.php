<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Entity\Order;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Symfony\Component\Serializer\Attribute as Serializer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;

/**
 * When the courier is due to collect an order, as reported on the order. To book a collection, use
 * {@see \Polkurier\Webservice\Entity\Order\PickupMessage}.
 *
 * Every field is optional. A carrier that schedules collections on its own terms fills in only what
 * it has settled: some report just a date, others a date and a time window. The reference number is
 * missing until the carrier has accepted the booking. An order with no collection arranged has no
 * pickup at all ({@see \Polkurier\Webservice\Entity\Order\Order::getPickup()} returns null).
 *
 * The date and the 2 times arrive as separate values. The times are wall-clock only, so ignore the
 * date part of the objects the time accessors return, and read the day from
 * {@see self::getPickupDate()}.
 */
readonly class Pickup implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_PICKUP_DATE = 'pickupdate';
    public const string FIELD_PICKUP_TIME_FROM = 'pickuptimefrom';
    public const string FIELD_PICKUP_TIME_TO = 'pickuptimeto';
    public const string FIELD_NO_COURIER_ORDER = 'nocourierorder';
    public const string FIELD_MULTI_PICKUP = 'multiPickup';
    public const string FIELD_PICKUP_NUMBER = 'pickup_number';
    /**-@#*/

    /**
     * @param \DateTimeInterface|null $pickupDate the day of collection
     * @param \DateTimeInterface|null $pickupTimeFrom start of the window, as a time of day
     * @param \DateTimeInterface|null $pickupTimeTo end of the window, as a time of day
     * @param bool $noCourierOrder true when the sender will hand the parcel over themselves and no
     *                             collection should be booked
     * @param bool $multiPickup whether the order was placed for a collective pickup
     * @param string|null $pickupNumber the carrier's reference for the booked collection
     */
    public function __construct(
        /**
         * @var \DateTimeInterface|null
         */
        #[Serializer\SerializedName(self::FIELD_PICKUP_DATE)]
        #[Serializer\Context([DateTimeNormalizer::FORMAT_KEY => 'Y-m-d'])]
        #[OmitWhenEmpty]
        private ?\DateTimeInterface $pickupDate = null,

        /**
         * @var \DateTimeInterface|null
         */
        #[Serializer\SerializedName(self::FIELD_PICKUP_TIME_FROM)]
        #[Serializer\Context([DateTimeNormalizer::FORMAT_KEY => 'H:i'])]
        #[OmitWhenEmpty]
        private ?\DateTimeInterface $pickupTimeFrom = null,

        /**
         * @var \DateTimeInterface|null
         */
        #[Serializer\SerializedName(self::FIELD_PICKUP_TIME_TO)]
        #[Serializer\Context([DateTimeNormalizer::FORMAT_KEY => 'H:i'])]
        #[OmitWhenEmpty]
        private ?\DateTimeInterface $pickupTimeTo = null,

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_NO_COURIER_ORDER)]
        private bool $noCourierOrder = false,

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_MULTI_PICKUP)]
        private bool $multiPickup = false,

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_PICKUP_NUMBER)]
        #[OmitWhenEmpty]
        private ?string $pickupNumber = null,
    ) {}

    /**
     * @return \DateTimeInterface|null the day of collection, at midnight
     */
    public function getPickupDate(): ?\DateTimeInterface
    {
        return $this->pickupDate;
    }

    /**
     * Start of the collection window.
     *
     * @return \DateTimeInterface|null the time of day, on 1 January 1970
     */
    public function getPickupTimeFrom(): ?\DateTimeInterface
    {
        return $this->pickupTimeFrom;
    }

    /**
     * End of the collection window.
     *
     * @return \DateTimeInterface|null the time of day, on 1 January 1970
     */
    public function getPickupTimeTo(): ?\DateTimeInterface
    {
        return $this->pickupTimeTo;
    }

    /**
     * True when the sender hands the parcel over themselves and no collection should be booked.
     *
     * @return bool
     */
    public function isNoCourierOrder(): bool
    {
        return $this->noCourierOrder;
    }

    /**
     * Whether the order was placed for a collective pickup.
     *
     * @return bool
     */
    public function isMultiPickup(): bool
    {
        return $this->multiPickup;
    }

    /**
     * The carrier's reference for the booked collection, once it has issued one.
     *
     * @return string|null
     */
    public function getPickupNumber(): ?string
    {
        return $this->pickupNumber;
    }
}
