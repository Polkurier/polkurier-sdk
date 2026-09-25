<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Enum\MoneyBox;

/**
 * How a money box payout reaches the customer.
 *
 * `get_money_box_config` returns which of the 2 an account can use and the details already saved
 * for it.
 */
enum MoneyBoxPayoutTypeEnum: string
{
    /**
     * Bank transfer. Needs an account number.
     */
    case Account = 'ACCOUNT';

    /**
     * Postal money order. Needs a full postal address instead of an account number.
     */
    case Postal = 'POSTAL';
}
