<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Unit\Serializer;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Polkurier\Webservice\Dto\Response\Order\OrderStatusMessage;
use Polkurier\Webservice\Entity\Order\Pickup;
use Polkurier\Webservice\Serializer\ExactDateTimeNormalizer;
use Polkurier\Webservice\Serializer\SerializerProvider;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * A date reads from the fields its format has, never from the moment it was read.
 */
#[CoversNothing]
final class ExactDateTest extends TestCase
{
    private const array PICKUP = [
        'pickupdate' => '2026-10-08',
        'pickuptimefrom' => '10:00',
        'pickuptimeto' => '14:30',
    ];

    public function testTheSameResponseReadTwiceGivesEqualDates(): void
    {
        $first = self::pickup();
        \usleep(1100000);

        self::assertEquals($first, self::pickup());
    }

    public function testADateReadsAsMidnight(): void
    {
        self::assertSame('2026-10-08 00:00:00.000000', self::pickup()->getPickupDate()?->format('Y-m-d H:i:s.u'));
    }

    public function testATimeReadsOnTheFirstOfJanuary1970WithNoSeconds(): void
    {
        $pickup = self::pickup();

        self::assertSame('1970-01-01 10:00:00.000000', $pickup->getPickupTimeFrom()?->format('Y-m-d H:i:s.u'));
        self::assertSame('1970-01-01 14:30:00.000000', $pickup->getPickupTimeTo()?->format('Y-m-d H:i:s.u'));
    }

    public function testADateAndTimeWithoutSecondsReadsWithNone(): void
    {
        $status = (new SerializerProvider())->getSerializer()->denormalize(
            ['status_date' => '2026-10-08 12:34'],
            OrderStatusMessage::class,
            'json',
        );

        self::assertInstanceOf(OrderStatusMessage::class, $status);
        self::assertSame('2026-10-08 12:34:00.000000', $status->statusDate?->format('Y-m-d H:i:s.u'));
    }

    public function testWritingKeepsTheFormat(): void
    {
        $serializer = (new SerializerProvider())->getSerializer();
        self::assertInstanceOf(NormalizerInterface::class, $serializer);

        $wire = $serializer->normalize(self::pickup(), 'json');

        self::assertIsArray($wire);
        self::assertSame(self::PICKUP, \array_intersect_key($wire, self::PICKUP));
    }

    public function testATimestampAndAFormatThatAlreadyResetsAreReadAsTheyAre(): void
    {
        $normalizer = new ExactDateTimeNormalizer();
        $read = static fn(int|string $value, string $format): \DateTimeInterface => $normalizer->denormalize(
            $value,
            \DateTimeImmutable::class,
            null,
            [DateTimeNormalizer::FORMAT_KEY => $format],
        );

        self::assertSame(1791460800, $read(1791460800, 'U')->getTimestamp());
        self::assertSame('2026-10-08 00:00:00', $read('2026-10-08', 'Y-m-d|')->format('Y-m-d H:i:s'));
    }

    private static function pickup(): Pickup
    {
        $pickup = (new SerializerProvider())->getSerializer()->denormalize(self::PICKUP, Pickup::class, 'json');
        self::assertInstanceOf(Pickup::class, $pickup);

        return $pickup;
    }
}
