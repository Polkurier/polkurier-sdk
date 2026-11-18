<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Enum\MoneyBox;

/**
 * How far the handling fee of a money box payout has got.
 *
 * It describes the fee, not the payout: see {@see MoneyBoxPayoutStatusEnum} for the money itself.
 *
 * @api
 */
enum MoneyBoxFeePaymentStatusEnum: string
{
    /**
     * Not settled yet.
     */
    case Unpaid = 'UNPAID';

    /**
     * Paid online, and the payment has not been confirmed yet.
     */
    case Pending = 'PENDING';

    /**
     * Settled. A fee of nothing, and one added to an invoice, are settled as the payout is created.
     */
    case Paid = 'PAID';
}
