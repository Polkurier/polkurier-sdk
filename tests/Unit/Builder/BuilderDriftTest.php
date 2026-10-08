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
        $root = \dirname(__DIR__, 3) . '/src';
        $pairs = [];

        /** @var \SplFileInfo $file */
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root . '/Builder')) as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            /** @var class-string $builder */
            $builder = 'Polkurier\\Webservice\\' . \str_replace(
                '/',
                '\\',
                \substr($file->getPathname(), \strlen($root) + 1, -4)
            );

            /** @var class-string $message */
            $message = \str_replace(
                ['\\Builder\\', 'Builder'],
                ['\\Request\\', ''],
                $builder
            );

            $reflection = new \ReflectionClass($builder);

            // The base carries the machinery, not a message's fields.
            if ($reflection->isAbstract()) {
                continue;
            }

            $pairs[$reflection->getShortName()] = [$builder, $message];
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

            self::assertTrue(
                $reflection->hasMethod($name),
                \sprintf(
                    '%s has no setter for $%s, which the message now takes.',
                    $reflection->getShortName(),
                    $name,
                ),
            );

            self::assertSame(
                \ltrim((string)$parameter->getType(), '\\'),
                \ltrim((string)$reflection->getMethod($name)->getParameters()[0]->getType(), '\\'),
                \sprintf(
                    '%s::%s() takes a different type than the message does',
                    $reflection->getShortName(),
                    $name,
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
        $root = \dirname(__DIR__, 3) . '/src';
        $missing = [];

        /** @var \SplFileInfo $file */
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root . '/Request')) as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            /** @var class-string $message */
            $message = 'Polkurier\\Webservice\\' . \str_replace(
                '/',
                '\\',
                \substr($file->getPathname(), \strlen($root) + 1, -4)
            );

            if (!\class_exists($message)) {
                continue;
            }

            $constructor = (new \ReflectionClass($message))->getConstructor();

            if ($constructor === null || \count($constructor->getParameters()) < self::THRESHOLD) {
                continue;
            }

            $builder = \str_replace('\\Request\\', '\\Builder\\', $message) . 'Builder';

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
                $method->getName(),
                $parameters,
                \sprintf(
                    '%s::%s() sets a field the message no longer takes.',
                    $reflection->getShortName(),
                    $method->getName(),
                ),
            );
        }
    }
}
