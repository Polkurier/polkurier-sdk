<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Unit\Message;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Polkurier\Webservice\Entity\Order\PackItem;
use Polkurier\Webservice\Entity\Pack;
use Polkurier\Webservice\Entity\PackTemplate;
use Polkurier\Webservice\Enum\Order\PackTypeEnum;
use Polkurier\Webservice\Enum\Order\ShipmentTypeEnum;

/**
 * The request messages that hand their arguments down to a parent.
 *
 * A positional hand-off is only as good as the parameter order on the other side, and the order on
 * the other side is not visible from the call. That is how an address once mapped its company onto
 * its street: the parent's signature changed, the child kept passing five arguments, and every
 * test still passed because each one was a string. The dimensions here are three ints and a float,
 * which is the same trap with the same silence, so each one is read back by name.
 */
#[CoversClass(Pack::class)]
#[CoversClass(PackItem::class)]
#[CoversClass(PackTemplate::class)]
final class RequestMessageConstructionTest extends TestCase
{
    public function testPackPassesEveryDimensionThroughToTheParentInTheRightOrder(): void
    {
        $pack = new PackItem(
            length: 30,
            width: 20,
            height: 15,
            weight: 2.5,
            type: PackTypeEnum::Standard,
            amount: 3,
        );

        self::assertSame(30, $pack->getLength());
        self::assertSame(20, $pack->getWidth());
        self::assertSame(15, $pack->getHeight());
        self::assertSame(2.5, $pack->getWeight());
        self::assertSame(PackTypeEnum::Standard, $pack->getType());
        self::assertSame(3, $pack->getAmount());
    }

    public function testPackTemplatePassesEveryDimensionThroughToTheParentInTheRightOrder(): void
    {
        $template = new PackTemplate(
            name: 'Karton A',
            length: 30,
            width: 20,
            height: 15,
            weight: 2.5,
            shipmentType: ShipmentTypeEnum::Box,
        );

        self::assertSame(30, $template->getLength());
        self::assertSame(20, $template->getWidth());
        self::assertSame(15, $template->getHeight());
        self::assertSame(2.5, $template->getWeight());
        self::assertSame('Karton A', $template->getName());
        self::assertSame(ShipmentTypeEnum::Box, $template->getShipmentType());
        self::assertNull($template->getId());
    }

    /**
     * Every optional parameter has to follow every required one, or construction by named
     * arguments fails outright on PHP 8.
     */
    public function testConstructsFromRequiredNamedArgumentsAlone(): void
    {
        $pack = new PackItem(length: 30, width: 20, height: 15, weight: 2.5);

        self::assertSame(1, $pack->getAmount());
        self::assertSame(PackTypeEnum::Standard, $pack->getType());
    }
}
