<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
/**
 * get_money_box_payouts — page through past and pending payouts.
 *
 * Each entry reports whether it can still be cancelled, which is what to check before offering the
 * option rather than attempting the call and handling the refusal.
 */
declare(strict_types=1);

use Polkurier\Webservice\Exception\Exception;
use Polkurier\Webservice\Request\MoneyBox\GetMoneyBoxPayoutsMessage;


require __DIR__ . '/bootstrap.php';

try {
    $list = polkurier()->moneyBox()->getPayouts(
        new GetMoneyBoxPayoutsMessage(pageSize: 20, page: 1)
    );

    foreach ($list->getResult() as $payout) {
        \printf(
            "%-16s %10.2f PLN  %-14s %s\n",
            $payout->getNumber(),
            $payout->getAmount(),
            $payout->getStatus(),
            $payout->isCancellable() ? '(cancellable)' : ''
        );
    }

    \printf("-- page %d of %d\n", $list->getCurrentPage(), $list->getTotalPages());
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

