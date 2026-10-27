<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Enum\Order;

/**
 * The status of a cash-on-delivery amount, from collection to settlement.
 */
enum CodStatusEnum: string
{
    /**
     * Waiting for collection or settlement, before any of the states below.
     */
    case Waiting = 'WAITING';

    /**
     * The shipment is in transit, so the amount isn't collected yet.
     */
    case InTransit = 'IN_TRANSIT';

    /**
     * Credited to the money box balance.
     */
    case MoneyBox = 'MONEY_BOX';

    /**
     * Due to be paid out to the customer.
     */
    case PaymentPending = 'PAYMENT_PENDING';

    /**
     * Withheld and offset against an amount the customer owes.
     */
    case Compensation = 'COMPENSATION';

    /**
     * Paid out to the customer.
     */
    case Paid = 'PAID';

    /**
     * Settled with nothing left to pay out to the customer.
     */
    case Settled = 'SETTLED';
}
