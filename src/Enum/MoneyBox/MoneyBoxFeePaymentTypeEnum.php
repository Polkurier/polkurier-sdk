<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Enum\MoneyBox;

/**
 * How the payout handling fee is settled.
 *
 * Not every account can use every option. `get_money_box_config` returns the allowed ones under
 * `fee_payments_types_available`.
 *
 * @api
 */
enum MoneyBoxFeePaymentTypeEnum: string
{
    /**
     * Deducted from the money box balance being paid out.
     */
    case MoneyBox = 'MONEY_BOX';

    /**
     * Added to the account's next invoice.
     */
    case Invoice = 'INVOICE';

    /**
     * Taken from the prepaid balance.
     */
    case Prepaid = 'PREPAID';

    /**
     * Paid immediately by card or transfer.
     */
    case Online = 'ONLINE';

    /**
     * No fee was due, as when the account still has free payouts left.
     * `calculate_money_box_payout_fee` returns how many remain.
     */
    case Free = 'FREE';
}
