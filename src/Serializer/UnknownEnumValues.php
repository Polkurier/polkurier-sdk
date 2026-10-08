<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Serializer;

/**
 * Collects the enum values a response had that the SDK doesn't know.
 *
 * Put an instance in the denormalization context under {@see self::CONTEXT_KEY}. Enum fields are
 * then read leniently: an unknown value becomes null, or is left out of a list, and is recorded
 * here. Without it in the context, an unknown value fails as usual.
 *
 * An empty string isn't recorded. It reads as null like any other unknown value.
 */
class UnknownEnumValues
{
    public const string CONTEXT_KEY = 'polkurier_unknown_enum_values';

    /**
     * @var list<UnknownEnumValue>
     */
    private array $values = [];

    /**
     * @param class-string<\BackedEnum> $enum
     * @param mixed $value
     * @param string|null $path
     *
     * @return void
     */
    public function add(string $enum, mixed $value, ?string $path): void
    {
        if ($value === '') {
            return;
        }

        $this->values[] = new UnknownEnumValue($enum, $value, $path);
    }

    /**
     * @return list<UnknownEnumValue>
     */
    public function all(): array
    {
        return $this->values;
    }

    /**
     * The collector in the context, if there is one.
     *
     * @param array<string, mixed> $context
     *
     * @return self|null
     */
    public static function fromContext(array $context): ?self
    {
        $collector = $context[self::CONTEXT_KEY] ?? null;

        return $collector instanceof self ? $collector : null;
    }
}
