<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Unit\Serializer;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Polkurier\Webservice\Serializer\SerializerProvider;
use Symfony\Component\Serializer\Exception\MissingConstructorArgumentsException;

/**
 * No response class can be handed back half-built.
 *
 * The previous serializer instantiated without calling the constructor and filled properties by
 * reflection, so a key the API left out became a typed property that was never initialised. The
 * object looked fine until the first getter, which threw `Error: must not be accessed before
 * initialization` — a crash with no connection to the response that caused it. That is how an
 * unknown pickup point id, answered with an empty payload, turned into an exception three frames
 * away from the call.
 *
 * Building through the constructor is what removes that state: either every required field is
 * present and the object is whole, or construction fails immediately and says which field was
 * missing. This walks every response class with the emptiest possible payload and insists on one
 * of those two outcomes — never a third.
 *
 * A class that fails here is not necessarily wrong. It means the API must always send those
 * fields, which is a claim worth making deliberately: if it turns out it does not, the fix is a
 * default on that field, not a catch at the call site.
 */
#[CoversNothing]
final class ResponseConstructionTest extends TestCase
{
    /**
     * Every class under Response.
     *
     * @return array<string, array{class-string}>
     */
    public static function responseClasses(): array
    {
        $root = \dirname(__DIR__, 3) . '/src';
        $classes = [];

        /** @var \SplFileInfo $file */
        foreach (new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root . '/Response')
        ) as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            /** @var class-string $class */
            $class = 'Polkurier\\Webservice\\' . \str_replace(
                '/',
                '\\',
                \substr($file->getPathname(), \strlen($root) + 1, -4)
            );

            if (!\class_exists($class) || (new \ReflectionClass($class))->isAbstract()) {
                continue;
            }

            $classes[\str_replace('Polkurier\\Webservice\\Response\\', '', $class)] = [$class];
        }

        self::assertNotEmpty($classes);

        return $classes;
    }

    /**
     * @param class-string $class
     *
     * @return void
     */
    #[DataProvider('responseClasses')]
    public function testIsEitherWholeOrRefusedOutright(string $class): void
    {
        $serializer = (new SerializerProvider())->getSerializer();

        try {
            // Not a literal empty array: that is how the API says "no object here", and the
            // serializer answers it with null before construction is ever attempted. An
            // unrecognised key is ignored, so this is as close to empty as a payload can get
            // while still asking for the class to be built.
            $object = $serializer->denormalize(['__absent__' => true], $class, 'json');
        } catch (MissingConstructorArgumentsException $refusal) {
            // Refused, and said which fields were missing. That is the other acceptable outcome,
            // and the message has to name them or the refusal is not actionable.
            self::assertStringContainsString('$', $refusal->getMessage());

            return;
        }

        self::assertInstanceOf($class, $object);

        // Whole means readable. An uninitialised typed property raises Error here, which is the
        // failure this test exists to make impossible.
        foreach ((new \ReflectionClass($class))->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->isStatic() || $method->getNumberOfRequiredParameters() > 0) {
                continue;
            }
            if (!\preg_match('/^(get|is|has)/', $method->getName())) {
                continue;
            }

            $method->invoke($object);
        }
    }
}
