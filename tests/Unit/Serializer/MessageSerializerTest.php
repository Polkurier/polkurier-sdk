<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Unit\Serializer;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Dto\Request\Contact\SaveContactFormMessage;
use Polkurier\Webservice\Dto\Request\Order\CreateOrderMessage;
use Polkurier\Webservice\Dto\Request\Order\MultiPickupRequestMessage;
use Polkurier\Webservice\Dto\Request\Order\OrderValuationMessage;
use Polkurier\Webservice\Dto\Response\ErrorMessage;
use Polkurier\Webservice\Dto\Response\Order\OrderListMessage;
use Polkurier\Webservice\Dto\Response\Order\OrderStatusMessage;
use Polkurier\Webservice\Dto\Response\ResponseMessage;
use Polkurier\Webservice\Dto\Response\Ticket\TicketMessage;
use Polkurier\Webservice\Entity\Order;
use Polkurier\Webservice\Entity\Order\Address;
use Polkurier\Webservice\Entity\Order\PackItem;
use Polkurier\Webservice\Enum\Order\ShipmentTypeEnum;
use Polkurier\Webservice\Enum\Serializer\ResponseDriftTypeEnum;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Serializer\MessageSerializer;
use Polkurier\Webservice\Serializer\SerializerConfig;
use Polkurier\Webservice\Serializer\SerializerProvider;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * The one object the SDK reads and writes bodies with, and the one a caller is handed.
 *
 * What it writes is what a call sends, so a record kept before the call matches the request; what
 * it reads is what a call returns, envelope and all.
 *
 * @see \Polkurier\Webservice\Api\SerializerInterface
 */
#[CoversClass(MessageSerializer::class)]
final class MessageSerializerTest extends TestCase
{
    private MessageSerializer $serializer;

    protected function setUp(): void
    {
        $this->serializer = new MessageSerializer(new SerializerProvider());
    }

    /**
     * The credentials and the API method are added around the payload when the request is built,
     * so neither reaches what a caller stores.
     *
     * @return void
     */
    public function testWritesThePayloadAloneWithTheQuirksOfTheMethodApplied(): void
    {
        $payload = \json_decode($this->serializer->encode(self::message()), true);

        self::assertIsArray($payload);
        self::assertArrayNotHasKey('apimethod', $payload);
        self::assertArrayNotHasKey('authorization', $payload);
        // The order is inlined, so its fields sit beside the message's own.
        self::assertSame('ABC', $payload['coupon_code']);
        self::assertSame('DPD', $payload['courier']);
        // The cover address keeps only what create_order reads there.
        self::assertSame(
            ['person', 'street', 'city', 'postcode', 'email', 'phone', 'flat_number'],
            \array_keys((array)$payload['cover_address']),
        );
    }

    /**
     * A stored payload reads back into the message it was written from, inlined record included,
     * and writes again byte for byte.
     *
     * @return void
     */
    public function testReadsItsOwnPayloadBack(): void
    {
        $written = $this->serializer->encode(self::message());

        $message = $this->serializer->decode($written, CreateOrderMessage::class);

        self::assertInstanceOf(CreateOrderMessage::class, $message);
        self::assertSame('ABC', $message->couponCode);
        self::assertSame('DPD', $message->order->getCourier());
        self::assertSame('Cover', $message->order->getCoverAddress()?->getPerson());
        self::assertSame($written, $this->serializer->encode($message));
    }

    /**
     * @return void
     */
    public function testReadsASuccessfulResponseAndReportsHowItDiffered(): void
    {
        $body = (string)\json_encode([
            'status' => 'success',
            'response' => [
                'totalrows' => 1,
                'currentpage' => 1,
                'pagesize' => 100,
                'result' => [['courier' => 'DPD', 'number' => '202610-000009', 'invented_field' => 1]],
            ],
        ]);

        $read = $this->serializer->readResponse($body, OrderListMessage::class);

        self::assertInstanceOf(ResponseMessage::class, $read->envelope);
        $list = $read->envelope->response;
        self::assertInstanceOf(OrderListMessage::class, $list);
        self::assertSame('202610-000009', $list->result[0]->getNumber());
        self::assertCount(1, $read->drift);
        self::assertSame(ResponseDriftTypeEnum::UnknownField, $read->drift[0]->type);
    }

    /**
     * @return void
     */
    public function testReadsARejectedCallAsTheErrorTheApiSent(): void
    {
        $body = (string)\json_encode(['status' => 'error', 'error_code' => 'ORDER_NOT_FOUND', 'response' => 'nope']);

        $read = $this->serializer->readResponse($body, OrderListMessage::class);

        self::assertInstanceOf(ErrorMessage::class, $read->envelope);
        self::assertSame('ORDER_NOT_FOUND', $read->envelope->errorCode);
        self::assertSame([], $read->drift);
    }

    /**
     * A body that is not an API response names what was wrong with it, without an HTTP response to
     * point at: the response factory adds that where there is one.
     *
     * @return void
     */
    public function testRefusesABodyThatIsNotAResponse(): void
    {
        foreach (['maintenance in progress', '{"a":1}', '{"status":"weird"}'] as $body) {
            try {
                $this->serializer->readResponse($body, OrderListMessage::class);
                self::fail('Expected an InvalidArgumentException for: ' . $body);
            } catch (InvalidArgumentException $e) {
                self::assertNotSame('', $e->getMessage());
            }
        }
    }

    /**
     * A record that inlines a record of its own is written flat all the way down, so reading it
     * back means claiming each key for the level that wrote it.
     *
     * @return void
     */
    public function testReadsAPayloadBackThroughTwoLevelsOfInlining(): void
    {
        $valuation = new OrderValuationMessage(order: self::message(), courier: 'DPD');

        $written = $this->serializer->encode($valuation);
        $message = $this->serializer->decode($written, OrderValuationMessage::class);

        self::assertSame('DPD', $message->order->order->getCourier());
        self::assertSame('ABC', $message->order->couponCode);
        self::assertSame($written, $this->serializer->encode($message));
    }

    /**
     * Reading one body leaves what another read reported alone: the drift comes back with the
     * envelope, and the object itself remembers nothing.
     *
     * @return void
     */
    public function testKeepsNothingOfWhatItRead(): void
    {
        $withDrift = (string)\json_encode([
            'status' => 'success',
            'response' => ['totalrows' => 0, 'result' => [], 'invented_field' => 1],
        ]);
        $clean = (string)\json_encode(['status' => 'success', 'response' => ['totalrows' => 0, 'result' => []]]);

        $first = $this->serializer->readResponse($withDrift, OrderListMessage::class);
        $second = $this->serializer->readResponse($clean, OrderListMessage::class);

        self::assertCount(1, $first->drift);
        self::assertSame([], $second->drift);
    }

    /**
     * Strict reading refuses what lenient reading reports, through this object as everywhere else.
     *
     * @return void
     */
    public function testStrictReadingRefusesWhatLenientReadingReports(): void
    {
        $body = (string)\json_encode([
            'status' => 'success',
            'response' => ['totalrows' => 1, 'result' => [['courier' => 'DPD', 'invented_field' => 1]]],
        ]);
        $strict = new MessageSerializer(new SerializerProvider(new SerializerConfig(strictResponses: true)));

        $this->expectException(InvalidArgumentException::class);
        $strict->readResponse($body, OrderListMessage::class);
    }

    /**
     * A record whose keys are all claimed by a map it inlines reads back whole: nothing of the
     * payload is left for a field that cannot take it.
     *
     * @return void
     */
    public function testReadsAPayloadWhoseKeysAreAllUnclaimed(): void
    {
        $body = (string)\json_encode(['status' => 'success', 'response' => ['unclaimed' => 'u']]);

        $read = $this->serializer->readResponse($body, SaveContactFormMessage::class);

        self::assertInstanceOf(ResponseMessage::class, $read->envelope);
        $message = $read->envelope->response;
        self::assertInstanceOf(SaveContactFormMessage::class, $message);
        self::assertSame(['unclaimed' => 'u'], $message->fields);
    }

    /**
     * `create_order` reads the cover address's flat number under a name of its own, so a payload
     * the SDK wrote carries that name and has to be read back under it, strictly or not, whatever
     * class the order is.
     *
     * @param class-string<Order> $orderClass
     * @param bool $strict
     *
     * @return void
     */
    #[DataProvider('ordersAndStrictness')]
    public function testReadsBackTheCoverAddressFlatNumberItWrote(string $orderClass, bool $strict): void
    {
        $serializer = new MessageSerializer(new SerializerProvider(new SerializerConfig(strictResponses: $strict)));
        $order = new $orderClass(
            courier: 'DPD',
            shipmentType: ShipmentTypeEnum::Box,
            packs: [new PackItem(length: 30, width: 20, height: 15, weight: 2.5)],
            sender: new Address(person: 'Jan', street: 'Długa', city: 'Poznań', postcode: '61-001', phone: '500600700'),
            recipient: new Address(person: 'Anna', street: 'Krucza', city: 'Warszawa', postcode: '00-001', phone: '1'),
        );
        $order->setCoverAddress(new Address(
            person: 'Cover',
            street: 'Rynek',
            city: 'Poznań',
            postcode: '61-002',
            flatNumber: '7',
        ));

        $written = $serializer->encode($order);
        $read = $serializer->decode($written, $orderClass);

        self::assertSame('7', $read->getCoverAddress()?->getFlatNumber());
        self::assertSame($written, $serializer->encode($read));
    }

    /**
     * @return array<string, array{class-string<Order>, bool}>
     */
    public static function ordersAndStrictness(): array
    {
        return [
            'the order itself, read leniently' => [Order::class, false],
            'the order itself, read strictly' => [Order::class, true],
            'a subclassed order, read leniently' => [SubclassedOrder::class, false],
            'a subclassed order, read strictly' => [SubclassedOrder::class, true],
        ];
    }

    /**
     * A class the payload cannot build is refused as the SDK's own failure, not as the
     * serializer's: a caller catching the SDK's exceptions catches this too.
     *
     * @return void
     */
    public function testAConstructorRefusingWhatWasReadIsTheSdksOwnException(): void
    {
        $body = (string)\json_encode(['status' => 'success', 'response' => ['addr' => []]]);

        $this->expectException(InvalidArgumentException::class);

        $this->serializer->readResponse($body, NeedsAnAddress::class);
    }

    /**
     * The configured timezone says how a date without an offset is read. A date being written
     * already has one, so the setting must not move it, or an order would be collected in a
     * window the caller never asked for.
     *
     * @return void
     */
    public function testTheTimezoneSettingDoesNotMoveADateBeingWritten(): void
    {
        $at = new \DateTimeImmutable('2026-11-17 23:30:00', new \DateTimeZone('UTC'));
        $message = new MultiPickupRequestMessage(
            orders: ['POL123456'],
            pickupDate: $at,
            pickupTimeFrom: $at,
            pickupTimeTo: $at,
        );

        $written = [];
        foreach ([null, new \DateTimeZone('Europe/Warsaw'), new \DateTimeZone('America/New_York')] as $timezone) {
            $serializer = new MessageSerializer(new SerializerProvider(new SerializerConfig(timezone: $timezone)));
            $written[] = $serializer->encode($message);
        }

        self::assertSame([$written[0], $written[0], $written[0]], $written);
        self::assertStringContainsString('"pickuptimefrom":"23:30"', $written[0]);
    }

    /**
     * The same setting still decides which zone a date without an offset is read in.
     *
     * @return void
     */
    public function testTheTimezoneSettingStillSaysHowADateIsRead(): void
    {
        $body = (string)\json_encode([
            'status' => 'success',
            'response' => ['status' => 'Nowe', 'status_code' => 'NEW', 'status_date' => '2026-11-17 08:00'],
        ]);
        $serializer = new MessageSerializer(
            new SerializerProvider(new SerializerConfig(timezone: new \DateTimeZone('Europe/Warsaw'))),
        );

        $read = $serializer->readResponse($body, OrderStatusMessage::class);

        $message = $read->envelope->response;
        self::assertInstanceOf(OrderStatusMessage::class, $message);
        self::assertSame('2026-11-17 08:00:00 +01:00', $message->statusDate?->format('Y-m-d H:i:s P'));
    }

    /**
     * A payload holding none of a class's fields is refused the way the method documents, not by
     * a type error from the return value.
     *
     * @return void
     */
    public function testAPayloadWithNoneOfTheClassesFieldsIsRefused(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->serializer->decode('{}', CreateOrderMessage::class);
    }

    /**
     * A response enum the API left out reads as null. A default naming a real case would hand the
     * caller a fact the response never carried, and nothing would mark it as invented.
     *
     * @return void
     */
    public function testAnAbsentResponseEnumReadsAsNullRatherThanACase(): void
    {
        $body = (string)\json_encode([
            'status' => 'success',
            'response' => ['id' => '7', 'number' => 'T-7', 'subject' => 'Re: order'],
        ]);

        $read = $this->serializer->readResponse($body, TicketMessage::class);

        $ticket = $read->envelope->response;
        self::assertInstanceOf(TicketMessage::class, $ticket);
        self::assertNull($ticket->status);
    }

    /**
     * @return CreateOrderMessage
     */
    private static function message(): CreateOrderMessage
    {
        $order = new Order(
            courier: 'DPD',
            shipmentType: ShipmentTypeEnum::Box,
            packs: [new PackItem(length: 30, width: 20, height: 15, weight: 2.5)],
            sender: new Address(
                person: 'Jan Kowalski',
                street: 'Długa',
                city: 'Poznań',
                postcode: '61-001',
                email: 'jan@example.com',
                phone: '500600700',
            ),
            recipient: new Address(
                person: 'Anna Nowak',
                street: 'Krucza',
                city: 'Warszawa',
                postcode: '00-001',
                phone: '600700800',
            ),
        );
        $order->setCoverAddress(new Address(
            person: 'Cover',
            street: 'Rynek',
            city: 'Poznań',
            postcode: '61-002',
            email: 'cover@example.com',
            phone: '500600701',
            flatNumber: '7',
            pointId: 'POZ01A',
        ));

        return new CreateOrderMessage(order: $order, couponCode: 'ABC');
    }
}

/**
 * A record of an integrator's own, to prove a quirk keyed on the SDK's class reaches a subclass.
 */
class SubclassedOrder extends Order {}

/**
 * A response class the serializer can map a payload onto but cannot construct from it.
 */
final readonly class NeedsAnAddress implements MessageInterface
{
    /**
     * @param Address $addr
     */
    public function __construct(
        /**
         * @var Address
         */
        #[Serializer\SerializedName('addr')]
        public Address $addr,
    ) {}
}
