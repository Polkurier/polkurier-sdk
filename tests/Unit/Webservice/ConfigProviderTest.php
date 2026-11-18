<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Unit\Webservice;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Polkurier\Webservice\Api\ConfigInterface;
use Polkurier\Webservice\Config;
use Polkurier\Webservice\Dto\Request\AuthorizationMessage;
use Polkurier\Webservice\Dto\Request\Order\GetOrdersMessage;
use Polkurier\Webservice\Exception\ValidationException;
use Polkurier\Webservice\Exception\WebserviceException;
use Polkurier\Webservice\Http\Request\RequestMessage;
use Polkurier\Webservice\PolkurierWebservice;
use Polkurier\Webservice\Testing\RecordingClient;

/**
 * A configuration provider is asked on every call, so one client can serve several tenants.
 */
#[CoversNothing]
final class ConfigProviderTest extends TestCase
{
    public function testEachCallUsesTheConfigurationCurrentAtThatTime(): void
    {
        $provider = new SwitchingConfigProvider(self::config('11111', 'shop-a'));
        $client = new RecordingClient('{"status":"success","response":[]}');
        $sdk = new PolkurierWebservice(
            config: self::config('00000', 'unused'),
            configProvider: $provider,
            clientFactory: $client,
        );

        $sdk->addresses()->getAddresses();
        self::assertSame('11111', self::login($client));
        self::assertStringStartsWith('shop-a/', $client->getLastRequest()?->getHeaderLine('User-Agent') ?? '');

        $provider->current = self::config('22222', 'shop-b');

        $sdk->addresses()->getAddresses();
        self::assertSame('22222', self::login($client));
        self::assertStringStartsWith('shop-b/', $client->getLastRequest()?->getHeaderLine('User-Agent') ?? '');
    }

    public function testPayloadValidationFollowsTheCurrentConfiguration(): void
    {
        $provider = new SwitchingConfigProvider(self::config('11111', 'shop-a', validatePayloads: false));
        $client = new RecordingClient('{"status":"success","response":[]}');
        $sdk = new PolkurierWebservice(
            config: self::config('00000', 'unused'),
            configProvider: $provider,
            clientFactory: $client,
        );
        $tooLarge = new GetOrdersMessage(pageSize: GetOrdersMessage::MAX_PAGE_SIZE + 1);

        try {
            $sdk->orders()->getOrders($tooLarge);
        } catch (WebserviceException) {
            // Whatever the stub answers, the request went out.
        }
        self::assertSame(1, $client->countRequests());

        $provider->current = self::config('11111', 'shop-a');

        $rejected = false;
        try {
            $sdk->orders()->getOrders($tooLarge);
        } catch (ValidationException) {
            $rejected = true;
        }
        self::assertTrue($rejected, 'Validation should have rejected the page size.');
        self::assertSame(1, $client->countRequests(), 'Nothing should have been sent.');
    }

    private static function config(string $login, string $platform, bool $validatePayloads = true): ConfigInterface
    {
        return new Config(
            authorization: new AuthorizationMessage(token: 'token-' . $login, login: $login),
            platform: $platform,
            platformVersion: '1.0.0',
            validatePayloads: $validatePayloads,
        );
    }

    private static function login(RecordingClient $client): mixed
    {
        $authorization = $client->readLastPayload()[RequestMessage::FIELD_AUTHORIZATION] ?? null;

        return \is_array($authorization) ? ($authorization['login'] ?? null) : null;
    }
}
