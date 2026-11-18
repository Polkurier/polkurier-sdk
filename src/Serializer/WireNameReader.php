<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Serializer;

/**
 * Reads the names a class writes its fields under, for the code that works on the payload's keys
 * and so cannot ask the name converter: one member's name with {@see self::read()}, a whole
 * class's with {@see self::readNames()}.
 *
 * The attribute is matched by its short name and its argument read positionally or by name,
 * because its namespace and its accessors differ across Symfony versions.
 */
final class WireNameReader
{
    /**
     * The names each class writes, read once.
     *
     * @var array<string, string[]>
     */
    private static array $names = [];

    /**
     * The name a property or method is written under.
     *
     * @param \ReflectionProperty|\ReflectionMethod $member
     *
     * @return string
     */
    public static function read(\ReflectionProperty|\ReflectionMethod $member): string
    {
        $declared = self::readDeclared($member);
        if ($declared !== null) {
            return $declared;
        }

        return ClassMemberReader::readName($member);
    }

    /**
     * Every name a class writes, its parents' included.
     *
     * A member writes a field when it carries a serializer name, or when the object normalizer
     * reads it: any property, and a method only if it is a getter. A setter or a helper is left
     * out, since nothing it is called puts a key in the payload.
     *
     * @param string $class
     *
     * @return string[]
     */
    public static function readNames(string $class): array
    {
        if (isset(self::$names[$class])) {
            return self::$names[$class];
        }

        $names = [];
        foreach (ClassMemberReader::readAll($class) as $member) {
            if (self::readDeclared($member) !== null || ClassMemberReader::writesAField($member)) {
                $names[] = self::read($member);
            }
        }

        return self::$names[$class] = \array_values(\array_unique($names));
    }

    /**
     * The name a serializer attribute gives a property or method, if one does.
     *
     * @param \ReflectionProperty|\ReflectionMethod $member
     *
     * @return string|null
     */
    public static function readDeclared(\ReflectionProperty|\ReflectionMethod $member): ?string
    {
        foreach ($member->getAttributes() as $attribute) {
            if (!\str_ends_with($attribute->getName(), '\\SerializedName')) {
                continue;
            }
            $arguments = $attribute->getArguments();
            $candidate = $arguments[0] ?? $arguments['serializedName'] ?? null;
            if (\is_string($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}
