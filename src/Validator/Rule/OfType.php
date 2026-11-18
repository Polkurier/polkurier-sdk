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
        public readonly string $type,
    ) {}

    /**
     * @param mixed $value
     *
     * @return RuleFailure[]
     */
    public function validate(mixed $value): array
    {
        if ($value === null) {
            return [];
        }

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

        $separator = \strrpos($this->type, '\\');
        $name = $separator === false ? $this->type : \substr($this->type, $separator + 1);

        return [
            new RuleFailure(
                ValidationErrorCodeEnum::WrongType,
                // The type names the rule takes are `int`, `Address`, `PackItem` and the like, so
                // an article in front of one would be right for some and wrong for the rest.
                \sprintf('must be of type %s', $name),
                ['type' => $name, 'declared' => $this->type],
            ),
        ];
    }
}
