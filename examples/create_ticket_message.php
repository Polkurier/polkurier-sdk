<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
/**
 * create_ticket_message — reply to a ticket.
 *
 * Adds a message to an existing ticket's timeline, optionally with files. Answers with the ticket
 * as it now stands rather than with the message alone.
 */
declare(strict_types=1);

use Polkurier\Webservice\Exception\Exception;
use Polkurier\Webservice\Request\Ticket\CreateTicketMessageMessage;


require __DIR__ . '/bootstrap.php';

if (!hasFlag('--confirm')) {
    echo "This example posts a real reply. Re-run with --confirm to do it for real.\n";
    exit(0);
}

$ticketId = arguments()[0] ?? '1';

try {
    $ticket = polkurier()->tickets()->createTicketMessage(new CreateTicketMessageMessage(
        ticketId: $ticketId,
        content: 'Dziękuję za odpowiedź, przesyłka dotarła.',
    ));

    \printf("Replied to %s; it now has %d messages.\n",
        $ticket->getNumber(), \count($ticket->getMessages()));
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

