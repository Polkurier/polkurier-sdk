<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\Courier;

use Polkurier\Webservice\Api\MessageInterface;
use Symfony\Component\Serializer\Attribute as Serializer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;

/**
 * One day a carrier can collect on, with the windows available that day.
 */
readonly class PickupTimeMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_PICKUP_DATE = 'pickupdate';
    public const string FIELD_TIME = 'time';
    /**-@#*/

    /**
     * @param \DateTimeInterface $pickupDate
     * @param TimeIntervalMessage[] $time
     */
    public function __construct(
        /**
         * @var \DateTimeInterface
         */
        #[Serializer\SerializedName(self::FIELD_PICKUP_DATE)]
        #[Serializer\Context([DateTimeNormalizer::FORMAT_KEY => 'Y-m-d'])]
        private \DateTimeInterface $pickupDate,

        /**
         * @var TimeIntervalMessage[]
         */
        #[Serializer\SerializedName(self::FIELD_TIME)]
        private array $time = [],
    ) {}

    /**
     * @return \DateTimeInterface the day, at midnight
     */
    public function getPickupDate(): \DateTimeInterface
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
