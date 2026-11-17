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
use Polkurier\Webservice\Dto\Response\ErrorMessage;
use Polkurier\Webservice\Dto\Response\ResponseMessage;
use Polkurier\Webservice\Dto\Response\Webhook\OrderEventMessage;
use Polkurier\Webservice\Entity\Order;
use Polkurier\Webservice\Enum\Serializer\ResponseDriftTypeEnum;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Exception\MalformedResponseException;
use Polkurier\Webservice\Http\Response\ResponseFactory;
use Polkurier\Webservice\Serializer\MessageSerializer;
use Polkurier\Webservice\Serializer\SerializerConfig;
use Polkurier\Webservice\Serializer\SerializerProvider;
use Polkurier\Webservice\Test\Support\Accessors;
use Polkurier\Webservice\Test\Support\MessageClasses;
use Polkurier\Webservice\Test\Support\Sample;
use Polkurier\Webservice\Webhook\OrderReader;

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
     * Every response class, except the order event, which has a test of its own, and the envelopes,
     * which the response factory reads before any payload.
     *
     * @return array<string, array{class-string}>
     */
    public static function responseClasses(): array
    {
        $classes = \array_filter(
            MessageClasses::responses(),
            static fn(string $class): bool => !\in_array(
                $class,
                [OrderEventMessage::class, ResponseMessage::class, ErrorMessage::class],
                true,
            ),
        );

        return \array_map(static fn(string $class): array => [$class], $classes);
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
        $lenient = new ResponseFactory(new MessageSerializer(new SerializerProvider()));
        $strict = new ResponseFactory(
            new MessageSerializer(new SerializerProvider(new SerializerConfig(strictResponses: true))),
        );

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
                Accessors::readAll($wrapper->getEnvelope()->response);
                $reported = \array_filter(
                    $wrapper->getDrift(),
                    static fn($drift) => $drift->type !== ResponseDriftTypeEnum::UnknownEnumValue,
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
        $strictConfig = new SerializerConfig(strictResponses: true);

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

                $reader = new OrderReader();
                try {
                    $read = $reader->read($body);
                    Accessors::readAll($read->event);
                } catch (InvalidArgumentException $e) {
                    self::assertStringContainsString('None of the fields', $e->getMessage(), $case);
                    continue;
                }
                $reported = \array_filter(
                    $read->drift,
                    static fn($drift) => $drift->type !== ResponseDriftTypeEnum::UnknownEnumValue,
                );

                try {
                    (new OrderReader($strictConfig))->read($body);
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
}
