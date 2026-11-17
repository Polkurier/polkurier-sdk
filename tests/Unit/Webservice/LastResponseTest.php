<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Unit\Webservice;

use PHPUnit\Framework\TestCase;
use Polkurier\Webservice\Config;
use Polkurier\Webservice\Dto\Request\AuthorizationMessage;
use Polkurier\Webservice\Dto\Request\Order\GetOrdersMessage;
use Polkurier\Webservice\Enum\Address\AddressTypeEnum;
use Polkurier\Webservice\Enum\Serializer\ResponseDriftTypeEnum;
use Polkurier\Webservice\Exception\ValidationException;
use Polkurier\Webservice\Exception\WebserviceException;
use Polkurier\Webservice\PolkurierWebservice;
use Polkurier\Webservice\Testing\RecordingClient;

/**
 * What the client keeps about the last call: the HTTP response and how it differed from the SDK.
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
        self::assertSame([], $this->sdk->getLastResponseDrift());
    }

    public function testTheLastResponseAndItsUnknownValuesAreKept(): void
    {
        $this->client->willAnswerWith(self::ADDRESS_WITH_UNKNOWN_TYPE);

        $addresses = $this->sdk->addresses()->getAddresses();

        self::assertNull($addresses[0]->getType());
        self::assertSame(self::ADDRESS_WITH_UNKNOWN_TYPE, (string)$this->sdk->getLastResponse()?->getBody());

        $drift = $this->sdk->getLastResponseDrift();
        self::assertCount(1, $drift);
        self::assertSame(ResponseDriftTypeEnum::UnknownEnumValue, $drift[0]->type);
        self::assertSame(AddressTypeEnum::class, $drift[0]->expected);
        self::assertSame('SOMETHING_NEW', $drift[0]->value);
        self::assertSame('[0].type', $drift[0]->path);
    }

    public function testTheNextCallThroughAnyServiceReplacesIt(): void
    {
        $this->client->willAnswerWith(self::ADDRESS_WITH_UNKNOWN_TYPE);
        $this->sdk->addresses()->getAddresses();

        $this->client->willAnswerWith('{"status":"success","response":[]}');
        $this->sdk->bankAccounts()->getBankAccounts();

        self::assertSame('{"status":"success","response":[]}', (string)$this->sdk->getLastResponse()?->getBody());
        self::assertSame([], $this->sdk->getLastResponseDrift());
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
        self::assertSame([], $this->sdk->getLastResponseDrift());
    }
}
