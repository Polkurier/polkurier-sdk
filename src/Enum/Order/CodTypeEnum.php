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
 * Faster settlement costs more. Which options an account may use depends on its agreement, so
 * check `available_carriers` rather than assuming all four are available.
 */
enum CodTypeEnum: string
{
    /**
     * The carrier's ordinary settlement period.
     */
    case Standard = 'S';

    /**
     * Paid out one day after collection.
     */
    case OneDay = '1D';

    /**
     * Paid out four days after collection.
     */
    case FourDays = '4D';

    /**
     * Paid out sixteen days after collection.
     */
    case SixteenDays = '16D';
}
