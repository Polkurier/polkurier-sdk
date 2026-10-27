<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
/**
 * create_money_box_payout — request a payout of collected cash-on-delivery money.
 *
 * A bank transfer goes to a saved bank account, here the one get_money_box_config returns. Only a
 * postal money order to an address other than the saved default needs a PIN or two-factor token.
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
    $config = polkurier()->moneyBox()->getConfig();

    $payout = polkurier()->moneyBox()->createPayout(new CreateMoneyBoxPayoutMessage(
        payoutType: MoneyBoxPayoutTypeEnum::Account,
        feePaymentType: MoneyBoxFeePaymentTypeEnum::MoneyBox,
        bankAccountNumber: $config->getIban(),
    ));

    \printf("Payout %s for %.2f PLN is %s\n",
        $payout->getNumber(), $payout->getAmount(), $payout->getStatus());
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

