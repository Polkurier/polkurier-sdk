<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Unit\Serializer;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Polkurier\Webservice\Dto\Response\Account\AccountInfoMessage;
use Polkurier\Webservice\Dto\Response\Account\ExternalApplicationSettingMessage;
use Polkurier\Webservice\Dto\Response\Carrier\AdditionalData\Property;
use Polkurier\Webservice\Dto\Response\Contact\ContactFormFieldMessage;
use Polkurier\Webservice\Dto\Response\Contact\ContactFormMessage;
use Polkurier\Webservice\Dto\Response\Contact\ContactFormOptionMessage;
use Polkurier\Webservice\Dto\Response\Contact\ContactFormResultDataMessage;
use Polkurier\Webservice\Dto\Response\Contact\ContactFormValueMessage;
use Polkurier\Webservice\Dto\Response\CourierPoint;
use Polkurier\Webservice\Dto\Response\Order\DocumentMessage;
use Polkurier\Webservice\Dto\Response\Order\OrderListMessage;
use Polkurier\Webservice\Dto\Response\Order\TrackingEventMessage;
use Polkurier\Webservice\Dto\Response\TwoFactorToken;
use Polkurier\Webservice\Enum\Courier\PointFunctionEnum;
use Polkurier\Webservice\Enum\Serializer\ResponseDriftTypeEnum;
use Polkurier\Webservice\Serializer\ResponseDriftCollector;
use Polkurier\Webservice\Serializer\ResponseQuirksDenormalizer;
use Polkurier\Webservice\Serializer\SerializerProvider;
use Polkurier\Webservice\Test\Support\Sample;

/**
 * Each known quirk of the API's responses reads as a clean value, without being reported as drift.
 */
#[CoversClass(ResponseQuirksDenormalizer::class)]
final class ResponseQuirksTest extends TestCase
{
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

    public function testAPointKeepsTheFunctionsTheSdkKnowsEachOnce(): void
    {
        $point = $this->read(CourierPoint::class, [
            'functions' => ['send', 'CARRIER_ONLY', 'send', 'collect'],
        ] + Sample::wire(CourierPoint::class));

        self::assertInstanceOf(CourierPoint::class, $point);
        self::assertSame([PointFunctionEnum::Send, PointFunctionEnum::Collect], $point->functions);
        self::assertSame([], $this->drift->all());
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
