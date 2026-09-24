<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Unit\Webservice;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Polkurier\Webservice\Enum\Http\ApiMethodEnum;
use Polkurier\Webservice\Enum\Order\PackTypeEnum;
use Polkurier\Webservice\Enum\Order\ShipmentTypeEnum;
use Polkurier\Webservice\Config;
use Polkurier\Webservice\Http\Request\AuthorizationMessage;
use Polkurier\Webservice\Http\Request\RequestMessage;
use Polkurier\Webservice\Request\Address\DeleteMessage as AddressDeleteMessage;
use Polkurier\Webservice\Request\Common\DeleteMessage;
use Polkurier\Webservice\Request\Courier\GetPickupTimeMessage;
use Polkurier\Webservice\Request\Courier\GetPointMessage;
use Polkurier\Webservice\Request\Courier\PickupMessage;
use Polkurier\Webservice\Request\Order\CreateOrderMessage;
use Polkurier\Webservice\Request\Order\GetDocumentMessage;
use Polkurier\Webservice\Request\Order\GetStatusMessage;
use Polkurier\Webservice\Request\Order\OrderAddressMessage;
use Polkurier\Webservice\Request\Order\OrderIdMessage;
use Polkurier\Webservice\Request\Order\PackMessage;
use Polkurier\Webservice\Request\System\ValidateCouponMessage;
use Polkurier\Webservice\PolkurierWebservice;
use Polkurier\Webservice\Testing\RecordingClient;

/**
 * Drives every service method through a stub transport.
 *
 * Several service methods used to pass their payload where the response format belonged, which
 * meant they raised a TypeError or an ArgumentCountError on the very first call — the failure was
 * total, but nothing exercised them. This suite calls each one so that class of mistake cannot
 * return unnoticed.
 */
#[CoversNothing]
final class ServiceCallTest extends TestCase
{
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

    /**
     * Each case: a callable that performs the call, the method the API should see, and a canned
     * response envelope for it.
     *
     * @return array<string, array{\Closure(PolkurierWebservice): mixed, ApiMethodEnum, string}>
     */
    public static function serviceCalls(): array
    {
        $ok = static fn(string $response): string => '{"status":"success","response":' . $response . '}';

        return [
            'heartbeat' => [
                static fn(PolkurierWebservice $s) => $s->utils()->heartbeat(),
                ApiMethodEnum::Heartbeat,
                $ok('{"time":"2026-09-23T09:10:13+02:00"}'),
            ],
            'available carriers' => [
                static fn(PolkurierWebservice $s) => $s->courier()->availableCarriers(),
                ApiMethodEnum::AvailableCarriers,
                $ok('[]'),
            ],
            'pickup courier' => [
                static fn(PolkurierWebservice $s) => $s->courier()->pickupCourier(
                    new PickupMessage(courier: 'UPS', shipFrom: '61-001')
                ),
                ApiMethodEnum::PickupCourier,
                $ok('{"pickupdate":true,"time":[{"timefrom":"10:00","timeto":"14:00"}]}'),
            ],
            'courier pickup time' => [
                static fn(PolkurierWebservice $s) => $s->courier()->getCourierPickupTime(
                    new GetPickupTimeMessage(courier: 'UPS', shipFrom: '61-001')
                ),
                ApiMethodEnum::GetCourierPickupTime,
                $ok('[{"pickupdate":"2026-09-24","time":[{"timefrom":"10:00","timeto":"14:00"}]}]'),
            ],
            'supported countries' => [
                static fn(PolkurierWebservice $s) => $s->courier()->getSupportedCountries(),
                ApiMethodEnum::GetSupportedCountries,
                $ok('[{"id":"PL","name":"Polska","is_ue":true,"is_default":true,"provinces":[]}]'),
            ],
            'courier point by id' => [
                static fn(PolkurierWebservice $s) => $s->courier()->getCourierPoint('POZ01A', ['INPOST']),
                ApiMethodEnum::GetCourierPoint,
                $ok('{"id":"POZ01A","city":"Poznań"}'),
            ],
            'courier point search' => [
                static fn(PolkurierWebservice $s) => $s->courier()->findCourierPoints(
                    new GetPointMessage(couriers: ['INPOST'], searchQuery: 'Poznań')
                ),
                ApiMethodEnum::GetCourierPoint,
                $ok('[{"id":"POZ01A","city":"Poznań"}]'),
            ],
            'get addresses' => [
                static fn(PolkurierWebservice $s) => $s->addresses()->getAddresses(),
                ApiMethodEnum::GetAddresses,
                $ok('[]'),
            ],
            'delete address' => [
                static fn(PolkurierWebservice $s) => $s->addresses()->deleteAddress(
                    new AddressDeleteMessage(id: '42', type: \Polkurier\Webservice\Enum\Address\AddressTypeEnum::Sender)
                ),
                ApiMethodEnum::DeleteAddress,
                $ok('true'),
            ],
            'get bank accounts' => [
                static fn(PolkurierWebservice $s) => $s->bankAccounts()->getBankAccounts(),
                ApiMethodEnum::GetBankAccounts,
                $ok('[]'),
            ],
            'delete bank account' => [
                static fn(PolkurierWebservice $s) => $s->bankAccounts()->deleteBankAccount(
                    new \Polkurier\Webservice\Request\BankAccount\DeleteBankAccountMessage(id: '7')
                ),
                ApiMethodEnum::DeleteBankAccount,
                $ok('true'),
            ],
            'get pack templates' => [
                static fn(PolkurierWebservice $s) => $s->packTemplates()->getPackTemplates(),
                ApiMethodEnum::GetPackTemplates,
                $ok('[]'),
            ],
            'delete pack template' => [
                static fn(PolkurierWebservice $s) => $s->packTemplates()->deletePackTemplates(
                    new DeleteMessage(id: '3')
                ),
                ApiMethodEnum::DeletePackTemplate,
                $ok('true'),
            ],
            'map token' => [
                static fn(PolkurierWebservice $s) => $s->geo()->getMapToken(),
                ApiMethodEnum::GetMapToken,
                $ok('{"token":"abc"}'),
            ],
            'get orders' => [
                static fn(PolkurierWebservice $s) => $s->orders()->getOrders(),
                ApiMethodEnum::GetOrders,
                $ok('{"totalrows":0,"totalpages":0,"currentpage":1,"pagesize":100,"result":[]}'),
            ],
            'cancel order' => [
                static fn(PolkurierWebservice $s) => $s->orders()->cancelOrder(
                    new OrderIdMessage(orderNo: '20534-7674')
                ),
                ApiMethodEnum::CancelOrder,
                $ok('{"cancellation":true}'),
            ],
            'get label' => [
                static fn(PolkurierWebservice $s) => $s->orders()->getLabel(
                    new GetDocumentMessage(orderNo: ['20534-7674'])
                ),
                ApiMethodEnum::GetLabel,
                $ok('{"file":"JVBERi0="}'),
            ],
            'get protocol' => [
                static fn(PolkurierWebservice $s) => $s->orders()->getProtocol(
                    new GetDocumentMessage(orderNo: ['20534-7674'])
                ),
                ApiMethodEnum::GetProtocol,
                $ok('{"file":"JVBERi0="}'),
            ],
            'get status' => [
                static fn(PolkurierWebservice $s) => $s->orders()->getStatus(
                    new GetStatusMessage(orderNo: '20534-7674', tracking: true)
                ),
                ApiMethodEnum::GetStatus,
                $ok('{"status":"W przewozie","status_code":"WP","url":"https://x","tracking":[]}'),
            ],
            'multi pickup availability' => [
                static fn(PolkurierWebservice $s) => $s->orders()->isMultiPickupAvailable(),
                ApiMethodEnum::IsMultiPickupAvailable,
                $ok('{"isMultiPickupAvailable":true}'),
            ],
            'system info' => [
                static fn(PolkurierWebservice $s) => $s->system()->getSystemInfo(),
                ApiMethodEnum::GetSystemInfo,
                $ok('{"verification_transfer_amount_pln":2,"security":{"user_pin_min_length":4}}'),
            ],
            'couriers messages' => [
                static fn(PolkurierWebservice $s) => $s->system()->getCouriersMessages(),
                ApiMethodEnum::GetCouriersMessages,
                $ok('[{"courier":"FEDEX","message":"x","type":"ALERT","severity":"WARNING","hide_timeout":4}]'),
            ],
            'validate coupon' => [
                static fn(PolkurierWebservice $s) => $s->system()->validateCouponCode(
                    new ValidateCouponMessage(couponCode: 'SPRING10')
                ),
                ApiMethodEnum::ValidateCouponCode,
                $ok('{"code":"SPRING10","valid":false,"valid_for_order":false,"couriers":null,"description":""}'),
            ],
            'create order' => [
                static fn(PolkurierWebservice $s) => $s->orders()->createOrder(self::createOrderMessage()),
                ApiMethodEnum::CreateOrder,
                $ok('{"order_number":"20534-7674","label":["1Z999"],"price_net":10.0}'),
            ],
            'order valuation' => [
                static fn(PolkurierWebservice $s) => $s->orders()->orderValuation(self::createOrderMessage()),
                ApiMethodEnum::OrderValuation,
                $ok('[]'),
            ],
        ];
    }

    /**
     * @param \Closure(PolkurierWebservice): mixed $call
     */
    #[DataProvider('serviceCalls')]
    public function testServiceCallReachesTheApiAndParses(
        \Closure $call,
        ApiMethodEnum $expectedMethod,
        string $cannedResponse,
    ): void {
        $this->client->willAnswerWith($cannedResponse);

        $call($this->sdk);

        self::assertSame(1, $this->client->requestCount());

        $payload = $this->client->lastPayload();
        self::assertSame($expectedMethod->value, $payload[RequestMessage::FIELD_API_METHOD] ?? null);
    }

    /**
     * Every call except heartbeat must carry the configured credentials.
     */
    public function testAuthenticatedCallsCarryTheConfiguredCredentials(): void
    {
        $this->client->willAnswerWith('{"status":"success","response":[]}');

        $this->sdk->addresses()->getAddresses();

        $authorization = $this->client->lastPayload()[RequestMessage::FIELD_AUTHORIZATION] ?? null;

        self::assertIsArray($authorization);
        self::assertSame('20534', $authorization['login'] ?? null);
        self::assertSame('test-token', $authorization['token'] ?? null);
    }

    /**
     * heartbeat is the one method that must go out unauthenticated.
     */
    public function testHeartbeatIsSentWithoutCredentials(): void
    {
        $this->client->willAnswerWith('{"status":"success","response":{"time":"2026-09-23T09:10:13+02:00"}}');

        $this->sdk->utils()->heartbeat();

        self::assertArrayNotHasKey(RequestMessage::FIELD_AUTHORIZATION, $this->client->lastPayload());
    }

    /**
     * The services are created once and reused.
     */
    public function testServicesAreMemoised(): void
    {
        self::assertSame($this->sdk->orders(), $this->sdk->orders());
        self::assertSame($this->sdk->courier(), $this->sdk->courier());
    }

    private static function createOrderMessage(): CreateOrderMessage
    {
        $address = new OrderAddressMessage(
            person: 'Jan Kowalski',
            street: 'Długa',
            city: 'Poznań',
            postcode: '61-001',
            email: 'jan@example.com',
            phone: '500600700',
            houseNumber: '12',
        );

        return new CreateOrderMessage(
            courier: 'UPS',
            shipmentType: ShipmentTypeEnum::Box,
            packs: [new PackMessage(length: 10, width: 10, height: 10, weight: 1.5, type: PackTypeEnum::Standard)],
            sender: $address,
            recipient: $address,
        );
    }
}
