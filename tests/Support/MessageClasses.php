<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Support;

/**
 * The SDK's request and response classes, found by scanning the source, so a new class is covered
 * by every test that iterates them.
 *
 * An entity is both sent and read, so it counts as a request and as a response class.
 */
final class MessageClasses
{
    /**
     * @return array<string, class-string> keyed by the class name without the SDK's namespace
     */
    public static function requests(): array
    {
        return self::scan('Dto/Request') + self::scan('Entity');
    }

    /**
     * @return array<string, class-string> keyed by the class name without the SDK's namespace
     */
    public static function responses(): array
    {
        return self::scan('Dto/Response') + self::scan('Entity');
    }

    /**
     * Every concrete class under a directory of the source.
     *
     * @param string $directory relative to src
     *
     * @return array<string, class-string>
     */
    private static function scan(string $directory): array
    {
        $root = \dirname(__DIR__, 2) . '/src';
        $classes = [];
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root . '/' . $directory, \FilesystemIterator::SKIP_DOTS),
        );
        /** @var \SplFileInfo $file */
        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            $name = \str_replace('/', '\\', \substr($file->getPathname(), \strlen($root) + 1, -4));
            /** @var class-string $class */
            $class = 'Polkurier\\Webservice\\' . $name;
            if (!\class_exists($class) || (new \ReflectionClass($class))->isAbstract()) {
                continue;
            }
            $classes[$name] = $class;
        }
        \ksort($classes);

        return $classes;
    }
}
