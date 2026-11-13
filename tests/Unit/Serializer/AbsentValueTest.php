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
use Polkurier\Webservice\Dto\Response\Carrier\AdditionalData\Pickup;
use Polkurier\Webservice\Dto\Response\Order\IndividualPricing;
use Polkurier\Webservice\Entity\Order\Order;
use Polkurier\Webservice\Serializer\AbsentDateNormalizer;
use Polkurier\Webservice\Serializer\OmitEmptyNormalizer;
use Polkurier\Webservice\Serializer\SerializerProvider;

/**
 * The two ways the API says a value is not there.
 *
 * Neither is null. A block that does not apply is an empty array — an order without cash on
 * delivery carries `"COD": []` — and a date it does not have is an empty string, which is what
 * `delivered_date` holds while a parcel is in transit. Unhandled, the first refuses to construct,
 * and the second fails inside the date normalizer with a message about parse formats that names
 * neither the field nor the reason.
 */
#[CoversClass(OmitEmptyNormalizer::class)]
#[CoversClass(AbsentDateNormalizer::class)]
final class AbsentValueTest extends TestCase
{
    public function testAnEmptyBlockIsNoObjectRatherThanAnEmptyOne(): void
    {
        $order = self::order(['COD' => [], 'pickup' => []]);

        self::assertNull($order->getCod(), 'an empty COD block means no cash on delivery');
        self::assertNull($order->getPickup(), 'an empty pickup block means no collection arranged');
    }

    public function testAnEmptyStringWhereADateBelongsIsNoDate(): void
    {
        self::assertNull(self::order(['delivered_date' => ''])->getDeliveredDate());
    }

    /**
     * An order without a waybill, or before its first status, has an empty status date.
     */
    public function testAnOrderWithoutAWaybillHasNoStatusDate(): void
    {
        self::assertNull(self::order(['status_date' => ''])->getStatusDate());
    }

    public function testARealDateStillParses(): void
    {
        $order = self::order(['delivered_date' => '2026-09-24']);

        self::assertSame('2026-09-24', $order->getDeliveredDate()?->format('Y-m-d'));
    }

    /**
     * An offer that hasn't been confirmed has no confirmation date, and may have no validity date.
     */
    public function testAnUnconfirmedOfferHasNoConfirmationDate(): void
    {
        $pricing = (new SerializerProvider())->getSerializer()->denormalize([
            'offer_sent_date' => '2026-09-01 10:00:00',
            'offer_valid_date' => '',
            'customer_confirmed_date' => null,
        ], IndividualPricing::class, 'json');

        self::assertInstanceOf(IndividualPricing::class, $pricing);
        self::assertNull($pricing->offerValidDate);
        self::assertNull($pricing->customerConfirmedDate);
        self::assertSame('', $pricing->customerRemarks);
    }

    /**
     * A carrier that leaves out parts of its additional data still reads, with those parts null,
     * so one sparse carrier doesn't fail the whole carrier list.
     */
    public function testACarrierWithSparseAdditionalDataStillReads(): void
    {
        $carriers = (new SerializerProvider())->getSerializer()->denormalize([
            [
                'servicecode' => 'UPS',
                'name' => 'UPS',
                'foreign_shipments' => true,
                'additional_data' => [
                    'pickup' => [Pickup::FIELD_PICKUP_DATE => ['status' => true]],
                    'points_map' => ['sender_point' => 'NONE'],
                ],
            ],
        ], Carrier::class . '[]', 'json');

        self::assertIsArray($carriers);
        self::assertInstanceOf(Carrier::class, $carriers[0]);
        $data = $carriers[0]->additionalData;
        self::assertNotNull($data);
        self::assertNull($data->cod);
        self::assertNull($data->insurance);
        self::assertNull($data->pickup?->pickupTimeFrom);
        self::assertNull($data->pointsMap?->recipientPoint);
        self::assertSame([], $data->pointsMap?->availableProviders);
    }

    /**
     * The address shape the order list carries. Populated, because an empty one is read as absent,
     * which is the behaviour under test, not the fixture's job to exercise.
     *
     * @return array<string, string>
     */
    private static function address(): array
    {
        return [
            'person' => 'Jan Kowalski',
            'street' => 'Długa 1',
            'city' => 'Poznań',
            'postcode' => '61-001',
            'email' => 'jan@example.com',
            'phone' => '500600700',
            'country' => 'PL',
        ];
    }

    /**
     * An order carrying only what it must, plus whatever the case under test needs.
     *
     * @param array<string, mixed> $overrides
     *
     * @return Order
     */
    private static function order(array $overrides): Order
    {
        $payload = [
            'number' => '12345-2476',
            'status' => 'Dostarczone',
            'status_code' => 'D',
            'date' => '2018-04-13T14:28:20+02:00',
            'status_date' => '2018-05-16T08:52:00+02:00',
            'shipmenttype' => 'box',
            'courier' => 'ETL',
            'sender' => self::address(),
            'recipient' => self::address(),
            'waybills' => [],
            'price_net' => 119.0,
            'price_gross' => 146.37,
            'unpaid_amount' => 0.0,
        ] + $overrides;

        /** @var Order $order */
        $order = (new SerializerProvider())->getSerializer()
            ->denormalize($overrides + $payload, Order::class, 'json');

        return $order;
    }
}
