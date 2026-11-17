<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Validator\Rule;

use Attribute;
use Polkurier\Webservice\Enum\Validator\ValidationErrorCodeEnum;
use Polkurier\Webservice\Validator\RuleFailure;

/**
 * A whole number within bounds.
 *
 * Mostly used for paging, to keep a page size within the documented maximum.
 *
 * Null passes. Use {@see \Polkurier\Webservice\Validator\Rule\NotBlank} to make the field required.
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
     * @return RuleFailure[]
     */
    public function validate(mixed $value): array
    {
        if ($value === null) {
            return [];
        }
        if (!\is_int($value)) {
            return [new RuleFailure(ValidationErrorCodeEnum::NotAWholeNumber, 'must be a whole number')];
        }
        if ($value < $this->min || $value > $this->max) {
            // An open-ended maximum states a bound no caller can read: PHP_INT_MAX is the absence
            // of an upper limit, not a limit.
            if ($this->max === \PHP_INT_MAX) {
                return [
                    new RuleFailure(
                        ValidationErrorCodeEnum::OutOfRange,
                        \sprintf('must be at least %d', $this->min),
                        ['minimum' => $this->min],
                    ),
                ];
            }

            return [
                new RuleFailure(
                    ValidationErrorCodeEnum::OutOfRange,
                    \sprintf('must be between %d and %d', $this->min, $this->max),
                    ['minimum' => $this->min, 'maximum' => $this->max],
                ),
            ];
        }

        return [];
    }
}
