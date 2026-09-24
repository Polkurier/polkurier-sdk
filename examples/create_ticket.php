<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
/**
 * create_ticket — open a support ticket.
 *
 * The topic comes from get_contact_topics. Files can be attached at creation, and the answer is
 * the ticket as opened, with the number to quote afterwards.
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
        topicId: arguments()[0] ?? '1',
        content: 'Przesyłka nie została odebrana w deklarowanym terminie.',
        name: 'Nieodebrana przesyłka',
        origin: TicketOriginEnum::Api,
    ));

    \printf("Ticket %s opened (%s)\n", $ticket->getNumber(), $ticket->getStatus()->value);
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

