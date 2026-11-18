<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Validator;

use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Serializer\Attribute\InlineFields;
use Polkurier\Webservice\Serializer\ClassMemberReader;
use Polkurier\Webservice\Serializer\WireNameReader;

/**
 * Reads a message's properties and their wire names through reflection.
 *
 * Validation reports use wire field names, so they match the API's own reports and the request
 * that would have been sent. The names are read from the serializer attribute on each property,
 * so a renamed field stays in step with its rules. The fields of an object marked
 * {@see InlineFields} are sent at the enclosing level, so they are read as the message's own.
 *
 * Reflection runs once per class and is then reused. The cache is keyed by the message class
 * alone, so resolving a property, its getter, its wire name and the record inlining it is this
 * class's own and private: a subclass changes what {@see self::readValue()} and
 * {@see self::readField()} do with them, not how they are found.
 *
 * An inlined record is read through another instance of this class, built from the class of the
 * object holding it, so a subclass reads an inlined record with itself.
 *
 * @phpstan-consistent-constructor
 */
class MessageFieldReader
{
    /**
     * Property name to wire field name, per message class.
     *
     * @var array<class-string, array<string, string>>
     */
    protected static array $fieldNames = [];

    /**
     * Resolved properties, per class, including those declared on parents.
     *
     * @var array<class-string, array<string, \ReflectionProperty|null>>
     */
    protected static array $properties = [];

    /**
     * The method each property is read through, per class, null when it has none.
     *
     * @var array<class-string, array<string, \ReflectionMethod|null>>
     */
    protected static array $getters = [];

    /**
     * Properties marked {@see InlineFields}, per class.
     *
     * @var array<class-string, list<\ReflectionProperty>>
     */
    protected static array $inlined = [];

    /**
     * @param object $message
     */
    public function __construct(
        protected readonly object $message,
    ) {}

    /**
     * Returns the value of one property, whatever its visibility and wherever it is declared.
     *
     * Walks up the class hierarchy, because a private property declared on a parent is not
     * visible through the child's reflection.
     *
     * @param string $property
     *
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function readValue(string $property): mixed
    {
        $inlined = $this->resolveInlinedHolder($property);
        if ($inlined !== null) {
            return $inlined->readValue($property);
        }

        $reflectionProperty = self::findProperty($this->message::class, $property);

        // A property that was never set has no value to check, whichever way it would be read.
        if ($reflectionProperty !== null && !$reflectionProperty->isInitialized($this->message)) {
            return null;
        }

        // What a getter returns is what is sent, so that is what the rules check.
        $getter = self::findGetter($this->message::class, $property);
        if ($getter !== null) {
            return $getter->invoke($this->message);
        }

        if ($reflectionProperty === null) {
            return null;
        }

        return $reflectionProperty->getValue($this->message);
    }

    /**
     * Returns the wire field name a property is sent under.
     *
     * Falls back to the property name if no serializer attribute declares one. That only happens
     * for properties that are never sent.
     *
     * @param string $property
     *
     * @return string
     * @throws InvalidArgumentException
     */
    public function readField(string $property): string
    {
        return $this->resolveInlinedHolder($property)?->readField($property)
            ?? self::readFieldNames($this->message::class)[$property]
            ?? $property;
    }

    /**
     * Whether a class declares a property, or inlines a record that declares it.
     *
     * A name that resolves here is a name {@see self::readValue()} reads a value for, so a rule
     * naming a property can be told from a rule naming nothing before either is run.
     *
     * @param class-string $class
     * @param string $property
     *
     * @return bool
     * @throws InvalidArgumentException when the class does not exist
     */
    public static function providesProperty(string $class, string $property): bool
    {
        $pending = [$class];
        $seen = [];

        while ($pending !== []) {
            $current = \array_pop($pending);
            // Records that inline each other would otherwise be walked without end.
            if (isset($seen[$current])) {
                continue;
            }
            $seen[$current] = true;

            if (self::findProperty($current, $property) !== null) {
                return true;
            }

            foreach (self::findInlinedProperties($current) as $inlined) {
                $pending = [...$pending, ...self::readDeclaredClasses($inlined)];
            }
        }

        return false;
    }

    /**
     * The classes a property's declared type admits, interfaces among them.
     *
     * A type that is not one of them, a map of values the payload carries flat among them, has no
     * declarations to read.
     *
     * @param \ReflectionProperty $property
     *
     * @return list<class-string>
     */
    private static function readDeclaredClasses(\ReflectionProperty $property): array
    {
        $type = $property->getType();
        $parts = match (true) {
            $type instanceof \ReflectionUnionType, $type instanceof \ReflectionIntersectionType => $type->getTypes(),
            $type !== null => [$type],
            default => [],
        };

        $classes = [];
        foreach ($parts as $part) {
            // A part of a union or an intersection is named, or is itself an intersection, whose
            // written form `A&B` names no class.
            if (!$part instanceof \ReflectionNamedType) {
                continue;
            }

            $name = $part->getName();
            if (\class_exists($name) || \interface_exists($name)) {
                $classes[] = $name;
            }
        }

        return $classes;
    }

    /**
     * The fields of the inlined object that holds a property the message itself doesn't declare.
     *
     * @param string $property
     *
     * @return self|null null when the message declares the property or no inlined object holds it
     * @throws InvalidArgumentException
     */
    protected function resolveInlinedHolder(string $property): ?self
    {
        if (self::findProperty($this->message::class, $property) !== null) {
            return null;
        }

        foreach (self::findInlinedProperties($this->message::class) as $inlined) {
            if (!$inlined->isInitialized($this->message)) {
                continue;
            }
            $object = $inlined->getValue($this->message);
            if (\is_object($object) && self::findProperty($object::class, $property) !== null) {
                return new static($object);
            }
        }

        return null;
    }

    /**
     * The properties holding an object or map whose fields are sent at the enclosing level.
     *
     * A method can carry the attribute too, but only a property holds a value to read a field
     * from, so the methods are left out here.
     *
     * @param class-string $class
     *
     * @return list<\ReflectionProperty>
     */
    private static function findInlinedProperties(string $class): array
    {
        if (isset(self::$inlined[$class])) {
            return self::$inlined[$class];
        }

        $found = [];
        foreach (ClassMemberReader::readMarkedWith($class, InlineFields::class) as $member) {
            if ($member instanceof \ReflectionProperty) {
                $found[] = $member;
            }
        }

        return self::$inlined[$class] = $found;
    }

    /**
     * @param class-string $class
     *
     * @return array<string, string>
     * @throws InvalidArgumentException when the class does not exist
     */
    private static function readFieldNames(string $class): array
    {
        if (isset(self::$fieldNames[$class])) {
            return self::$fieldNames[$class];
        }

        $names = [];
        $reflection = self::reflect($class);

        while ($reflection !== false) {
            foreach ($reflection->getProperties() as $property) {
                if (isset($names[$property->getName()])) {
                    continue;
                }
                $name = WireNameReader::readDeclared($property);
                if ($name !== null) {
                    $names[$property->getName()] = $name;
                }
            }
            $reflection = $reflection->getParentClass();
        }

        return self::$fieldNames[$class] = $names;
    }

    /**
     * The method a property is read through, when it has one that takes no arguments.
     *
     * @param class-string $class
     * @param string $property
     *
     * @return \ReflectionMethod|null
     * @throws InvalidArgumentException when the class does not exist
     */
    private static function findGetter(string $class, string $property): ?\ReflectionMethod
    {
        if (\array_key_exists($property, self::$getters[$class] ?? [])) {
            return self::$getters[$class][$property];
        }

        $found = null;
        $reflection = self::reflect($class);
        // The same prefixes the object normalizer reads a field through, so a value the
        // serializer writes is a value the rules can see.
        foreach (['get', 'is', 'has', 'can'] as $prefix) {
            $name = $prefix . \ucfirst($property);
            if ($reflection->hasMethod($name)) {
                $method = $reflection->getMethod($name);
                if ($method->isPublic() && $method->getNumberOfRequiredParameters() === 0) {
                    $found = $method;

                    break;
                }
            }
        }

        return self::$getters[$class][$property] = $found;
    }

    /**
     * @param class-string $class
     * @param string $property
     *
     * @return \ReflectionProperty|null
     * @throws InvalidArgumentException when the class does not exist
     */
    private static function findProperty(string $class, string $property): ?\ReflectionProperty
    {
        if (\array_key_exists($property, self::$properties[$class] ?? [])) {
            return self::$properties[$class][$property];
        }

        $found = null;
        $reflection = self::reflect($class);

        while ($reflection !== false) {
            if ($reflection->hasProperty($property)) {
                $found = $reflection->getProperty($property);

                break;
            }
            $reflection = $reflection->getParentClass();
        }

        return self::$properties[$class][$property] = $found;
    }

    /**
     * Reflects a class and turns a \ReflectionException into an SDK exception.
     *
     * A missing class here is a programming error. Wrapping the exception keeps every method that
     * validates within the SDK's own exception hierarchy.
     *
     * @param string $class expected to be a class name, but not guaranteed
     *
     * @return \ReflectionClass<object>
     * @throws InvalidArgumentException when the class does not exist
     */
    public static function reflect(string $class): \ReflectionClass
    {
        try {
            return new \ReflectionClass($class);
        } catch (\ReflectionException $e) {
            throw new InvalidArgumentException(\sprintf('There is no class %s to inspect.', $class), 0, $e);
        }
    }
}
