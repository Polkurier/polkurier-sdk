<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
/**
 * create_ticket — open a support ticket.
 *
 * The topic comes from get_contact_topics. Files can be attached at creation: pass their paths
 * after the topic id. The answer is the ticket as opened, with the number to quote afterwards and
 * the id the other ticket examples take.
 */
declare(strict_types=1);

use Polkurier\Webservice\Enum\Ticket\TicketOriginEnum;
use Polkurier\Webservice\Exception\Exception;
use Polkurier\Webservice\Request\Ticket\CreateTicketMessage;


require __DIR__ . '/bootstrap.php';

if (!hasFlag('--confirm')) {
    echo "This example opens a real ticket. Re-run with --confirm to do it for real.\n";
    exit(0);
}

try {
    $ticket = polkurier()->tickets()->createTicket(new CreateTicketMessage(
        topicId: arguments()[0] ?? 'topic-id-from-get_contact_topics',
        content: 'Przesyłka nie została odebrana w deklarowanym terminie.',
        name: 'Jan Kowalski',
        origin: TicketOriginEnum::Api,
        files: attachments(\array_slice(arguments(), 1)),
    ));

    \printf(
        "Ticket %s opened (%s). Its id, for the other ticket examples: %s\n",
        $ticket->getNumber(),
        $ticket->getStatus()->value ?? 'unknown',
        $ticket->getId()
    );
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

