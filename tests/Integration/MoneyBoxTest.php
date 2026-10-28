<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Integration;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;
use Polkurier\Webservice\Enum\MoneyBox\MoneyBoxPayoutTypeEnum;
use Polkurier\Webservice\Request\MoneyBox\CancelMoneyBoxPayoutMessage;
use Polkurier\Webservice\Request\MoneyBox\CreateMoneyBoxPayoutMessage;
use Polkurier\Webservice\Request\MoneyBox\GetMoneyBoxPayoutsMessage;

#[Group('integration')]
#[CoversNothing]
final class MoneyBoxTest extends IntegrationTestCase
{
    public function testTheConfigReads(): void
    {
        $this->assertReads(fn() => $this->sdk->moneyBox()->getConfig());
    }

    public function testThePayoutFeeIsQuoted(): void
    {
        self::assertGreaterThanOrEqual(0.0, $this->sdk->moneyBox()->calculatePayoutFee()->getFee());
    }

    public function testPayoutsRead(): void
    {
        $this->assertReads(
            fn() => $this->sdk->moneyBox()->getPayouts(new GetMoneyBoxPayoutsMessage(pageSize: 100, page: 1)),
        );
    }

    /**
     * A payout moves money, so the test sends one the API refuses: to an account not on file.
     */
    public function testAPayoutToAnAccountNotOnFileIsRefused(): void
    {
        self::assertApiError(
            fn() => self::client(validatePayloads: false)->moneyBox()->createPayout(new CreateMoneyBoxPayoutMessage(
                payoutType: MoneyBoxPayoutTypeEnum::Account,
                bankAccountNumber: '34102010260000000000000001',
            )),
            'FORBIDDEN',
        );
    }

    public function testCancellingAnUnknownPayoutIsNotFound(): void
    {
        self::assertApiError(
            fn() => $this->sdk->moneyBox()->cancelPayout(
                new CancelMoneyBoxPayoutMessage(payoutNumber: 'WP/2026/999999'),
            ),
            'NOT_FOUND',
        );
    }
}
