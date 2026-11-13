<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Integration;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;
use Polkurier\Webservice\Dto\Request\Courier\AvailableCarriersMessage;
use Polkurier\Webservice\Dto\Request\Courier\GetCountriesMessage;
use Polkurier\Webservice\Dto\Request\Courier\GetPickupTimeMessage;
use Polkurier\Webservice\Dto\Request\Courier\GetPointMessage;
use Polkurier\Webservice\Dto\Request\Courier\PickupMessage;
use Polkurier\Webservice\Enum\Courier\PointFunctionEnum;
use Polkurier\Webservice\Enum\Order\ShipmentTypeEnum;

#[Group('integration')]
#[CoversNothing]
final class CourierTest extends IntegrationTestCase
{
    public function testCarriersRead(): void
    {
        self::assertNotEmpty($this->sdk->courier()->availableCarriers());
    }

    public function testCarriersReadWithTheirAdditionalData(): void
    {
        self::assertNotEmpty(
            $this->sdk->courier()->availableCarriers(new AvailableCarriersMessage(additionalData: true)),
        );
    }

    public function testACarrierListsItsCountries(): void
    {
        self::assertNotEmpty($this->sdk->courier()->getCountries(new GetCountriesMessage(couriers: 'DPDEXP')));
    }

    public function testSupportedCountriesRead(): void
    {
        self::assertNotEmpty($this->sdk->courier()->getSupportedCountries());
    }

    public function testPointsAreFoundAndOneIsFetchedById(): void
    {
        $points = $this->sdk->courier()->findCourierPoints(new GetPointMessage(
            couriers: ['INPOST_PACZKOMAT'],
            functions: [PointFunctionEnum::Send],
            limit: 2,
            page: 1,
        ));
        self::assertNotEmpty($points);

        $point = $this->sdk->courier()->getCourierPoint($points[0]->getId(), ['INPOST_PACZKOMAT']);

        self::assertSame($points[0]->getId(), $point?->getId());
    }

    public function testPickupDaysRead(): void
    {
        $this->assertReads(fn() => $this->sdk->courier()->getCourierPickupTime(new GetPickupTimeMessage(
            courier: 'DPD',
            shipFrom: '61-001',
            shipTo: '00-001',
            parcel: ShipmentTypeEnum::Box,
        )));
    }

    public function testAPickupDayIsChecked(): void
    {
        $this->assertReads(fn() => $this->sdk->courier()->pickupCourier(new PickupMessage(
            courier: 'DPD',
            shipFrom: '61-001',
            shipTo: '00-001',
            parcel: ShipmentTypeEnum::Box,
            pickupDate: self::nextWorkday(),
        )));
    }
}
