<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Unit\Http;

use GuzzleHttp\Psr7\NoSeekStream;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Polkurier\Webservice\Api\ParsedResponseInterface;
use Polkurier\Webservice\Dto\Response\Order\OrderListMessage;
use Polkurier\Webservice\Dto\Response\System\SystemInfoMessage;
use Polkurier\Webservice\Entity\Address;
use Polkurier\Webservice\Http\Response\ResponseFactory;
use Polkurier\Webservice\Serializer\MessageSerializer;
use Polkurier\Webservice\Serializer\SerializerConfig;
use Polkurier\Webservice\Serializer\SerializerProvider;
use Polkurier\Webservice\Test\Support\Accessors;
use Polkurier\Webservice\Test\Support\MessageClasses;
use Polkurier\Webservice\Test\Support\Responses;
use Polkurier\Webservice\Test\Support\Sample;

#[CoversClass(ResponseFactory::class)]
final class RawResponseTest extends TestCase
{
    /**
     * The classes the service methods return. Read from the services themselves, because a method
     * that returns an entity declares an interface on {@see \Polkurier\Webservice\Api\Service}.
     *
     * @return array<string, array{class-string}>
     */
    public static function returnedClasses(): array
    {
        $classes = [];
        foreach (\glob(\dirname(__DIR__, 3) . '/src/Service/*Service.php') ?: [] as $file) {
            $service = 'Polkurier\\Webservice\\Service\\' . \basename($file, '.php');
            self::assertTrue(\class_exists($service));
            foreach ((new \ReflectionClass($service))->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                foreach (self::namedTypes($method->getReturnType()) as $class) {
                    if (\class_exists($class)) {
                        $classes[$class] = [$class];
                    }
                }
            }
        }
        self::assertNotEmpty($classes);

        return $classes;
    }

    /**
     * The class names a return type is made of, an intersection type included.
     *
     * @param \ReflectionType|null $type
     *
     * @return list<class-string>
     */
    private static function namedTypes(?\ReflectionType $type): array
    {
        if ($type instanceof \ReflectionNamedType) {
            /** @var list<class-string> */
            return $type->isBuiltin() ? [] : [$type->getName()];
        }

        if ($type instanceof \ReflectionIntersectionType || $type instanceof \ReflectionUnionType) {
            $names = [];
            foreach ($type->getTypes() as $part) {
                $names = [...$names, ...self::namedTypes($part)];
            }

            return $names;
        }

        return [];
    }

    /**
     * @param class-string $class
     *
     * @return void
     */
    #[DataProvider('returnedClasses')]
    public function testEveryReturnedClassKeepsTheRawResponse(string $class): void
    {
        self::assertTrue(\is_subclass_of($class, ParsedResponseInterface::class));

        // The response factory passes the body to this parameter by name.
        $parameters = (new \ReflectionClass($class))->getConstructor()?->getParameters() ?? [];
        $last = $parameters[\count($parameters) - 1] ?? null;
        self::assertSame('rawResponse', $last?->getName());
        self::assertSame('?string', (string)$last->getType());
        self::assertTrue($last->isDefaultValueAvailable());
        self::assertNull($last->getDefaultValue());
    }

    public function testOnlyReturnedClassesKeepTheRawResponse(): void
    {
        $returned = \array_keys(self::returnedClasses());
        foreach (MessageClasses::responses() as $class) {
            if (\is_subclass_of($class, ParsedResponseInterface::class)) {
                self::assertContains($class, $returned, $class . ' is never returned by a service method.');
            }
        }
    }

    /**
     * @param class-string $class
     *
     * @return void
     */
    #[DataProvider('returnedClasses')]
    public function testTheReturnedObjectKeepsTheBodyByteForByte(string $class): void
    {
        // Whitespace, escapes and a float spelling that decoding and encoding again would change.
        $body = "  \n{\n  \"status\" : \"success\",\n  \"extra\": 1.10,\n  \"escaped\": \"a\\u0105\\/\",\n"
            . '  "response": ' . \json_encode(Sample::wire($class), \JSON_PRETTY_PRINT) . "\n}\n";

        $read = Responses::readBody($class, $body)->getEnvelope()->response;

        self::assertInstanceOf(ParsedResponseInterface::class, $read);
        self::assertSame($body, $read->getRawResponse());
        foreach (self::nestedIn($read) as $nested) {
            self::assertNull($nested->getRawResponse(), $nested::class . ' inside ' . $class);
        }
    }

    public function testListItemsDontKeepTheBody(): void
    {
        $body = '{"status":"success","response":[' . \json_encode(Sample::wire(Address::class)) . ']}';

        $addresses = Responses::readBody(Address::class . '[]', $body)->getEnvelope()->response;

        self::assertIsArray($addresses);
        self::assertInstanceOf(Address::class, $addresses[0]);
        self::assertNull($addresses[0]->getRawResponse());
    }

    public function testAnEmptyPageKeepsTheBody(): void
    {
        $body = '{"status":"success","response":[]}';

        $page = Responses::readBody(OrderListMessage::class, $body)->getEnvelope()->response;

        self::assertInstanceOf(OrderListMessage::class, $page);
        self::assertSame($body, $page->getRawResponse());
    }

    public function testTheBodyIsReadFromTheStartOfTheStream(): void
    {
        $body = self::systemInfoBody();
        $stream = Utils::streamFor($body);
        $stream->getContents();

        $info = (new ResponseFactory(new MessageSerializer(new SerializerProvider())))
            ->create(new Response(200, [], $stream), SystemInfoMessage::class)
            ->getEnvelope()
            ->response;

        self::assertInstanceOf(SystemInfoMessage::class, $info);
        self::assertSame($body, $info->getRawResponse());
    }

    public function testABodyThatCantBeReadAgainIsStillKept(): void
    {
        $body = self::systemInfoBody();
        $response = new Response(200, [], new NoSeekStream(Utils::streamFor($body)));

        $info = (new ResponseFactory(new MessageSerializer(new SerializerProvider())))
            ->create($response, SystemInfoMessage::class)
            ->getEnvelope()
            ->response;

        self::assertInstanceOf(SystemInfoMessage::class, $info);
        self::assertSame('', (string)$response->getBody());
        self::assertSame($body, $info->getRawResponse());
    }

    public function testStrictReadingKeepsTheBody(): void
    {
        $body = self::systemInfoBody();

        $info = Responses::readBody(
            SystemInfoMessage::class,
            $body,
            new SerializerConfig(strictResponses: true),
        )->getEnvelope()->response;

        self::assertInstanceOf(SystemInfoMessage::class, $info);
        self::assertSame($body, $info->getRawResponse());
    }

    public function testAnObjectBuiltInCodeHasNoBody(): void
    {
        self::assertNull((new OrderListMessage())->getRawResponse());
    }

    public function testTheBodySurvivesSerialization(): void
    {
        $body = self::systemInfoBody();
        $info = Responses::readBody(SystemInfoMessage::class, $body)->getEnvelope()->response;

        $copy = \unserialize(\serialize($info));

        self::assertInstanceOf(SystemInfoMessage::class, $copy);
        self::assertSame($body, $copy->getRawResponse());
    }

    /**
     * @return string
     */
    private static function systemInfoBody(): string
    {
        return (string)\json_encode(['status' => 'success', 'response' => Sample::wire(SystemInfoMessage::class)]);
    }

    /**
     * Every response object reachable through the public getters, the given one excluded.
     *
     * @param object $root
     *
     * @return list<ParsedResponseInterface>
     */
    private static function nestedIn(object $root): array
    {
        $seen = [$root];
        $queue = [$root];
        $found = [];
        while ($queue !== []) {
            $object = \array_shift($queue);
            foreach ((new \ReflectionObject($object))->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                if ($method->isStatic()
                    || $method->getNumberOfRequiredParameters() > 0
                    || \preg_match(Accessors::PATTERN, $method->getName()) !== 1
                ) {
                    continue;
                }
                $values = $method->invoke($object);
                foreach (\is_array($values) ? $values : [$values] as $value) {
                    if (\is_object($value) && !\in_array($value, $seen, true)) {
                        $seen[] = $value;
                        $queue[] = $value;
                        if ($value instanceof ParsedResponseInterface) {
                            $found[] = $value;
                        }
                    }
                }
            }
        }

        return $found;
    }
}
