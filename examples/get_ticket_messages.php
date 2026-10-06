<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
/**
 * get_ticket_messages — read a ticket's conversation.
 *
 * The timeline, newest first: messages from both sides, status changes, and the files attached to
 * each message.
 */
declare(strict_types=1);

use Polkurier\Webservice\Exception\Exception;
use Polkurier\Webservice\Request\Ticket\TicketIdMessage;


require __DIR__ . '/bootstrap.php';

$ticketId = arguments()[0] ?? 'ticket-id-from-get_tickets';

try {
    $timeline = polkurier()->tickets()->getTicketMessages(new TicketIdMessage(ticketId: $ticketId));

    foreach ($timeline as $entry) {
        \printf(
            "[%s] %s (%s):\n%s\n%s\n",
            $entry->getDate()?->format('Y-m-d H:i') ?? '',
            $entry->getAuthorName(),
            $entry->getType()->value ?? 'unknown',
            $entry->getContent(),
            $entry->getFiles() === [] ? '' : \count($entry->getFiles()) . " file(s) attached\n"
        );
    }
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

