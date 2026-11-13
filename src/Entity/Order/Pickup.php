<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Entity\Order;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Polkurier\Webservice\Serializer\Attribute\ResponseOnly;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;

/**
 * When the courier should collect an order.
 *
 * Every field is optional because carriers differ: some need only a date, some a time window too,
 * and not every carrier uses them. `available_carriers` reports what each one expects.
 *
 * The times are wall-clock values. Only the time part of those objects is read, and the day comes
 * from the date field alone. Set `noCourierOrder` when the sender will drop the parcel off at a
 * branch or drop-off point and no collection should be booked; a carrier that doesn't allow this
 * refuses the order. Set `multiPickup` to have the order collected together with others, booked
 * later with
 * {@see \Polkurier\Webservice\Api\Service\OrdersServiceInterface::createMultiOrderPickup()}.
 *
 * The carrier's reference for a booked collection is returned on the order, in
 * {@see self::$pickupNumber}.
 *
 * @api
 */
#[Validate\InOrder(
    earlier: 'pickupTimeFrom',
    later: 'pickupTimeTo',
    reason: 'the collection window ends before it starts',
)]
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
     * @param \DateTimeInterface|null $pickupTimeFrom start of the window, time part only
     * @param \DateTimeInterface|null $pickupTimeTo end of the window, time part only
     * @param bool $noCourierOrder true when the sender will hand the parcel over themselves and no
     *                             collection should be booked
     * @param bool $multiPickup place the order for a collective pickup, booked later for several
     *        orders at once with createMultiOrderPickup(). $noCourierOrder wins when both are set
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
        #[ResponseOnly]
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
     * @return static
     */
    public function setPickupDate(?\DateTimeInterface $pickupDate): static
    {
        $this->pickupDate = $pickupDate;

        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getPickupTimeFrom(): ?\DateTimeInterface
    {
        return $this->pickupTimeFrom;
    }

    /**
     * @param \DateTimeInterface|null $pickupTimeFrom
     *
     * @return static
     */
    public function setPickupTimeFrom(?\DateTimeInterface $pickupTimeFrom): static
    {
        $this->pickupTimeFrom = $pickupTimeFrom;

        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getPickupTimeTo(): ?\DateTimeInterface
    {
        return $this->pickupTimeTo;
    }

    /**
     * @param \DateTimeInterface|null $pickupTimeTo
     *
     * @return static
     */
    public function setPickupTimeTo(?\DateTimeInterface $pickupTimeTo): static
    {
        $this->pickupTimeTo = $pickupTimeTo;

        return $this;
    }

    /**
     * @return bool
     */
    public function isNoCourierOrder(): bool
    {
        return $this->noCourierOrder;
    }

    /**
     * @param bool $noCourierOrder
     *
     * @return static
     */
    public function setNoCourierOrder(bool $noCourierOrder): static
    {
        $this->noCourierOrder = $noCourierOrder;

        return $this;
    }

    /**
     * @return bool
     */
    public function isMultiPickup(): bool
    {
        return $this->multiPickup;
    }

    /**
     * @param bool $multiPickup
     *
     * @return static
     */
    public function setMultiPickup(bool $multiPickup): static
    {
        $this->multiPickup = $multiPickup;

        return $this;
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

    /**
     * @param string|null $pickupNumber
     *
     * @return static
     */
    public function setPickupNumber(?string $pickupNumber): static
    {
        $this->pickupNumber = $pickupNumber;

        return $this;
    }
}
