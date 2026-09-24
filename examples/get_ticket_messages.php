<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
/**
 * get_ticket_messages — read one ticket and its conversation.
 *
 * The ticket with its timeline: messages from both sides, status changes, and the files attached to
 * each.
 */
declare(strict_types=1);

use Polkurier\Webservice\Exception\Exception;
use Polkurier\Webservice\Request\Ticket\TicketIdMessage;


require __DIR__ . '/bootstrap.php';

$ticketId = arguments()[0] ?? '1';

try {
    $ticket = polkurier()->tickets()->getTicketMessages(new TicketIdMessage(ticketId: $ticketId));

    \printf("%s — %s\n\n", $ticket->getNumber(), $ticket->getSubject());

    foreach ($ticket->getMessages() as $message) {
        \printf(
            "[%s] %s:\n%s\n\n",
            $message->getDate()?->format('Y-m-d H:i') ?? '',
            $message->getAuthorName(),
            $message->getContent()
        );
    }
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

