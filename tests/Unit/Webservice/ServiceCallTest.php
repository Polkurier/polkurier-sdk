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
use Polkurier\Webservice\Exception\ValidationException;
use Polkurier\Webservice\Http\Request\RequestMessage;
use Polkurier\Webservice\Request\Address\DeleteMessage as AddressDeleteMessage;
use Polkurier\Webservice\Request\Common\DeleteMessage;
use Polkurier\Webservice\Request\Courier\GetPickupTimeMessage;
use Polkurier\Webservice\Request\Courier\GetPointMessage;
use Polkurier\Webservice\Request\Courier\PickupMessage;
use Polkurier\Webservice\Request\Order\CreateOrderMessage;
use Polkurier\Webservice\Request\Order\GetDocumentMessage;
use Polkurier\Webservice\Request\Order\GetStatusMessage;
use Polkurier\Webservice\Request\Order\MultiPickupAvailabilityRequestMessage;
use Polkurier\Webservice\Request\Order\OrderAddressMessage;
use Polkurier\Webservice\Request\Order\OrderIdMessage;
use Polkurier\Webservice\Request\Order\OrderValuationMessage;
use Polkurier\Webservice\Request\Order\PackMessage;
use Polkurier\Webservice\Request\System\ValidateCouponMessage;
use Polkurier\Webservice\Request\Ticket\CreateTicketMessageMessage;
use Polkurier\Webservice\Request\Ticket\TicketIdMessage;
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
                static fn(PolkurierWebservice $s) => $s->orders()->isMultiPickupAvailable(
                    new MultiPickupAvailabilityRequestMessage(
                        courier: 'DPD',
                        packs: [new PackMessage(length: 40, width: 30, height: 20, weight: 0.55)],
                    )
                ),
                ApiMethodEnum::IsMultiPickupAvailable,
                $ok('{"isMultiPickupAvailable":true}'),
            ],
            'ticket messages, a list of timeline entries' => [
                static fn(PolkurierWebservice $s) => $s->tickets()->getTicketMessages(new TicketIdMessage(ticketId: 'b379362e')),
                ApiMethodEnum::GetTicketMessages,
                $ok('[{"id":"94a7","type":"MESSAGE","date":"2026-10-06T14:00:36+02:00","origin":"EMPLOYEE","author_name":"Max","content":"Zamknięte.","date_read":null,"files":[]},'
                    . '{"id":"01fb","type":"ACTION","date":"2026-10-06T14:00:35+02:00","origin":"EMPLOYEE","author_name":"Max","content":"Zamknięto zgłoszenie","date_read":"2026-10-06T14:00:35+02:00"}]'),
            ],
            'ticket reply, the new timeline entry' => [
                static fn(PolkurierWebservice $s) => $s->tickets()->createTicketMessage(
                    new CreateTicketMessageMessage(ticketId: 'b379362e', content: 'Dziękuję')
                ),
                ApiMethodEnum::CreateTicketMessage,
                $ok('{"id":"54a8","type":"MESSAGE","date":"2026-10-06T14:01:00+02:00","origin":"CUSTOMER","author_name":"Max","content":"Dziękuję","date_read":null,"files":[]}'),
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
            'order valuation for one carrier' => [
                static fn(PolkurierWebservice $s) => $s->orders()->orderValuation(self::createOrderMessage(), 'DPD'),
                ApiMethodEnum::OrderValuation,
                $ok('{"servicecode":"DPD","netprice":14.5,"grossprice":17.84,"shipment":true,"available":true}'),
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
     * The configured platform and version travel in the User-Agent header.
     */
    public function testRequestsCarryTheUserAgent(): void
    {
        $this->client->willAnswerWith('{"status":"success","response":[]}');

        $this->sdk->addresses()->getAddresses();

        $request = $this->client->lastRequest();
        self::assertNotNull($request);
        self::assertMatchesRegularExpression(
            '~^phpunit/1\.0\.0 polkurier-sdk/\d+\.\d+\.\d+$~',
            $request->getHeaderLine('User-Agent'),
        );
    }

    /**
     * The services are created once and reused.
     */
    public function testServicesAreMemoised(): void
    {
        self::assertSame($this->sdk->orders(), $this->sdk->orders());
        self::assertSame($this->sdk->courier(), $this->sdk->courier());
    }

    /**
     * An account that has never started verification gets an empty response, reported as null.
     */
    public function testAccountVerificationInfoIsNullWhenNothingWasStarted(): void
    {
        $this->client->willAnswerWith('{"status":"success","response":[]}');

        self::assertNull($this->sdk->accountVerification()->getInfo());
    }

    public function testAListLeavesOutAnEntrySentAsAnEmptyObject(): void
    {
        $this->client->willAnswerWith('{"status":"success","response":['
            . '{"id":"94a7","type":"MESSAGE","date":"2026-10-06T14:00:36+02:00","origin":"EMPLOYEE",'
            . '"author_name":"Max","content":"Zamknięte.","date_read":null,"files":[]},{}]}');

        $timeline = $this->sdk->tickets()->getTicketMessages(new TicketIdMessage(ticketId: 'b379362e'));

        self::assertCount(1, $timeline);
        self::assertSame('94a7', $timeline[0]->getId());
    }

    public function testAValuationForOneCarrierSendsTheOrderWithTheCarrierNextToIt(): void
    {
        $this->client->willAnswerWith('{"status":"success","response":'
            . '{"servicecode":"DPD","netprice":14.5,"grossprice":17.84,"conditional_price_nett":0,'
            . '"conditional_price_gross":0,"promotion_nett":0,"promotion_gross":0,"rebate_nett":0,'
            . '"rebate_gross":0,"shipment":true,"available":true,"unavailable_message":""}}');

        $quotes = $this->sdk->orders()->orderValuation(self::createOrderMessage(), 'DPD');

        $data = $this->client->lastPayload()[RequestMessage::FIELD_DATA];
        self::assertIsArray($data);
        self::assertSame('DPD', $data[OrderValuationMessage::FIELD_RETURN_VALUATIONS]);
        self::assertSame('UPS', $data[CreateOrderMessage::FIELD_COURIER]);
        self::assertArrayHasKey(CreateOrderMessage::FIELD_SENDER, $data);
        self::assertArrayNotHasKey(OrderValuationMessage::FIELD_ORDER, $data);
        self::assertCount(1, $quotes);
        self::assertSame('DPD', $quotes[0]->getServiceCode());
        self::assertSame('', $quotes[0]->getServiceName());
        self::assertSame(14.5, $quotes[0]->getNetPrice());
    }

    public function testAnOrderPricedForOneCarrierIsRejectedUnderItsOwnFieldNames(): void
    {
        $order = new CreateOrderMessage(
            courier: 'UPS',
            shipmentType: ShipmentTypeEnum::Box,
            packs: [new PackMessage(length: 10, width: 10, height: 10, weight: 1.5, type: PackTypeEnum::Standard)],
            sender: new OrderAddressMessage(
                person: 'Jan Kowalski',
                street: 'Długa',
                city: 'Poznań',
                postcode: '61001',
                email: 'jan@example.com',
                phone: '500600700',
                houseNumber: '12',
            ),
            recipient: self::createOrderMessage()->getRecipient(),
        );

        try {
            $this->sdk->orders()->orderValuation($order, 'DPD');
            self::fail('Expected a rejection.');
        } catch (ValidationException $e) {
            self::assertNotSame([], $e->getFieldErrors('sender.postcode'));
            self::assertSame([], $e->getFieldErrors('order.sender.postcode'));
        }
        self::assertSame(0, $this->client->requestCount());
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
