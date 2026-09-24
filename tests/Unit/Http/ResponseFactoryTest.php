<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Unit\Http;

use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Polkurier\Webservice\Enum\Http\ResponseStatusEnum;
use Polkurier\Webservice\Exception\MalformedResponseException;
use Polkurier\Webservice\Response\Order;
use Polkurier\Webservice\Http\Response\ErrorMessage;
use Polkurier\Webservice\Http\Response\ResponseFactory;
use Polkurier\Webservice\Response\Order\OrderListMessage;
use Polkurier\Webservice\Serializer\SerializerProvider;

#[CoversClass(ResponseFactory::class)]
final class ResponseFactoryTest extends TestCase
{
    private ResponseFactory $factory;

    protected function setUp(): void

    {

        $this->factory = new ResponseFactory(new SerializerProvider());

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

        $list = $wrapper->getParsedResponse()->getResponse();

        self::assertInstanceOf(OrderListMessage::class, $list);
        self::assertSame(2, $list->getTotalRows());
        self::assertSame(100, $list->getPageSize());
        self::assertCount(1, $list->getResult());
        self::assertInstanceOf(Order::class, $list->getResult()[0]);
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

        self::assertSame(['Pobranie (COD)', 'Ubezpieczenie przesyłki'], $order->getCourierService());
    }

    public function testDeserializesNestedCollectionsIntoObjects(): void
    {
        $order = $this->deserializeOrder($this->orderPayload());

        self::assertInstanceOf(\Polkurier\Webservice\Response\Order\Pack::class, $order->getPacks()[0]);
        self::assertInstanceOf(\Polkurier\Webservice\Response\Order\Waybill::class, $order->getWaybills()[0]);
        self::assertInstanceOf(\Polkurier\Webservice\Response\BaseAddress::class, $order->getSender());
    }

    /**
     * Methods that only acknowledge answer with a bare `true`; there is no object to map.
     */
    public function testReadsAnAcknowledgementOnlyResponse(): void
    {
        $wrapper = $this->factory->create(
            $this->jsonResponse(['status' => 'success', 'response' => true]),
            ResponseFactory::FORMAT_BOOLEAN
        );

        self::assertSame(ResponseStatusEnum::Success, $wrapper->getParsedResponse()->getStatus());
        self::assertTrue($wrapper->getParsedResponse()->getResponse());
    }

    /**
     * The delete_* family answers with an empty array rather than `true`. Casting that would
     * report a successful delete as a failure.
     */
    public function testReadsAnEmptyArrayAcknowledgementAsSuccess(): void
    {
        $wrapper = $this->factory->create(
            $this->jsonResponse(['status' => 'success', 'response' => []]),
            ResponseFactory::FORMAT_BOOLEAN
        );

        self::assertTrue($wrapper->getParsedResponse()->getResponse());
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
            \Polkurier\Webservice\Response\CourierPoint::class
        );

        self::assertNull($wrapper->getParsedResponse()->getResponse());
    }

    /**
     * The same empty array still means an empty collection when a collection was asked for.
     */
    public function testReadsAnEmptyCollectionPayloadAsAnEmptyArray(): void
    {
        $wrapper = $this->factory->create(
            $this->jsonResponse(['status' => 'success', 'response' => []]),
            \Polkurier\Webservice\Response\CourierPoint::class . '[]'
        );

        self::assertSame([], $wrapper->getParsedResponse()->getResponse());
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
            ResponseFactory::FORMAT_BOOLEAN
        )->getParsedResponse();

        self::assertInstanceOf(ErrorMessage::class, $parsed);
        self::assertSame(4, $parsed->getStatusCode());
        self::assertSame('EMPTY_AUTH_DATA', $parsed->getErrorCode());
    }

    /**
     * The API runs its error payload through array_filter(), so a status_code of 0 and an empty
     * error_code are dropped from the JSON entirely. Neither may be assumed present.
     */
    public function testParsesAnErrorEnvelopeWithBothCodesOmitted(): void
    {
        $parsed = $this->factory->create(
            $this->jsonResponse(['status' => 'error', 'response' => 'Coś poszło nie tak']),
            ResponseFactory::FORMAT_BOOLEAN
        )->getParsedResponse();

        self::assertInstanceOf(ErrorMessage::class, $parsed);
        self::assertNull($parsed->getStatusCode());
        self::assertNull($parsed->getErrorCode());
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

        $this->factory->create(new Response($httpStatus, [], $body), ResponseFactory::FORMAT_BOOLEAN);
    }

    public function testRejectsAnEnvelopeWithoutAStatusField(): void
    {
        $this->expectException(MalformedResponseException::class);

        $this->factory->create($this->jsonResponse(['response' => []]), ResponseFactory::FORMAT_BOOLEAN);
    }

    public function testRejectsAnUnknownStatusValue(): void
    {
        $this->expectException(MalformedResponseException::class);

        $this->factory->create(
            $this->jsonResponse(['status' => 'partial', 'response' => []]),
            ResponseFactory::FORMAT_BOOLEAN
        );
    }

    /**
     * The exception must carry the response so the caller can inspect the real status line.
     */
    public function testMalformedResponseExceptionCarriesTheHttpResponse(): void
    {
        try {
            $this->factory->create(new Response(503, [], 'maintenance'), ResponseFactory::FORMAT_BOOLEAN);
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
        )->getParsedResponse()->getResponse()[0];

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
