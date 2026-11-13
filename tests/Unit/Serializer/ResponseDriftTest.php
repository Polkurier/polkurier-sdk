<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Unit\Serializer;

use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Polkurier\Webservice\Enum\Address\AddressTypeEnum;
use Polkurier\Webservice\Exception\MalformedResponseException;
use Polkurier\Webservice\Http\Response\ErrorMessage;
use Polkurier\Webservice\Http\Response\ResponseFactory;
use Polkurier\Webservice\Http\Response\ResponseWrapper;
use Polkurier\Webservice\Response\Address;
use Polkurier\Webservice\Response\Carrier;
use Polkurier\Webservice\Response\Carrier\AdditionalData\PointsMap;
use Polkurier\Webservice\Response\Contact\ContactFormValueMessage;
use Polkurier\Webservice\Response\Courier\TimeIntervalMessage;
use Polkurier\Webservice\Response\Util\HeartbeatMessage;
use Polkurier\Webservice\Serializer\LenientDateDenormalizer;
use Polkurier\Webservice\Serializer\LenientEnumDenormalizer;
use Polkurier\Webservice\Serializer\LenientListDenormalizer;
use Polkurier\Webservice\Serializer\LenientObjectDenormalizer;
use Polkurier\Webservice\Serializer\ResponseDrift;
use Polkurier\Webservice\Serializer\SerializerConfig;
use Polkurier\Webservice\Serializer\SerializerProvider;
use Polkurier\Webservice\Serializer\StrictResponseSerializer;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;

/**
 * A response that differs from what this SDK version expects still reads, and each difference is
 * recorded, so a change on the API's side neither breaks the application nor passes unnoticed.
 */
#[CoversClass(LenientObjectDenormalizer::class)]
#[CoversClass(LenientDateDenormalizer::class)]
#[CoversClass(LenientListDenormalizer::class)]
#[CoversClass(LenientEnumDenormalizer::class)]
#[CoversClass(StrictResponseSerializer::class)]
final class ResponseDriftTest extends TestCase
{
    public function testAnUnknownFieldIsRecordedAndIgnored(): void
    {
        $wrapper = self::read(self::carrier(['brand_new' => 'x']), Carrier::class . '[]');

        self::assertSame('DPD', self::carriers($wrapper)[0]->getServiceCode());
        self::assertSame([['UNKNOWN_FIELD', '[0].brand_new', 'x', null]], self::drift($wrapper));
    }

    public function testAMissingFieldReadsAsAnEmptyValueAndIsRecorded(): void
    {
        $carrier = self::carrier();
        unset($carrier[0][Carrier::FIELD_NAME]);

        $wrapper = self::read($carrier, Carrier::class . '[]');

        self::assertSame('', self::carriers($wrapper)[0]->getName());
        self::assertSame([['MISSING_FIELD', '[0].name', null, 'string']], self::drift($wrapper));
    }

    public function testANullFieldReadsAsAnEmptyValueAndIsRecorded(): void
    {
        $wrapper = self::read(self::carrier([Carrier::FIELD_NAME => null]), Carrier::class . '[]');

        self::assertSame('', self::carriers($wrapper)[0]->getName());
        self::assertSame([['MISSING_FIELD', '[0].name', null, 'string']], self::drift($wrapper));
    }

    public function testAnOptionalFieldLeftOutIsNotRecorded(): void
    {
        self::assertSame([], self::drift(self::read(self::carrier(), Carrier::class . '[]')));
    }

    public function testASimpleValueOfAnotherTypeIsConverted(): void
    {
        $wrapper = self::read(
            self::carrier([Carrier::FIELD_SERVICE_CODE => 12, Carrier::FIELD_FOREIGN_SHIPMENTS => 'true']),
            Carrier::class . '[]',
        );

        $carrier = self::carriers($wrapper)[0];
        self::assertSame('12', $carrier->getServiceCode());
        self::assertTrue($carrier->isForeignShipments());
        self::assertSame([
            ['CAST_VALUE', '[0].serviceCode', 12, 'string'],
            ['CAST_VALUE', '[0].foreignShipments', 'true', 'bool'],
        ], self::drift($wrapper));
    }

    public function testAValueThatCanNotBeConvertedReadsAsAnEmptyValue(): void
    {
        $wrapper = self::read(self::carrier([Carrier::FIELD_NAME => ['x']]), Carrier::class . '[]');

        self::assertSame('', self::carriers($wrapper)[0]->getName());
        self::assertSame([['CAST_VALUE', '[0].name', ['x'], 'string']], self::drift($wrapper));
    }

    public function testAMissingDateReadsAsNull(): void
    {
        $wrapper = self::read([TimeIntervalMessage::FIELD_TIME_TO => '12:00'], TimeIntervalMessage::class);

        $interval = $wrapper->getEnvelope()->getResponse();
        self::assertInstanceOf(TimeIntervalMessage::class, $interval);
        self::assertNull($interval->getTimeFrom());
        self::assertSame([['MISSING_FIELD', 'timeFrom', null, \DateTimeInterface::class]], self::drift($wrapper));
    }

    public function testADateThatDoesNotParseReadsAsNullAndIsRecorded(): void
    {
        $wrapper = self::read(['time' => 'garbage'], HeartbeatMessage::class);

        $heartbeat = $wrapper->getEnvelope()->getResponse();
        self::assertInstanceOf(HeartbeatMessage::class, $heartbeat);
        self::assertNull($heartbeat->getTime());
        self::assertSame([['CAST_VALUE', 'time', 'garbage', \DateTimeInterface::class]], self::drift($wrapper));
    }

    public function testAListItemOfAnotherTypeIsConvertedOrLeftOut(): void
    {
        self::skipWithoutItemTypes();

        $wrapper = self::read(
            [PointsMap::FIELD_AVAILABLE_PROVIDERS => ['INPOST', 7, ['x'], 'DPD']],
            PointsMap::class,
        );

        $map = $wrapper->getEnvelope()->getResponse();
        self::assertInstanceOf(PointsMap::class, $map);
        self::assertSame(['INPOST', '7', 'DPD'], $map->getAvailableProviders());
        self::assertSame([
            ['CAST_VALUE', 'availableProviders[1]', 7, 'string'],
            ['CAST_VALUE', 'availableProviders[2]', ['x'], 'string'],
        ], self::drift($wrapper));
    }

    public function testAListItemThatIsNotAnObjectIsLeftOut(): void
    {
        $wrapper = self::read(['x', ...self::carrier()], Carrier::class . '[]');

        self::assertCount(1, self::carriers($wrapper));
        self::assertSame('DPD', self::carriers($wrapper)[0]->getServiceCode());
        self::assertSame([['CAST_VALUE', '[0]', 'x', Carrier::class]], self::drift($wrapper));
    }

    public function testAnEnumValueOfAnotherTypeIsConvertedOrReadAsNull(): void
    {
        $wrapper = self::read([self::address(['type' => 12])], Address::class . '[]');

        /** @var list<Address> $addresses */
        $addresses = $wrapper->getEnvelope()->getResponse();
        self::assertNull($addresses[0]->getType());
        self::assertSame([['CAST_VALUE', '[0].type', 12, AddressTypeEnum::class]], self::drift($wrapper));
    }

    public function testAValueOfNoneOfAUnionsTypesReadsAsNull(): void
    {
        $wrapper = self::read(['name' => 'phone', 'value' => ['x']], ContactFormValueMessage::class);

        $value = $wrapper->getEnvelope()->getResponse();
        self::assertInstanceOf(ContactFormValueMessage::class, $value);
        self::assertNull($value->getValue());
        self::assertSame([['CAST_VALUE', 'value', ['x'], 'string|int|float|bool']], self::drift($wrapper));
    }

    public function testTheItemsOfAMapOfListsAreConvertedOrLeftOut(): void
    {
        self::skipWithoutItemTypes();

        $wrapper = (new ResponseFactory(new SerializerProvider()))->create(
            new Response(200, [], (string)\json_encode([
                'status' => 'error',
                'response' => 'Błędne dane',
                'validation_errors' => ['name' => 'Pole wymagane', 'phone' => ['Zły numer', 5]],
            ])),
            Carrier::class . '[]',
        );

        $error = $wrapper->getEnvelope();
        self::assertInstanceOf(ErrorMessage::class, $error);
        self::assertSame(['phone' => ['Zły numer', '5']], $error->getValidationErrors());
        self::assertSame([
            ['CAST_VALUE', 'validationErrors[name]', 'Pole wymagane', 'array'],
            ['CAST_VALUE', 'validationErrors[phone][1]', 5, 'string'],
        ], self::drift($wrapper));
    }

    public function testAnObjectWithNoneOfItsFieldsFails(): void
    {
        $this->expectException(MalformedResponseException::class);
        $this->expectExceptionMessage('None of the fields of ' . Carrier::class);

        self::read([['code' => 'DPD', 'label' => 'DPD Classic']], Carrier::class . '[]');
    }

    public function testAnObjectWithOneOfItsFieldsReads(): void
    {
        $wrapper = self::read([[Carrier::FIELD_NAME => 'DPD Classic', 'label' => 'x']], Carrier::class . '[]');

        self::assertSame('DPD Classic', self::carriers($wrapper)[0]->getName());
    }

    public function testAnErrorEnvelopeRecordsItsUnknownFields(): void
    {
        $wrapper = (new ResponseFactory(new SerializerProvider()))->create(
            new Response(200, [], '{"status":"error","response":"Brak","brand_new":1}'),
            Carrier::class . '[]',
        );

        self::assertInstanceOf(ErrorMessage::class, $wrapper->getEnvelope());
        self::assertSame([['UNKNOWN_FIELD', 'brand_new', 1, null]], self::drift($wrapper));
    }

    public function testStrictReadingFailsOnAMissingField(): void
    {
        $carrier = self::carrier();
        unset($carrier[0][Carrier::FIELD_NAME]);

        $this->expectException(MalformedResponseException::class);
        self::read($carrier, Carrier::class . '[]', new SerializerConfig(strictResponses: true));
    }

    public function testStrictReadingFailsOnAMissingFieldThatCanBeNull(): void
    {
        $this->expectException(MalformedResponseException::class);
        self::read(
            [TimeIntervalMessage::FIELD_TIME_FROM => '10:00'],
            TimeIntervalMessage::class,
            new SerializerConfig(strictResponses: true),
        );
    }

    public function testStrictReadingFailsOnADateThatDoesNotParse(): void
    {
        $this->expectException(MalformedResponseException::class);
        self::read(['time' => 'garbage'], HeartbeatMessage::class, new SerializerConfig(strictResponses: true));
    }

    public function testStrictReadingFailsOnAListItemOfAnotherType(): void
    {
        $this->expectException(MalformedResponseException::class);
        self::read(['x', ...self::carrier()], Carrier::class . '[]', new SerializerConfig(strictResponses: true));
    }

    public function testStrictReadingFailsOnAnEnumValueOfAnotherType(): void
    {
        $this->expectException(MalformedResponseException::class);
        self::read(
            [self::address(['type' => 12])],
            Address::class . '[]',
            new SerializerConfig(strictResponses: true),
        );
    }

    public function testStrictReadingStillReadsAnUnknownEnumValueAsNull(): void
    {
        $wrapper = self::read(
            [self::address(['type' => 'SOMETHING_NEW'])],
            Address::class . '[]',
            new SerializerConfig(strictResponses: true),
        );

        /** @var list<Address> $addresses */
        $addresses = $wrapper->getEnvelope()->getResponse();
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
     * @param mixed $payload
     * @param string $format
     * @param SerializerConfig $config
     *
     * @return ResponseWrapper
     */
    private static function read(
        mixed $payload,
        string $format,
        SerializerConfig $config = new SerializerConfig(),
    ): ResponseWrapper {
        $body = (string)\json_encode(['status' => 'success', 'response' => $payload]);

        return (new ResponseFactory(new SerializerProvider($config)))->create(new Response(200, [], $body), $format);
    }

    /**
     * @param ResponseWrapper $wrapper
     *
     * @return list<Carrier>
     */
    private static function carriers(ResponseWrapper $wrapper): array
    {
        /** @var list<Carrier> $carriers */
        $carriers = $wrapper->getEnvelope()->getResponse();

        return $carriers;
    }

    /**
     * @param ResponseWrapper $wrapper
     *
     * @return list<array{string, string, mixed, string|null}>
     */
    private static function drift(ResponseWrapper $wrapper): array
    {
        return \array_map(
            static fn(ResponseDrift $drift): array => [
                $drift->getType()->value,
                $drift->getPath(),
                $drift->getValue(),
                $drift->getExpected(),
            ],
            $wrapper->getDrift(),
        );
    }
}
