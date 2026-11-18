<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Unit\Serializer;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Polkurier\Webservice\Dto\Response\Carrier;
use Polkurier\Webservice\Dto\Response\Carrier\AdditionalData\PointsMap;
use Polkurier\Webservice\Dto\Response\Contact\ContactFormValueMessage;
use Polkurier\Webservice\Dto\Response\Courier\TimeIntervalMessage;
use Polkurier\Webservice\Dto\Response\CourierPoint;
use Polkurier\Webservice\Dto\Response\ErrorMessage;
use Polkurier\Webservice\Dto\Response\Util\HeartbeatMessage;
use Polkurier\Webservice\Dto\Response\Webhook\OrderEventMessage;
use Polkurier\Webservice\Entity\Address;
use Polkurier\Webservice\Entity\Order;
use Polkurier\Webservice\Enum\Address\AddressTypeEnum;
use Polkurier\Webservice\Enum\Courier\PointFunctionEnum;
use Polkurier\Webservice\Enum\Http\ResponseStatusEnum;
use Polkurier\Webservice\Exception\MalformedResponseException;
use Polkurier\Webservice\Http\Response\ResponseWrapper;
use Polkurier\Webservice\Serializer\EnumListDenormalizer;
use Polkurier\Webservice\Serializer\LenientDateDenormalizer;
use Polkurier\Webservice\Serializer\LenientEnumDenormalizer;
use Polkurier\Webservice\Serializer\LenientListDenormalizer;
use Polkurier\Webservice\Serializer\LenientObjectDenormalizer;
use Polkurier\Webservice\Serializer\ResponseDrift;
use Polkurier\Webservice\Serializer\ResponseDriftCollector;
use Polkurier\Webservice\Serializer\SerializerConfig;
use Polkurier\Webservice\Serializer\SerializerProvider;
use Polkurier\Webservice\Serializer\StrictResponseSerializer;
use Polkurier\Webservice\Test\Support\Responses;
use Polkurier\Webservice\Test\Support\Sample;
use Polkurier\Webservice\Test\Support\SourceClasses;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerException;

/**
 * A response that differs from what this SDK version expects still reads, and each difference is
 * recorded, so a change on the API's side neither breaks the application nor passes unnoticed.
 */
#[CoversClass(LenientObjectDenormalizer::class)]
#[CoversClass(LenientDateDenormalizer::class)]
#[CoversClass(LenientListDenormalizer::class)]
#[CoversClass(LenientEnumDenormalizer::class)]
#[CoversClass(EnumListDenormalizer::class)]
#[CoversClass(StrictResponseSerializer::class)]
final class ResponseDriftTest extends TestCase
{
    public function testAnUnknownFieldIsRecordedAndIgnored(): void
    {
        $wrapper = Responses::readWrapped(Carrier::class . '[]', self::carrier(['brand_new' => 'x']));

        self::assertSame('DPD', self::carriers($wrapper)[0]->serviceCode);
        self::assertSame([['UNKNOWN_FIELD', '[0].brand_new', 'x', null]], self::drift($wrapper));
    }

    public function testAMissingFieldReadsAsAnEmptyValueAndIsRecorded(): void
    {
        $carrier = self::carrier();
        unset($carrier[0][Carrier::FIELD_NAME]);

        $wrapper = Responses::readWrapped(Carrier::class . '[]', $carrier);

        self::assertSame('', self::carriers($wrapper)[0]->name);
        self::assertSame([['MISSING_FIELD', '[0].name', null, 'string']], self::drift($wrapper));
    }

    public function testANullFieldReadsAsAnEmptyValueAndIsRecorded(): void
    {
        $wrapper = Responses::readWrapped(Carrier::class . '[]', self::carrier([Carrier::FIELD_NAME => null]));

        self::assertSame('', self::carriers($wrapper)[0]->name);
        self::assertSame([['MISSING_FIELD', '[0].name', null, 'string']], self::drift($wrapper));
    }

    public function testAnOptionalFieldLeftOutIsNotRecorded(): void
    {
        self::assertSame([], self::drift(Responses::readWrapped(Carrier::class . '[]', self::carrier())));
    }

    public function testASimpleValueOfAnotherTypeIsConverted(): void
    {
        $wrapper = Responses::readWrapped(
            Carrier::class . '[]',
            self::carrier([Carrier::FIELD_SERVICE_CODE => 12, Carrier::FIELD_FOREIGN_SHIPMENTS => 'true']),
        );

        $carrier = self::carriers($wrapper)[0];
        self::assertSame('12', $carrier->serviceCode);
        self::assertTrue($carrier->foreignShipments);
        self::assertSame([
            ['CAST_VALUE', '[0].serviceCode', 12, 'string'],
            ['CAST_VALUE', '[0].foreignShipments', 'true', 'bool'],
        ], self::drift($wrapper));
    }

    public function testAValueThatCanNotBeConvertedReadsAsAnEmptyValue(): void
    {
        $wrapper = Responses::readWrapped(Carrier::class . '[]', self::carrier([Carrier::FIELD_NAME => ['x']]));

        self::assertSame('', self::carriers($wrapper)[0]->name);
        self::assertSame([['CAST_VALUE', '[0].name', ['x'], 'string']], self::drift($wrapper));
    }

    public function testAMissingDateReadsAsNull(): void
    {
        $wrapper = Responses::readWrapped(TimeIntervalMessage::class, [TimeIntervalMessage::FIELD_TIME_TO => '12:00']);

        $interval = $wrapper->getEnvelope()->response;
        self::assertInstanceOf(TimeIntervalMessage::class, $interval);
        self::assertNull($interval->timeFrom);
        self::assertSame([['MISSING_FIELD', 'timeFrom', null, \DateTimeInterface::class]], self::drift($wrapper));
    }

    public function testADateThatDoesNotParseReadsAsNullAndIsRecorded(): void
    {
        $wrapper = Responses::readWrapped(HeartbeatMessage::class, ['time' => 'garbage']);

        $heartbeat = $wrapper->getEnvelope()->response;
        self::assertInstanceOf(HeartbeatMessage::class, $heartbeat);
        self::assertNull($heartbeat->time);
        self::assertSame([['CAST_VALUE', 'time', 'garbage', \DateTimeInterface::class]], self::drift($wrapper));
    }

    public function testAListItemOfAnotherTypeIsConvertedOrLeftOut(): void
    {
        self::skipWithoutItemTypes();

        $wrapper = Responses::readWrapped(
            PointsMap::class,
            [PointsMap::FIELD_AVAILABLE_PROVIDERS => ['INPOST', 7, ['x'], 'DPD']],
        );

        $map = $wrapper->getEnvelope()->response;
        self::assertInstanceOf(PointsMap::class, $map);
        self::assertSame(['INPOST', '7', 'DPD'], $map->availableProviders);
        self::assertSame([
            ['CAST_VALUE', 'availableProviders[1]', 7, 'string'],
            ['CAST_VALUE', 'availableProviders[2]', ['x'], 'string'],
        ], self::drift($wrapper));
    }

    public function testAListItemThatIsNotAnObjectIsLeftOut(): void
    {
        $wrapper = Responses::readWrapped(Carrier::class . '[]', ['x', ...self::carrier()]);

        self::assertCount(1, self::carriers($wrapper));
        self::assertSame('DPD', self::carriers($wrapper)[0]->serviceCode);
        self::assertSame([['CAST_VALUE', '[0]', 'x', Carrier::class]], self::drift($wrapper));
    }

    public function testAnEnumValueOfAnotherTypeIsConvertedOrReadAsNull(): void
    {
        $wrapper = Responses::readWrapped(Address::class . '[]', [self::address(['type' => 12])]);

        /** @var list<Address> $addresses */
        $addresses = $wrapper->getEnvelope()->response;
        self::assertNull($addresses[0]->getType());
        self::assertSame([['CAST_VALUE', '[0].type', 12, AddressTypeEnum::class]], self::drift($wrapper));
    }

    public function testAnEnumListKeepsEachValueOnceAndRecordsTheUnknownOnes(): void
    {
        $wrapper = Responses::readWrapped(
            CourierPoint::class,
            ['functions' => ['send', 'CARRIER_ONLY', 'send', 'collect']] + Sample::wire(CourierPoint::class),
        );

        $point = $wrapper->getEnvelope()->response;
        self::assertInstanceOf(CourierPoint::class, $point);
        self::assertSame([PointFunctionEnum::Send, PointFunctionEnum::Collect], $point->functions);
        self::assertSame(
            [['UNKNOWN_ENUM_VALUE', 'functions[1]', 'CARRIER_ONLY', PointFunctionEnum::class]],
            self::drift($wrapper),
        );
    }

    public function testEveryEnumTheSdkDeclaresIsBackedByStrings(): void
    {
        $backing = [];
        foreach (SourceClasses::under('Enum') as $name => $class) {
            if (\enum_exists($class)) {
                $backing[$name] = (new \ReflectionEnum($class))->getBackingType()?->getName();
            }
        }

        self::assertNotSame([], $backing);
        self::assertSame(
            \array_fill_keys(\array_keys($backing), 'string'),
            $backing,
            'Lenient reading converts an enum value to the string the enum is backed by.',
        );
    }

    public function testAValueOfNoneOfAUnionsTypesReadsAsNull(): void
    {
        $wrapper = Responses::readWrapped(ContactFormValueMessage::class, ['name' => 'phone', 'value' => ['x']]);

        $value = $wrapper->getEnvelope()->response;
        self::assertInstanceOf(ContactFormValueMessage::class, $value);
        self::assertNull($value->value);
        self::assertSame([['CAST_VALUE', 'value', ['x'], 'string|int|float|bool']], self::drift($wrapper));
    }

    public function testTheItemsOfAMapOfListsAreConvertedOrLeftOut(): void
    {
        self::skipWithoutItemTypes();

        $wrapper = Responses::readBody(Carrier::class . '[]', (string)\json_encode([
            'status' => 'error',
            'response' => 'Błędne dane',
            'validation_errors' => ['name' => 'Pole wymagane', 'phone' => ['Zły numer', 5]],
        ]));

        $error = $wrapper->getEnvelope();
        self::assertInstanceOf(ErrorMessage::class, $error);
        self::assertSame(['phone' => ['Zły numer', '5']], $error->validationErrors);
        self::assertSame([
            ['CAST_VALUE', 'validationErrors[name]', 'Pole wymagane', 'array'],
            ['CAST_VALUE', 'validationErrors[phone][1]', 5, 'string'],
        ], self::drift($wrapper));
    }

    public function testAMissingFieldWithoutAnEmptyValueIsRecordedBeforeTheReadFails(): void
    {
        $drift = new ResponseDriftCollector();

        try {
            self::readLeniently([OrderEventMessage::FIELD_TYPE => 'UPDATED'], OrderEventMessage::class, $drift);
            self::fail('An order event without its order was read.');
        } catch (SerializerException) {
        }

        self::assertSame([['MISSING_FIELD', 'order', null, Order::class]], self::entries($drift->getDrift()));
    }

    public function testAValueOfAnotherTypeIsRecordedAsItArrivedWhereTheFieldHasNoEmptyValue(): void
    {
        $drift = new ResponseDriftCollector();

        try {
            self::readLeniently(
                [ErrorMessage::FIELD_STATUS => true, ErrorMessage::FIELD_RESPONSE => 'Brak'],
                ErrorMessage::class,
                $drift,
            );
            self::fail('An error envelope with a boolean status was read.');
        } catch (\TypeError) {
        }

        self::assertNotSame([], $drift->getDrift());
        foreach (self::entries($drift->getDrift()) as $entry) {
            self::assertSame(['CAST_VALUE', 'status', true, ResponseStatusEnum::class], $entry);
        }
    }

    public function testAnObjectWithNoneOfItsFieldsFails(): void
    {
        $this->expectException(MalformedResponseException::class);
        $this->expectExceptionMessage('None of the fields of ' . Carrier::class);

        Responses::readWrapped(Carrier::class . '[]', [['code' => 'DPD', 'label' => 'DPD Classic']]);
    }

    public function testAnObjectWithOneOfItsFieldsReads(): void
    {
        $wrapper = Responses::readWrapped(
            Carrier::class . '[]',
            [[Carrier::FIELD_NAME => 'DPD Classic', 'label' => 'x']],
        );

        self::assertSame('DPD Classic', self::carriers($wrapper)[0]->name);
    }

    public function testAnErrorEnvelopeRecordsItsUnknownFields(): void
    {
        $wrapper = Responses::readBody(
            Carrier::class . '[]',
            '{"status":"error","response":"Brak","brand_new":1}',
        );

        self::assertInstanceOf(ErrorMessage::class, $wrapper->getEnvelope());
        self::assertSame([['UNKNOWN_FIELD', 'brand_new', 1, null]], self::drift($wrapper));
    }

    public function testStrictReadingFailsOnAMissingField(): void
    {
        $carrier = self::carrier();
        unset($carrier[0][Carrier::FIELD_NAME]);

        $this->expectException(MalformedResponseException::class);
        Responses::readWrapped(Carrier::class . '[]', $carrier, new SerializerConfig(strictResponses: true));
    }

    public function testStrictReadingFailsOnAMissingFieldThatCanBeNull(): void
    {
        $this->expectException(MalformedResponseException::class);
        Responses::readWrapped(
            TimeIntervalMessage::class,
            [TimeIntervalMessage::FIELD_TIME_FROM => '10:00'],
            new SerializerConfig(strictResponses: true),
        );
    }

    public function testStrictReadingFailsOnADateThatDoesNotParse(): void
    {
        $this->expectException(MalformedResponseException::class);
        Responses::readWrapped(
            HeartbeatMessage::class,
            ['time' => 'garbage'],
            new SerializerConfig(strictResponses: true),
        );
    }

    public function testStrictReadingFailsOnAListItemOfAnotherType(): void
    {
        $this->expectException(MalformedResponseException::class);
        Responses::readWrapped(
            Carrier::class . '[]',
            ['x', ...self::carrier()],
            new SerializerConfig(strictResponses: true),
        );
    }

    public function testStrictReadingFailsOnAnEnumValueOfAnotherType(): void
    {
        $this->expectException(MalformedResponseException::class);
        Responses::readWrapped(
            Address::class . '[]',
            [self::address(['type' => 12])],
            new SerializerConfig(strictResponses: true),
        );
    }

    public function testStrictReadingStillReadsAnUnknownEnumValueAsNull(): void
    {
        $wrapper = Responses::readWrapped(
            Address::class . '[]',
            [self::address(['type' => 'SOMETHING_NEW'])],
            new SerializerConfig(strictResponses: true),
        );

        /** @var list<Address> $addresses */
        $addresses = $wrapper->getEnvelope()->response;
        self::assertNull($addresses[0]->getType());
        self::assertSame(
            [['UNKNOWN_ENUM_VALUE', '[0].type', 'SOMETHING_NEW', AddressTypeEnum::class]],
            self::drift($wrapper),
        );
    }

    /**
     * @param array<string, mixed> $extra
     *
     * @return list<array<string, mixed>>
     */
    private static function carrier(array $extra = []): array
    {
        return [[
            Carrier::FIELD_SERVICE_CODE => 'DPD',
            Carrier::FIELD_NAME => 'DPD Classic',
            Carrier::FIELD_FOREIGN_SHIPMENTS => false,
            ...$extra,
        ]];
    }

    /**
     * symfony/property-info 6.4 reads no item types, so its lists of strings, numbers or booleans read
     * as the API sent them.
     *
     * @return void
     */
    private static function skipWithoutItemTypes(): void
    {
        if (!(new \ReflectionClass(PropertyTypeExtractorInterface::class))->hasMethod('getType')) {
            self::markTestSkipped('symfony/property-info 6.4 reads no item types.');
        }
    }

    /**
     * @param array<string, mixed> $extra
     *
     * @return array<string, mixed>
     */
    private static function address(array $extra): array
    {
        return [
            'id' => '1', 'type' => 'SENDER', 'name' => 'Magazyn', 'person' => 'Jan Kowalski',
            'street' => 'Długa', 'housenumber' => '12', 'city' => 'Poznań', 'postcode' => '61-001',
            'email' => 'jan@example.com', 'phone' => '500600700',
            ...$extra,
        ];
    }

    /**
     * @param ResponseWrapper $wrapper
     *
     * @return list<Carrier>
     */
    private static function carriers(ResponseWrapper $wrapper): array
    {
        /** @var list<Carrier> $carriers */
        $carriers = $wrapper->getEnvelope()->response;

        return $carriers;
    }

    /**
     * A payload read on its own, without the envelope the response factory checks first.
     *
     * @param array<string, mixed> $payload
     * @param class-string $class
     * @param ResponseDriftCollector $drift
     *
     * @return mixed
     * @throws SerializerException when the payload doesn't map
     * @throws \TypeError when a constructor refuses what the denormalizer built
     */
    private static function readLeniently(array $payload, string $class, ResponseDriftCollector $drift): mixed
    {
        return (new SerializerProvider())->getSerializer()->denormalize(
            $payload,
            $class,
            JsonEncoder::FORMAT,
            [ResponseDriftCollector::CONTEXT_KEY => $drift],
        );
    }

    /**
     * @param ResponseWrapper $wrapper
     *
     * @return list<array{string, string, mixed, string|null}>
     */
    private static function drift(ResponseWrapper $wrapper): array
    {
        return self::entries($wrapper->getDrift());
    }

    /**
     * @param list<ResponseDrift> $drift
     *
     * @return list<array{string, string, mixed, string|null}>
     */
    private static function entries(array $drift): array
    {
        return \array_map(
            static fn(ResponseDrift $entry): array => [
                $entry->type->value,
                $entry->path,
                $entry->value,
                $entry->expected,
            ],
            $drift,
        );
    }
}
