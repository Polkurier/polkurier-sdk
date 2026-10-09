<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Unit\Webservice;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Polkurier\Webservice\Config;
use Polkurier\Webservice\Http\Request\AuthorizationMessage;
use Polkurier\Webservice\Http\Response\ResponseFactory;
use Polkurier\Webservice\Http\Response\ResponseFactoryInterface;
use Polkurier\Webservice\Http\Response\ResponseWrapper;
use Polkurier\Webservice\PolkurierWebservice;
use Polkurier\Webservice\Serializer\SerializerProvider;
use Polkurier\Webservice\Test\Support\Sample;
use Polkurier\Webservice\Testing\RecordingClient;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Calls every method of every service and parses a response of the shape that method expects.
 *
 * The response is generated from the format the service asks the response factory for: a fully
 * populated instance of the expected class, normalized to its wire form. So every response class
 * is read back from what it would look like on the wire.
 */
final class EveryServiceMethodTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function serviceMethods(): array
    {
        $accessors = [];
        $methods = (new \ReflectionClass(PolkurierWebservice::class))->getMethods(\ReflectionMethod::IS_PUBLIC);
        foreach ($methods as $method) {
            $type = $method->getReturnType();
            if ($type instanceof \ReflectionNamedType && \str_ends_with($type->getName(), 'ServiceInterface')) {
                $accessors[$type->getName()] = $method->getName();
            }
        }

        $cases = [];
        /** @var array<class-string, string> $accessors */
        foreach ($accessors as $interface => $accessor) {
            foreach ((new \ReflectionClass($interface))->getMethods() as $method) {
                $cases[$accessor . '()->' . $method->getName() . '()'] = [$accessor, $method->getName()];
            }
        }
        \ksort($cases);

        return $cases;
    }

    #[DataProvider('serviceMethods')]
    public function testTheMethodParsesAResponseOfTheShapeItExpects(string $accessor, string $method): void
    {
        $client = new RecordingClient();
        $sdk = new PolkurierWebservice(
            config: new Config(
                authorization: new AuthorizationMessage(token: 'test-token', login: '20534'),
                platform: 'phpunit',
                platformVersion: '1.0.0',
                validatePayloads: false,
            ),
            clientFactory: $client,
            responseFactory: new SampleResponseFactory(),
        );

        $service = $sdk->{$accessor}();
        self::assertIsObject($service);
        $reflection = new \ReflectionMethod($service, $method);

        $arguments = \array_map(self::argument(...), $reflection->getParameters());
        $reflection->invokeArgs($service, $arguments);

        self::assertSame(1, $client->requestCount());
    }

    private static function argument(\ReflectionParameter $parameter): mixed
    {
        $type = $parameter->getType();
        $name = $type instanceof \ReflectionNamedType ? $type->getName() : 'string';

        return match (true) {
            $name === 'string' => 'x',
            $name === 'array' => ['x'],
            $name === 'int' => 1,
            $name === 'bool' => true,
            \class_exists($name) => Sample::of($name),
            default => null,
        };
    }
}

/**
 * Answers with a sample payload of whatever format the service asks for.
 */
final readonly class SampleResponseFactory implements ResponseFactoryInterface
{
    private ResponseFactory $inner;

    public function __construct()
    {
        $this->inner = new ResponseFactory(new SerializerProvider());
    }

    public function create(ResponseInterface $httpResponse, string $responseFormat): ResponseWrapper
    {
        $body = \json_encode(
            ['status' => 'success', 'response' => self::payload($responseFormat)],
            \JSON_THROW_ON_ERROR | \JSON_PRESERVE_ZERO_FRACTION,
        );

        return $this->inner->create(new Response(200, [], $body), $responseFormat);
    }

    private static function payload(string $format): mixed
    {
        return match (true) {
            $format === '' || $format === ResponseFactory::FORMAT_BOOLEAN => true,
            $format === ResponseFactory::FORMAT_RAW_ARRAY,
            $format === ResponseFactory::FORMAT_STRING_LIST => ['a' => 'b'],
            \str_ends_with($format, '[]') => [self::payload(\substr($format, 0, -2))],
            default => self::normalized($format),
        };
    }

    private static function normalized(string $class): mixed
    {
        $serializer = (new SerializerProvider())->getSerializer();
        self::assertNormalizer($serializer);

        /** @var class-string $class */
        return $serializer->normalize(Sample::of($class), 'json');
    }

    /**
     * @phpstan-assert NormalizerInterface $serializer
     */
    private static function assertNormalizer(object $serializer): void
    {
        if (!$serializer instanceof NormalizerInterface) {
            throw new \LogicException('The SDK serializer normalizes.');
        }
    }
}
