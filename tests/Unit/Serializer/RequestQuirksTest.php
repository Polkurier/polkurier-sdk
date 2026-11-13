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
use Polkurier\Webservice\Dto\Request\Order\CreateOrderMessage;
use Polkurier\Webservice\Dto\Request\Order\OrderValuationMessage;
use Polkurier\Webservice\Entity\Order;
use Polkurier\Webservice\Entity\Order\Address;
use Polkurier\Webservice\Entity\Order\PackItem;
use Polkurier\Webservice\Enum\Order\ShipmentTypeEnum;
use Polkurier\Webservice\Serializer\RequestQuirksNormalizer;
use Polkurier\Webservice\Serializer\SerializerProvider;
use Polkurier\Webservice\Test\Support\Sample;
use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * `create_order` reads the cover address from the 8 keys of
 * {@see RequestQuirksNormalizer::COVER_ADDRESS}, and the flat number under `flat_number` instead
 * of `flatnumber`. Any other key in the block makes the API print the cover address and charge for
 * it, so the block goes out trimmed, under whichever message the order is sent.
 */
#[CoversClass(RequestQuirksNormalizer::class)]
final class RequestQuirksTest extends TestCase
{
    /**
     * The messages an order is sent under, each wrapping the order it is given.
     *
     * @return array<string, array{\Closure(Order): object}>
     */
    public static function messagesAnOrderIsSentUnder(): array
    {
        return [
            'the order itself' => [static fn(Order $order): object => $order],
            'an order to place' => [static fn(Order $order): object => new CreateOrderMessage($order)],
            'an order to price' => [
                static fn(Order $order): object => new OrderValuationMessage(new CreateOrderMessage($order), 'DPD'),
            ],
        ];
    }

    /**
     * @param \Closure(Order): object $wrap
     *
     * @return void
     */
    #[DataProvider('messagesAnOrderIsSentUnder')]
    public function testEveryKeyTheCoverBlockIsReadFromSurvivesTheTrim(\Closure $wrap): void
    {
        $cover = self::coverAddressOf($wrap(self::order(new Address(
            person: 'Jan Kowalski',
            street: 'Polna',
            city: 'Poznań',
            postcode: '61-001',
            email: 'jan@example.com',
            phone: '500600700',
            company: 'Sklep Internetowy',
            houseNumber: '5',
            flatNumber: '2',
        ))));

        self::assertSame([
            'city' => 'Poznań',
            'company' => 'Sklep Internetowy',
            'email' => 'jan@example.com',
            'flat_number' => '2',
            'housenumber' => '5',
            'person' => 'Jan Kowalski',
            'phone' => '500600700',
            'postcode' => '61-001',
            'street' => 'Polna',
        ], $cover);
    }

    /**
     * @return array<string, array{string|null, array<string, string>}>
     */
    public static function flatNumbers(): array
    {
        return [
            'a flat number' => ['2', ['flat_number' => '2']],
            'an empty flat number' => ['', []],
            'no flat number' => [null, []],
        ];
    }

    /**
     * @param string|null $flatNumber
     * @param array<string, string> $expected
     *
     * @return void
     */
    #[DataProvider('flatNumbers')]
    public function testTheFlatNumberGoesOutUnderTheKeyTheCoverBlockReadsItFrom(
        ?string $flatNumber,
        array $expected,
    ): void {
        $cover = self::coverAddressOf(self::order(new Address(
            person: 'Jan Kowalski',
            street: 'Polna',
            city: 'Poznań',
            postcode: '61-001',
            houseNumber: '5',
            flatNumber: $flatNumber,
        )));

        self::assertSame($expected, \array_intersect_key($cover, ['flat_number' => null, 'flatnumber' => null]));
    }

    /**
     * The block is trimmed to the keys it is read from whoever declared the rest: the country and
     * the point the record itself sends, and a column a subclass adds. The same subclass sent as
     * the sender keeps its column, which no quirk touches.
     *
     * @return void
     */
    public function testAKeyTheCoverBlockIsNotReadFromIsLeftOutWhoeverDeclaredIt(): void
    {
        $address = new class(
            person: 'Jan Kowalski',
            street: 'Polna',
            city: 'Poznań',
            postcode: '61-001',
            country: 'DE',
            pointId: 'POZ01A',
        ) extends Address {
            #[SerializedName('warehouse')]
            public string $warehouse = 'W-1';
        };

        $payload = self::payloadOf(self::order($address, sender: $address));

        self::assertSame([
            'city' => 'Poznań',
            'person' => 'Jan Kowalski',
            'postcode' => '61-001',
            'street' => 'Polna',
        ], self::trimmedCoverAddress($payload));

        $sender = $payload[Order::FIELD_SENDER] ?? [];
        self::assertSame('W-1', $sender['warehouse'] ?? null);
        self::assertSame('DE', $sender[Address::FIELD_COUNTRY] ?? null);
    }

    /**
     * An order the API would accept, with the given cover address.
     *
     * @param Address $coverAddress
     * @param Address|null $sender
     *
     * @return Order
     */
    private static function order(Address $coverAddress, ?Address $sender = null): Order
    {
        return new Order(
            courier: 'DPD',
            shipmentType: ShipmentTypeEnum::Box,
            packs: [Sample::of(PackItem::class)],
            sender: $sender ?? Sample::of(Address::class),
            recipient: Sample::of(Address::class),
            coverAddress: $coverAddress,
        );
    }

    /**
     * @param object $message
     *
     * @return array<string, mixed>
     */
    private static function payloadOf(object $message): array
    {
        $payload = \json_decode(
            (new SerializerProvider())->getSerializer()->serialize($message, 'json'),
            true,
            flags: \JSON_THROW_ON_ERROR
        );

        self::assertIsArray($payload);

        return $payload;
    }

    /**
     * @param object $message
     *
     * @return array<string, mixed>
     */
    private static function coverAddressOf(object $message): array
    {
        return self::trimmedCoverAddress(self::payloadOf($message));
    }

    /**
     * The cover address block of a payload, its keys sorted so that what is asserted is which keys
     * are there, not the order the serializer happens to emit them in.
     *
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    private static function trimmedCoverAddress(array $payload): array
    {
        self::assertArrayHasKey(Order::FIELD_COVER_ADDRESS, $payload);
        $cover = $payload[Order::FIELD_COVER_ADDRESS];
        self::assertIsArray($cover);
        \ksort($cover);

        return $cover;
    }
}
