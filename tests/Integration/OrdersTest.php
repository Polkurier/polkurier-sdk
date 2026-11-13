<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Integration;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\Group;
use Polkurier\Webservice\Dto\Request\Order\CancelOrderMessage;
use Polkurier\Webservice\Dto\Request\Order\GetDocumentMessage;
use Polkurier\Webservice\Dto\Request\Order\GetOrderFileMessage;
use Polkurier\Webservice\Dto\Request\Order\GetOrdersMessage;
use Polkurier\Webservice\Dto\Request\Order\GetStatusMessage;
use Polkurier\Webservice\Dto\Request\Order\MultiPickupAvailabilityRequestMessage;
use Polkurier\Webservice\Dto\Request\Order\MultiPickupRequestMessage;
use Polkurier\Webservice\Enum\Order\ShipmentTypeEnum;

/**
 * One order placed, read back in every way and cancelled.
 */
#[Group('integration')]
#[CoversNothing]
final class OrdersTest extends IntegrationTestCase
{
    public function testAnOrderIsPricedByEveryCarrier(): void
    {
        self::assertNotEmpty($this->sdk->orders()->orderValuation(self::order()));
    }

    public function testAnOrderIsPricedByOneCarrier(): void
    {
        $quotes = $this->sdk->orders()->orderValuation(self::order(), 'DPD');

        self::assertCount(1, $quotes);
    }

    public function testOrdersReadAsAPage(): void
    {
        $page = $this->sdk->orders()->getOrders(new GetOrdersMessage(pageSize: 5, page: 1));

        self::assertLessThanOrEqual(5, \count($page->getResult()));
    }

    public function testAnOrderIsPlaced(): string
    {
        self::requireSandbox();

        $created = $this->sdk->orders()->createOrder(self::order());

        self::assertNotSame('', $created->getOrderNumber());

        return $created->getOrderNumber();
    }

    #[Depends('testAnOrderIsPlaced')]
    public function testTheOrderReadsBackByItsNumber(string $orderNumber): void
    {
        $page = $this->sdk->orders()->getOrders(new GetOrdersMessage(orderNo: $orderNumber));

        self::assertSame($orderNumber, $page->getResult()[0]->getNumber());
    }

    #[Depends('testAnOrderIsPlaced')]
    public function testTheOrderHasAStatus(string $orderNumber): void
    {
        $this->assertReads(fn() => $this->sdk->orders()->getStatus(new GetStatusMessage(orderNo: $orderNumber)));
    }

    #[Depends('testAnOrderIsPlaced')]
    public function testTheOrderHasALabelAndAProtocol(string $orderNumber): void
    {
        $label = $this->sdk->orders()->getLabel(new GetDocumentMessage(orderNo: [$orderNumber]));
        $protocol = $this->sdk->orders()->getProtocol(new GetDocumentMessage(orderNo: [$orderNumber]));

        self::assertNotSame('', $label->getDecodedFile());
        self::assertNotSame('', $protocol->getDecodedFile());
    }

    #[Depends('testAnOrderIsPlaced')]
    public function testAnUnknownOrderFileIsNotFound(string $orderNumber): void
    {
        self::assertApiError(
            fn() => $this->sdk->orders()->getOrderFile(
                new GetOrderFileMessage(orderNo: $orderNumber, filename: 'potwierdzenie.pdf'),
            ),
            'NOT_FOUND',
        );
    }

    public function testACollectivePickupIsChecked(): void
    {
        $this->assertReads(fn() => $this->sdk->orders()->isMultiPickupAvailable(
            new MultiPickupAvailabilityRequestMessage(
                courier: 'DPD',
                packs: [self::pack()],
                senderPostCode: '61-001',
                shipmentType: ShipmentTypeEnum::Box,
            ),
        ));
    }

    #[Depends('testAnOrderIsPlaced')]
    public function testACollectivePickupReportsEachOrder(string $orderNumber): void
    {
        $day = self::nextWorkday();
        $result = $this->sdk->orders()->createMultiOrderPickup(new MultiPickupRequestMessage(
            pickupDate: $day,
            pickupTimeFrom: $day->setTime(10, 0),
            pickupTimeTo: $day->setTime(14, 0),
            orders: [$orderNumber, '1-999999'],
        ));

        self::assertCount(2, [...$result->getSuccess(), ...$result->getFailed()]);
    }

    #[Depends('testAnOrderIsPlaced')]
    public function testTheOrderIsCancelled(string $orderNumber): void
    {
        self::assertTrue(
            $this->sdk->orders()->cancelOrder(new CancelOrderMessage(orderNo: $orderNumber))->isCancelled(),
        );
    }
}
