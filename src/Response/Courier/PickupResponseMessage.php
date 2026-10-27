<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\Courier;

use Polkurier\Webservice\Api\MessageInterface;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Whether a carrier can collect on the requested day, and the windows it offers.
 *
 * Carriers that do not report time windows return an empty list even when they accept the date. An
 * empty list is not a refusal.
 */
readonly class PickupResponseMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_PICKUP_DATE = 'pickupdate';
    public const string FIELD_TIME = 'time';
    /**-@#*/

    /**
     * @param bool $pickupDate whether the carrier can collect on the requested date
     * @param TimeIntervalMessage[] $time pickup windows, empty for couriers that do not
     *                                             report them
     */
    public function __construct(
        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_PICKUP_DATE)]
        private bool $pickupDate = false,

        /**
         * @var TimeIntervalMessage[]
         */
        #[Serializer\SerializedName(self::FIELD_TIME)]
        private array $time = [],
    ) {}

    /**
     * @return bool
     */
    public function isPickupDate(): bool
    {
        return $this->pickupDate;
    }

    /**
     * @return TimeIntervalMessage[]
     */
    public function getTime(): array
    {
        return $this->time;
    }
}
