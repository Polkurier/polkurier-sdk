<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Builder;

use Polkurier\Webservice\Exception\InvalidArgumentException;

/**
 * Base class for the builders.
 *
 * Each builder adds one typed setter per field. This class collects the values, works out which
 * fields the message requires and refuses to build until they are all set.
 *
 * Values are stored by field name and passed to the message constructor as named arguments. A field
 * you never set is not passed at all, so the message's own constructor default applies. The builders
 * do not keep copies of those defaults.
 *
 * @phpstan-consistent-constructor
 */
abstract class AbstractBuilder
{
    /**
     * Cache of the required constructor parameters, per message class.
     *
     * @var array<class-string, list<string>>
     */
    private static array $required = [];

    /**
     * Values set so far, by field name.
     *
     * @var array<string, mixed>
     */
    protected array $values = [];

    /**
     * Use create() to start a builder, or let a DI container create it. The constructor takes no
     * arguments, so create() can instantiate any subclass. A subclass that declares its own must
     * keep it parameterless and call this one.
     */
    public function __construct() {}

    /**
     * @return static
     */
    public static function create(): static
    {
        return new static();
    }

    /**
     * Forgets every value set so far, so the builder can start over. Values otherwise persist
     * after build().
     *
     * @return static
     */
    public function reset(): static
    {
        $this->values = [];

        return $this;
    }

    /**
     * Stores one field value. The typed setters call this.
     *
     * @param string $field
     * @param mixed $value
     *
     * @return static
     */
    protected function set(string $field, mixed $value): static
    {
        $this->values[$field] = $value;

        return $this;
    }

    /**
     * Builds the message.
     *
     * If required fields are missing, the exception lists all of them, not just the first one.
     *
     * @template T of object
     *
     * @param class-string<T> $message
     *
     * @return T
     * @throws InvalidArgumentException when a field the message requires was never given
     */
    protected function make(string $message): object
    {
        $missing = \array_values(\array_diff(self::requiredOf($message), \array_keys($this->values)));

        if ($missing !== []) {
            throw new InvalidArgumentException(\sprintf(
                '%s cannot be built without %s.',
                $message,
                \implode(', ', $missing),
            ));
        }

        return new $message(...$this->values);
    }

    /**
     * Returns the constructor parameters that have no default value, read via reflection.
     *
     * @param class-string $message
     *
     * @return list<string>
     * @throws InvalidArgumentException when the named message class does not exist
     */
    private static function requiredOf(string $message): array
    {
        if (isset(self::$required[$message])) {
            return self::$required[$message];
        }

        $required = [];

        try {
            $constructor = new \ReflectionClass($message)->getConstructor();
        } catch (\ReflectionException $e) {
            throw new InvalidArgumentException(\sprintf('There is no message class %s.', $message), 0, $e);
        }

        foreach ($constructor?->getParameters() ?? [] as $parameter) {
            if (!$parameter->isDefaultValueAvailable() && !$parameter->isVariadic()) {
                $required[] = $parameter->getName();
            }
        }

        return self::$required[$message] = $required;
    }
}
