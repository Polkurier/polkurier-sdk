<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Enum\Order;

/**
 * Where a cash-on-delivery amount has got to, from collection through to settlement.
 */
enum CodStatusEnum: string
{
    /**
     * Not yet collected from the recipient.
     */
    case Waiting = 'WAITING';

    /**
     * Collected; on its way from the carrier to Polkurier.
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
     * Closed; no further movement expected.
     */
    case Settled = 'SETTLED';
}
