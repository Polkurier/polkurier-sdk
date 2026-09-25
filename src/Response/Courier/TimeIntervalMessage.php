<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\Courier;

use Symfony\Component\Serializer\Attribute as Serializer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;

/**
 * A collection window.
 *
 * The API sends wall-clock times without a date, so only the time part of these values means
 * anything. The date comes from the message that owns the window.
 */
readonly class TimeIntervalMessage
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_TIME_FROM = 'timefrom';
    public const string FIELD_TIME_TO = 'timeto';
    /**-@#*/

    /**
     * Pickup windows arrive as wall-clock times ("H:i") with no date, so ignore the date part of these
     * objects. The owning message carries the pickup date.
     *
     * @param \DateTimeInterface $timeFrom
     * @param \DateTimeInterface $timeTo
     */
    public function __construct(
        /**
         * @var \DateTimeInterface
         */
        #[Serializer\SerializedName(self::FIELD_TIME_FROM)]
        #[Serializer\Context([DateTimeNormalizer::FORMAT_KEY => 'H:i'])]
        private \DateTimeInterface $timeFrom,

        /**
         * @var \DateTimeInterface
         */
        #[Serializer\SerializedName(self::FIELD_TIME_TO)]
        #[Serializer\Context([DateTimeNormalizer::FORMAT_KEY => 'H:i'])]
        private \DateTimeInterface $timeTo,
    ) {}

    /**
     * @return \DateTimeInterface
     */
    public function getTimeFrom(): \DateTimeInterface
    {
        return $this->timeFrom;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getTimeTo(): \DateTimeInterface
    {
        return $this->timeTo;
    }
}
