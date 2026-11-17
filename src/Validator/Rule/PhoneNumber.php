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
 * A phone number.
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
     * @return RuleFailure[]
     */
    public function validate(mixed $value): array
    {
        // Empty values pass. {@see \Polkurier\Webservice\Validator\Rule\NotBlank} handles required
        // fields. Rejecting them here would make every optional field of this kind mandatory.
        if ($value === null || $value === '') {
            return [];
        }

        if (!\is_string($value)) {
            return [new RuleFailure(ValidationErrorCodeEnum::NotAPhoneNumber, 'must be a phone number')];
        }

        $digits = \preg_replace('/\D/', '', $value) ?? '';

        // A value with no digits in it is not an empty field, so it is not {@see NotBlank}'s fault
        // to report: the customer typed something and it is not a phone number.
        if ($digits === '') {
            return [new RuleFailure(ValidationErrorCodeEnum::NotAPhoneNumber, 'must be a phone number')];
        }
        if (\strlen($digits) < self::MIN_DIGITS) {
            return [
                new RuleFailure(
                    ValidationErrorCodeEnum::PhoneNumberTooShort,
                    'is too short to be a phone number',
                    ['minimum' => self::MIN_DIGITS],
                ),
            ];
        }

        return [];
    }
}
