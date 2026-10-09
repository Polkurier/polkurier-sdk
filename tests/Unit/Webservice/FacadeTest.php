<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Unit\Webservice;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Polkurier\Webservice\Enum\Http\ApiMethodEnum;
use Polkurier\Webservice\Exception\MalformedResponseException;
use Polkurier\Webservice\Config;
use Polkurier\Webservice\Http\Request\AuthorizationMessage;
use Polkurier\Webservice\Http\Request\RequestMessage;
use Polkurier\Webservice\PolkurierWebservice;
use Polkurier\Webservice\Testing\RecordingClient;

/**
 * Exercises the facade and everything it wires.
 *
 * A service accessor that was added but never wired throws the first time anyone calls it, and a
 * service method can be wrong in a way no signature shows — passing its payload where the response
 * format belongs, for instance, which raises a TypeError on the first request. Both are invisible
 * until something actually calls them, so every accessor is resolved here and every method that
 * needs no arguments is called against a stub transport.
 */
#[CoversNothing]
final class FacadeTest extends TestCase
{
    private RecordingClient $client;

    private PolkurierWebservice $sdk;

    protected function setUp(): void
    {
        $this->client = new RecordingClient('{"status":"success","response":[]}');
        $this->sdk = new PolkurierWebservice(
            config: new Config(
                authorization: new AuthorizationMessage(token: 'test-token', login: '12345'),
                platform: 'phpunit',
                platformVersion: '1.0.0',
            ),
            clientFactory: $this->client,
        );
    }

    /**
     * Every service accessor the facade exposes.
     *
     * @return array<string, array{string}>
     */
    public static function facadeAccessors(): array
    {
        $accessors = [];

        $methods = (new \ReflectionClass(PolkurierWebservice::class))->getMethods(\ReflectionMethod::IS_PUBLIC);
        foreach ($methods as $method) {
            $type = $method->getReturnType();
            if (
                $method->getNumberOfParameters() > 0
                || !$type instanceof \ReflectionNamedType
                || !\str_ends_with($type->getName(), 'ServiceInterface')
            ) {
                continue;
            }
            $accessors[$method->getName()] = [$method->getName()];
        }

        self::assertNotEmpty($accessors);

        return $accessors;
    }

    #[DataProvider('facadeAccessors')]
    public function testAccessorResolvesAndMemoises(string $accessor): void
    {
        $service = $this->sdk->{$accessor}();

        self::assertIsObject($service);
        self::assertSame($service, $this->sdk->{$accessor}(), 'services should be created once and reused');
    }

    /**
     * Calls every service method that takes no arguments, and checks which endpoint it reached.
     *
     * The stub answers every call with the same empty payload, which is not the shape most of
     * these endpoints promise. That is fine here: what is under test is the request, and a
     * service that rejects the reply has already sent the thing being asserted about.
     */
    public function testEveryArgumentlessServiceMethodReachesItsEndpoint(): void
    {
        $called = [];

        foreach (\array_keys(self::facadeAccessors()) as $accessor) {
            $service = $this->sdk->{$accessor}();

            foreach ((new \ReflectionClass($service))->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                if ($method->getName() === '__construct' || $method->getNumberOfRequiredParameters() > 0) {
                    continue;
                }

                try {
                    $service->{$method->getName()}();
                } catch (MalformedResponseException) {
                    // The stub payload is not this endpoint's shape; the request still went out.
                }

                $apiMethod = $this->client->lastPayload()[RequestMessage::FIELD_API_METHOD] ?? null;
                self::assertIsString(
                    $apiMethod,
                    \sprintf('%s()->%s() sent no apimethod', $accessor, $method->getName())
                );
                self::assertNotNull(
                    ApiMethodEnum::tryFrom($apiMethod),
                    \sprintf('%s()->%s() sent an unmapped apimethod "%s"', $accessor, $method->getName(), $apiMethod)
                );

                $called[$apiMethod] = true;
            }
        }

        self::assertGreaterThan(15, \count($called), 'expected the argumentless methods to be exercised');
    }
}
