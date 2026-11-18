<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Enum\Account;

/**
 * How an account is being verified.
 *
 * Its values are lower case, unlike the other sets the API sends.
 *
 * @api
 */
enum AccountVerificationTypeEnum: string
{
    /**
     * By an online payment.
     */
    case Online = 'online';

    /**
     * By a transfer from the account holder's own bank account.
     */
    case BankTransfer = 'bank_transfer';
}
