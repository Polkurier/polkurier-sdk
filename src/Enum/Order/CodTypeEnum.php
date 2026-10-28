<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Enum\Order;

/**
 * How quickly collected cash-on-delivery funds are paid out.
 *
 * Faster payout costs more. The options an account can use depend on its agreement.
 */
enum CodTypeEnum: string
{
    /**
     * The standard payout period, 5 to 7 working days depending on the carrier.
     */
    case Standard = 'S';

    /**
     * Paid out 1 working day after delivery.
     */
    case OneDay = '1D';

    /**
     * Paid out 4 working days after delivery.
     */
    case FourDays = '4D';

    /**
     * Paid out 16 working days after delivery. Reported on orders, refused by `create_order`.
     */
    case SixteenDays = '16D';
}
