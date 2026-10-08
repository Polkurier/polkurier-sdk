<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Enum\Ticket;

/**
 * The state of a support ticket.
 *
 * Returned on every ticket, but only {@see self::Closed} can be set through the API. A customer
 * can close their own ticket and nothing else. The other states change as the ticket is handled.
 */
enum TicketStatusEnum: string
{
    /**
     * Created and waiting to be picked up.
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
     * Finished. The only state a customer can set.
     */
    case Closed = 'CLOSED';
}
