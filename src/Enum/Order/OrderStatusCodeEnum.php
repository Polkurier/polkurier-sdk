<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Enum\Order;

/**
 * Stable order status codes you can branch on.
 *
 * The human-readable status next to the code is localised and may change, so switch on the code.
 * The same codes are used for the `status` filter of `get_orders`.
 */
enum OrderStatusCodeEnum: string
{
    /**
     * Placed but not yet confirmed.
     */
    case Pending = 'O';

    /**
     * Confirmed, no carrier scan yet.
     */
    case Confirmed = 'P';

    /**
     * Cancelled, by the customer or by the carrier.
     */
    case Canceled = 'A';

    /**
     * Picked up and moving through the carrier network.
     */
    case InTransit = 'WP';

    /**
     * Delivered to the recipient.
     */
    case Delivered = 'D';

    /**
     * Confirmed and waiting for the courier to collect it.
     * The usual state between placing an order and the first scan.
     */
    case AwaitingPickup = 'OC';

    /**
     * On its way back to the sender.
     */
    case Return = 'Z';

    /**
     * The carrier reported a problem needing attention.
     */
    case Exception = 'W';

    /**
     * Part of a collective consignment.
     * Only used as a `get_orders` filter. No order is returned with this status.
     */
    case Collective = 'PZ';
}
