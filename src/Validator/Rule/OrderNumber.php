<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Validator\Rule;

use Attribute;
use Polkurier\Webservice\Api\Validation\ValueValidatorInterface;

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
     * @return string[]
     */
    public function validate(mixed $value): array
    {
        // Empty values pass. {@see \Polkurier\Webservice\Validator\Rule\NotBlank} handles required
        // fields. Rejecting them here would make every optional field of this kind mandatory.
        if ($value === null || $value === '') {
            return [];
        }

        if (!\is_string($value) || \preg_match(self::PATTERN, $value) !== 1) {
            return ['is not a valid order number'];
        }

        return [];
    }
}
