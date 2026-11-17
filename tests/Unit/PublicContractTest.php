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
     * Where the parameter names of the contract are written down.
     */
    private const string PARAMETERS = __DIR__ . '/../Support/contract-parameters.txt';

    /**
     * @return array<string, array{class-string, bool}> the type, and whether it is contract
     */
    public static function sourceTypes(): array
    {
        $root = \dirname(__DIR__, 2) . '/src';
        $types = [];
        /** @var \SplFileInfo $file */
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root)) as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }
            $name = \str_replace('/', '\\', \substr($file->getPathname(), \strlen($root) + 1, -4));
            $namespace = \explode('\\', $name)[0];
            /** @var class-string $class */
            $class = 'Polkurier\\Webservice\\' . $name;
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
     * A named argument is how an integrator calls a constructor with many optional parameters, and
     * some frameworks pass every argument by name, so a renamed parameter breaks callers at
     * runtime and no static analysis sees it. The names of every public parameter of the contract
     * are therefore recorded, and a change to one has to be made here as well.
     *
     * Run with POLKURIER_CONTRACT_BASELINE=1 to write the file after a deliberate change.
     *
     * @return void
     */
    public function testTheContractKeepsItsParameterNames(): void
    {
        $signatures = [];
        foreach (self::sourceTypes() as [$class, $public]) {
            if (!$public) {
                continue;
            }
            $reflection = new \ReflectionClass($class);
            foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                if ($method->getDeclaringClass()->getName() !== $reflection->getName()
                    || $method->getParameters() === []
                ) {
                    continue;
                }
                $names = \array_map(
                    static fn(\ReflectionParameter $parameter): string => $parameter->getName(),
                    $method->getParameters(),
                );
                $signatures[] = \sprintf(
                    '%s::%s(%s)',
                    $reflection->getName(),
                    $method->getName(),
                    \implode(', ', $names),
                );
            }
        }
        \sort($signatures, \SORT_STRING);
        $current = \implode("\n", $signatures) . "\n";

        if (\getenv('POLKURIER_CONTRACT_BASELINE') !== false) {
            \file_put_contents(self::PARAMETERS, $current);
            self::markTestSkipped('The recorded parameter names were rewritten.');
        }

        self::assertFileExists(self::PARAMETERS);
        self::assertSame(
            \file_get_contents(self::PARAMETERS),
            $current,
            'A parameter of the public contract was renamed, added or removed. If that is intended,'
            . ' run the suite once with POLKURIER_CONTRACT_BASELINE=1 and commit the file.',
        );
    }
}
