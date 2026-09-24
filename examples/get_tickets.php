<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
/**
 * get_tickets — page through the account’s tickets.
 *
 * A paging envelope. Each entry carries its read state, which is what drives an unread badge
 * without fetching every ticket's messages.
 */
declare(strict_types=1);

use Polkurier\Webservice\Exception\Exception;


require __DIR__ . '/bootstrap.php';

try {
    $list = polkurier()->tickets()->getTickets();

    foreach ($list->getResult() as $ticket) {
        \printf(
            "%-14s %-40s %-12s %s\n",
            $ticket->getNumber(),
            $ticket->getSubject(),
            $ticket->getStatus()->value,
            $ticket->isRead() ? '' : '(unread)'
        );
    }

    \printf("-- page %d, %d tickets total\n", $list->getCurrentPage(), $list->getTotalRows());
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

