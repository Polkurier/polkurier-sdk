<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Enum\Ticket;

/**
 * What an entry on a ticket's timeline represents.
 */
enum TicketTimelineItemTypeEnum: string
{
    /**
     * A message written by the customer or in reply to them.
     */
    case Message = 'MESSAGE';

    /**
     * Something that happened to the ticket rather than something said — a status change, a
     * reassignment. Its content is a description of the event.
     */
    case Action = 'ACTION';
}
