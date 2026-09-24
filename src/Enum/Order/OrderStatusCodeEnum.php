<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Enum\Order;

/**
 * Stable status codes for an order, suitable for branching on.
 *
 * The accompanying human-readable status is localised and may be reworded, so switch on these
 * codes rather than on the label beside them. The same codes double as the `status` filter of
 * `get_orders`.
 *
 * The code reflects the primary waybill once one exists, and the order record before that.
 */
enum OrderStatusCodeEnum: string
{
    /**
     * Placed but not yet confirmed.
     */
    case Pending = 'O';

    /**
     * Confirmed; no carrier scan yet.
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
     * The most common state between placing an order and the first scan.
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
     * Only meaningful as a `get_orders` filter; no order is reported with it.
     */
    case Collective = 'PZ';
}
