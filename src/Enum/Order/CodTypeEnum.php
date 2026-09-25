<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Enum\Order;

/**
 * How quickly collected cash-on-delivery funds are paid out.
 *
 * Faster payout costs more. The options an account can use depend on its agreement, so check
 * `available_carriers` instead of assuming all 4 are available.
 */
enum CodTypeEnum: string
{
    /**
     * The carrier's standard payout period.
     */
    case Standard = 'S';

    /**
     * Paid out one day after collection.
     */
    case OneDay = '1D';

    /**
     * Paid out 4 days after collection.
     */
    case FourDays = '4D';

    /**
     * Paid out 16 days after collection.
     */
    case SixteenDays = '16D';
}
