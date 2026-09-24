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
 * The field must be present and not just whitespace.
 *
 * Deliberately separate from the format rules: whether a field is required is a property of the
 * message, not of the value, and keeping the two apart is what lets the same format rule be optional
 * in one message and mandatory in another.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class NotBlank implements ValueValidatorInterface
{
    /**
     * @param mixed $value
     *
     * @return string[]
     */
    public function validate(mixed $value): array
    {
        if ($value === null) {
            return ['must not be empty'];
        }
        if (\is_string($value) && \trim($value) === '') {
            return ['must not be empty'];
        }
        if (\is_array($value) && $value === []) {
            return ['must not be empty'];
        }

        return [];
    }
}
