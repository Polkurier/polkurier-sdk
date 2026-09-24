<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Builder;

use Polkurier\Webservice\Exception\InvalidArgumentException;

/**
 * What every builder does, so that no builder has to say it.
 *
 * A builder's only irreducible part is its typed setters: one per field, named for it and taking
 * exactly what the message takes. Everything around them — collecting the values, knowing which
 * fields are required, refusing to build without them — is the same for all of them and lives
 * here.
 *
 * Values are held by field name and handed to the constructor as named arguments, which is what
 * lets a field never set simply not be passed. That matters more than it sounds: a builder that
 * mirrored each default would go stale the moment a message changed one, and would then quietly
 * send the old value. Here the message's own defaults are the only defaults there are.
 */
abstract class AbstractBuilder
{
    /**
     * The constructor parameters each message cannot be built without, once worked out.
     *
     * @var array<class-string, list<string>>
     */
    private static array $required = [];

    /**
     * Fields that have been given, by name.
     *
     * @var array<string, mixed>
     */
    protected array $values = [];

    /**
     * Builders are started with create() and filled by their setters, so there is nothing to
     * construct. Fixed here so that create() can safely build a subclass.
     */
    final public function __construct() {}

    /**
     * @return static
     */
    public static function create(): static
    {
        return new static();
    }

    /**
     * Records one field. The typed setters are what callers use; this is what they call.
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
     * Builds the message, or says which fields it still needs.
     *
     * Every missing field is named at once. Letting the constructor fail instead would report the
     * first and stop, which for a payload of a dozen fields is a dozen attempts.
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
     * The constructor parameters with no default, read from the message itself.
     *
     * Read rather than generated, so it cannot disagree with the constructor it describes.
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
