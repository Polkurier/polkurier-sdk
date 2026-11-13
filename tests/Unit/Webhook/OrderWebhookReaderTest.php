<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Unit\Webhook;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Polkurier\Webservice\Entity\Order\Order;
use Polkurier\Webservice\Enum\Webhook\OrderWebhookTypeEnum;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Serializer\ResponseDrift;
use Polkurier\Webservice\Serializer\SerializerConfig;
use Polkurier\Webservice\Serializer\SerializerProvider;
use Polkurier\Webservice\Test\Support\Sample;
use Polkurier\Webservice\Webhook\OrderWebhookReader;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * An order event reads as Polkurier posts it: form-encoded, every value a string, null values and
 * empty lists left out, and the hash last, made with the recipe the Polkurier panel gives.
 */
#[CoversNothing]
final class OrderWebhookReaderTest extends TestCase
{
    private const string SECRET = 'webhook-secret';

    public function testItReadsTheTypeAndTheOrderAsGetOrdersWouldHaveGivenIt(): void
    {
        $order = self::order();
        $reader = new OrderWebhookReader();
        $event = $reader->read(self::body(['type' => 'UPDATED', 'data' => $order]));

        self::assertSame(OrderWebhookTypeEnum::Updated, $event->getType());
        self::assertEquals(self::fromJson($order), $event->getOrder());
        self::assertSame([], $reader->getLastDrift());
    }

    public function testAnOrderWithoutWaybillsOrSenderStillReads(): void
    {
        $order = ['waybills' => [], 'sender' => null] + self::order();
        $event = (new OrderWebhookReader())->read(self::body(['type' => 'CREATED', 'data' => $order]));

        self::assertSame([], $event->getOrder()->getWaybills());
        self::assertNull($event->getOrder()->getSender());
    }

    public function testAnOrderWithMoreFieldsThanMaxInputVarsReads(): void
    {
        $order = self::order();
        $items = (array)$order['items'];
        $order['items'] = \array_fill(0, 300, $items[0]);
        $body = self::body(['type' => 'UPDATED', 'data' => $order]);
        self::assertGreaterThan(1000, \substr_count($body, '&'));

        $event = (new OrderWebhookReader())->read($body);

        self::assertCount(300, $event->getOrder()->getItems());
        self::assertTrue((new OrderWebhookReader())->isAuthentic($body, self::SECRET));
    }

    public function testAnUnknownTypeReadsAsNull(): void
    {
        $event = (new OrderWebhookReader())->read(self::body(['type' => 'MERGED', 'data' => self::order()]));

        self::assertNull($event->getType());
    }

    public function testOnlyTheWebhookSecretMakesAnEventAuthentic(): void
    {
        $reader = new OrderWebhookReader();
        $body = self::body(['type' => 'UPDATED', 'data' => self::order()]);

        self::assertTrue($reader->isAuthentic($body, self::SECRET));
        self::assertFalse($reader->isAuthentic($body, 'another-secret'));
        self::assertFalse($reader->isAuthentic($body, ''));
        self::assertFalse($reader->isAuthentic(\str_replace('type=UPDATED', 'type=CANCELLED', $body), self::SECRET));
        self::assertFalse($reader->isAuthentic(\substr($body, 0, (int)\strrpos($body, '&hash=')), self::SECRET));
    }

    public function testTheHashMayComeInCapitals(): void
    {
        $body = self::body(['type' => 'UPDATED', 'data' => self::order()]);
        $at = (int)\strrpos($body, '&hash=') + 6;

        $inCapitals = \substr($body, 0, $at) . \strtoupper(\substr($body, $at));

        self::assertTrue((new OrderWebhookReader())->isAuthentic($inCapitals, self::SECRET));
    }

    public function testABodyThatIsNotAnOrderEventIsRefused(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new OrderWebhookReader())->read('type=UPDATED&hash=abc');
    }

    /**
     * An order missing fields reads with empty values, and the reader reports what was missing.
     */
    public function testAnOrderMissingFieldsReadsAndReportsThem(): void
    {
        $reader = new OrderWebhookReader();

        $event = $reader->read(self::body(['type' => 'UPDATED', 'data' => ['number' => '1', 'status' => 'Nowe']]));

        self::assertSame('1', $event->getOrder()->getNumber());
        self::assertSame('', $event->getOrder()->getStatusCode());
        $missing = \array_map(static fn(ResponseDrift $drift) => $drift->getPath(), $reader->getLastDrift());
        self::assertContains('order.statusCode', $missing);
    }

    /**
     * A form value that doesn't convert to the field's type reads as the field's empty value, and an
     * empty one as a missing field.
     */
    public function testAFormValueOfAnotherTypeReadsAsAnEmptyValueAndIsReported(): void
    {
        $reader = new OrderWebhookReader();
        $order = [
            'price_net' => 'abc',
            'price_gross' => '',
            'is_paid' => 'maybe',
            'sender' => 'x',
            'date' => 'garbage',
        ] + self::order();

        $event = $reader->read(self::body(['type' => 'UPDATED', 'data' => $order]))->getOrder();

        self::assertSame(0.0, $event->getPriceNet());
        self::assertSame(0.0, $event->getPriceGross());
        self::assertFalse($event->isPaid());
        self::assertNull($event->getSender());
        self::assertNull($event->getDate());
        $drift = \array_map(
            static fn(ResponseDrift $drift) => $drift->getType()->value . ' ' . $drift->getPath(),
            $reader->getLastDrift(),
        );
        self::assertEqualsCanonicalizing([
            'CAST_VALUE order.priceNet',
            'MISSING_FIELD order.priceGross',
            'CAST_VALUE order.paid',
            'CAST_VALUE order.sender',
            'CAST_VALUE order.date',
        ], $drift);
    }

    /**
     * A form leaves out null values, so the fields that can be null and arrive absent are no
     * difference, strict or not.
     */
    public function testAnOrderWithNullFieldsReadsWithoutDifferences(): void
    {
        $order = ['status_date' => null, 'sender' => null] + self::order();
        $body = self::body(['type' => 'UPDATED', 'data' => $order]);
        $strict = new SerializerProvider(new SerializerConfig(strictResponses: true));

        $reader = new OrderWebhookReader();
        $event = $reader->read($body);
        (new OrderWebhookReader($strict))->read($body);

        self::assertNull($event->getOrder()->getStatusDate());
        self::assertNull($event->getOrder()->getSender());
        self::assertSame([], $reader->getLastDrift());
    }

    /**
     * A form value that isn't exactly a number or a boolean is converted as in a response, and
     * reported.
     */
    public function testAFormValueThatIsNotExactIsConvertedAndReported(): void
    {
        $order = self::order();
        self::assertIsArray($order['items']);
        self::assertIsArray($order['items'][0]);
        $order['items'][0]['amount'] = '2.7';
        $order['is_paid'] = 'yes';
        $reader = new OrderWebhookReader();

        $event = $reader->read(self::body(['type' => 'UPDATED', 'data' => $order]))->getOrder();

        self::assertSame(2, $event->getItems()[0]->getAmount());
        self::assertTrue($event->isPaid());
        $drift = \array_map(
            static fn(ResponseDrift $drift) => $drift->getType()->value . ' ' . $drift->getPath(),
            $reader->getLastDrift(),
        );
        self::assertEqualsCanonicalizing(['CAST_VALUE order.items[0].amount', 'CAST_VALUE order.paid'], $drift);
    }

    public function testAnEventWhoseOrderHasNoneOfItsFieldsIsRefused(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new OrderWebhookReader())->read(self::body(['type' => 'UPDATED', 'data' => ['id' => '1', 'state' => 'x']]));
    }

    public function testABodyThatIsNotAnOrderEventClearsTheLastDrift(): void
    {
        $reader = new OrderWebhookReader();
        $reader->read(self::body(['type' => 'UPDATED', 'data' => ['number' => '1', 'status' => 'Nowe']]));
        self::assertNotSame([], $reader->getLastDrift());

        try {
            $reader->read('type=UPDATED&hash=abc');
            self::fail('The body should have been refused.');
        } catch (InvalidArgumentException) {
        }

        self::assertSame([], $reader->getLastDrift());
    }

    /**
     * A fully populated order, in the wire form get_orders gives it.
     *
     * @return array<array-key, mixed>
     */
    private static function order(): array
    {
        $serializer = (new SerializerProvider())->getSerializer();
        self::assertInstanceOf(NormalizerInterface::class, $serializer);
        $order = $serializer->normalize(Sample::of(Order::class), 'json');
        self::assertIsArray($order);

        return $order;
    }

    /**
     * @param array<array-key, mixed> $order
     */
    private static function fromJson(array $order): Order
    {
        $json = \json_decode((string)\json_encode($order, \JSON_PRESERVE_ZERO_FRACTION), true);
        $read = (new SerializerProvider())->getSerializer()->denormalize($json, Order::class, 'json');
        self::assertInstanceOf(Order::class, $read);

        return $read;
    }

    /**
     * The body as Polkurier posts it: the fields form-encoded, then the hash of them.
     *
     * @param array<string, mixed> $fields
     */
    private static function body(array $fields): string
    {
        $encoded = \http_build_query($fields);

        return $encoded . '&hash=' . \hash_hmac('sha256', $encoded, self::SECRET);
    }
}
