<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Unit\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Polkurier\Webservice\Entity\Address\Address;
use Polkurier\Webservice\Entity\Order\OrderAddress;
use Polkurier\Webservice\Enum\Address\AddressTypeEnum;
use Polkurier\Webservice\Serializer\SerializerProvider;

/**
 * Optional constructor parameters declared before required ones are deprecated in PHP 8 and break
 * construction by named arguments, so these classes take every required parameter first.
 */
#[CoversClass(Address::class)]
#[CoversClass(OrderAddress::class)]
final class AddressTest extends TestCase
{
    public function testConstructsFromNamedArgumentsWithoutOptionalOnes(): void
    {
        $address = new Address(
            person: 'Jan Kowalski',
            street: 'Długa',
            city: 'Poznań',
            postcode: '61-001',
            email: 'jan@example.com',
            phone: '500600700',
            name: 'Magazyn',
            houseNumber: '12',
        );

        self::assertSame('Jan Kowalski', $address->getPerson());
        self::assertSame('Długa', $address->getStreet());
        self::assertSame('12', $address->getHouseNumber());
        self::assertNull($address->getCompany());
        self::assertSame(OrderAddress::DEFAULT_COUNTRY, $address->getCountry());
    }

    /**
     * {@see Address} passes its own arguments down to {@see OrderAddress} by name. A positional
     * hand-off would map $company onto $street whenever the parent's parameter order differs.
     */
    public function testPassesEveryFieldThroughToTheParentInTheRightOrder(): void
    {
        $address = new Address(
            person: 'Jan Kowalski',
            street: 'Długa',
            city: 'Poznań',
            postcode: '61-001',
            email: 'jan@example.com',
            phone: '500600700',
            name: 'Magazyn',
            houseNumber: '12',
            company: 'Acme sp. z o.o.',
            province: 'wielkopolskie',
            country: 'DE',
            pointId: 'POZ01A',
        );

        self::assertSame('Acme sp. z o.o.', $address->getCompany());
        self::assertSame('wielkopolskie', $address->getProvince());
        self::assertSame('DE', $address->getCountry());
        self::assertSame('POZ01A', $address->getPointId());
        self::assertSame('Długa', $address->getStreet());
    }

    /**
     * The read-only fields a response carries reach the parent too, and a missing flat number
     * reads back as null.
     */
    public function testDenormalizesTheReadOnlyFieldsAndANullFlatNumber(): void
    {
        $address = (new SerializerProvider())->getSerializer()->denormalize([
            'person' => 'Jan Kowalski',
            'street' => 'Długa 12',
            'city' => 'Poznań',
            'postcode' => '61-001',
            'email' => 'jan@example.com',
            'phone' => '500600700',
            'name' => 'Magazyn',
            'housenumber' => '12',
            'flatnumber' => null,
            'streetname' => 'Długa',
            'machinename' => 'POZ01A',
            'point' => ['id' => 'POZ01A', 'city' => 'Poznań'],
        ], Address::class, 'json');

        self::assertInstanceOf(Address::class, $address);
        self::assertNull($address->getFlatNumber());
        self::assertSame('Długa', $address->getStreetName());
        self::assertSame('POZ01A', $address->getMachineName());
        self::assertSame('POZ01A', $address->getPoint()?->getId());
    }

    /**
     * @return array<string, array{string, mixed}>
     */
    public static function expectedSerializedKeys(): array
    {
        return [
            'person' => ['person', 'Jan Kowalski'],
            'street' => ['street', 'Długa'],
            'house number' => ['housenumber', '12'],
            'postcode' => ['postcode', '61-001'],
            'country defaults to PL' => ['country', 'PL'],
            'type defaults to SENDER' => ['type', 'SENDER'],
        ];
    }

    #[DataProvider('expectedSerializedKeys')]
    public function testSerializesToTheKeysTheApiExpects(string $key, mixed $value): void
    {
        $json = (new SerializerProvider())->getSerializer()->serialize(
            new Address(
                person: 'Jan Kowalski',
                street: 'Długa',
                city: 'Poznań',
                postcode: '61-001',
                email: 'jan@example.com',
                phone: '500600700',
                name: 'Magazyn',
                houseNumber: '12',
            ),
            'json'
        );

        /** @var array<string, mixed> $decoded */
        $decoded = \json_decode($json, true);

        self::assertArrayHasKey($key, $decoded);
        self::assertSame($value, $decoded[$key]);
    }

    public function testOmitsEmptyOptionalFields(): void
    {
        $json = (new SerializerProvider())->getSerializer()->serialize(
            new Address(
                person: 'Jan Kowalski',
                street: 'Długa',
                city: 'Poznań',
                postcode: '61-001',
                email: 'jan@example.com',
                phone: '500600700',
                name: 'Magazyn',
                houseNumber: '12',
                type: AddressTypeEnum::Recipient,
            ),
            'json'
        );

        /** @var array<string, mixed> $decoded */
        $decoded = \json_decode($json, true);

        self::assertArrayNotHasKey('company', $decoded);
        self::assertArrayNotHasKey('id', $decoded);
        self::assertSame('RECIPIENT', $decoded['type']);
    }
}
