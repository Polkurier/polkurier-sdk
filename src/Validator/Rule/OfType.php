<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Validator\Rule;

use Attribute;

/**
 * A value of the given type: `string`, `int`, `float` or `bool`, or a class, interface or enum.
 *
 * Mostly for list entries, whose type PHP can't declare:
 *
 * ```php
 * #[Each(OfType::class, PackItem::class)]
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class OfType implements ValueValidatorInterface
{
    /**
     * @param string $type a scalar type name, or a class, interface or enum name
     */
    public function __construct(
        /**
         * @var string
         */
        public readonly string $type,
    ) {}

    /**
     * @param mixed $value
     *
     * @return string[]
     */
    public function validate(mixed $value): array
    {
        $matches = match ($this->type) {
            'string' => \is_string($value),
            'int' => \is_int($value),
            'float' => \is_float($value) || \is_int($value),
            'bool' => \is_bool($value),
            default => $value instanceof $this->type,
        };

        if ($matches) {
            return [];
        }

        $name = \str_contains($this->type, '\\') ? \substr((string)\strrchr($this->type, '\\'), 1) : $this->type;

        return [\sprintf('must be a %s', $name)];
    }
}
