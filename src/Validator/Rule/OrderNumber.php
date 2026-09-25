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
 * An order number.
 *
 * Uses the same pattern as the API. A number that fails here would be rejected by the API as
 * malformed.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class OrderNumber implements ValueValidatorInterface
{
    private const string PATTERN = '/\d+-\d+/';

    /**
     * @param mixed $value
     *
     * @return string[]
     */
    public function validate(mixed $value): array
    {
        // Empty values pass. NotBlank handles required fields. Rejecting them here would make
        // every optional field of this kind mandatory.
        if ($value === null || $value === '') {
            return [];
        }

        if (!\is_string($value) || \preg_match(self::PATTERN, $value) !== 1) {
            return ['is not a valid order number'];
        }

        return [];
    }
}
