<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Validator\Rule;

use Attribute;

/**
 * A telephone number.
 *
 * Only the length is checked: at least 4 digits, not counting other characters. That is the
 * shortest national number E.164 allows, so no number the API accepts is refused here. A Polish
 * number is 9 digits and a foreign one may be fewer, so a plan-specific length would reject a
 * recipient the carrier delivers to.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class PhoneNumber implements ValueValidatorInterface
{
    private const int MIN_DIGITS = 4;

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
