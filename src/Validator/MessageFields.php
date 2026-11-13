<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Validator;

use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Serializer\Attribute\InlineFields;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Reads a message's properties and their wire names through reflection.
 *
 * Validation reports use wire field names, so they match the API's own reports and the request
 * that would have been sent. The names are read from the serializer attribute on each property,
 * so a renamed field stays in step with its rules. The fields of an object marked
 * {@see InlineFields} are sent at the enclosing level, so they are read as the message's own.
 *
 * Reflection runs once per class and is then reused.
 *
 * An inlined record is read through another instance of this class, built from the class of the
 * object holding it, so a subclass reads an inlined record with itself.
 *
 * @phpstan-consistent-constructor
 */
class MessageFields
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
        /**
         * @var object
         */
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
    public function value(string $property): mixed
    {
        $inlined = $this->inlinedHolder($property);
        if ($inlined !== null) {
            return $inlined->value($property);
        }

        $reflectionProperty = static::propertyFor($this->message::class, $property);

        // A property that was never set has no value to check, whichever way it would be read.
        if ($reflectionProperty !== null && !$reflectionProperty->isInitialized($this->message)) {
            return null;
        }

        // What a getter returns is what is sent, so that is what the rules check.
        $getter = static::getterFor($this->message::class, $property);
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
    public function field(string $property): string
    {
        return $this->inlinedHolder($property)?->field($property)
            ?? static::fieldNamesFor($this->message::class)[$property]
            ?? $property;
    }

    /**
     * The fields of the inlined object that holds a property the message itself doesn't declare.
     *
     * @param string $property
     *
     * @return self|null null when the message declares the property or no inlined object holds it
     * @throws InvalidArgumentException
     */
    protected function inlinedHolder(string $property): ?self
    {
        if (static::propertyFor($this->message::class, $property) !== null) {
            return null;
        }

        foreach (static::inlinedPropertiesOf($this->message::class) as $inlined) {
            if (!$inlined->isInitialized($this->message)) {
                continue;
            }
            $object = $inlined->getValue($this->message);
            if (\is_object($object) && static::propertyFor($object::class, $property) !== null) {
                return new static($object);
            }
        }

        return null;
    }

    /**
     * @param class-string $class
     *
     * @return list<\ReflectionProperty>
     * @throws InvalidArgumentException when the class does not exist
     */
    protected static function inlinedPropertiesOf(string $class): array
    {
        if (isset(self::$inlined[$class])) {
            return self::$inlined[$class];
        }

        $found = [];
        for ($reflection = self::reflect($class); $reflection !== false; $reflection = $reflection->getParentClass()) {
            foreach ($reflection->getProperties() as $property) {
                if ($property->getAttributes(InlineFields::class) !== []) {
                    $found[] = $property;
                }
            }
        }

        return self::$inlined[$class] = $found;
    }

    /**
     * @return object
     */
    public function message(): object
    {
        return $this->message;
    }

    /**
     * @param class-string $class
     *
     * @return array<string, string>
     * @throws InvalidArgumentException when the class does not exist
     */
    protected static function fieldNamesFor(string $class): array
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
                $attributes = $property->getAttributes(Serializer\SerializedName::class);
                if ($attributes === []) {
                    continue;
                }
                // The attribute's argument, read the same way on every supported Symfony version
                $arguments = $attributes[0]->getArguments();
                $name = $arguments['serializedName'] ?? $arguments[0] ?? null;
                if (\is_string($name)) {
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
    protected static function getterFor(string $class, string $property): ?\ReflectionMethod
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
    protected static function propertyFor(string $class, string $property): ?\ReflectionProperty
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
