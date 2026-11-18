<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Unit\Serializer;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Polkurier\Webservice\Entity\Order;
use Polkurier\Webservice\Entity\Order\Address;
use Polkurier\Webservice\Enum\Order\AdditionalServiceEnum;
use Polkurier\Webservice\Serializer\SentFieldsNormalizer;
use Polkurier\Webservice\Serializer\SerializerProvider;
use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * Which of an object's fields reach the payload.
 *
 * A record of yours that extends one of the SDK's sends the fields the SDK declares, and a column
 * of your own only once you name it with `#[SerializedName]`: the API rejects or misreads a key it
 * doesn't know, and a cover address is switched on by any value in its block. A class that
 * inherits nothing from the SDK is none of its business and is written whole.
 */
#[CoversClass(SentFieldsNormalizer::class)]
final class SentFieldsTest extends TestCase
{
    public function testASubclassSendsTheColumnItNamesAndKeepsTheRestToItself(): void
    {
        $local = new class(
            person: 'Jan Kowalski',
            street: 'Długa',
            city: 'Poznań',
            postcode: '61-001',
        ) extends Address {
            #[SerializedName('local_id')]
            public int $localId = 7;

            public string $note = 'order 1001, picked by Ola';
        };

        $sent = self::payloadOf($local);

        self::assertSame(
            self::payloadOf(new Address(
                person: 'Jan Kowalski',
                street: 'Długa',
                city: 'Poznań',
                postcode: '61-001',
            )) + ['local_id' => 7],
            $sent,
        );
    }

    /**
     * `#[SerializedName]` takes its name positionally or as `serializedName:`, and the payload is
     * filtered by the name either way.
     *
     * @return void
     */
    public function testAColumnNamedByANamedArgumentIsSent(): void
    {
        $local = new class(
            person: 'Jan Kowalski',
            street: 'Długa',
            city: 'Poznań',
            postcode: '61-001',
        ) extends Address {
            #[SerializedName(serializedName: 'local_id')]
            public int $localId = 7;
        };

        self::assertSame(7, self::payloadOf($local)['local_id'] ?? null);
    }

    /**
     * A class of your own, inheriting nothing from the SDK, keeps every field it declares, named
     * or not.
     *
     * @return void
     */
    public function testAClassWithNoSdkAncestorIsWrittenWhole(): void
    {
        $own = new class {
            public string $sku = 'A-1';

            public int $quantity = 2;
        };

        self::assertSame(['sku' => 'A-1', 'quantity' => 2], self::payloadOf($own));
    }

    /**
     * A subclass may redeclare a property to change its default or its type. The attributes stay
     * on the declaration that carries them, so the field keeps the name the SDK gave it.
     *
     * @return void
     */
    public function testARedeclaredPropertyKeepsTheNameItsParentGaveIt(): void
    {
        $redeclared = new class(
            person: 'Jan Kowalski',
            street: 'Długa',
            city: 'Poznań',
            postcode: '61-001',
            flatNumber: '9',
        ) extends Address {
            protected ?string $flatNumber = null;
        };

        $sent = self::payloadOf($redeclared);

        self::assertArrayHasKey('flatnumber', $sent);
        self::assertSame('9', $sent['flatnumber']);
    }

    /**
     * An order writes its services as a computed flag map under `courierservice`, the same key the
     * response-only names property reads them back under. A response-only field is taken out of
     * the payload by its wire name, so the two sharing one name would take the map with them and
     * an order would quietly go out with no services on it.
     *
     * @return void
     */
    public function testAnOrderSendsTheServicesItWasGiven(): void
    {
        $payload = self::payloadOf(new Order(
            courier: 'INPOST',
            courierService: [AdditionalServiceEnum::CoverSenderAddress],
        ));

        self::assertSame(
            [AdditionalServiceEnum::CoverSenderAddress->value => true],
            $payload[Order::FIELD_COURIER_SERVICE] ?? null,
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
}
