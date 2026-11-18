<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
/**
 * get_account_info — read the account: balances, settings and what it is allowed to do.
 *
 * The one to call before anything conditional. It carries the money box, prepaid and invoice
 * balances, the acceptance flags, whether orders are refused, and which PIN locks are engaged — the
 * flags that decide whether a later call will need a PIN or a two-factor token.
 */
declare(strict_types=1);

use Polkurier\Webservice\Exception\Exception;


require __DIR__ . '/bootstrap.php';

try {
    $account = polkurier()->account()->getAccountInfo();

    \printf("%s <%s>\n", $account->displayName, $account->email);
    \printf("  money box  %8.2f PLN (available %.2f)\n",
        $account->moneyBoxBalance, $account->moneyBoxPayoutAvailableBalance);
    \printf("  prepaid    %8.2f PLN\n", $account->prepaidBalance);
    \printf("  invoices   %8.2f PLN\n", $account->invoiceBalance);
    \printf("  verified   %s\n", $account->verified ? 'yes' : 'no');
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

