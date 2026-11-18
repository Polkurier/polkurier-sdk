<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Enum\Ticket;

/**
 * What an entry on a ticket's timeline represents.
 *
 * @api
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
