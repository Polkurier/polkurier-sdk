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
 * A number that may be zero but not below it.
 *
 * Unlike {@see \Polkurier\Webservice\Validator\Rule\Positive}, this allows zero, which has a
 * meaning in some fields: an insurance value of zero means no insurance, and a cash-on-delivery
 * amount of zero means no collection.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class NonNegative implements ValueValidatorInterface
{
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
        if (!\is_int($value) && !\is_float($value)) {
            return [new RuleFailure(ValidationErrorCodeEnum::NotANumber, 'must be a number')];
        }
        if ($value < 0) {
            return [
                new RuleFailure(
                    ValidationErrorCodeEnum::OutOfRange,
                    'must not be negative',
                    ['minimum' => 0],
                ),
            ];
        }

        return [];
    }
}
