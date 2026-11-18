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
 * An order number: digits, a hyphen, digits, and nothing else (e.g. `12345-678`).
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class OrderNumber implements ValueValidatorInterface
{
    private const string PATTERN = '/^\d+-\d+$/';

    /**
     * @param mixed $value
     *
     * @return RuleFailure[]
     */
    public function validate(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (!\is_string($value) || \preg_match(self::PATTERN, $value) !== 1) {
            return [new RuleFailure(ValidationErrorCodeEnum::NotAnOrderNumber, 'is not a valid order number')];
        }

        return [];
    }
}
