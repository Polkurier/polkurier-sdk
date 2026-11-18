<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Enum\Account;

/**
 * How the account pays for its shipments.
 *
 * `get_account_info` returns it under `payment_type` on every account.
 *
 * @api
 */
enum AccountPaymentTypeEnum: string
{
    /**
     * Shipments are billed on an invoice, against the account's credit limit.
     * `invoice_limit`, `invoice_limit_used` and `invoice_balance` describe that limit.
     */
    case Invoice = 'INVOICE';

    /**
     * Shipments are paid from the prepaid balance, which `prepaid_balance` holds.
     */
    case Prepaid = 'PREPAID';
}
