<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
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

    foreach ($list->result as $ticket) {
        \printf(
            "%-36s %-14s %-40s %-12s %s\n",
            $ticket->id,
            $ticket->number,
            $ticket->subject,
            $ticket->status->value ?? 'unknown',
            $ticket->read ? '' : '(unread)'
        );
    }

    \printf("-- page %d, %d tickets total\n", $list->currentPage, $list->totalRows);
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

