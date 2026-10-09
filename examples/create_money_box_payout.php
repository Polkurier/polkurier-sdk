<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
/**
 * create_money_box_payout — request a payout of collected cash-on-delivery money.
 *
 * Moving money, so the API guards it: expect to need the security PIN, a confirmed two-factor
 * token, or both, depending on what get_account_info and get_system_info report. The destination
 * defaults to what get_money_box_config holds and only needs supplying to override it.
 */
declare(strict_types=1);

use Polkurier\Webservice\Enum\MoneyBox\MoneyBoxFeePaymentTypeEnum;
use Polkurier\Webservice\Enum\MoneyBox\MoneyBoxPayoutTypeEnum;
use Polkurier\Webservice\Exception\Exception;
use Polkurier\Webservice\Request\MoneyBox\CreateMoneyBoxPayoutMessage;


require __DIR__ . '/bootstrap.php';

if (!hasFlag('--confirm')) {
    echo "This example moves real money. Re-run with --confirm to do it for real.\n";
    exit(0);
}

try {
    $payout = polkurier()->moneyBox()->createPayout(new CreateMoneyBoxPayoutMessage(
        payoutType: MoneyBoxPayoutTypeEnum::Account,
        feePaymentType: MoneyBoxFeePaymentTypeEnum::MoneyBox,
        // securityPin / twoFactorToken / twoFactorCode as the account requires.
    ));

    \printf("Payout %s for %.2f PLN is %s\n",
        $payout->getNumber(), $payout->getAmount(), $payout->getStatus());
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

