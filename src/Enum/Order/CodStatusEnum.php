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
     * Not yet collected from the recipient.
     */
    case Waiting = 'WAITING';

    /**
     * Collected and on its way from the carrier to Polkurier.
     */
    case InTransit = 'IN_TRANSIT';

    /**
     * Credited to the money box balance.
     */
    case MoneyBox = 'MONEY_BOX';

    /**
     * Payout to the customer has been initiated.
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
     * Closed. No further changes expected.
     */
    case Settled = 'SETTLED';
}
