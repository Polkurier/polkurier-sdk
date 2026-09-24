<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Enum\Ticket;

/**
 * Which side of a ticket conversation an entry came from.
 */
enum TicketAuthorEnum: string
{
    /**
     * Written by the account holder.
     */
    case Customer = 'CUSTOMER';

    /**
     * Written by Polkurier in reply.
     */
    case Employee = 'EMPLOYEE';
}
