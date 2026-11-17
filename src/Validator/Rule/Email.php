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
 * An email address.
 *
 * The check is loose. The address is used for delivery notifications, so it only catches values
 * that are clearly not an address, like a name, a phone number or a value with a space in it. A
 * stricter check would reject working addresses and block the order.
 *
 * Empty values pass. Use {@see \Polkurier\Webservice\Validator\Rule\NotBlank} to make the field
 * required.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class Email implements ValueValidatorInterface
{
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
        if (!\is_string($value)) {
            return [new RuleFailure(ValidationErrorCodeEnum::NotAnEmail, 'must be an email address')];
        }
        if (\preg_match('/^[^@\s]+@[^@\s]+\.[^@\s]+$/', \trim($value)) !== 1) {
            return [new RuleFailure(ValidationErrorCodeEnum::NotAnEmail, 'must be an email address')];
        }

        return [];
    }
}
