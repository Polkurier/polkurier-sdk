<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Serializer;

/**
 * Reads the name a property or method is written under, for the code that works on the payload's
 * keys and so cannot ask the name converter.
 *
 * The attribute is matched by its short name and its argument read positionally or by name,
 * because its namespace and its accessors differ across Symfony versions.
 */
final class WireNameReader
{
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

        $name = $member->getName();
        if ($member instanceof \ReflectionMethod) {
            $name = \lcfirst((string)\preg_replace('/^(get|is|has|can)(?=[A-Z])/', '', $name));
        }

        return $name;
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
