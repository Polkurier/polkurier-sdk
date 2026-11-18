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
 * A number above zero.
 *
 * Used for parcel dimensions, weights and quantities, which the API requires to be positive.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class Positive implements ValueValidatorInterface
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
        if ($value <= 0) {
            return [
                new RuleFailure(
                    ValidationErrorCodeEnum::OutOfRange,
                    'must be greater than zero',
                    ['minimum' => 0, 'exclusiveMinimum' => true],
                ),
            ];
        }

        return [];
    }
}
