<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Validator\Rule;

use Attribute;

/**
 * A list with at least one non-blank entry.
 *
 * Blank entries don't count, so a list of empty strings fails.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class NotEmptyList implements ValueValidatorInterface
{
    /**
     * @param mixed $value
     *
     * @return string[]
     */
    public function validate(mixed $value): array
    {
        if (!\is_array($value)) {
            return ['must be a list'];
        }

        $usable = \array_filter(
            $value,
            static fn(mixed $item): bool => $item !== null && $item !== '' && $item !== [],
        );

        return $usable === [] ? ['must contain at least one entry'] : [];
    }
}
