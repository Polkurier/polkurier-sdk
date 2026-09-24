<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Integration;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Polkurier\Webservice\Exception\AuthorizationException;
use Polkurier\Webservice\Exception\ConnectionException;
use Polkurier\Webservice\Config;
use Polkurier\Webservice\Http\Request\AuthorizationMessage;
use Polkurier\Webservice\PolkurierWebservice;

/**
 * Exercises the SDK against a real Polkurier API.
 *
 * Skipped unless POLKURIER_API_URL is set, so the default test run stays offline:
 *
 *     POLKURIER_API_URL=https://api-sandbox.polkurier.pl vendor/bin/phpunit --group integration
 *
 * Set POLKURIER_API_LOGIN and POLKURIER_API_TOKEN as well to cover the authenticated methods;
 * without them only the unauthenticated ones run.
 */
#[Group('integration')]
#[CoversNothing]
final class LiveApiTest extends TestCase
{
    private PolkurierWebservice $sdk;

    protected function setUp(): void
    {
        $url = \getenv('POLKURIER_API_URL');
        if ($url === false || $url === '') {
            self::markTestSkipped('Set POLKURIER_API_URL to run the integration suite.');
        }

        $this->sdk = new PolkurierWebservice(new Config(
            authorization: new AuthorizationMessage(
                token: (string)(\getenv('POLKURIER_API_TOKEN') ?: ''),
                login: (string)(\getenv('POLKURIER_API_LOGIN') ?: ''),
            ),
            platform: 'polkurier-sdk-tests',
            platformVersion: '2.0.0-dev',
            apiUrl: $url,
        ));
    }

    /**
     * heartbeat needs no credentials, so this doubles as a reachability check.
     *
     * @throws ConnectionException
     */
    public function testHeartbeatReportsTheServerTime(): void
    {
        $response = $this->sdk->utils()->heartbeat();

        // Epoch 0 is what an unparsed or missing date decays to, so a plausible year is the
        // assertion worth making: it proves a real timestamp came back and was understood.
        self::assertGreaterThan(2020, (int)$response->getTime()->format('Y'));
    }

    /**
     * An unauthenticated call to an authenticated method must surface as an AuthorizationException
     * rather than as a generic failure — and must do so even though the API answers HTTP 200.
     */
    public function testMissingCredentialsRaiseAnAuthorizationException(): void
    {
        if (\getenv('POLKURIER_API_TOKEN')) {
            self::markTestSkipped('Credentials are configured; this case needs none.');
        }

        $this->expectException(AuthorizationException::class);

        $this->sdk->addresses()->getAddresses();
    }

    public function testListsAddressesWhenCredentialsAreConfigured(): void
    {
        $this->requireCredentials();

        $addresses = $this->sdk->addresses()->getAddresses();

        foreach ($addresses as $address) {
            self::assertNotNull($address->getId(), 'a stored address always carries its id');
        }
    }

    public function testListsOrdersAsAPagedEnvelope(): void
    {
        $this->requireCredentials();

        $list = $this->sdk->orders()->getOrders();

        self::assertLessThanOrEqual(100, $list->getPageSize());
        self::assertLessThanOrEqual($list->getPageSize(), \count($list->getResult()));
    }

    /**
     * @return void
     */
    private function requireCredentials(): void
    {
        if (!\getenv('POLKURIER_API_TOKEN') || !\getenv('POLKURIER_API_LOGIN')) {
            self::markTestSkipped('Set POLKURIER_API_LOGIN and POLKURIER_API_TOKEN for this case.');
        }
    }
}
