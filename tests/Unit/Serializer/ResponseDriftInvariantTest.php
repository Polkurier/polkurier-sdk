<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Unit\Serializer;

use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Polkurier\Webservice\Enum\Serializer\ResponseDriftTypeEnum;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Exception\MalformedResponseException;
use Polkurier\Webservice\Http\Response\ResponseFactory;
use Polkurier\Webservice\Response\Order;
use Polkurier\Webservice\Response\Webhook\OrderWebhookMessage;
use Polkurier\Webservice\Serializer\SerializerConfig;
use Polkurier\Webservice\Serializer\SerializerProvider;
use Polkurier\Webservice\Test\Support\Sample;
use Polkurier\Webservice\Webhook\OrderWebhookReader;

/**
 * Every response class reads whatever a field turns into, and strict reading rejects exactly what
 * lenient reading reports.
 *
 * Each top-level field of a sample response, and of the order in an order event, is removed, or
 * replaced with a value of each other shape. Lenient reading must then give an object whose getters
 * all work, unless none of the object's fields is left, and strict reading must fail exactly when
 * lenient reading failed or recorded something other than an unknown enum value.
 */
#[CoversNothing]
final class ResponseDriftInvariantTest extends TestCase
{
    private const string REMOVE = "\0remove";

    private const array CHANGES = [
        'removed' => self::REMOVE,
        'null' => null,
        'a string' => 'x',
        'a number' => 12,
        'a boolean' => true,
        'a list of lists' => [[1]],
        'an object' => ['a' => 1],
    ];

    /**
     * Every class under Response, except the order event, which has a test of its own.
     *
     * @return array<string, array{class-string}>
     */
    public static function responseClasses(): array
    {
        $root = \dirname(__DIR__, 3) . '/src';
        $classes = [];

        /** @var \SplFileInfo $file */
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root . '/Response')) as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            /** @var class-string $class */
            $class = 'Polkurier\\Webservice\\' . \str_replace(
                '/',
                '\\',
                \substr($file->getPathname(), \strlen($root) + 1, -4)
            );

            if (!\class_exists($class) || (new \ReflectionClass($class))->isAbstract()
                || $class === OrderWebhookMessage::class
            ) {
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
    public function testEveryChangedFieldReadsAndStrictReadingAgrees(string $class): void
    {
        $sample = Sample::wire($class);
        $lenient = new ResponseFactory(new SerializerProvider());
        $strict = new ResponseFactory(new SerializerProvider(new SerializerConfig(strictResponses: true)));

        foreach (\array_keys($sample) as $field) {
            foreach (self::CHANGES as $change => $value) {
                $payload = $sample;
                if ($value === self::REMOVE) {
                    unset($payload[$field]);
                } else {
                    $payload[$field] = $value;
                }
                if ($payload === []) {
                    continue;
                }
                $body = (string)\json_encode(['status' => 'success', 'response' => $payload]);
                $case = \sprintf('%s with "%s" %s', $class, $field, $change);

                try {
                    $wrapper = $lenient->create(new Response(200, [], $body), $class);
                } catch (MalformedResponseException $e) {
                    // With none of its fields left, the object is something else.
                    self::assertStringContainsString('None of the fields', $e->getMessage(), $case);
                    $this->expectStrictFailure($strict, $body, $class, $case);
                    continue;
                }
                self::assertReadable($wrapper->getParsedResponse()->getResponse());
                $reported = \array_filter(
                    $wrapper->getDrift(),
                    static fn($drift) => $drift->getType() !== ResponseDriftTypeEnum::UnknownEnumValue,
                );

                try {
                    $strict->create(new Response(200, [], $body), $class);
                    self::assertSame([], $reported, $case . ': strict reading accepted what lenient reading reported');
                } catch (MalformedResponseException $e) {
                    self::assertNotSame([], $reported, $case . ': strict reading failed: ' . $e->getMessage());
                }
            }
        }
    }

    /**
     * The same for an order event, which arrives as a form: a field set to null is left out.
     *
     * @return void
     */
    public function testEveryChangedOrderFieldInAnEventReadsAndStrictReadingAgrees(): void
    {
        $sample = Sample::wire(Order::class);
        $strictProvider = new SerializerProvider(new SerializerConfig(strictResponses: true));

        foreach (\array_keys($sample) as $field) {
            foreach (self::CHANGES as $change => $value) {
                $order = $sample;
                if ($value === self::REMOVE) {
                    unset($order[$field]);
                } else {
                    $order[$field] = $value;
                }
                $body = \http_build_query(['type' => 'UPDATED', 'data' => $order]);
                $case = \sprintf('An event with "%s" %s', $field, $change);

                $reader = new OrderWebhookReader();
                try {
                    self::assertReadable($reader->read($body));
                } catch (InvalidArgumentException $e) {
                    self::assertStringContainsString('None of the fields', $e->getMessage(), $case);
                    continue;
                }
                $reported = \array_filter(
                    $reader->getLastDrift(),
                    static fn($drift) => $drift->getType() !== ResponseDriftTypeEnum::UnknownEnumValue,
                );

                try {
                    (new OrderWebhookReader($strictProvider))->read($body);
                    self::assertSame([], $reported, $case . ': strict reading accepted what lenient reading reported');
                } catch (InvalidArgumentException $e) {
                    self::assertNotSame([], $reported, $case . ': strict reading failed: ' . $e->getMessage());
                }
            }
        }
    }

    /**
     * @param ResponseFactory $strict
     * @param string $body
     * @param class-string $class
     * @param string $case
     *
     * @return void
     */
    private function expectStrictFailure(ResponseFactory $strict, string $body, string $class, string $case): void
    {
        try {
            $strict->create(new Response(200, [], $body), $class);
            self::fail($case . ': strict reading accepted what lenient reading refused');
        } catch (MalformedResponseException) {
        }
    }

    /**
     * Calls every getter, recursively, so a value of the wrong type raises an error here.
     *
     * @param mixed $value
     * @param int $depth
     *
     * @return void
     */
    private static function assertReadable(mixed $value, int $depth = 0): void
    {
        if (\is_array($value) && $depth < 4) {
            foreach ($value as $item) {
                self::assertReadable($item, $depth + 1);
            }

            return;
        }
        if (!\is_object($value) || $value instanceof \DateTimeInterface || $value instanceof \UnitEnum || $depth >= 4) {
            return;
        }
        foreach ((new \ReflectionClass($value))->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->isStatic() || $method->getNumberOfRequiredParameters() > 0
                || !\preg_match('/^(get|is|has)/', $method->getName())
            ) {
                continue;
            }
            self::assertReadable($method->invoke($value), $depth + 1);
        }
    }
}
