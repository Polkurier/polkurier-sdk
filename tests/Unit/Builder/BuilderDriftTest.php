<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Unit\Builder;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Polkurier\Webservice\Builder\AbstractBuilder;
use Polkurier\Webservice\Serializer\Attribute\ResponseOnly;
use Polkurier\Webservice\Test\Support\MessageClasses;
use Polkurier\Webservice\Test\Support\Sample;
use Polkurier\Webservice\Test\Support\SourceClasses;

/**
 * The committed builders still match the messages they build.
 *
 * A builder is written by hand beside the message it builds, and nothing makes the two stay in
 * step. Add a constructor parameter and the builder keeps compiling: it simply never sets the new
 * field, so payloads go out quietly missing it, which is the worst kind of bug this SDK can have.
 * This walks every builder against the message's current constructor instead of trusting that
 * whoever changed one remembered the other.
 */
#[CoversNothing]
final class BuilderDriftTest extends TestCase
{
    /**
     * Below this a builder would be three lines to say one, and named arguments read well enough.
     */
    private const int THRESHOLD = 4;

    /**
     * @return array<string, array{class-string, class-string}>
     */
    public static function builders(): array
    {
        $pairs = [];

        foreach (SourceClasses::under('Builder') as $name => $builder) {
            // A builder mirrors what it builds, e.g. Builder\Entity\Order\CodBuilder builds
            // Entity\Order\Cod, and Builder\Request\Order\GetOrdersMessageBuilder builds
            // Dto\Request\Order\GetOrdersMessage.
            $relative = \substr($name, \strlen('Builder\\'), -\strlen('Builder'));
            $candidates = \array_filter(
                MessageClasses::requests(),
                static fn(string $class): bool => \str_ends_with($class, '\\' . $relative),
            );
            /** @var class-string $message */
            $message = \reset($candidates) ?: 'Polkurier\\Webservice\\Dto\\Request\\' . $relative;

            $reflection = new \ReflectionClass($builder);

            // The base carries the machinery, not a message's fields.
            if ($reflection->isAbstract()) {
                continue;
            }

            // Several builders share a short name, e.g. Address\SaveMessageBuilder and
            // BankAccount\SaveMessageBuilder, so the data set is named by the namespace too.
            $pairs[\substr($name, \strlen('Builder\\'))] = [$builder, $message];
        }

        self::assertNotEmpty($pairs);

        return $pairs;
    }

    /**
     * @param class-string $builder
     * @param class-string $message
     *
     * @return void
     */
    #[DataProvider('builders')]
    public function testEveryConstructorParameterHasASetter(string $builder, string $message): void
    {
        self::assertTrue(\class_exists($message), "{$builder} builds a message that no longer exists");

        $constructor = (new \ReflectionClass($message))->getConstructor();
        self::assertNotNull($constructor);

        $reflection = new \ReflectionClass($builder);

        foreach ($constructor->getParameters() as $parameter) {
            $name = $parameter->getName();
            $setter = self::setterFor($name);

            // A builder assembles what is sent, so it has no setter for a field only the API sends.
            if ($parameter->getAttributes(ResponseOnly::class) !== [] || $name === 'rawResponse') {
                self::assertFalse(
                    $reflection->hasMethod($setter),
                    \sprintf('%s::%s() sets a field the SDK never sends.', $builder, $setter),
                );

                continue;
            }

            self::assertTrue(
                $reflection->hasMethod($setter),
                \sprintf(
                    '%s has no %s() for $%s, which the message now takes.',
                    $builder,
                    $setter,
                    $name,
                ),
            );

            $declared = \ltrim((string)$parameter->getType(), '\\');
            $taken = \ltrim((string)$reflection->getMethod($setter)->getParameters()[0]->getType(), '\\');

            // A field a rule requires is nullable on the message, so that a response without it
            // still reads. The builder may refuse null for it, but no other difference.
            self::assertContains(
                $taken,
                [$declared, \ltrim($declared, '?')],
                \sprintf(
                    '%s::%s() takes a different type than the message does',
                    $builder,
                    $setter,
                ),
            );
        }
    }

    /**
     * A message that grows past the threshold needs a builder generating for it, and nothing else
     * would say so — the drift tests above only walk builders that already exist.
     *
     * @return void
     */
    public function testEveryMessageBigEnoughHasABuilder(): void
    {
        $missing = [];

        foreach (MessageClasses::requests() as $name => $message) {
            $constructor = (new \ReflectionClass($message))->getConstructor();

            $sent = \array_filter(
                $constructor?->getParameters() ?? [],
                static fn(\ReflectionParameter $parameter): bool
                    => $parameter->getAttributes(ResponseOnly::class) === []
                    && $parameter->getName() !== 'rawResponse',
            );

            if (\count($sent) < self::THRESHOLD) {
                continue;
            }

            $relative = \str_replace('Dto\\Request\\', 'Request\\', $name);
            $builder = 'Polkurier\\Webservice\\Builder\\' . $relative . 'Builder';

            if (!\class_exists($builder)) {
                $missing[] = $message;
            }
        }

        self::assertSame(
            [],
            $missing,
            'these have grown past ' . self::THRESHOLD . ' fields and want a builder.',
        );
    }

    /**
     * Each setter lands in its own field.
     *
     * The field name a builder passes is a string, so a setter can carry a name that is spelled
     * right and belongs to another field: `setRecipient()` writing `sender` compiles, passes the
     * name check above, and quietly sends the order to the wrong address. Every field that can
     * hold one is given a value naming the field it was set through, so a value arriving anywhere
     * else is visible.
     *
     * @param class-string $builder
     * @param class-string $message
     *
     * @return void
     */
    #[DataProvider('builders')]
    public function testEverySetterLandsInItsOwnField(string $builder, string $message): void
    {
        $constructor = (new \ReflectionClass($message))->getConstructor();
        self::assertNotNull($constructor);

        $built = new $builder();
        $sample = Sample::of($message);
        $expected = [];
        foreach ($constructor->getParameters() as $parameter) {
            $name = $parameter->getName();
            $setter = 'set' . \ucfirst($name);
            if (!\method_exists($built, $setter)) {
                continue;
            }

            if (self::takesAString($parameter)) {
                $expected[$name] = 'sentinel-' . $name;
                $built->{$setter}($expected[$name]);

                continue;
            }

            // Not a string, so it cannot carry a name of its own; it is set only where the
            // message cannot be built without it.
            if (!$parameter->isDefaultValueAvailable()) {
                $built->{$setter}(self::readField($sample, $name));
            }
        }

        if ($expected === []) {
            self::markTestSkipped($builder . ' has no field that can carry a name of its own.');
        }

        $result = $built->build();
        foreach ($expected as $name => $value) {
            self::assertSame($value, self::readField($result, $name), $builder . ' put ' . $value . ' elsewhere');
        }
    }

    /**
     * Whether the field holds a plain string, which is what can carry a value naming it.
     *
     * @param \ReflectionParameter $parameter
     *
     * @return bool
     */
    private static function takesAString(\ReflectionParameter $parameter): bool
    {
        $type = $parameter->getType();

        return $type instanceof \ReflectionNamedType && $type->getName() === 'string';
    }

    /**
     * @param object $message
     * @param string $name
     *
     * @return mixed
     */
    private static function readField(object $message, string $name): mixed
    {
        foreach (['get', 'is', 'has'] as $prefix) {
            $accessor = $prefix . \ucfirst($name);
            if (\method_exists($message, $accessor)) {
                return $message->{$accessor}();
            }
        }

        return (new \ReflectionProperty($message, $name))->getValue($message);
    }

    /**
     * A builder names the constructor parameter it fills with a string, which nothing compiles.
     *
     * A name that matches nothing is spread into the constructor as an unknown named argument and
     * raises a PHP `Error`, or, where the parameter it should have filled is required, makes
     * {@see \Polkurier\Webservice\Builder\AbstractBuilder::make()} report that field missing
     * although the caller set it. A setter whose own name is right hides either case, so the
     * string is checked rather than the method name.
     *
     * @param class-string $builder
     * @param class-string $message
     *
     * @return void
     */
    #[DataProvider('builders')]
    public function testEveryFieldNameABuilderPassesNamesAParameter(string $builder, string $message): void
    {
        $constructor = (new \ReflectionClass($message))->getConstructor();
        self::assertNotNull($constructor);

        $parameters = \array_map(
            static fn(\ReflectionParameter $p): string => $p->getName(),
            $constructor->getParameters(),
        );

        // A builder can inherit its setters, e.g. GetPickupTimeMessageBuilder from
        // PickupQueryMessageBuilder, so the whole chain down to the machinery is read.
        $fields = [];
        for ($class = new \ReflectionClass($builder); $class !== false; $class = $class->getParentClass()) {
            if ($class->getName() === AbstractBuilder::class) {
                break;
            }
            $file = $class->getFileName();
            self::assertIsString($file);
            \preg_match_all('/\$this->set\(\s*\'([^\']++)\'/', (string)\file_get_contents($file), $matches);
            $fields = [...$fields, ...$matches[1]];
        }

        self::assertNotEmpty($fields, $builder . ' sets no field at all.');

        foreach (\array_unique($fields) as $field) {
            self::assertContains(
                $field,
                $parameters,
                \sprintf('%s passes the field name %s, which %s does not take.', $builder, $field, $message),
            );
        }
    }

    /**
     * The setter a builder names a constructor parameter with.
     *
     * @param string $parameter
     *
     * @return string
     */
    private static function setterFor(string $parameter): string
    {
        return 'set' . \ucfirst($parameter);
    }

    /**
     * A setter the message no longer has would compile and then fail inside build().
     *
     * @param class-string $builder
     * @param class-string $message
     *
     * @return void
     */
    #[DataProvider('builders')]
    public function testNoSetterOutlivesItsParameter(string $builder, string $message): void
    {
        $constructor = (new \ReflectionClass($message))->getConstructor();
        self::assertNotNull($constructor);

        $parameters = \array_map(
            static fn(\ReflectionParameter $p): string => $p->getName(),
            $constructor->getParameters(),
        );

        $reflection = new \ReflectionClass($builder);

        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            // Everything the base defines is machinery; only the generated setters name fields.
            if ($method->getDeclaringClass()->getName() !== $builder) {
                continue;
            }

            if ($method->getName() === 'build') {
                continue;
            }

            self::assertContains(
                \lcfirst((string)\preg_replace('/^set/', '', $method->getName())),
                $parameters,
                \sprintf(
                    '%s::%s() sets a field the message no longer takes.',
                    $builder,
                    $method->getName(),
                ),
            );
        }
    }
}
