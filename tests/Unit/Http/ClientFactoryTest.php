<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Unit\Http;

use GuzzleHttp\Client;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Polkurier\Webservice\Config;
use Polkurier\Webservice\Dto\Request\AuthorizationMessage;
use Polkurier\Webservice\Http\ClientFactory;
use Polkurier\Webservice\Test\Unit\Webservice\SwitchingConfigProvider;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\UriInterface;

#[CoversClass(ClientFactory::class)]
final class ClientFactoryTest extends TestCase
{
    public function testTheSameConfigurationReusesOneClient(): void
    {
        $factory = new ClientFactory(new SwitchingConfigProvider(self::config('https://a.example')));

        self::assertSame($factory->create(), $factory->create());
    }

    public function testAnotherApiUrlGetsItsOwnClient(): void
    {
        $provider = new SwitchingConfigProvider(self::config('https://a.example'));
        $factory = new ClientFactory($provider);
        $first = $factory->create();

        $provider->current = self::config('https://b.example');

        self::assertNotSame($first, $factory->create());
    }

    public function testRedirectsAreNotFollowed(): void
    {
        self::assertFalse(self::option(self::build(), 'allow_redirects'));
    }

    /**
     * A client that raised on a non-2xx status would hide the body from
     * {@see \Polkurier\Webservice\Http\Response\ResponseFactory}.
     */
    public function testErrorStatusesDoNotRaise(): void
    {
        self::assertFalse(self::option(self::build(), 'http_errors'));
    }

    public function testTheBaseUriComesFromTheConfiguration(): void
    {
        $baseUri = self::option(self::build('https://c.example/api'), 'base_uri');

        self::assertInstanceOf(UriInterface::class, $baseUri);
        self::assertSame('https://c.example/api', (string)$baseUri);
    }

    public function testTheTimeoutComesFromTheConfiguration(): void
    {
        self::assertSame(45, self::option(self::build(apiTimeout: 45), 'timeout'));
    }

    private static function build(string $apiUrl = 'https://a.example', int $apiTimeout = 30): ClientInterface
    {
        $provider = new SwitchingConfigProvider(self::config($apiUrl, $apiTimeout));

        return (new ClientFactory($provider))->create();
    }

    private static function option(ClientInterface $client, string $option): mixed
    {
        self::assertInstanceOf(Client::class, $client);

        return $client->getConfig($option);
    }

    private static function config(string $apiUrl, int $apiTimeout = 30): Config
    {
        return new Config(
            authorization: new AuthorizationMessage(token: 't', login: '1'),
            platform: 'phpunit',
            platformVersion: '1.0.0',
            apiUrl: $apiUrl,
            apiTimeout: $apiTimeout,
        );
    }
}
