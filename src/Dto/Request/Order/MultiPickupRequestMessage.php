<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Request\Order;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;

/**
 * Books one courier pickup for several orders.
 *
 * All 3 scheduling fields are required, whatever the carrier needs for a per-order pickup.
 *
 * Only orders placed for a collective pickup can join, see
 * {@see \Polkurier\Webservice\Entity\Order\Pickup::$multiPickup}.
 *
 * @api
 */
#[Validate\InOrder(
    earlier: 'pickupTimeFrom',
    later: 'pickupTimeTo',
    reason: 'must not be later than the end of the collection window',
)]
final readonly class MultiPickupRequestMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_PICKUP_DATE = 'pickupdate';
    public const string FIELD_PICKUP_TIME_FROM = 'pickuptimefrom';
    public const string FIELD_PICKUP_TIME_TO = 'pickuptimeto';
    public const string FIELD_ORDERS = 'orders';
    /**-@#*/

    /**
     * @param \DateTimeInterface $pickupDate the day the courier should call
     * @param \DateTimeInterface $pickupTimeFrom start of the window, only the time is sent
     * @param \DateTimeInterface $pickupTimeTo end of the window, only the time is sent
     * @param string[] $orders order numbers, not waybill numbers. At least one is required
     */
    public function __construct(
        /**
         * @var \DateTimeInterface
         */
        #[Serializer\SerializedName(self::FIELD_PICKUP_DATE)]
        #[Serializer\Context([DateTimeNormalizer::FORMAT_KEY => 'Y-m-d'])]
        public \DateTimeInterface $pickupDate,

        /**
         * @var \DateTimeInterface
         */
        #[Serializer\SerializedName(self::FIELD_PICKUP_TIME_FROM)]
        #[Serializer\Context([DateTimeNormalizer::FORMAT_KEY => 'H:i'])]
        public \DateTimeInterface $pickupTimeFrom,

        /**
         * @var \DateTimeInterface
         */
        #[Serializer\SerializedName(self::FIELD_PICKUP_TIME_TO)]
        #[Serializer\Context([DateTimeNormalizer::FORMAT_KEY => 'H:i'])]
        public \DateTimeInterface $pickupTimeTo,

        /**
         * @var string[]
         */
        #[Serializer\SerializedName(self::FIELD_ORDERS)]
        #[Validate\NotEmptyList]
        #[Validate\Each(Validate\NotBlank::class)]
        #[Validate\Each(Validate\OrderNumber::class)]
        #[Validate\Each(Validate\OfType::class, 'string')]
        public array $orders,
    ) {}
}
