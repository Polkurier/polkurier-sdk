<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Integration;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;
use Polkurier\Webservice\Config;
use Polkurier\Webservice\Dto\Request\AuthorizationMessage;
use Polkurier\Webservice\Exception\AuthorizationException;
use Polkurier\Webservice\PolkurierWebservice;

#[Group('integration')]
#[CoversNothing]
final class UtilsTest extends IntegrationTestCase
{
    protected function needsCredentials(): bool
    {
        return false;
    }

    public function testHeartbeatReportsTheServerTime(): void
    {
        $time = $this->sdk->utils()->heartbeat()->time;

        self::assertNotNull($time);
        self::assertLessThan(300, \abs($time->getTimestamp() - \time()));
    }

    public function testTheGivenCredentialsAreChecked(): void
    {
        if (self::token() === '') {
            self::markTestSkipped('Set POLKURIER_API_LOGIN and POLKURIER_API_TOKEN.');
        }

        $this->assertReads(fn() => $this->sdk->utils()->testAuthApi(
            new AuthorizationMessage(token: self::token(), login: self::login()),
        ));
    }

    public function testAWrongTokenRaisesAnAuthorizationException(): void
    {
        $this->expectException(AuthorizationException::class);

        $this->sdk->utils()->testAuthApi(new AuthorizationMessage(token: 'wrong-token', login: self::login() ?: '1'));
    }

    public function testMissingCredentialsRaiseAnAuthorizationException(): void
    {
        $sdk = new PolkurierWebservice(new Config(
            authorization: new AuthorizationMessage(token: '', login: ''),
            platform: 'polkurier-sdk-tests',
            platformVersion: '2.0.0',
            apiUrl: (string)\getenv('POLKURIER_API_URL'),
            validatePayloads: false,
        ));

        $this->expectException(AuthorizationException::class);
        $sdk->addresses()->getAddresses();
    }
}
