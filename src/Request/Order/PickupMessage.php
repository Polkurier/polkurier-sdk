<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Request\Order;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;

/**
 * When the courier should collect an order.
 *
 * Every field is optional because which of them a carrier needs varies: some want only a date,
 * some a time window as well, and some arrange collection on their own schedule and ignore what is
 * sent. `available_carriers` reports what each one expects.
 *
 * The times are wall-clock values, so only the time part of the objects passed in is read; the day
 * comes from the date field alone. Set `noCourierOrder` when the sender will hand the parcel in at
 * a branch or drop-off point and no collection should be booked at all.
 *
 * The carrier's reference for a booked collection comes back on the order rather than here; see
 * {@see \Polkurier\Webservice\Response\Order\Pickup}.
 */
#[Validate\InOrder(
    earlier: 'pickupTimeFrom',
    later: 'pickupTimeTo',
    because: 'the collection window ends before it starts',
)]
readonly class PickupMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_PICKUP_DATE = 'pickupdate';
    public const string FIELD_PICKUP_TIME_FROM = 'pickuptimefrom';
    public const string FIELD_PICKUP_TIME_TO = 'pickuptimeto';
    public const string FIELD_NO_COURIER_ORDER = 'nocourierorder';
    public const string FIELD_MULTI_PICKUP = 'multiPickup';
    /**-@#*/

    /**
     * @param \DateTimeInterface|null $pickupDate the day of collection
     * @param \DateTimeInterface|null $pickupTimeFrom start of the window; time part only
     * @param \DateTimeInterface|null $pickupTimeTo end of the window; time part only
     * @param bool $noCourierOrder true when the sender will hand the parcel over themselves and no
     *                             collection should be booked
     * @param bool $multiPickup whether this collection covers several orders
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
    ) {}

    /**
     * @return \DateTimeInterface|null
     */
    public function getPickupDate(): ?\DateTimeInterface
    {
        return $this->pickupDate;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getPickupTimeFrom(): ?\DateTimeInterface
    {
        return $this->pickupTimeFrom;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getPickupTimeTo(): ?\DateTimeInterface
    {
        return $this->pickupTimeTo;
    }

    /**
     * @return bool
     */
    public function isNoCourierOrder(): bool
    {
        return $this->noCourierOrder;
    }

    /**
     * @return bool
     */
    public function isMultiPickup(): bool
    {
        return $this->multiPickup;
    }
}
