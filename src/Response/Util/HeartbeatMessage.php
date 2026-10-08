<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\Util;

use Polkurier\Webservice\Api\MessageInterface;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * The server clock, as reported by an availability check.
 *
 * It shows clock skew between your host and the API, which matters when a pickup is scheduled.
 */
readonly class HeartbeatMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_TIME = 'time';
    /**-@#*/

    /**
     * @param \DateTimeInterface $time
     */
    public function __construct(
        /**
         * @var \DateTimeInterface
         */
        #[Serializer\SerializedName(self::FIELD_TIME)]
        private \DateTimeInterface $time,
    ) {}

    /**
     * @return \DateTimeInterface
     */
    public function getTime(): \DateTimeInterface
    {
        return $this->time;
    }
}
