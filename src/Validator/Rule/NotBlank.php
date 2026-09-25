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
 * Kept apart from the format rules. Whether a field is required depends on the message, so the same
 * format rule can be used on an optional field in one message and a required one in another.
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
