<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Validator;

use Polkurier\Webservice\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Reads a message's properties and their wire names through reflection.
 *
 * Validation reports use wire field names, so they match the API's own reports and the request
 * that would have been sent. The names are read from the serializer attribute on each property,
 * so a renamed field stays in step with its rules.
 *
 * Reflection runs once per class and is then reused.
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
        $reflectionProperty = static::propertyFor($this->message::class, $property);

        if ($reflectionProperty === null || !$reflectionProperty->isInitialized($this->message)) {
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
        return static::fieldNamesFor($this->message::class)[$property] ?? $property;
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
