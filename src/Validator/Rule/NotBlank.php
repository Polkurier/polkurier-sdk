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
 * The field must be present and not just whitespace.
 *
 * Kept apart from the format rules. Whether a field is required depends on the message, so the same
 * format rule can be used on an optional field in one message and a required one in another.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class NotBlank implements ValueValidatorInterface
{
    /**
     * @param mixed $value
     *
     * @return RuleFailure[]
     */
    public function validate(mixed $value): array
    {
        if ($value === null) {
            return [new RuleFailure(ValidationErrorCodeEnum::Blank, 'must not be empty')];
        }
        if (\is_string($value) && \trim($value) === '') {
            return [new RuleFailure(ValidationErrorCodeEnum::Blank, 'must not be empty')];
        }
        if (\is_array($value) && $value === []) {
            return [new RuleFailure(ValidationErrorCodeEnum::Blank, 'must not be empty')];
        }

        return [];
    }
}
