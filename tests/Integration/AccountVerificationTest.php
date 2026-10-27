<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Integration;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;
use Polkurier\Webservice\Request\Account\RegisterAccountVerificationMessage;
use Polkurier\Webservice\Request\Order\GetOrdersMessage;

#[Group('integration')]
#[CoversNothing]
final class AccountVerificationTest extends IntegrationTestCase
{
    public function testAVerificationIsStartedAndReadBack(): void
    {
        self::requireSandbox();
        $orders = $this->sdk->orders()->getOrders(new GetOrdersMessage(pageSize: 1, page: 1))->getResult();
        if ($orders === []) {
            self::markTestSkipped('The account has no order to tie the verification to.');
        }

        $this->assertReads(fn() => $this->sdk->accountVerification()->register(
            new RegisterAccountVerificationMessage(orderNo: $orders[0]->getNumber()),
        ));
        self::assertNotNull($this->sdk->accountVerification()->getInfo());
    }
}
