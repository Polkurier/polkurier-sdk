<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Integration;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;
use Polkurier\Webservice\Request\System\GetCouriersMessagesMessage;
use Polkurier\Webservice\Request\System\ValidateCouponMessage;

#[Group('integration')]
#[CoversNothing]
final class SystemTest extends IntegrationTestCase
{
    public function testSystemInfoReads(): void
    {
        $info = $this->sdk->system()->getSystemInfo();

        self::assertGreaterThan(0, $info->getRegulationsAcceptancePeriod());
        self::assertNotNull($info->getSecurity());
    }

    public function testCarrierNoticesRead(): void
    {
        $this->assertReads(fn() => $this->sdk->system()->getCouriersMessages());
    }

    public function testCarrierNoticesNarrowToOneCarrier(): void
    {
        $notices = $this->sdk->system()->getCouriersMessages(new GetCouriersMessagesMessage(courier: 'DPD'));

        foreach ($notices as $notice) {
            self::assertSame('DPD', $notice->getCourier());
        }
    }

    public function testAnUnknownCouponIsInvalid(): void
    {
        $result = $this->sdk->system()->validateCouponCode(new ValidateCouponMessage(couponCode: 'NOPE123'));

        self::assertFalse($result->isValid());
    }
}
