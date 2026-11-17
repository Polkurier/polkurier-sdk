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
 * A collection window.
 *
 * The API sends wall-clock times ("H:i") without a date, so these values read as that time on
 * 1 January 1970. The date comes from the message that owns the window.
 *
 * @api
 */
final readonly class TimeIntervalMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_TIME_FROM = 'timefrom';
    public const string FIELD_TIME_TO = 'timeto';
    /**-@#*/

    /**
     * @param \DateTimeInterface|null $timeFrom
     * @param \DateTimeInterface|null $timeTo
     */
    public function __construct(
        /**
         * @var \DateTimeInterface|null the time of day, on 1 January 1970
         */
        #[Serializer\SerializedName(self::FIELD_TIME_FROM)]
        #[Serializer\Context([DateTimeNormalizer::FORMAT_KEY => 'H:i'])]
        public ?\DateTimeInterface $timeFrom,

        /**
         * @var \DateTimeInterface|null the time of day, on 1 January 1970
         */
        #[Serializer\SerializedName(self::FIELD_TIME_TO)]
        #[Serializer\Context([DateTimeNormalizer::FORMAT_KEY => 'H:i'])]
        public ?\DateTimeInterface $timeTo,
    ) {}
}
