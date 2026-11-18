<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Support;

/**
 * The types the source declares, found by scanning it, so a new file is covered by every test that
 * iterates them.
 *
 * The name comes from the file's path, which is what PSR-4 autoloading resolves; a file whose
 * declaration disagrees with its path yields a name nothing answers to.
 */
final class SourceClasses
{
    /**
     * Every name a directory of the source spells, whether or not a type answers to it.
     *
     * @param string $directory relative to src, empty for all of it
     *
     * @return array<string, class-string> keyed by the name without the SDK's root namespace
     */
    public static function under(string $directory = ''): array
    {
        $root = \dirname(__DIR__, 2) . '/src';
        $classes = [];
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $directory === '' ? $root : $root . '/' . $directory,
                \FilesystemIterator::SKIP_DOTS,
            ),
        );

        /** @var \SplFileInfo $file */
        foreach ($files as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }
            $name = \str_replace('/', '\\', \substr($file->getPathname(), \strlen($root) + 1, -4));
            /** @var class-string $class */
            $class = 'Polkurier\\Webservice\\' . $name;
            $classes[$name] = $class;
        }
        \ksort($classes);

        return $classes;
    }

    /**
     * Those of them that can be instantiated: an interface, a trait, an abstract class and a name
     * nothing declares are left out.
     *
     * @param string $directory relative to src, empty for all of it
     *
     * @return array<string, class-string> keyed by the name without the SDK's root namespace
     */
    public static function instantiableUnder(string $directory = ''): array
    {
        return \array_filter(
            self::under($directory),
            static fn(string $class): bool => \class_exists($class) && !(new \ReflectionClass($class))->isAbstract(),
        );
    }
}
