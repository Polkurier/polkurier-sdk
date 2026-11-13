<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
/**
 * get_ticket_messages — read a ticket's conversation.
 *
 * The timeline, newest first: messages from both sides, status changes, and the files attached to
 * each message.
 */
declare(strict_types=1);

use Polkurier\Webservice\Dto\Request\Ticket\TicketIdMessage;
use Polkurier\Webservice\Exception\Exception;


require __DIR__ . '/bootstrap.php';

$ticketId = arguments()[0] ?? 'ticket-id-from-get_tickets';

try {
    $timeline = polkurier()->tickets()->getTicketMessages(new TicketIdMessage(ticketId: $ticketId));

    foreach ($timeline as $entry) {
        \printf(
            "[%s] %s (%s):\n%s\n%s\n",
            $entry->date?->format('Y-m-d H:i') ?? '',
            $entry->authorName,
            $entry->type->value ?? 'unknown',
            $entry->content,
            $entry->files === [] ? '' : "Attached (entry {$entry->id}): "
                . \implode(', ', \array_map(static fn($file) => $file->name, $entry->files)) . "\n"
        );
    }
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

