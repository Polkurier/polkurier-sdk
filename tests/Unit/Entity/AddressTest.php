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
use Polkurier\Webservice\Entity\Address;
use Polkurier\Webservice\Enum\Address\AddressTypeEnum;
use Polkurier\Webservice\Serializer\SerializerProvider;

/**
 * The address book entry, a record of its own: it holds the fields an order address has no use
 * for, and the ones they share are its own too.
 *
 * Optional constructor parameters declared before required ones are deprecated in PHP 8 and break
 * construction by named arguments, so it takes every required parameter first.
 */
#[CoversClass(Address::class)]
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
        self::assertSame(Address::DEFAULT_COUNTRY, $address->getCountry());
        self::assertSame(Address::DEFAULT_TYPE, $address->getType());
    }

    /**
     * Each optional field is read back from the getter named after it, the entry's own fields
     * among them.
     */
    public function testKeepsEveryOptionalFieldItIsGiven(): void
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
            id: '42',
            type: AddressTypeEnum::Recipient,
            company: 'Acme sp. z o.o.',
            flatNumber: '3',
            province: 'wielkopolskie',
            country: 'DE',
            pointId: 'POZ01A',
            default: true,
            coverAddress: true,
        );

        self::assertSame('42', $address->getId());
        self::assertSame(AddressTypeEnum::Recipient, $address->getType());
        self::assertSame('Acme sp. z o.o.', $address->getCompany());
        self::assertSame('3', $address->getFlatNumber());
        self::assertSame('wielkopolskie', $address->getProvince());
        self::assertSame('DE', $address->getCountry());
        self::assertSame('POZ01A', $address->getPointId());
        self::assertTrue($address->isDefault());
        self::assertTrue($address->isCoverAddress());
        self::assertSame('Długa', $address->getStreet());
    }

    /**
     * The fields only a response carries read as well, and a missing flat number reads back as
     * null.
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
        self::assertSame('POZ01A', $address->getPoint()?->id);
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
