<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Unit\Webservice;

use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Polkurier\Webservice\Api\SerializerInterface;
use Polkurier\Webservice\Config;
use Polkurier\Webservice\ConfigProvider;
use Polkurier\Webservice\Dto\Request\AuthorizationMessage;
use Polkurier\Webservice\Http\Request\RequestFactory;
use Polkurier\Webservice\Http\Response\ResponseFactory;
use Polkurier\Webservice\Http\Response\ResponseFactoryInterface;
use Polkurier\Webservice\Http\Response\ResponseWrapper;
use Polkurier\Webservice\PolkurierWebservice;
use Polkurier\Webservice\Serializer\MessageSerializer;
use Polkurier\Webservice\Serializer\SerializerProvider;
use Polkurier\Webservice\Test\Support\Sample;
use Polkurier\Webservice\Testing\RecordingClient;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\NullLogger;

/**
 * Calls every method of every service and parses a response of the shape that method expects.
 *
 * The response is generated from the format the service asks the response factory for: a fully
 * populated instance of the expected class, normalized to its wire form. So every response class
 * is read back from what it would look like on the wire.
 */
#[CoversNothing]
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
        $service = self::service($accessor, $client);
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

    /**
     * The service the facade's accessor builds, wired with a response factory that answers with a
     * sample of whatever shape the method asks for.
     *
     * @param string $accessor
     * @param RecordingClient $client records the request the method sends
     *
     * @return object
     */
    private static function service(string $accessor, RecordingClient $client): object
    {
        $interface = (string)(new \ReflectionMethod(PolkurierWebservice::class, $accessor))->getReturnType();
        /** @var class-string $class */
        $class = \str_replace(
            ['Polkurier\\Webservice\\Api\\Service\\', 'Interface'],
            ['Polkurier\\Webservice\\Service\\', ''],
            $interface,
        );
        self::assertTrue(\class_exists($class), $interface . ' has no service');

        $configProvider = new ConfigProvider(new Config(
            authorization: new AuthorizationMessage(token: 'test-token', login: '20534'),
            platform: 'phpunit',
            platformVersion: '1.0.0',
            validatePayloads: false,
        ));
        $serializer = new MessageSerializer(new SerializerProvider());

        return new $class(
            new RequestFactory($serializer, $configProvider),
            new SampleResponseFactory(),
            new NullLogger(),
            $client,
            $configProvider,
        );
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
        $this->inner = new ResponseFactory(new MessageSerializer(new SerializerProvider()));
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
            $format === '' || $format === SerializerInterface::FORMAT_BOOLEAN => true,
            $format === SerializerInterface::FORMAT_RAW_ARRAY,
            $format === SerializerInterface::FORMAT_STRING_LIST => ['a' => 'b'],
            \str_ends_with($format, '[]') => [self::payload(\substr($format, 0, -2))],
            default => self::normalized($format),
        };
    }

    private static function normalized(string $class): mixed
    {
        /** @var class-string $class */
        return Sample::wire($class);
    }
}
