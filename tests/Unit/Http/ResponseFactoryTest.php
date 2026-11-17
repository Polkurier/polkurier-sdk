<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Unit\Http;

use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Polkurier\Webservice\Api\SerializerInterface;
use Polkurier\Webservice\Dto\Response\Carrier;
use Polkurier\Webservice\Dto\Response\CourierPoint;
use Polkurier\Webservice\Dto\Response\ErrorMessage;
use Polkurier\Webservice\Dto\Response\Invoice\InvoiceSummaryListMessage;
use Polkurier\Webservice\Dto\Response\MoneyBox\MoneyBoxPayoutListMessage;
use Polkurier\Webservice\Dto\Response\Order\OrderListMessage;
use Polkurier\Webservice\Dto\Response\Order\Waybill;
use Polkurier\Webservice\Dto\Response\Ticket\TicketListMessage;
use Polkurier\Webservice\Dto\Response\TwoFactorToken;
use Polkurier\Webservice\Entity\Order;
use Polkurier\Webservice\Entity\Order\Address as OrderAddress;
use Polkurier\Webservice\Entity\Order\PackItem;
use Polkurier\Webservice\Entity\PackTemplate;
use Polkurier\Webservice\Enum\Courier\PointFunctionEnum;
use Polkurier\Webservice\Enum\Http\ResponseStatusEnum;
use Polkurier\Webservice\Enum\Order\AdditionalServiceEnum;
use Polkurier\Webservice\Enum\Order\PackTypeEnum;
use Polkurier\Webservice\Enum\Order\ShipmentTypeEnum;
use Polkurier\Webservice\Enum\Serializer\ResponseDriftTypeEnum;
use Polkurier\Webservice\Enum\TwoFactorToken\TwoFactorTransportTypeEnum;
use Polkurier\Webservice\Exception\MalformedResponseException;
use Polkurier\Webservice\Http\Response\ResponseFactory;
use Polkurier\Webservice\Serializer\MessageSerializer;
use Polkurier\Webservice\Serializer\ResponseDrift;
use Polkurier\Webservice\Serializer\SerializerProvider;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerException;

#[CoversClass(ResponseFactory::class)]
final class ResponseFactoryTest extends TestCase
{
    private ResponseFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new ResponseFactory(new MessageSerializer(new SerializerProvider()));
    }

    public function testDeserializesAnOrderListEnvelope(): void
    {
        $wrapper = $this->factory->create(
            $this->jsonResponse([
                'status' => 'success',
                'response' => [
                    'totalrows' => 2,
                    'totalpages' => 1,
                    'currentpage' => 1,
                    'pagesize' => 100,
                    'result' => [$this->orderPayload()],
                ],
            ]),
            OrderListMessage::class
        );

        $list = $wrapper->getEnvelope()->response;

        self::assertInstanceOf(OrderListMessage::class, $list);
        self::assertSame(2, $list->totalRows);
        self::assertSame(100, $list->pageSize);
        self::assertCount(1, $list->result);
        self::assertInstanceOf(Order::class, $list->result[0]);
    }

    /**
     * Filtered by order number, the list method answers with the order itself. It reads as a page
     * with that one order, not as an empty page.
     */
    public function testASingleOrderWhereAPageIsExpectedReadsAsAOneRowPage(): void
    {
        $wrapper = $this->factory->create(
            $this->jsonResponse(['status' => 'success', 'response' => $this->orderPayload()]),
            OrderListMessage::class
        );

        $list = $wrapper->getEnvelope()->response;

        self::assertInstanceOf(OrderListMessage::class, $list);
        self::assertSame(1, $list->totalRows);
        self::assertCount(1, $list->result);
        self::assertSame($this->orderPayload()['number'], $list->result[0]->getNumber());
    }

    /**
     * A one-ticket answer carries no paging keys at all.
     */
    public function testASingleTicketWhereAPageIsExpectedReadsAsAOneRowPage(): void
    {
        $list = $this->factory->create(
            $this->jsonResponse(['status' => 'success', 'response' => [
                'id' => 'ade25fef', 'number' => '202610-000009', 'subject' => 'Współpraca', 'status' => 'OPENED',
                'has_employee_responses' => false, 'is_read' => false, 'date_created' => '2026-10-06T14:05:00+02:00',
                'employee_name' => '', 'orders' => [], 'complaints' => [], 'invoices' => [], 'messages' => [],
            ]]),
            TicketListMessage::class
        )->getEnvelope()->response;

        self::assertInstanceOf(TicketListMessage::class, $list);
        self::assertSame(1, $list->totalRows);
        self::assertSame('202610-000009', $list->result[0]->number);
    }

    /**
     * Asked for one payout number, the payout list answers with the payout itself.
     */
    public function testASinglePayoutWhereAPageIsExpectedReadsAsAOneRowPage(): void
    {
        $list = $this->factory->create(
            $this->jsonResponse(['status' => 'success', 'response' => [
                'number' => 'WP/2026/000123', 'date' => '2026-09-01T12:00:00+02:00', 'request_type' => 'MANUAL',
                'payout_type' => 'ACCOUNT', 'bank_account_number' => '34102010260000000000000001',
                'company' => '', 'person' => '', 'street' => '', 'postcode' => '', 'city' => '',
                'fee' => 2.46, 'fee_nett' => 2, 'fee_vat_rate' => 23, 'fee_payment_type' => 'INVOICE',
                'fee_payment_status' => 'UNPAID', 'status' => 'PENDING', 'amount' => 420.0,
                'to_pay_amount' => 420.0, 'paid_amount' => 0, 'compensation_amount' => 0, 'items_count' => 1,
                'is_cancellable' => true,
            ]]),
            MoneyBoxPayoutListMessage::class
        )->getEnvelope()->response;

        self::assertInstanceOf(MoneyBoxPayoutListMessage::class, $list);
        self::assertSame(1, $list->totalRows);
        self::assertSame(1, $list->totalPages);
        self::assertSame('WP/2026/000123', $list->result[0]->number);
    }

    /**
     * Listed without the packs, items and files flags, orders come without those keys, and pickup
     * dates can be null or an empty string.
     */
    public function testAnOrderFromAListWithoutOptionalKeysReads(): void
    {
        $payload = $this->orderPayload();
        unset($payload['packs'], $payload['items'], $payload['files']);
        $payload['pickup'] = ['pickupdate' => null, 'pickuptimefrom' => null, 'pickuptimeto' => null];
        $payload['delivered_date'] = '';

        $order = $this->deserializeOrder($payload);

        self::assertSame([], $order->getPacks());
        self::assertSame([], $order->getItems());
        self::assertNull($order->getPickup()?->getPickupDate());
        self::assertNull($order->getDeliveredDate());
    }

    /**
     * A two-factor token says how its code was sent and how else it can be. The other methods can
     * come back as an object with gaps in its keys, and a method type the SDK doesn't know reads
     * as null.
     */
    public function testATwoFactorTokenReadsItsTransportMethods(): void
    {
        $method = static fn(string $id, string $type): array => [
            'id' => $id, 'name' => '+48 *** *** 123', 'type' => $type, 'description' => 'Kod', 'type_name' => 'SMS',
        ];
        $wrapper = $this->factory->create(
            $this->jsonResponse(['status' => 'success', 'response' => [
                'id' => 't1',
                'phone_number' => '',
                'operation_title' => 'Wypłata',
                'operation_number' => 1,
                'operation_date' => '2026-10-05T10:00:00+02:00',
                'sent_at' => null,
                'expires_at' => '2026-10-05T10:05:00+02:00',
                'transport_method' => $method('m1', 'SMS'),
                'available_methods' => ['1' => $method('m2', 'EMAIL'), '3' => $method('m3', 'SOMETHING_NEW')],
            ]]),
            TwoFactorToken::class
        );

        $token = $wrapper->getEnvelope()->response;

        self::assertInstanceOf(TwoFactorToken::class, $token);
        $transport = $token->transportMethod;
        self::assertNotNull($transport);
        self::assertSame(TwoFactorTransportTypeEnum::Sms, $transport->type);
        self::assertSame('+48 *** *** 123', $transport->name);
        $methods = $token->availableMethods;
        self::assertSame(['m2', 'm3'], \array_map(static fn($m) => $m->id, $methods));
        self::assertSame(TwoFactorTransportTypeEnum::Email, $methods[0]->type);
        self::assertNull($methods[1]->type);
    }

    /**
     * The API emits `courierservice` without an underscore. The SDK read `courier_service` for a
     * long time and silently dropped the field.
     */
    public function testReadsTheCarrierServicesUnderTheKeyTheApiActuallySends(): void
    {
        $order = $this->deserializeOrder(
            ['courierservice' => ['Pobranie (COD)', 'Ubezpieczenie przesyłki']] + $this->orderPayload()
        );

        self::assertSame(['Pobranie (COD)', 'Ubezpieczenie przesyłki'], $order->getCourierServiceNames());
    }

    public function testAnOrderAddressReadsTheStreetSplitIntoItsParts(): void
    {
        $street = ['streetname' => 'Długa', 'housenumber' => '1', 'flatnumber' => '2'];
        $sender = $street + $this->addressPayload('Jan Kowalski');

        $order = $this->deserializeOrder(['sender' => $sender] + $this->orderPayload());

        $address = $order->getSender();
        self::assertNotNull($address);
        self::assertSame('Długa', $address->getStreetName());
        self::assertSame('1', $address->getHouseNumber());
        self::assertSame('2', $address->getFlatNumber());
    }

    /**
     * A cover address comes without an email address.
     */
    public function testAnOrderWithACoverAddressReads(): void
    {
        $cover = [
            'company' => 'Sklep', 'person' => 'Jan Kowalski', 'street' => 'Rynek 1', 'streetname' => 'Rynek',
            'housenumber' => '1', 'flatnumber' => '', 'postcode' => '61-001', 'city' => 'Poznań',
            'phone' => '500600700', 'country' => 'PL', 'point_id' => '',
        ];

        $order = $this->deserializeOrder(['cover_address' => $cover] + $this->orderPayload());

        $address = $order->getCoverAddress();
        self::assertNotNull($address);
        self::assertSame('Rynek 1', $address->getStreet());
        self::assertNull($address->getEmail());
    }

    public function testAnInvoiceSummaryReadsItsMediaType(): void
    {
        $summary = $this->factory->create(
            $this->jsonResponse(['status' => 'success', 'response' => [
                'filename' => 'zestawienie.csv', 'type' => 'text/csv', 'file' => \base64_encode("a;b\n"),
            ]]),
            InvoiceSummaryListMessage::class
        )->getEnvelope()->response;

        self::assertInstanceOf(InvoiceSummaryListMessage::class, $summary);
        self::assertSame('text/csv', $summary->fileType);
        self::assertSame("a;b\n", $summary->file);
    }

    /**
     * A carrier's pickup fields carry 2 flags, `status` and `required`, each read as sent.
     */
    public function testEachPickupFlagReadsAsSent(): void
    {
        $carriers = $this->factory->create(
            $this->jsonResponse(['status' => 'success', 'response' => [[
                'servicecode' => 'INPOST_PACZKOMAT_ECONOMY', 'name' => 'InPost', 'foreign_shipments' => false,
                'additional_data' => ['pickup' => [
                    'pickupdate' => ['status' => true, 'description' => ''],
                    'pickuptimeto' => ['required' => true, 'description' => ''],
                ]],
            ]]]),
            Carrier::class . '[]'
        )->getEnvelope()->response;

        self::assertIsArray($carriers);
        self::assertInstanceOf(Carrier::class, $carriers[0]);
        $pickup = $carriers[0]->additionalData?->pickup;
        self::assertNotNull($pickup);
        self::assertTrue($pickup->pickupDate?->status);
        self::assertFalse($pickup->pickupDate->required);
        self::assertTrue($pickup->pickupTimeTo?->required);
        self::assertFalse($pickup->pickupTimeTo->status);
        self::assertNull($pickup->pickupTimeFrom);
    }

    /**
     * A value added to the API after this SDK version was released must not make the whole list
     * unreadable. It reads as null in a single field and is left out of a list.
     */
    public function testAnUnknownEnumValueReadsAsNullAndTheRestOfTheResponseStillReads(): void
    {
        $template = ['id' => 'aB3', 'length' => 60, 'width' => 40, 'height' => 30, 'weight' => 10, 'name' => 'Nowy'];
        $wrapper = $this->factory->create(
            $this->jsonResponse([
                'status' => 'success',
                'response' => [
                    $template + [
                        'type' => 'SOMETHING_NEW',
                        'shipmenttype' => 'crate',
                        'services' => ['ROD', 'SOMETHING_NEW', 'TIRES'],
                    ],
                    $template + ['type' => 'ST', 'shipmenttype' => 'box', 'services' => []],
                ],
            ]),
            PackTemplate::class . '[]'
        );

        /** @var PackTemplate[] $templates */
        $templates = $wrapper->getEnvelope()->response;

        self::assertCount(2, $templates);
        self::assertNull($templates[0]->getType());
        self::assertNull($templates[0]->getShipmentType());
        self::assertSame(
            [AdditionalServiceEnum::ReturnOfDocuments, AdditionalServiceEnum::Tires],
            $templates[0]->getServices(),
        );
        self::assertSame(PackTypeEnum::Standard, $templates[1]->getType());
        self::assertSame(ShipmentTypeEnum::Box, $templates[1]->getShipmentType());

        $unknown = \array_map(
            static fn(ResponseDrift $u) => [$u->getExpected(), $u->getValue(), $u->getPath()],
            $wrapper->getDrift(),
        );
        self::assertEqualsCanonicalizing([
            [PackTypeEnum::class, 'SOMETHING_NEW', '[0].type'],
            [ShipmentTypeEnum::class, 'crate', '[0].shipmentType'],
            [AdditionalServiceEnum::class, 'SOMETHING_NEW', '[0].services[1]'],
        ], $unknown);
    }

    public function testAnEmptyEnumValueReadsAsNullWithoutBeingReported(): void
    {
        $wrapper = $this->factory->create(
            $this->jsonResponse([
                'status' => 'success',
                'response' => [
                    [
                        'id' => 'aB4',
                        'length' => 60,
                        'width' => 40,
                        'height' => 30,
                        'weight' => 10,
                        'name' => 'Stary',
                        'shipmenttype' => '',
                    ],
                ],
            ]),
            PackTemplate::class . '[]'
        );

        /** @var PackTemplate[] $templates */
        $templates = $wrapper->getEnvelope()->response;

        self::assertNull($templates[0]->getShipmentType());
        self::assertSame([], $wrapper->getDrift());
    }

    /**
     * A list can come back as a JSON object with numeric keys. It's still read as a plain list.
     */
    public function testAListOfEnumValuesSentAsAnObjectReadsAsAList(): void
    {
        $wrapper = $this->factory->create(
            $this->jsonResponse([
                'status' => 'success',
                'response' => [
                    [
                        'length' => 60,
                        'width' => 40,
                        'height' => 30,
                        'weight' => 10,
                        'name' => 'Nowy',
                        'shipmenttype' => 'box',
                        'services' => ['1' => 'ROD', '3' => 'TIRES'],
                    ],
                ],
            ]),
            PackTemplate::class . '[]'
        );

        /** @var PackTemplate[] $templates */
        $templates = $wrapper->getEnvelope()->response;

        self::assertSame(
            [AdditionalServiceEnum::ReturnOfDocuments, AdditionalServiceEnum::Tires],
            $templates[0]->getServices(),
        );
    }

    /**
     * Only responses are lenient. Read without the response context, an unknown value still fails.
     */
    public function testAnUnknownEnumValueFailsOutsideAResponse(): void
    {
        $this->expectException(SerializerException::class);

        (new SerializerProvider())->getSerializer()->deserialize(
            (string)\json_encode(
                ['length' => 60, 'width' => 40, 'height' => 30, 'weight' => 10, 'type' => 'SOMETHING_NEW'],
            ),
            PackItem::class,
            'json'
        );
    }

    /**
     * A point's functions are an open list: carriers add their own. Only the known ones are kept,
     * each once, and each of the others is reported at its index in the list the API sent.
     */
    public function testAPointKeepsItsKnownFunctionsOnceAndReportsTheRestByIndex(): void
    {
        $wrapper = $this->factory->create(
            $this->jsonResponse([
                'status' => 'success',
                'response' => ['id' => 'KRA423M', 'functions' => ['cod', 'parcel_collect_247', 'send', 'cod']],
            ]),
            CourierPoint::class
        );

        $point = $wrapper->getEnvelope()->response;

        self::assertInstanceOf(CourierPoint::class, $point);
        self::assertSame([PointFunctionEnum::Cod, PointFunctionEnum::Send], $point->functions);
        self::assertEquals(
            [new ResponseDrift(
                ResponseDriftTypeEnum::UnknownEnumValue,
                'functions[1]',
                'parcel_collect_247',
                PointFunctionEnum::class,
            )],
            $wrapper->getDrift(),
        );
    }

    /**
     * A nested object the order doesn't have can come back as `[]` or null. Either reads as null.
     */
    public function testAnAbsentNestedObjectSentAsAnEmptyArrayReadsAsNull(): void
    {
        $order = $this->deserializeOrder(
            ['COD' => [], 'pickup' => [], 'cover_address' => null, 'sender' => [], 'recipient' => []]
                + $this->orderPayload()
        );

        self::assertNull($order->getSender());
        self::assertNull($order->getRecipient());
        self::assertNull($order->getCod());
        self::assertNull($order->getPickup());
        self::assertNull($order->getCoverAddress());
    }

    public function testDeserializesNestedCollectionsIntoObjects(): void
    {
        $order = $this->deserializeOrder($this->orderPayload());

        self::assertInstanceOf(PackItem::class, $order->getPacks()[0]);
        self::assertInstanceOf(Waybill::class, $order->getWaybills()[0]);
        self::assertInstanceOf(OrderAddress::class, $order->getSender());
    }

    /**
     * Methods that only acknowledge answer with a bare `true`; there is no object to map.
     */
    public function testReadsAnAcknowledgementOnlyResponse(): void
    {
        $wrapper = $this->factory->create(
            $this->jsonResponse(['status' => 'success', 'response' => true]),
            SerializerInterface::FORMAT_BOOLEAN
        );

        self::assertSame(ResponseStatusEnum::Success, $wrapper->getEnvelope()->status);
        self::assertTrue($wrapper->getEnvelope()->response);
    }

    /**
     * The delete_* family answers with an empty array rather than `true`. Casting that would
     * report a successful delete as a failure.
     */
    public function testReadsAnEmptyArrayAcknowledgementAsSuccess(): void
    {
        $wrapper = $this->factory->create(
            $this->jsonResponse(['status' => 'success', 'response' => []]),
            SerializerInterface::FORMAT_BOOLEAN
        );

        self::assertTrue($wrapper->getEnvelope()->response);
    }

    /**
     * "Nothing found" is reported as an empty array even where the documented shape is a single
     * object. Mapping that onto the object leaves its typed properties uninitialised, so the
     * failure surfaces at the first getter instead of here.
     */
    public function testReadsAnEmptyObjectPayloadAsNull(): void
    {
        $wrapper = $this->factory->create(
            $this->jsonResponse(['status' => 'success', 'response' => []]),
            CourierPoint::class
        );

        self::assertNull($wrapper->getEnvelope()->response);
    }

    /**
     * The same empty array still means an empty collection when a collection was asked for.
     */
    public function testReadsAnEmptyCollectionPayloadAsAnEmptyArray(): void
    {
        $wrapper = $this->factory->create(
            $this->jsonResponse(['status' => 'success', 'response' => []]),
            CourierPoint::class . '[]'
        );

        self::assertSame([], $wrapper->getEnvelope()->response);
    }

    public function testParsesAnErrorEnvelope(): void
    {
        $parsed = $this->factory->create(
            $this->jsonResponse([
                'status' => 'error',
                'status_code' => 4,
                'error_code' => 'EMPTY_AUTH_DATA',
                'response' => 'Brak danych do autoryzacji',
            ]),
            SerializerInterface::FORMAT_BOOLEAN
        )->getEnvelope();

        self::assertInstanceOf(ErrorMessage::class, $parsed);
        self::assertSame(4, $parsed->statusCode);
        self::assertSame('EMPTY_AUTH_DATA', $parsed->errorCode);
    }

    /**
     * The API runs its error payload through array_filter(), so a status_code of 0 and an empty
     * error_code are dropped from the JSON entirely. Neither may be assumed present.
     */
    public function testParsesAnErrorEnvelopeWithBothCodesOmitted(): void
    {
        $parsed = $this->factory->create(
            $this->jsonResponse(['status' => 'error', 'response' => 'Coś poszło nie tak']),
            SerializerInterface::FORMAT_BOOLEAN
        )->getEnvelope();

        self::assertInstanceOf(ErrorMessage::class, $parsed);
        self::assertNull($parsed->statusCode);
        self::assertNull($parsed->errorCode);
    }

    /**
     * @return array<string, array{string, int}>
     */
    public static function nonEnvelopeBodies(): array
    {
        return [
            'proxy error page' => ['<html><body>502 Bad Gateway</body></html>', 502],
            'empty body' => ['', 200],
            'truncated json' => ['{"status":"suc', 200],
            'json scalar' => ['"nope"', 200],
        ];
    }

    #[DataProvider('nonEnvelopeBodies')]
    public function testRejectsABodyThatIsNotAnEnvelope(string $body, int $httpStatus): void
    {
        $this->expectException(MalformedResponseException::class);

        $this->factory->create(new Response($httpStatus, [], $body), SerializerInterface::FORMAT_BOOLEAN);
    }

    public function testRejectsAnEnvelopeWithoutAStatusField(): void
    {
        $this->expectException(MalformedResponseException::class);

        $this->factory->create($this->jsonResponse(['response' => []]), SerializerInterface::FORMAT_BOOLEAN);
    }

    /**
     * A rejected contact form answers with the status in capitals.
     */
    public function testReadsAStatusSentInCapitals(): void
    {
        $wrapper = $this->factory->create(
            $this->jsonResponse([
                'status' => 'ERROR', 'status_code' => 26, 'error_code' => 'VALIDATION_ERROR',
                'response' => 'Należy wyrazić zgodę [marketing_consent]',
                'validation_errors' => ['marketing_consent' => ['Należy wyrazić zgodę']],
                'nonce' => 'eyJ0eXAi…',
            ]),
            SerializerInterface::FORMAT_BOOLEAN
        );

        $error = $wrapper->getEnvelope();
        self::assertInstanceOf(ErrorMessage::class, $error);
        self::assertSame(['marketing_consent' => ['Należy wyrazić zgodę']], $error->validationErrors);
    }

    public function testRejectsAnUnknownStatusValue(): void
    {
        $this->expectException(MalformedResponseException::class);

        $this->factory->create(
            $this->jsonResponse(['status' => 'partial', 'response' => []]),
            SerializerInterface::FORMAT_BOOLEAN
        );
    }

    /**
     * The exception must carry the response so the caller can inspect the real status line.
     */
    public function testMalformedResponseExceptionCarriesTheHttpResponse(): void
    {
        try {
            $this->factory->create(new Response(503, [], 'maintenance'), SerializerInterface::FORMAT_BOOLEAN);
            self::fail('Expected a MalformedResponseException.');
        } catch (MalformedResponseException $e) {
            self::assertSame(503, $e->getResponse()->getStatusCode());
            self::assertStringContainsString('maintenance', $e->getMessage());
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function deserializeOrder(array $payload): Order
    {
        $order = $this->factory->create(
            $this->jsonResponse(['status' => 'success', 'response' => [$payload]]),
            Order::class . '[]'
        )->getEnvelope()->response[0];

        self::assertInstanceOf(Order::class, $order);

        return $order;
    }

    /**
     * @return array<string, mixed>
     */
    private function orderPayload(): array
    {
        return [
            'number' => '20534-7674',
            'status' => 'Oczekuje na odbiór',
            'status_code' => 'OC',
            'date' => '2026-01-01T10:00:00+01:00',
            'status_date' => '2026-01-02T10:00:00+01:00',
            'shipmenttype' => 'box',
            'courier' => 'UPS',
            'sender' => $this->addressPayload('Jan Kowalski'),
            'recipient' => $this->addressPayload('Ola Nowak'),
            'waybills' => [['number' => '1Z999', 'is_default' => true]],
            'items' => [],
            'packs' => [[
                'type' => 'ST', 'weight' => 1.5, 'width' => 10, 'height' => 10,
                'length' => 10, 'amount' => 1, 'price_net' => 1.0, 'price_gross' => 1.23,
            ]],
            'price_net' => 10.0,
            'price_gross' => 12.3,
            'unpaid_amount' => 0.0,
            'is_paid' => true,
            'has_invoice' => false,
            'pickup' => [],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function addressPayload(string $person): array
    {
        return [
            'person' => $person, 'street' => 'Długa 1', 'city' => 'Poznań',
            'postcode' => '61-001', 'email' => 'test@example.com', 'phone' => '500600700',
            'country' => 'PL',
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function jsonResponse(array $payload): Response
    {
        return new Response(200, ['Content-Type' => 'application/json'], (string)\json_encode($payload));
    }
}
