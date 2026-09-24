<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Unit\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Polkurier\Webservice\Enum\Address\AddressTypeEnum;
use Polkurier\Webservice\Response\Address;
use Polkurier\Webservice\Response\BaseAddress;
use Polkurier\Webservice\Serializer\SerializerProvider;

/**
 * These classes previously declared optional constructor parameters before required ones, which
 * PHP 8 deprecates and which made construction by named arguments fail outright.
 */
#[CoversClass(Address::class)]
#[CoversClass(BaseAddress::class)]
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
        self::assertSame(BaseAddress::DEFAULT_COUNTRY, $address->getCountry());
    }

    /**
     * Address passes its own arguments down to BaseAddress. When BaseAddress's parameter order
     * changed, a positional hand-off silently mapped $company onto $street.
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
