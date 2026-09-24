<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Validator\Rule;

use Attribute;
use Polkurier\Webservice\Api\Validation\ValueValidatorInterface;

/**
 * A whole number within bounds.
 *
 * Mostly paging, where asking for more than the documented maximum is not reported as an error —
 * you simply get fewer results than you asked for, which reads as missing data rather than as a
 * request that was never going to be honoured in full. Catching it here makes it a rejection.
 *
 * Null passes: whether the field is required is {@see NotBlank}'s business.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class IntegerRange implements ValueValidatorInterface
{
    /**
     * @param int $min
     * @param int $max
     */
    public function __construct(
        /**
         * @var int
         */
        public readonly int $min,

        /**
         * @var int
         */
        public readonly int $max,
    ) {}

    /**
     * @param mixed $value
     *
     * @return string[]
     */
    public function validate(mixed $value): array
    {
        if ($value === null) {
            return [];
        }
        if (!\is_int($value)) {
            return ['must be a whole number'];
        }
        if ($value < $this->min || $value > $this->max) {
            return [\sprintf('must be between %d and %d', $this->min, $this->max)];
        }

        return [];
    }
}
