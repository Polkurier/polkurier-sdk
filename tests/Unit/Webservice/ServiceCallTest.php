<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Unit\Webservice;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Polkurier\Webservice\Config;
use Polkurier\Webservice\Dto\Request\Account\GetRegulationsMessage;
use Polkurier\Webservice\Dto\Request\Address\DeleteMessage as AddressDeleteMessage;
use Polkurier\Webservice\Dto\Request\AuthorizationMessage;
use Polkurier\Webservice\Dto\Request\BankAccount\DeleteBankAccountMessage;
use Polkurier\Webservice\Dto\Request\Common\DeleteMessage;
use Polkurier\Webservice\Dto\Request\Contact\SaveContactFormMessage;
use Polkurier\Webservice\Dto\Request\Courier\GetPickupTimeMessage;
use Polkurier\Webservice\Dto\Request\Courier\GetPointMessage;
use Polkurier\Webservice\Dto\Request\Courier\PickupMessage;
use Polkurier\Webservice\Dto\Request\MoneyBox\CalculateMoneyBoxPayoutFeeMessage;
use Polkurier\Webservice\Dto\Request\MoneyBox\GetMoneyBoxPayoutsMessage;
use Polkurier\Webservice\Dto\Request\Order\CancelOrderMessage;
use Polkurier\Webservice\Dto\Request\Order\CreateOrderMessage;
use Polkurier\Webservice\Dto\Request\Order\GetDocumentMessage;
use Polkurier\Webservice\Dto\Request\Order\GetStatusMessage;
use Polkurier\Webservice\Dto\Request\Order\MultiPickupAvailabilityRequestMessage;
use Polkurier\Webservice\Dto\Request\Order\OrderValuationMessage;
use Polkurier\Webservice\Dto\Request\System\GetCouriersMessagesMessage;
use Polkurier\Webservice\Dto\Request\System\ValidateCouponMessage;
use Polkurier\Webservice\Dto\Request\Ticket\CreateTicketMessageMessage;
use Polkurier\Webservice\Dto\Request\Ticket\TicketIdMessage;
use Polkurier\Webservice\Entity\Order;
use Polkurier\Webservice\Entity\Order\Address;
use Polkurier\Webservice\Entity\Order\PackItem;
use Polkurier\Webservice\Enum\Account\RegulationsTypeEnum;
use Polkurier\Webservice\Enum\Address\AddressTypeEnum;
use Polkurier\Webservice\Enum\Contact\ContactFormResultActionEnum;
use Polkurier\Webservice\Enum\Contact\ContactFormResultStatusEnum;
use Polkurier\Webservice\Enum\Http\ApiMethodEnum;
use Polkurier\Webservice\Enum\Order\PackTypeEnum;
use Polkurier\Webservice\Enum\Order\ShipmentTypeEnum;
use Polkurier\Webservice\Exception\ValidationException;
use Polkurier\Webservice\Http\Request\RequestMessage;
use Polkurier\Webservice\PolkurierWebservice;
use Polkurier\Webservice\Testing\RecordingClient;

/**
 * Drives every service method through a stub transport.
 *
 * A service method that passes its payload where the response format belongs raises a TypeError
 * or an ArgumentCountError on its first call. Calling each one catches that.
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
                static fn(PolkurierWebservice $s) => $s->courier()->getAvailableCarriers(),
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
                    new AddressDeleteMessage(id: '42', type: AddressTypeEnum::Sender)
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
                    new DeleteBankAccountMessage(id: '7')
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
                    new CancelOrderMessage(orderNo: '20534-7674')
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
                        packs: [new PackItem(length: 40, width: 30, height: 20, weight: 0.55)],
                    )
                ),
                ApiMethodEnum::IsMultiPickupAvailable,
                $ok('{"isMultiPickupAvailable":true}'),
            ],
            'ticket messages, a list of timeline entries' => [
                static fn(PolkurierWebservice $s) => $s->tickets()->getTicketMessages(
                    new TicketIdMessage(ticketId: 'b379362e'),
                ),
                ApiMethodEnum::GetTicketMessages,
                $ok('[{"id":"94a7","type":"MESSAGE","date":"2026-10-06T14:00:36+02:00","origin":"EMPLOYEE",'
                    . '"author_name":"Max","content":"Zamknięte.","date_read":null,"files":[]},'
                    . '{"id":"01fb","type":"ACTION","date":"2026-10-06T14:00:35+02:00","origin":"EMPLOYEE",'
                    . '"author_name":"Max","content":"Zamknięto zgłoszenie",'
                    . '"date_read":"2026-10-06T14:00:35+02:00"}]'),
            ],
            'ticket reply, the new timeline entry' => [
                static fn(PolkurierWebservice $s) => $s->tickets()->createTicketMessage(
                    new CreateTicketMessageMessage(ticketId: 'b379362e', content: 'Dziękuję')
                ),
                ApiMethodEnum::CreateTicketMessage,
                $ok('{"id":"54a8","type":"MESSAGE","date":"2026-10-06T14:01:00+02:00","origin":"CUSTOMER",'
                    . '"author_name":"Max","content":"Dziękuję","date_read":null,"files":[]}'),
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

        self::assertSame(1, $this->client->countRequests());

        $payload = $this->client->readLastPayload();
        self::assertSame($expectedMethod->value, $payload[RequestMessage::FIELD_API_METHOD] ?? null);
    }

    /**
     * Every call except heartbeat must carry the configured credentials.
     */
    public function testAuthenticatedCallsCarryTheConfiguredCredentials(): void
    {
        $this->client->willAnswerWith('{"status":"success","response":[]}');

        $this->sdk->addresses()->getAddresses();

        $authorization = $this->client->readLastPayload()[RequestMessage::FIELD_AUTHORIZATION] ?? null;

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

        self::assertArrayNotHasKey(RequestMessage::FIELD_AUTHORIZATION, $this->client->readLastPayload());
    }

    /**
     * The configured platform and version travel in the User-Agent header, followed by the SDK's
     * own version. That version is checked against the release tag when the tag is mirrored to
     * GitHub (bitbucket-pipelines.yml).
     */
    public function testRequestsCarryTheUserAgent(): void
    {
        $this->client->willAnswerWith('{"status":"success","response":[]}');

        $this->sdk->addresses()->getAddresses();

        $request = $this->client->getLastRequest();
        self::assertNotNull($request);
        self::assertMatchesRegularExpression(
            '~^phpunit/1\.0\.0 polkurier-sdk/\d+\.\d+\.\d+$~',
            $request->getHeaderLine('User-Agent'),
        );
    }

    /**
     * The API rejects any other Content-Type, so the request carries it whatever the HTTP client.
     */
    public function testRequestsCarryTheJsonContentType(): void
    {
        $this->client->willAnswerWith('{"status":"success","response":[]}');

        $this->sdk->addresses()->getAddresses();

        self::assertSame('application/json', $this->client->getLastRequest()?->getHeaderLine('Content-Type'));
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

    /**
     * Called without a message, get_regulations asks for the terms of service.
     */
    public function testRegulationsWithoutAMessageAskForTheTermsOfService(): void
    {
        $this->client->willAnswerWith('{"status":"success","response":{"id":"00000000-0000-4000-8000-000000000000",'
            . '"title":"Regulamin","content":"<p>Regulamin</p>","date":"2026-01-20T15:55:06+01:00"}}');

        $this->sdk->account()->getRegulations();

        $data = $this->client->readLastPayload()[RequestMessage::FIELD_DATA] ?? null;
        self::assertIsArray($data);
        self::assertSame(RegulationsTypeEnum::Regulations->value, $data[GetRegulationsMessage::FIELD_TYPE] ?? null);
    }

    /**
     * Some carriers refuse a pickup day with an empty response instead of the false flag.
     */
    public function testAnEmptyPickupAnswerIsARefusedDay(): void
    {
        $this->client->willAnswerWith('{"status":"success","response":[]}');

        $pickup = $this->sdk->courier()->pickupCourier(new PickupMessage(courier: 'INPOST', shipFrom: '61-001'));

        self::assertFalse($pickup->available);
        self::assertSame([], $pickup->time);
    }

    /**
     * An empty order list means everything available, which the API reads only from a missing key.
     */
    public function testAPayoutFeeForEverythingSendsNoOrderList(): void
    {
        $this->client->willAnswerWith('{"status":"success","response":{"fee":0,"fee_nett":0,"fee_vat_rate":23,'
            . '"fees":[]}}');

        $this->sdk->moneyBox()->calculatePayoutFee(new CalculateMoneyBoxPayoutFeeMessage());

        self::assertArrayNotHasKey(
            CalculateMoneyBoxPayoutFeeMessage::FIELD_ORDERS,
            $this->client->readLastPayload()[RequestMessage::FIELD_DATA] ?? [],
        );
    }

    /**
     * A form asking for the e-mailed code answers inside a successful envelope; the result carries
     * that request and the next nonce instead of reading as a plain success.
     */
    public function testAContactFormAskingForTheAuthCodeIsReturnedWithTheNextNonce(): void
    {
        $this->client->willAnswerWith('{"status":"success","response":{"status":"ERROR","action":"UPDATE_FORM",'
            . '"nonce":"n2","data":{"require_auth_code":true}}}');

        $result = $this->sdk->tickets()->saveContactForm(
            new SaveContactFormMessage(category: 'c1', formId: 'f1', nonce: 'n1', fields: ['name' => 'Jan']),
        );

        self::assertSame(ContactFormResultStatusEnum::Error, $result->status);
        self::assertSame(ContactFormResultActionEnum::UpdateForm, $result->action);
        self::assertSame('n2', $result->nonce);
        self::assertTrue($result->data?->requireAuthCode);
    }

    public function testASubmittedContactFormReturnsTheTicketNumber(): void
    {
        $this->client->willAnswerWith('{"status":"success","response":{"status":"SUCCESS",'
            . '"action":"SHOW_MESSAGE","message":"Wysłano.","data":{"ticket_number":"202609-000123"}}}');

        $result = $this->sdk->tickets()->saveContactForm(
            new SaveContactFormMessage(category: 'c1', formId: 'f1', nonce: 'n1', fields: ['name' => 'Jan']),
        );

        self::assertSame(ContactFormResultStatusEnum::Success, $result->status);
        self::assertSame('202609-000123', $result->data?->ticketNumber);
    }

    public function testCarrierNoticesCanBeNarrowedToOneCarrier(): void
    {
        $this->client->willAnswerWith('{"status":"success","response":[]}');

        $this->sdk->system()->getCouriersMessages(new GetCouriersMessagesMessage(courier: 'DPD'));

        self::assertSame(
            [GetCouriersMessagesMessage::FIELD_COURIER => 'DPD'],
            $this->client->readLastPayload()[RequestMessage::FIELD_DATA] ?? null,
        );
    }

    /**
     * A postal payout has no bank account.
     */
    public function testAPayoutWithoutABankAccountReads(): void
    {
        $this->client->willAnswerWith('{"status":"success","response":{"number":"W1","payout_type":"POSTAL",'
            . '"bank_account_number":null}}');

        $page = $this->sdk->moneyBox()->getPayouts(new GetMoneyBoxPayoutsMessage(payoutNumber: 'W1'));

        self::assertNull($page->result[0]->bankAccountNumber);
    }

    public function testAListLeavesOutAnEntrySentAsAnEmptyObject(): void
    {
        $this->client->willAnswerWith('{"status":"success","response":['
            . '{"id":"94a7","type":"MESSAGE","date":"2026-10-06T14:00:36+02:00","origin":"EMPLOYEE",'
            . '"author_name":"Max","content":"Zamknięte.","date_read":null,"files":[]},{}]}');

        $timeline = $this->sdk->tickets()->getTicketMessages(new TicketIdMessage(ticketId: 'b379362e'));

        self::assertCount(1, $timeline);
        self::assertSame('94a7', $timeline[0]->id);
    }

    public function testAValuationForOneCarrierSendsTheOrderWithTheCarrierNextToIt(): void
    {
        $this->client->willAnswerWith('{"status":"success","response":'
            . '{"servicecode":"DPD","netprice":14.5,"grossprice":17.84,"conditional_price_nett":0,'
            . '"conditional_price_gross":0,"promotion_nett":0,"promotion_gross":0,"rebate_nett":0,'
            . '"rebate_gross":0,"shipment":true,"available":true,"unavailable_message":""}}');

        $quotes = $this->sdk->orders()->orderValuation(self::createOrderMessage(), 'DPD');

        $data = $this->client->readLastPayload()[RequestMessage::FIELD_DATA];
        self::assertIsArray($data);
        self::assertSame('DPD', $data[OrderValuationMessage::FIELD_RETURN_VALUATIONS]);
        self::assertSame('UPS', $data[Order::FIELD_COURIER]);
        self::assertArrayHasKey(Order::FIELD_SENDER, $data);
        self::assertCount(1, $quotes);
        self::assertSame('DPD', $quotes[0]->serviceCode);
        self::assertSame('', $quotes[0]->serviceName);
        self::assertSame(14.5, $quotes[0]->netPrice);
    }

    public function testAnOrderPricedForOneCarrierIsRejectedUnderItsOwnFieldNames(): void
    {
        $order = new CreateOrderMessage(new Order(
            courier: 'UPS',
            shipmentType: ShipmentTypeEnum::Box,
            packs: [new PackItem(length: 10, width: 10, height: 10, weight: 1.5, type: PackTypeEnum::Standard)],
            sender: new Address(
                person: 'Jan Kowalski',
                street: 'Długa',
                city: 'Poznań',
                postcode: '61001',
                email: 'jan@example.com',
                phone: '500600700',
                houseNumber: '12',
            ),
            recipient: self::createOrderMessage()->order->getRecipient(),
        ));

        try {
            $this->sdk->orders()->orderValuation($order, 'DPD');
            self::fail('Expected a rejection.');
        } catch (ValidationException $e) {
            self::assertNotSame([], $e->getFieldErrors('sender.postcode'));
            self::assertSame([], $e->getFieldErrors('order.sender.postcode'));
        }
        self::assertSame(0, $this->client->countRequests());
    }

    private static function createOrderMessage(): CreateOrderMessage
    {
        $address = new Address(
            person: 'Jan Kowalski',
            street: 'Długa',
            city: 'Poznań',
            postcode: '61-001',
            email: 'jan@example.com',
            phone: '500600700',
            houseNumber: '12',
        );

        return new CreateOrderMessage(new Order(
            courier: 'UPS',
            shipmentType: ShipmentTypeEnum::Box,
            packs: [new PackItem(length: 10, width: 10, height: 10, weight: 1.5, type: PackTypeEnum::Standard)],
            sender: $address,
            recipient: $address,
        ));
    }
}
