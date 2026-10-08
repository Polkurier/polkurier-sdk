<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
/**
 * calculate_money_box_payout_fee — find out what a payout would cost before requesting it.
 *
 * Fees depend on the payout type and on how many free payouts the account has left this period, so
 * this is worth showing before the customer commits. Naming specific orders prices a partial
 * payout; leaving the list empty prices the whole available balance.
 */
declare(strict_types=1);

use Polkurier\Webservice\Enum\MoneyBox\MoneyBoxPayoutTypeEnum;
use Polkurier\Webservice\Exception\Exception;
use Polkurier\Webservice\Request\MoneyBox\CalculateMoneyBoxPayoutFeeMessage;


require __DIR__ . '/bootstrap.php';

try {
    $fee = polkurier()->moneyBox()->calculatePayoutFee(
        new CalculateMoneyBoxPayoutFeeMessage(payoutType: MoneyBoxPayoutTypeEnum::Account)
    );

    \printf("Fee: %.2f PLN gross (%.2f net)\n", $fee->getFee(), $fee->getFeeNett());
    \printf("Free payouts left: %d of %d\n", $fee->getFreePayoutsLeft(), $fee->getFreePayoutsTotal());
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

