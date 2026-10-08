<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
/**
 * create_ticket_message — reply to a ticket.
 *
 * Adds a message to an existing ticket's timeline. Files can be attached: pass their paths after
 * the ticket id. Answers with the new timeline entry.
 */
declare(strict_types=1);

use Polkurier\Webservice\Exception\Exception;
use Polkurier\Webservice\Request\Ticket\CreateTicketMessageMessage;


require __DIR__ . '/bootstrap.php';

if (!hasFlag('--confirm')) {
    echo "This example posts a real reply. Re-run with --confirm to do it for real.\n";
    exit(0);
}

$ticketId = arguments()[0] ?? 'ticket-id-from-get_tickets';

try {
    $entry = polkurier()->tickets()->createTicketMessage(new CreateTicketMessageMessage(
        ticketId: $ticketId,
        content: 'Dziękuję za odpowiedź, przesyłka dotarła.',
        files: attachments(\array_slice(arguments(), 1)),
    ));

    \printf("Replied at %s as entry %s.\n", $entry->getDate()?->format('Y-m-d H:i') ?? '', $entry->getId());
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

