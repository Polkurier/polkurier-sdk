<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Unit;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Polkurier\Webservice\Test\Support\SourceClasses;

/**
 * What the SDK promises is marked, and only what it promises.
 *
 * A release may change anything that is not part of the public contract, so the line between the
 * two decides what a version number means. `@api` on the type is where that line is written down,
 * and this walks it in both directions: every type under the contract's namespaces carries the
 * tag, and no type outside them does.
 *
 * @see docs/architecture.md
 */
#[CoversNothing]
final class PublicContractTest extends TestCase
{
    /**
     * Namespaces whose every type is part of the contract, relative to the SDK's root namespace.
     *
     * @var list<string>
     */
    private const array PUBLIC_NAMESPACES = [
        'Api',
        'Builder',
        'Dto',
        'Entity',
        'Enum',
        'Exception',
        'Testing',
        'Webhook',
    ];

    /**
     * Contract types that sit outside those namespaces: the facade, its configuration, and the
     * types a public method hands out or takes.
     *
     * @var list<string>
     */
    private const array PUBLIC_CLASSES = [
        'Config',
        'ConfigProvider',
        'PolkurierWebservice',
        'Serializer\\ReadResponse',
        'Serializer\\ResponseDrift',
        'Serializer\\SerializerConfig',
        'Validator\\AttributeValidator',
        'Validator\\DefaultValidatorRegistryFactory',
        'Validator\\NullValidator',
        'Validator\\ValidatorRegistry',
    ];

    /**
     * Where the shape of the contract is written down.
     */
    private const string SURFACE = __DIR__ . '/../Support/contract-surface.txt';

    /**
     * @return array<string, array{class-string, bool}> the type, and whether it is contract
     */
    public static function sourceTypes(): array
    {
        $types = [];
        foreach (SourceClasses::under() as $name => $class) {
            $namespace = \explode('\\', $name)[0];
            $types[$name] = [
                $class,
                \in_array($namespace, self::PUBLIC_NAMESPACES, true)
                    || \in_array($name, self::PUBLIC_CLASSES, true),
            ];
        }
        self::assertNotEmpty($types);

        return $types;
    }

    /**
     * The tag is read the way a reader and an IDE read it: from the type's own docblock, the one
     * PHP reports, not whatever block happens to sit above it in the file.
     *
     * @param class-string $class
     * @param bool $public
     *
     * @return void
     */
    #[DataProvider('sourceTypes')]
    public function testTheContractIsMarkedAndNothingElseIs(string $class, bool $public): void
    {
        self::assertTrue(
            \class_exists($class) || \interface_exists($class) || \trait_exists($class) || \enum_exists($class),
            $class . ' does not match the file it is declared in',
        );

        $docBlock = (new \ReflectionClass($class))->getDocComment();
        $tagged = \is_string($docBlock) && \preg_match('~^\s*\* @api\s*$~m', $docBlock) === 1;

        self::assertSame(
            $public,
            $tagged,
            $public
                ? $class . ' is part of the public contract and its docblock has no @api tag'
                : $class . ' is not part of the public contract but is tagged @api',
        );
    }

    /**
     * A named argument is how an integrator calls a constructor with many optional parameters,
     * and some frameworks pass every argument by name, so a renamed parameter breaks callers at
     * runtime and no static analysis sees it. A method that disappears, a narrowed parameter
     * type, a dropped default, a changed return type, a changed constant and a class that stops
     * implementing an interface break them just as quietly. Everything a caller can write against
     * is therefore recorded, and a change to any of it has to be made here as well.
     *
     * What PHP itself defines on an enum is left out: `cases()`, `from()`, `tryFrom()`, `$name`
     * and `$value` change when PHP changes, not when this SDK does.
     *
     * Run with POLKURIER_CONTRACT_BASELINE=1 to write the file after a deliberate change.
     *
     * @return void
     */
    public function testTheContractKeepsItsShape(): void
    {
        $lines = [];
        foreach (self::sourceTypes() as [$class, $public]) {
            if (!$public) {
                continue;
            }
            $lines = [...$lines, ...self::shapeOf(new \ReflectionClass($class))];
        }
        \sort($lines, \SORT_STRING);
        $current = \implode("\n", $lines) . "\n";

        if (\getenv('POLKURIER_CONTRACT_BASELINE') !== false) {
            \file_put_contents(self::SURFACE, $current);
            self::markTestSkipped('The recorded contract was rewritten.');
        }

        self::assertFileExists(self::SURFACE);
        self::assertSame(
            \file_get_contents(self::SURFACE),
            $current,
            'The public contract changed: a class, a constant, a property, a method, a parameter,'
            . ' a type or a default. If that is intended, run the suite once with'
            . ' POLKURIER_CONTRACT_BASELINE=1 and commit the file.',
        );
    }

    /**
     * Every line one type contributes: what it is, then its constants, properties and methods.
     *
     * @param \ReflectionClass<object> $reflection
     *
     * @return list<string>
     */
    private static function shapeOf(\ReflectionClass $reflection): array
    {
        $name = $reflection->getName();
        $lines = [\sprintf('%s: %s', $name, self::kindOf($reflection))];

        foreach ($reflection->getReflectionConstants(\ReflectionClassConstant::IS_PUBLIC) as $constant) {
            if ($constant->getDeclaringClass()->getName() !== $name) {
                continue;
            }
            $value = $constant->getValue();
            // A case of this very enum is its own name on the left already, so only its backed
            // value says anything.
            $rendered = $value instanceof \UnitEnum && $value::class === $name
                ? self::render($value instanceof \BackedEnum ? $value->value : $value->name)
                : self::render($value);
            $lines[] = \sprintf('%s::%s = %s', $name, $constant->getName(), $rendered);
        }

        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->getDeclaringClass()->getName() !== $name
                || ($reflection->isEnum() && \in_array($property->getName(), ['name', 'value'], true))
            ) {
                continue;
            }
            $lines[] = \sprintf(
                '%s::$%s: %s%s',
                $name,
                $property->getName(),
                $property->isReadOnly() ? 'readonly ' : '',
                $property->hasType() ? (string)$property->getType() : 'mixed',
            );
        }

        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getDeclaringClass()->getName() !== $name
                || ($reflection->isEnum() && \in_array($method->getName(), ['cases', 'from', 'tryFrom'], true))
            ) {
                continue;
            }
            $lines[] = \sprintf(
                '%s::%s%s(%s)%s',
                $name,
                $method->isStatic() ? 'static ' : '',
                $method->getName(),
                \implode(', ', \array_map(self::parameter(...), $method->getParameters())),
                $method->isConstructor()
                    ? ''
                    : ': ' . ($method->hasReturnType() ? (string)$method->getReturnType() : 'mixed'),
            );
        }

        return $lines;
    }

    /**
     * What the type is, and what it promises to be a kind of.
     *
     * @param \ReflectionClass<object> $reflection
     *
     * @return string
     */
    private static function kindOf(\ReflectionClass $reflection): string
    {
        $words = [];
        if ($reflection->isFinal() && !$reflection->isEnum()) {
            $words[] = 'final';
        }
        if ($reflection->isReadOnly()) {
            $words[] = 'readonly';
        }
        if ($reflection->isAbstract() && !$reflection->isInterface()) {
            $words[] = 'abstract';
        }
        $words[] = match (true) {
            $reflection->isInterface() => 'interface',
            $reflection->isEnum() => 'enum',
            default => 'class',
        };

        $parent = $reflection->getParentClass();
        if ($parent !== false) {
            $words[] = 'extends ' . $parent->getName();
        }
        $interfaces = $reflection->getInterfaceNames();
        \sort($interfaces, \SORT_STRING);
        if ($interfaces !== []) {
            $words[] = 'implements ' . \implode(', ', $interfaces);
        }

        return \implode(' ', $words);
    }

    /**
     * One parameter, as a caller must be able to keep writing it.
     *
     * @param \ReflectionParameter $parameter
     *
     * @return string
     */
    private static function parameter(\ReflectionParameter $parameter): string
    {
        $default = '';
        if ($parameter->isDefaultValueAvailable()) {
            $default = ' = ' . ($parameter->isDefaultValueConstant()
                ? (string)$parameter->getDefaultValueConstantName()
                : self::render($parameter->getDefaultValue()));
        }

        return \sprintf(
            '%s%s: %s%s',
            $parameter->isVariadic() ? '...' : '',
            $parameter->getName(),
            $parameter->hasType() ? (string)$parameter->getType() : 'mixed',
            $default,
        );
    }

    /**
     * A value, as one line.
     *
     * @param mixed $value
     *
     * @return string
     */
    private static function render(mixed $value): string
    {
        return match (true) {
            $value === null => 'null',
            \is_string($value) => "'" . $value . "'",
            \is_bool($value) => $value ? 'true' : 'false',
            \is_array($value) => (string)\json_encode($value),
            $value instanceof \BackedEnum => $value::class . '::' . $value->name,
            \is_object($value) => $value::class,
            default => (string)$value,
        };
    }
}
