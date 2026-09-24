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
 * Only length is judged, and only after non-digits are stripped, because the API does the same. No
 * numbering plan is enforced: the service accepts foreign numbers, and a stricter rule here would
 * reject recipients it would happily deliver to.
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
        // Absence is not this rule's business; that is what NotBlank is for. Saying so here would
        // make every optional field of this kind mandatory.
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
