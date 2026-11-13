<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Unit\Serializer;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Polkurier\Webservice\Entity\Address\SaveAddressMessage;
use Polkurier\Webservice\Entity\Order\Pickup;
use Polkurier\Webservice\Enum\Address\AddressTypeEnum;
use Polkurier\Webservice\Serializer\SerializerConfig;
use Polkurier\Webservice\Serializer\SerializerProvider;
use Symfony\Component\Serializer\Exception\ExtraAttributesException;

/**
 * Each setting has to reach the serializer, or it is decoration.
 */
#[CoversClass(SerializerConfig::class)]
#[CoversClass(SerializerProvider::class)]
final class SerializerConfigTest extends TestCase
{
    public function testUtf8IsSentAsItselfRatherThanEscaped(): void
    {
        $json = (new SerializerProvider())->getSerializer()->serialize(self::address(), 'json');

        self::assertStringContainsString('Poznań', $json);
        self::assertStringNotContainsString('Pozna\\u0144', $json);
    }

    /**
     * Without JSON_PRESERVE_ZERO_FRACTION an amount of 250.0 goes out as `250`, so the default
     * has to carry it and this is the guard against an override quietly dropping it.
     */
    public function testAWholeAmountStaysAFloat(): void
    {
        $json = (new SerializerProvider())->getSerializer()->serialize(['amount' => 250.0], 'json');

        self::assertSame('{"amount":250.0}', $json);
    }

    public function testEncodeOptionsAreTakenFromTheConfiguration(): void
    {
        $provider = new SerializerProvider(
            new SerializerConfig(jsonEncodeOptions: SerializerConfig::DEFAULT_JSON_OPTIONS | \JSON_PRETTY_PRINT)
        );

        self::assertStringContainsString("\n", $provider->getSerializer()->serialize(self::address(), 'json'));
    }

    public function testAnUnknownFieldIsIgnoredByDefaultAndRejectedWhenAsked(): void
    {
        $payload = ['pickupdate' => '2026-09-24', 'something_the_api_just_added' => 'x'];

        $tolerant = (new SerializerProvider())->getSerializer();
        self::assertInstanceOf(Pickup::class, $tolerant->denormalize($payload, Pickup::class, 'json'));

        $strict = (new SerializerProvider(new SerializerConfig(strictResponses: true)))->getSerializer();

        $this->expectException(ExtraAttributesException::class);
        $strict->denormalize($payload, Pickup::class, 'json');
    }

    public function testDatesAreReadIntoTheConfiguredTimezone(): void
    {
        $provider = new SerializerProvider(new SerializerConfig(timezone: new \DateTimeZone('UTC')));

        /** @var Pickup $pickup */
        $pickup = $provider->getSerializer()->denormalize(
            ['pickupdate' => '2026-09-24'],
            Pickup::class,
            'json',
        );

        self::assertSame('UTC', $pickup->getPickupDate()?->getTimezone()->getName());
    }

    /**
     * @return SaveAddressMessage
     */
    private static function address(): SaveAddressMessage
    {
        return new SaveAddressMessage(
            name: 'Magazyn',
            person: 'Jan Kowalski',
            street: 'Długa',
            houseNumber: '12',
            postcode: '61-001',
            city: 'Poznań',
            email: 'jan@example.com',
            phone: '500600700',
            type: AddressTypeEnum::Sender,
        );
    }
}
