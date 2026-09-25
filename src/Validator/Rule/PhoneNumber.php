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
 * A telephone number.
 *
 * Only the length is checked: at least 9 digits, not counting other characters. No numbering plan
 * is enforced because foreign numbers are accepted.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class PhoneNumber implements ValueValidatorInterface
{
    private const int MIN_DIGITS = 9;

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

        if (!\is_string($value)) {
            return ['must be a telephone number'];
        }

        $digits = \preg_replace('/\D/', '', $value) ?? '';

        if ($digits === '') {
            return ['must not be empty'];
        }
        if (\strlen($digits) < self::MIN_DIGITS) {
            return ['is too short to be a telephone number'];
        }

        return [];
    }
}
