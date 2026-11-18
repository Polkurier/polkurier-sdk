<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Support;

/**
 * What counts as a readable accessor, and a walk that calls every one of them.
 *
 * The SDK reads a field through a getter named for it, so a test that proves an object is usable
 * calls them all: a value of the wrong type raises where it is read, not where it was built.
 */
final class Accessors
{
    /**
     * The prefixes the serializer writes a field through, and so the ones a test reads it back by.
     */
    public const string PATTERN = '/^(get|is|has)/';

    /**
     * Reads every public property and calls every accessor of the value and of everything it
     * holds, and returns how many it read.
     *
     * A response class exposes public readonly properties and an entity exposes getters, so both
     * are walked: counting one alone reports nothing read for half the SDK.
     *
     * @param mixed $value
     * @param int $depth
     *
     * @return int
     */
    public static function readAll(mixed $value, int $depth = 0): int
    {
        if ($depth >= 4) {
            return 0;
        }
        if (\is_array($value)) {
            $read = 0;
            foreach ($value as $item) {
                $read += self::readAll($item, $depth + 1);
            }

            return $read;
        }
        if (!\is_object($value) || $value instanceof \DateTimeInterface || $value instanceof \UnitEnum) {
            return 0;
        }

        $read = 0;
        $reflection = new \ReflectionClass($value);
        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->isStatic()) {
                continue;
            }
            ++$read;
            $read += self::readAll($property->getValue($value), $depth + 1);
        }
        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->isStatic() || $method->getNumberOfRequiredParameters() > 0
                || !\preg_match(self::PATTERN, $method->getName())
            ) {
                continue;
            }
            ++$read;
            $read += self::readAll($method->invoke($value), $depth + 1);
        }

        return $read;
    }
}
