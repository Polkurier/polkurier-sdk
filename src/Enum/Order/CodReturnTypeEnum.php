<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Enum\Order;

/**
 * Where collected cash-on-delivery funds are sent.
 */
enum CodReturnTypeEnum: string
{
    /**
     * Transferred to one of the account's registered bank accounts.
     * Register them through the bank accounts service.
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
