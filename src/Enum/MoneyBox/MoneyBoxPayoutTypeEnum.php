<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Enum\MoneyBox;

/**
 * How a money box payout reaches the customer.
 *
 * `get_money_box_config` returns the account's configured one and the details saved for each.
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
