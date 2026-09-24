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
 * A list holding at least one usable entry.
 *
 * Blank entries do not count. Several endpoints strip them before deciding whether anything was sent,
 * so a list of empty strings is rejected there as an empty list and is treated the same way here.
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
