<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Enum\MoneyBox;

/**
 * How far a money box payout has got.
 *
 * It answers for the amount, not for the handling fee, which has its own
 * {@see MoneyBoxFeePaymentStatusEnum}. A cancelled payout has no status: cancelling removes it, so
 * it stops being listed at all.
 *
 * @api
 */
enum MoneyBoxPayoutStatusEnum: string
{
    /**
     * Part of the amount is still to be paid out.
     */
    case Pending = 'PENDING';

    /**
     * Nothing is left to pay out. A payout whose whole amount was withheld against outstanding
     * debts reaches this without any money being sent.
     */
    case Paid = 'PAID';
}
