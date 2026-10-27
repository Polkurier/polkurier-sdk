<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\Courier;

use Symfony\Component\Serializer\Attribute as Serializer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;

/**
 * A collection window.
 *
 * The API sends wall-clock times ("H:i") without a date, so these values read as that time on
 * 1 January 1970. The date comes from the message that owns the window.
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
     * @return \DateTimeInterface the time of day, on 1 January 1970
     */
    public function getTimeFrom(): \DateTimeInterface
    {
        return $this->timeFrom;
    }

    /**
     * @return \DateTimeInterface the time of day, on 1 January 1970
     */
    public function getTimeTo(): \DateTimeInterface
    {
        return $this->timeTo;
    }
}
