<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Request\Order;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;

/**
 * Request for a single courier pickup covering several shipments.
 *
 * All three scheduling fields are mandatory — unlike a per-order pickup, where the carrier decides
 * what it needs. The API rejects a missing or unparseable date or time outright.
 */
#[Validate\InOrder(
    earlier: 'pickupTimeFrom',
    later: 'pickupTimeTo',
    because: 'must not be later than the end of the collection window',
)]
readonly class MultiPickupRequestMessage implements MessageInterface
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
     * @param \DateTimeInterface $pickupTimeFrom start of the window; only the time is sent
     * @param \DateTimeInterface $pickupTimeTo end of the window; only the time is sent
     * @param string[] $orders waybill numbers to collect; duplicates and empty entries are
     *                         discarded by the API, and an effectively empty list is an error
     *
     * @throws InvalidArgumentException when $orders is empty or holds non-strings
     */
    public function __construct(
        /**
         * @var \DateTimeInterface
         */
        #[Serializer\SerializedName(self::FIELD_PICKUP_DATE)]
        #[Serializer\Context([DateTimeNormalizer::FORMAT_KEY => 'Y-m-d'])]
        private \DateTimeInterface $pickupDate,

        /**
         * @var \DateTimeInterface
         */
        #[Serializer\SerializedName(self::FIELD_PICKUP_TIME_FROM)]
        #[Serializer\Context([DateTimeNormalizer::FORMAT_KEY => 'H:i'])]
        private \DateTimeInterface $pickupTimeFrom,

        /**
         * @var \DateTimeInterface
         */
        #[Serializer\SerializedName(self::FIELD_PICKUP_TIME_TO)]
        #[Serializer\Context([DateTimeNormalizer::FORMAT_KEY => 'H:i'])]
        private \DateTimeInterface $pickupTimeTo,

        /**
         * @var string[]
         */
        #[Serializer\SerializedName(self::FIELD_ORDERS)]
        #[Validate\Each(Validate\NotBlank::class)]
        #[Validate\NotEmptyList]
        private array $orders,
    ) {
        $this->validateOrders();
    }

    /**
     * @return \DateTimeInterface
     */
    public function getPickupDate(): \DateTimeInterface
    {
        return $this->pickupDate;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getPickupTimeFrom(): \DateTimeInterface
    {
        return $this->pickupTimeFrom;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getPickupTimeTo(): \DateTimeInterface
    {
        return $this->pickupTimeTo;
    }

    /**
     * @return string[]
     */
    public function getOrders(): array
    {
        return $this->orders;
    }

    /**
     * @return void
     * @throws InvalidArgumentException
     */
    private function validateOrders(): void
    {
        if (\array_filter($this->orders, static fn(mixed $o): bool => $o !== '' && $o !== null) === []) {
            throw new InvalidArgumentException(\sprintf(
                '"%s" field requires at least one waybill number.',
                self::FIELD_ORDERS
            ));
        }

        foreach ($this->orders as $order) {
            if (!\is_string($order)) {
                throw new InvalidArgumentException(\sprintf(
                    '"%s" field may only contain strings, %s provided.',
                    self::FIELD_ORDERS,
                    \get_debug_type($order)
                ));
            }
        }
    }
}
