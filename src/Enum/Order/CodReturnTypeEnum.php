<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Enum\Order;

/**
 * Where collected cash-on-delivery funds are sent.
 *
 * @api
 */
enum CodReturnTypeEnum: string
{
    /**
     * Transferred to the bank account given in the order's cash-on-delivery block.
     */
    case BankAccount = 'BA';

    /**
     * Paid out as a postal money order.
     */
    case PostOffice = 'PO';

    /**
     * Credited to the Polkurier money box balance for later withdrawal.
     */
    case MoneyBox = 'MB';
}
