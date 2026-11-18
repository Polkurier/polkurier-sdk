<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Enum\MoneyBox;

/**
 * What asked for a money box payout.
 *
 * @api
 */
enum MoneyBoxPayoutRequestTypeEnum: string
{
    /**
     * Asked for by the account holder.
     */
    case Manual = 'MANUAL';

    /**
     * Raised automatically, without saying which rule did it. Older payouts carry this; the rules
     * that run now name themselves.
     */
    case Auto = 'AUTO';

    /**
     * Raised automatically because the configured time had come.
     */
    case AutoTimely = 'AUTO_TIMELY';

    /**
     * Raised automatically because the balance had reached the configured amount.
     */
    case AutoAmount = 'AUTO_AMOUNT';
}
