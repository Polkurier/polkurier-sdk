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
use Polkurier\Webservice\Dto\Response\Carrier\AdditionalData\Property;
use Polkurier\Webservice\Dto\Response\Contact\ContactFormFieldMessage;
use Polkurier\Webservice\Dto\Response\Contact\ContactFormOptionMessage;
use Polkurier\Webservice\Dto\Response\CourierPoint;
use Polkurier\Webservice\Dto\Response\Order\OrderListMessage;
use Polkurier\Webservice\Dto\Response\Order\TrackingEventMessage;
use Polkurier\Webservice\Dto\Response\TwoFactorToken;
use Polkurier\Webservice\Entity\Order;
use Polkurier\Webservice\Enum\Courier\PointFunctionEnum;
use Polkurier\Webservice\Enum\Serializer\ResponseDriftTypeEnum;
use Polkurier\Webservice\Serializer\ResponseDrift;
use Polkurier\Webservice\Serializer\ResponseDriftCollector;
use Polkurier\Webservice\Serializer\ResponseQuirksDenormalizer;
use Polkurier\Webservice\Serializer\SerializerConfig;
use Polkurier\Webservice\Serializer\SerializerProvider;
use Polkurier\Webservice\Test\Support\Sample;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;

/**
 * Each known quirk of the API's responses reads as a clean value. A quirk that drops a value
 * because the SDK has nowhere to put it reports what it dropped as drift.
 */
#[CoversClass(ResponseQuirksDenormalizer::class)]
final class ResponseQuirksTest extends TestCase
{
    /**
     * The fields of an order that every `courierservice` case below is read with.
     *
     * @var array<string, string>
     */
    private const array ORDER = ['number' => '20534-7674', 'status' => 'Nowe', 'courier' => 'DPD'];

    private ResponseDriftCollector $drift;

    protected function setUp(): void
    {
        $this->drift = new ResponseDriftCollector();
    }

    public function testNullPagingReadsAsZero(): void
    {
        $page = $this->read(OrderListMessage::class, [
            'totalrows' => null, 'currentpage' => null, 'pagesize' => null, 'totalpages' => null, 'result' => [],
        ]);

        self::assertInstanceOf(OrderListMessage::class, $page);
        self::assertSame([0, 0, 0, 0], [$page->totalRows, $page->currentPage, $page->pageSize, $page->totalPages]);
        self::assertSame([], $this->drift->all());
    }

    public function testATrackingEventWithoutLocationOrDescriptionReadsAsEmpty(): void
    {
        $event = $this->read(TrackingEventMessage::class, [
            'status_code' => 'WP', 'status' => 'Doręczona', 'location' => null, 'description' => null,
        ]);

        self::assertInstanceOf(TrackingEventMessage::class, $event);
        self::assertSame(['', ''], [$event->location, $event->description]);
        self::assertSame([], $this->drift->all());
    }

    public function testAnOptionWithoutADefaultReadsAsOff(): void
    {
        $property = $this->read(Property::class, ['available' => true, 'description' => '', 'default' => null]);

        self::assertInstanceOf(Property::class, $property);
        self::assertFalse($property->default);
        self::assertSame([], $this->drift->all());
    }

    public function testADropDownReadsItsChoicesOrTheirSource(): void
    {
        $listed = $this->read(ContactFormFieldMessage::class, [
            'name' => 'target', 'options' => [['value' => 'DPD', 'label' => 'DPD']],
        ]);
        $sourced = $this->read(ContactFormFieldMessage::class, ['name' => 'target', 'options' => '@COURIERS']);

        self::assertInstanceOf(ContactFormFieldMessage::class, $listed);
        self::assertEquals([new ContactFormOptionMessage('DPD', 'DPD')], $listed->options);
        self::assertSame('', $listed->optionsSource);
        self::assertInstanceOf(ContactFormFieldMessage::class, $sourced);
        self::assertSame([], $sourced->options);
        self::assertSame('@COURIERS', $sourced->optionsSource);
        self::assertSame([], $this->drift->all());
    }

    public function testAPointKeepsTheFunctionsTheSdkKnowsEachOnceAndReportsTheRest(): void
    {
        $point = $this->read(CourierPoint::class, [
            'functions' => ['send', 'CARRIER_ONLY', 'send', 'collect'],
        ] + Sample::wire(CourierPoint::class));

        self::assertInstanceOf(CourierPoint::class, $point);
        self::assertSame([PointFunctionEnum::Send, PointFunctionEnum::Collect], $point->functions);
        self::assertEquals(
            [new ResponseDrift(
                ResponseDriftTypeEnum::UnknownEnumValue,
                'functions[1]',
                'CARRIER_ONLY',
                PointFunctionEnum::class,
            )],
            $this->drift->all(),
        );
    }

    public function testTransportMethodsKeyedByMethodReadAsAList(): void
    {
        $wire = Sample::wire(TwoFactorToken::class);
        $wire['available_methods'] = ['sms' => $wire['available_methods'][0] ?? []];

        $token = $this->read(TwoFactorToken::class, $wire);

        self::assertInstanceOf(TwoFactorToken::class, $token);
        self::assertTrue(\array_is_list($token->availableMethods));
    }

    /**
     * What `courierservice` has been seen to hold, the names the order reads from it, and whether
     * the value is reported.
     *
     * @return array<string, array{mixed, string[]|null, bool}>
     */
    public static function servicesAnOrderComesWith(): array
    {
        return [
            'the names of the services bought' => [
                ['Pobranie (COD)', 'Ubezpieczenie przesyłki'],
                ['Pobranie (COD)', 'Ubezpieczenie przesyłki'],
                false,
            ],
            'the flag map the order was sent with' => [['COD' => true, 'INSURANCE' => false], null, false],
            'nothing at all' => [null, null, false],
            'the names keyed by service id' => [['1' => 'Pobranie (COD)'], null, true],
            'one name on its own' => ['Pobranie (COD)', null, true],
        ];
    }

    /**
     * An order answers with the names of the services it was bought with under the key their codes
     * are sent under. The flag map that was sent says only which services were asked for, which the
     * names already report, so it is dropped silently; any other shape loses what it holds and is
     * reported.
     *
     * @param mixed $services
     * @param string[]|null $names
     * @param bool $reported
     *
     * @return void
     */
    #[DataProvider('servicesAnOrderComesWith')]
    public function testAnOrderReadsTheServiceNamesAndReportsAnyOtherShape(
        mixed $services,
        ?array $names,
        bool $reported,
    ): void {
        $order = $this->read(Order::class, ['courierservice' => $services] + self::ORDER);

        self::assertInstanceOf(Order::class, $order);
        self::assertSame($names, $order->getCourierServiceNames());
        self::assertEquals(
            $reported
                ? [new ResponseDrift(ResponseDriftTypeEnum::CastValue, 'courierservice', $services, 'string[]')]
                : [],
            $this->drift->all(),
        );
    }

    /**
     * An order of your own, subclassing the SDK's, reads the names as the SDK's order does.
     *
     * @return void
     */
    public function testASubclassOfTheOrderReadsTheServiceNamesToo(): void
    {
        $subclass = (new class('DPD') extends Order {})::class;

        $order = $this->read($subclass, ['courierservice' => ['Pobranie (COD)']] + self::ORDER);

        self::assertInstanceOf($subclass, $order);
        self::assertSame(['Pobranie (COD)'], $order->getCourierServiceNames());
        self::assertSame([], $this->drift->all());
    }

    /**
     * Reading strictly, a shape the names can't be read from fails the whole response, as every
     * other reported difference does.
     *
     * @return void
     */
    public function testAServicesShapeTheSdkCannotReadFailsStrictReading(): void
    {
        $this->expectException(UnexpectedValueException::class);

        (new SerializerProvider(new SerializerConfig(strictResponses: true)))->getSerializer()->denormalize(
            ['courierservice' => ['1' => 'Pobranie (COD)']] + self::ORDER,
            Order::class,
            'json',
            [ResponseDriftCollector::CONTEXT_KEY => $this->drift],
        );
    }

    /**
     * @param class-string $class
     * @param array<string, mixed> $wire
     *
     * @return mixed
     */
    private function read(string $class, array $wire): mixed
    {
        return (new SerializerProvider())->getSerializer()->denormalize($wire, $class, 'json', [
            ResponseDriftCollector::CONTEXT_KEY => $this->drift,
        ]);
    }
}
