<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Serializer;

/**
 * Reads the properties and methods a class declares, its parents' included.
 *
 * One walk for everything that asks what a class is made of: which members carry an attribute,
 * and which of them can put a key in the payload. Reflection costs more than the answer is worth
 * on every request, and a class does not change while the process runs, so each is walked once
 * and kept.
 */
final class ClassMemberReader
{
    /**
     * The prefixes the object normalizer reads a method as a getter by, matched only where an
     * uppercase letter follows, so a word that starts with one is left whole.
     */
    private const ACCESSOR_PREFIX = '/^(get|is|has|can)(?=[A-Z])/';

    /**
     * The members each class declares, read once.
     *
     * @var array<string, list<\ReflectionProperty|\ReflectionMethod>>
     */
    private static array $members = [];

    /**
     * Every declaration of every property and method the class and its parents make, the class
     * before its parents.
     *
     * A member redeclared in a subclass is listed twice, once per declaration, because an
     * attribute the subclass does not repeat still sits on the parent's copy and still governs
     * what is sent. Callers that want one entry per field collapse by wire name, which is the
     * name the payload is keyed by; callers that want the most derived declaration take the first.
     *
     * A type that is not a class has no members, which is the answer a caller holding a type it
     * has not checked needs.
     *
     * @param string $class
     *
     * @return list<\ReflectionProperty|\ReflectionMethod>
     */
    public static function readAll(string $class): array
    {
        if (isset(self::$members[$class])) {
            return self::$members[$class];
        }
        if (!\class_exists($class)) {
            return self::$members[$class] = [];
        }

        $members = [];
        // Guarded above, so this cannot fail.
        for ($current = new \ReflectionClass($class); $current !== false; $current = $current->getParentClass()) {
            foreach ([...$current->getProperties(), ...$current->getMethods()] as $member) {
                if ($member->getDeclaringClass()->getName() === $current->getName()) {
                    $members[] = $member;
                }
            }
        }

        return self::$members[$class] = $members;
    }

    /**
     * The members the class marks with an attribute.
     *
     * @param string $class
     * @param class-string $attributeClass
     *
     * @return list<\ReflectionProperty|\ReflectionMethod>
     */
    public static function readMarkedWith(string $class, string $attributeClass): array
    {
        $marked = [];
        foreach (self::readAll($class) as $member) {
            if ($member->getAttributes($attributeClass) !== []) {
                $marked[] = $member;
            }
        }

        return $marked;
    }

    /**
     * Whether a member without a serializer name of its own can put a key in the payload: a
     * property, or a method the object normalizer reads as a getter.
     *
     * @param \ReflectionProperty|\ReflectionMethod $member
     *
     * @return bool
     */
    public static function writesAField(\ReflectionProperty|\ReflectionMethod $member): bool
    {
        return !$member instanceof \ReflectionMethod
            || \preg_match(self::ACCESSOR_PREFIX, $member->getName()) === 1;
    }

    /**
     * The name the object normalizer knows a member by: a property's own name, an accessor's name
     * without its prefix.
     *
     * This is the name an attribute list is matched against, and the name a wire name is derived
     * from where no serializer name is declared.
     *
     * @param \ReflectionProperty|\ReflectionMethod $member
     *
     * @return string
     */
    public static function readName(\ReflectionProperty|\ReflectionMethod $member): string
    {
        if (!$member instanceof \ReflectionMethod) {
            return $member->getName();
        }

        return \lcfirst((string)\preg_replace(self::ACCESSOR_PREFIX, '', $member->getName()));
    }
}
