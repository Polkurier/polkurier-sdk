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
 * An email address.
 *
 * Deliberately permissive. The address is used for delivery notifications rather than for anything
 * the SDK sends itself, so the only failure worth catching is something that plainly is not an
 * address — a name, a phone number, a value with a space in it. Anything stricter starts rejecting
 * addresses that work, and the cost of a false rejection is an order that cannot be placed.
 *
 * Absence is {@see NotBlank}'s business, as with the other format rules.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class Email implements ValueValidatorInterface
{
    /**
     * @param mixed $value
     *
     * @return string[]
     */
    public function validate(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }
        if (!\is_string($value)) {
            return ['must be an email address'];
        }
        if (\preg_match('/^[^@\s]+@[^@\s]+\.[^@\s]+$/', \trim($value)) !== 1) {
            return ['must be an email address'];
        }

        return [];
    }
}
