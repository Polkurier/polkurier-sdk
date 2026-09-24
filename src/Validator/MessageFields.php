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
 * Reflective access to a message's properties and their wire names.
 *
 * Validation reports use wire field names so they line up with the API's own reports and with the
 * request that would have been sent. Those names are already declared, once, by the serializer
 * attribute on each property — so they are read from there rather than restated, and a renamed
 * field cannot drift out of step with the rule that mentions it.
 *
 * Reflection is done once per class and reused; a message is cheap to inspect after the first.
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
     * The value of one property, whatever its visibility and wherever it is declared.
     *
     * The hierarchy is walked rather than asked, because a private property declared on a parent
     * is invisible to the child's reflection — which silently skipped every inherited field until
     * it was noticed.
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
     * The wire field name a property is sent under.
     *
     * Falls back to the property name when nothing declares otherwise, which only happens for
     * properties that never cross the wire.
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
                /** @var Serializer\SerializedName $serializedName */
                $serializedName = $attributes[0]->newInstance();
                $names[$property->getName()] = $serializedName->serializedName;
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
     * Reflects a class, turning the one failure it has into an SDK exception.
     *
     * A missing class here is a programming error rather than a runtime condition, and letting
     * \ReflectionException escape would put an exception outside the SDK's own hierarchy into the
     * contract of every method that validates anything.
     *
     * @param string $class a name that is expected to be a class, but need not be one
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
