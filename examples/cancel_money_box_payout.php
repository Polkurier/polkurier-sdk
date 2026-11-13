<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
/**
 * cancel_money_box_payout — withdraw a payout request.
 *
 * Only while it is still cancellable: each payout reports that as a flag, and one already being
 * settled cannot be stopped.
 */
declare(strict_types=1);

use Polkurier\Webservice\Dto\Request\MoneyBox\CancelMoneyBoxPayoutMessage;
use Polkurier\Webservice\Exception\Exception;


require __DIR__ . '/bootstrap.php';

if (!hasFlag('--confirm')) {
    echo "This example cancels a real payout. Re-run with --confirm to do it for real.\n";
    exit(0);
}

$payoutNumber = arguments()[0] ?? 'WP/2026/0001';

try {
    $payout = polkurier()->moneyBox()->cancelPayout(
        new CancelMoneyBoxPayoutMessage(payoutNumber: $payoutNumber)
    );

    \printf("Payout %s for %.2f PLN cancelled\n", $payout->getNumber(), $payout->getAmount());
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

