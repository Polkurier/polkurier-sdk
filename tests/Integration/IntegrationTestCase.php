<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Integration;

use PHPUnit\Framework\TestCase;
use Polkurier\Webservice\Config;
use Polkurier\Webservice\Enum\Order\PackTypeEnum;
use Polkurier\Webservice\Enum\Order\ShipmentTypeEnum;
use Polkurier\Webservice\Exception\WebserviceException;
use Polkurier\Webservice\Http\Request\AuthorizationMessage;
use Polkurier\Webservice\Http\Response\ErrorMessage;
use Polkurier\Webservice\PolkurierWebservice;
use Polkurier\Webservice\Request\Order\CreateOrderMessage;
use Polkurier\Webservice\Request\Order\OrderAddressMessage;
use Polkurier\Webservice\Request\Order\PackMessage;
use Polkurier\Webservice\Request\Order\PickupMessage;
use Polkurier\Webservice\Serializer\SerializerConfig;
use Polkurier\Webservice\Serializer\SerializerProvider;

/**
 * Runs the SDK against a real Polkurier API.
 *
 * Skipped unless POLKURIER_API_URL is set, so the default test run stays offline:
 *
 *     POLKURIER_API_URL=https://api-sandbox.polkurier.pl POLKURIER_API_LOGIN=… POLKURIER_API_TOKEN=… \
 *         composer test:integration
 *
 * Responses are read in strict mode, so a field the SDK doesn't map fails the test. Calls that
 * change data run only against the sandbox. Calls that can't be undone, need a code from a phone
 * or move money are sent with a value the API rejects, and the test checks the SDK's exception.
 */
abstract class IntegrationTestCase extends TestCase
{
    protected PolkurierWebservice $sdk;

    protected function setUp(): void
    {
        if (self::url() === '') {
            self::markTestSkipped('Set POLKURIER_API_URL to run the integration suite.');
        }
        if ($this->needsCredentials() && self::token() === '') {
            self::markTestSkipped('Set POLKURIER_API_LOGIN and POLKURIER_API_TOKEN for the authenticated methods.');
        }

        $this->sdk = self::client();
    }

    /**
     * @return bool
     */
    protected function needsCredentials(): bool
    {
        return true;
    }

    /**
     * A client reading responses in strict mode.
     *
     * @param bool $validatePayloads false to let a payload the API rejects reach it
     *
     * @return PolkurierWebservice
     */
    protected static function client(bool $validatePayloads = true): PolkurierWebservice
    {
        return new PolkurierWebservice(
            config: new Config(
                authorization: new AuthorizationMessage(token: self::token(), login: self::login()),
                platform: 'polkurier-sdk-tests',
                platformVersion: '2.0.0',
                apiUrl: self::url(),
                validatePayloads: $validatePayloads,
            ),
            serializerProvider: new SerializerProvider(new SerializerConfig(strictResponses: true)),
        );
    }

    /**
     * Skips a test that changes data unless the suite runs against the sandbox.
     *
     * @return void
     */
    protected static function requireSandbox(): void
    {
        if (\rtrim(self::url(), '/') !== Config::SANDBOX_API_URL) {
            self::markTestSkipped('Changes data, so it runs only against ' . Config::SANDBOX_API_URL . '.');
        }
    }

    /**
     * Asserts that a call completes and its response maps in strict mode.
     *
     * @param callable(): mixed $call
     *
     * @return void
     */
    protected function assertReads(callable $call): void
    {
        $call();
        $this->addToAssertionCount(1);
    }

    /**
     * Asserts that the API rejects a call with the given error code.
     *
     * @param callable(): mixed $call
     * @param string|int $code `error_code`, or `status_code` for an error that has no `error_code`
     *
     * @return ErrorMessage
     */
    protected static function assertApiError(callable $call, string|int $code): ErrorMessage
    {
        try {
            $call();
        } catch (WebserviceException $e) {
            $error = $e->getErrorMessage();
            $actual = \is_int($code) ? $error->getStatusCode() : $error->getErrorCode();
            self::assertSame($code, $actual, $e->getMessage());

            return $error;
        }
        self::fail('The API accepted the call.');
    }

    /**
     * @return \DateTimeImmutable
     */
    protected static function nextWorkday(): \DateTimeImmutable
    {
        $day = new \DateTimeImmutable('tomorrow');
        while ((int)$day->format('N') > 5) {
            $day = $day->modify('+1 day');
        }

        return $day;
    }

    /**
     * A DPD parcel from Poznań to Warsaw, collected on the next working day.
     *
     * @return CreateOrderMessage
     */
    protected static function order(): CreateOrderMessage
    {
        return new CreateOrderMessage(
            courier: 'DPD',
            shipmentType: ShipmentTypeEnum::Box,
            packs: [self::pack()],
            sender: new OrderAddressMessage(
                person: 'Jan Kowalski',
                street: 'Długa',
                city: 'Poznań',
                postcode: '61-001',
                email: 'jan.kowalski@example.com',
                phone: '500600700',
                company: 'Acme sp. z o.o.',
                houseNumber: '12',
            ),
            recipient: new OrderAddressMessage(
                person: 'Anna Nowak',
                street: 'Krucza',
                city: 'Warszawa',
                postcode: '00-001',
                email: 'anna.nowak@example.com',
                phone: '600700800',
                houseNumber: '5',
            ),
            pickup: new PickupMessage(
                pickupDate: self::nextWorkday(),
                pickupTimeFrom: self::nextWorkday()->setTime(10, 0),
                pickupTimeTo: self::nextWorkday()->setTime(14, 0),
            ),
            description: 'Books',
        );
    }

    /**
     * @return PackMessage
     */
    protected static function pack(): PackMessage
    {
        return new PackMessage(length: 30, width: 20, height: 15, weight: 2.5, type: PackTypeEnum::Standard);
    }

    /**
     * @return string
     */
    private static function url(): string
    {
        return (string)(\getenv('POLKURIER_API_URL') ?: '');
    }

    /**
     * @return string
     */
    protected static function login(): string
    {
        return (string)(\getenv('POLKURIER_API_LOGIN') ?: '');
    }

    /**
     * @return string
     */
    protected static function token(): string
    {
        return (string)(\getenv('POLKURIER_API_TOKEN') ?: '');
    }
}
