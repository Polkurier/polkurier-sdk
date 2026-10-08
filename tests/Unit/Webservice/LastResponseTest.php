<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Unit\Webservice;

use PHPUnit\Framework\TestCase;
use Polkurier\Webservice\Config;
use Polkurier\Webservice\Enum\Address\AddressTypeEnum;
use Polkurier\Webservice\Exception\ValidationException;
use Polkurier\Webservice\Exception\WebserviceException;
use Polkurier\Webservice\Http\Request\AuthorizationMessage;
use Polkurier\Webservice\PolkurierWebservice;
use Polkurier\Webservice\Request\Order\GetOrdersMessage;
use Polkurier\Webservice\Testing\RecordingClient;

/**
 * What the client keeps about the last call: the HTTP response and the enum values it couldn't read.
 */
final class LastResponseTest extends TestCase
{
    private const string ADDRESS_WITH_UNKNOWN_TYPE = '{"status":"success","response":[{"id":"1","type":"SOMETHING_NEW",'
        . '"name":"Magazyn","person":"Jan Kowalski","street":"Długa","city":"Poznań","postcode":"61-001",'
        . '"email":"jan@example.com","phone":"500600700","housenumber":"12"}]}';

    private RecordingClient $client;

    private PolkurierWebservice $sdk;

    protected function setUp(): void
    {
        $this->client = new RecordingClient();
        $this->sdk = new PolkurierWebservice(
            config: new Config(
                authorization: new AuthorizationMessage(token: 'test-token', login: '20534'),
                platform: 'phpunit',
                platformVersion: '1.0.0',
            ),
            clientFactory: $this->client,
        );
    }

    public function testNothingIsKeptBeforeTheFirstCall(): void
    {
        self::assertNull($this->sdk->getLastResponse());
        self::assertSame([], $this->sdk->getLastUnknownEnumValues());
    }

    public function testTheLastResponseAndItsUnknownValuesAreKept(): void
    {
        $this->client->willAnswerWith(self::ADDRESS_WITH_UNKNOWN_TYPE);

        $addresses = $this->sdk->addresses()->getAddresses();

        self::assertNull($addresses[0]->getType());
        self::assertSame(self::ADDRESS_WITH_UNKNOWN_TYPE, (string)$this->sdk->getLastResponse()?->getBody());

        $unknown = $this->sdk->getLastUnknownEnumValues();
        self::assertCount(1, $unknown);
        self::assertSame(AddressTypeEnum::class, $unknown[0]->getEnum());
        self::assertSame('SOMETHING_NEW', $unknown[0]->getValue());
        self::assertSame('[0].type', $unknown[0]->getPath());
    }

    public function testTheNextCallThroughAnyServiceReplacesIt(): void
    {
        $this->client->willAnswerWith(self::ADDRESS_WITH_UNKNOWN_TYPE);
        $this->sdk->addresses()->getAddresses();

        $this->client->willAnswerWith('{"status":"success","response":[]}');
        $this->sdk->bankAccounts()->getBankAccounts();

        self::assertSame('{"status":"success","response":[]}', (string)$this->sdk->getLastResponse()?->getBody());
        self::assertSame([], $this->sdk->getLastUnknownEnumValues());
    }

    public function testAnErrorResponseIsKept(): void
    {
        $this->client->willAnswerWith('{"status":"error","response":"Nieznany błąd"}');

        try {
            $this->sdk->addresses()->getAddresses();
            self::fail('The error response should have been raised.');
        } catch (WebserviceException) {
        }

        self::assertSame(
            '{"status":"error","response":"Nieznany błąd"}',
            (string)$this->sdk->getLastResponse()?->getBody(),
        );
    }

    public function testACallThatSendsNothingClearsIt(): void
    {
        $this->client->willAnswerWith(self::ADDRESS_WITH_UNKNOWN_TYPE);
        $this->sdk->addresses()->getAddresses();

        try {
            $this->sdk->orders()->getOrders(new GetOrdersMessage(pageSize: GetOrdersMessage::MAX_PAGE_SIZE + 1));
            self::fail('Validation should have rejected the page size.');
        } catch (ValidationException) {
        }

        self::assertNull($this->sdk->getLastResponse());
        self::assertSame([], $this->sdk->getLastUnknownEnumValues());
    }
}
