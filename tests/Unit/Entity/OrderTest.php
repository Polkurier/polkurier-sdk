<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Unit\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Polkurier\Webservice\Entity\Order;
use Polkurier\Webservice\Enum\Serializer\ResponseDriftTypeEnum;
use Polkurier\Webservice\Serializer\ResponseDriftCollector;
use Polkurier\Webservice\Serializer\SerializerProvider;

/**
 * The order, read back from the API and sent to it.
 */
#[CoversClass(Order::class)]
final class OrderTest extends TestCase
{
    /**
     * The carrier-specific fields are a map and nothing else: an order that was never given any,
     * and one the API answers with a null under the key, both read as an empty map, so a caller
     * iterates them without a null check. A null is recorded as drift, which a strict read
     * refuses.
     *
     * @return void
     */
    public function testCarrierFieldsAreAMapWhateverTheResponseCarries(): void
    {
        self::assertSame([], (new Order(courier: 'INPOST'))->getAdditionalFields());

        $drift = new ResponseDriftCollector();
        $order = (new SerializerProvider())->getSerializer()->denormalize([
            Order::FIELD_COURIER => 'INPOST',
            Order::FIELD_ADDITIONAL_FIELDS => null,
        ], Order::class, 'json', [ResponseDriftCollector::CONTEXT_KEY => $drift]);

        self::assertInstanceOf(Order::class, $order);
        self::assertSame([], $order->getAdditionalFields());
        self::assertSame(
            [[ResponseDriftTypeEnum::MissingField, 'additionalFields']],
            \array_map(
                static fn($entry): array => [$entry->type, $entry->path],
                $drift->getDrift(),
            ),
        );
    }
}
