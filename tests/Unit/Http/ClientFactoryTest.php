<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Unit\Http;

use PHPUnit\Framework\TestCase;
use Polkurier\Webservice\Config;
use Polkurier\Webservice\Dto\Request\AuthorizationMessage;
use Polkurier\Webservice\Http\ClientFactory;
use Polkurier\Webservice\Test\Unit\Webservice\SwitchingConfigProvider;

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

    private static function config(string $apiUrl): Config
    {
        return new Config(
            authorization: new AuthorizationMessage(token: 't', login: '1'),
            platform: 'phpunit',
            platformVersion: '1.0.0',
            apiUrl: $apiUrl,
        );
    }
}
