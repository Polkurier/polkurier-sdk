<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Enum\Ticket;

/**
 * The state of a support ticket.
 *
 * Reported on every ticket, but only {@see self::Closed} can be set through the API — a customer
 * may close their own ticket and nothing else. The remaining states are set as the ticket is
 * handled.
 */
enum TicketStatusEnum: string
{
    /**
     * Raised and waiting to be picked up.
     */
    case Opened = 'OPENED';

    /**
     * Being worked on.
     */
    case InProgress = 'IN_PROGRESS';

    /**
     * Paused, typically waiting on something outside Polkurier.
     */
    case Postponed = 'POSTPONED';

    /**
     * Finished. The one state a customer can set.
     */
    case Closed = 'CLOSED';
}
