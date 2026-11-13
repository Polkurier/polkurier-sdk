<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response\System;

use Polkurier\Webservice\Api\MessageInterface;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Limits the API applies to coupon codes.
 */
final readonly class CouponLimitsMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_MIN_LENGTH = 'min_length';
    /**-@#*/

    /**
     * @param int $minLength shortest accepted coupon code
     */
    public function __construct(
        /**
         * @var int
         */
        #[Serializer\SerializedName(self::FIELD_MIN_LENGTH)]
        private int $minLength = 0,
    ) {}

    /**
     * @return int
     */
    public function getMinLength(): int
    {
        return $this->minLength;
    }
}
