<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response\Courier;

use Polkurier\Webservice\Api\MessageInterface;
use Symfony\Component\Serializer\Attribute as Serializer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;

/**
 * One day a carrier can collect on, with the windows available that day.
 *
 * @api
 */
final readonly class PickupTimeMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_PICKUP_DATE = 'pickupdate';
    public const string FIELD_TIME = 'time';
    /**-@#*/

    /**
     * @param \DateTimeInterface|null $pickupDate
     * @param TimeIntervalMessage[] $time
     */
    public function __construct(
        /**
         * @var \DateTimeInterface|null the day, at midnight
         */
        #[Serializer\SerializedName(self::FIELD_PICKUP_DATE)]
        #[Serializer\Context([DateTimeNormalizer::FORMAT_KEY => 'Y-m-d'])]
        public ?\DateTimeInterface $pickupDate,

        /**
         * @var TimeIntervalMessage[]
         */
        #[Serializer\SerializedName(self::FIELD_TIME)]
        public array $time = [],
    ) {}
}
