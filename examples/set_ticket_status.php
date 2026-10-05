<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
/**
 * set_ticket_status — close a ticket, or reopen it.
 *
 * Answers with the ticket in its new state. Closing one the customer considers unresolved is the
 * usual reason to reopen it, which is the same call with a different status.
 */
declare(strict_types=1);

use Polkurier\Webservice\Enum\Ticket\TicketStatusEnum;
use Polkurier\Webservice\Exception\Exception;
use Polkurier\Webservice\Request\Ticket\SetTicketStatusMessage;


require __DIR__ . '/bootstrap.php';

if (!hasFlag('--confirm')) {
    echo "This example changes a real ticket’s status. Re-run with --confirm to do it for real.\n";
    exit(0);
}

$ticketId = arguments()[0] ?? '1';

try {
    $ticket = polkurier()->tickets()->setTicketStatus(
        new SetTicketStatusMessage(ticketId: $ticketId, status: TicketStatusEnum::Closed)
    );

    \printf("%s is now %s\n", $ticket->getNumber(), $ticket->getStatus()->value ?? 'unknown');
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

