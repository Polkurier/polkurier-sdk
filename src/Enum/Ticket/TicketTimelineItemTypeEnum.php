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
     * An event on the ticket, e.g. a status change or a reassignment. Its content describes the
     * event.
     */
    case Action = 'ACTION';
}
