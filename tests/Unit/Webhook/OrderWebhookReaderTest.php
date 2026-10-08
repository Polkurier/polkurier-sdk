<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Unit\Webhook;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Polkurier\Webservice\Enum\Webhook\OrderWebhookTypeEnum;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Response\Order;
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
        $event = (new OrderWebhookReader())->read(self::body(['type' => 'UPDATED', 'data' => $order]));

        self::assertSame(OrderWebhookTypeEnum::Updated, $event->getType());
        self::assertEquals(self::fromJson($order), $event->getOrder());
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

        self::assertTrue((new OrderWebhookReader())->isAuthentic(\substr($body, 0, $at) . \strtoupper(\substr($body, $at)), self::SECRET));
    }

    public function testABodyThatIsNotAnOrderEventIsRefused(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new OrderWebhookReader())->read('type=UPDATED&hash=abc');
    }

    public function testAnEventWhoseOrderCanNotBeReadIsRefused(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new OrderWebhookReader())->read(self::body(['type' => 'UPDATED', 'data' => ['number' => '1']]));
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
