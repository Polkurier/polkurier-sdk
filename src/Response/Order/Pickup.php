<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\Order;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Symfony\Component\Serializer\Attribute as Serializer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;

/**
 * When the courier is due to collect an order, as reported on it. Booking a collection is
 * {@see \Polkurier\Webservice\Request\Order\PickupMessage} instead.
 *
 * Every field is optional because an order with no collection arranged carries an empty block, and
 * a carrier that schedules collections on its own terms fills in only what it has settled — some
 * report a date alone, some a time window with it. The reference number is absent until the
 * carrier has accepted the booking.
 *
 * The date and the two times arrive as separate values: the times are wall-clock only, so the date
 * part of the objects returned by the time accessors is meaningless — read the day from
 * {@see self::getPickupDate()}.
 */
class Pickup implements MessageInterface
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
     * @param \DateTimeInterface|null $pickupTimeFrom start of the window; time part only
     * @param \DateTimeInterface|null $pickupTimeTo end of the window; time part only
     * @param bool $noCourierOrder true when the sender will hand the parcel over themselves and no
     *                             collection should be booked
     * @param bool $multiPickup whether this collection covers several orders
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
     * @return \DateTimeInterface|null
     */
    public function getPickupDate(): ?\DateTimeInterface
    {
        return $this->pickupDate;
    }

    /**
     * @param \DateTimeInterface|null $pickupDate
     *
     * @return void
     */
    public function setPickupDate(?\DateTimeInterface $pickupDate): void
    {
        $this->pickupDate = $pickupDate;
    }

    /**
      * Start of the collection window; time part only.
      *
     * @return \DateTimeInterface|null
     */
    public function getPickupTimeFrom(): ?\DateTimeInterface
    {
        return $this->pickupTimeFrom;
    }

    /**
     * @param \DateTimeInterface|null $pickupTimeFrom
     *
     * @return void
     */
    public function setPickupTimeFrom(?\DateTimeInterface $pickupTimeFrom): void
    {
        $this->pickupTimeFrom = $pickupTimeFrom;
    }

    /**
      * End of the collection window; time part only.
      *
     * @return \DateTimeInterface|null
     */
    public function getPickupTimeTo(): ?\DateTimeInterface
    {
        return $this->pickupTimeTo;
    }

    /**
     * @param \DateTimeInterface|null $pickupTimeTo
     *
     * @return void
     */
    public function setPickupTimeTo(?\DateTimeInterface $pickupTimeTo): void
    {
        $this->pickupTimeTo = $pickupTimeTo;
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
     * @param bool $noCourierOrder
     *
     * @return void
     */
    public function setNoCourierOrder(bool $noCourierOrder): void
    {
        $this->noCourierOrder = $noCourierOrder;
    }

    /**
      * Whether this collection covers several orders at once.
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
