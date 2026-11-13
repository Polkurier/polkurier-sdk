<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
/**
 * set_ticket_status — close a ticket.
 *
 * CLOSED is the only status the call accepts, so a closed ticket can't be reopened through the API.
 * Answers with the ticket in its new state.
 */
declare(strict_types=1);

use Polkurier\Webservice\Dto\Request\Ticket\SetTicketStatusMessage;
use Polkurier\Webservice\Enum\Ticket\TicketStatusEnum;
use Polkurier\Webservice\Exception\Exception;


require __DIR__ . '/bootstrap.php';

if (!hasFlag('--confirm')) {
    echo "This example changes a real ticket’s status. Re-run with --confirm to do it for real.\n";
    exit(0);
}

$ticketId = arguments()[0] ?? 'ticket-id-from-get_tickets';

try {
    $ticket = polkurier()->tickets()->setTicketStatus(
        new SetTicketStatusMessage(ticketId: $ticketId, status: TicketStatusEnum::Closed)
    );

    \printf("%s is now %s\n", $ticket->number, $ticket->status->value ?? 'unknown');
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

