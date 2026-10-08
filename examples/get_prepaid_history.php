<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
/**
 * get_prepaid_history — page through movements on the prepaid balance.
 *
 * Top-ups and the charges drawn against them, newest first. The balance itself is on
 * get_account_info; this is how it got there.
 */
declare(strict_types=1);

use Polkurier\Webservice\Exception\Exception;
use Polkurier\Webservice\Request\Payment\GetPrepaidHistoryMessage;


require __DIR__ . '/bootstrap.php';

try {
    $history = polkurier()->payments()->getPrepaidHistory(
        new GetPrepaidHistoryMessage(pageSize: 20, page: 1)
    );

    foreach ($history->getResult() as $entry) {
        \printf(
            "%-12s %10.2f PLN  %s\n",
            $entry->getDate()?->format('Y-m-d') ?? '',
            $entry->getAmount(),
            $entry->getDescription()
        );
    }

    \printf("-- page %d, %d entries total\n", $history->getCurrentPage(), $history->getTotalRows());
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

