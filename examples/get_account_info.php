<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
/**
 * get_account_info — read the account: balances, settings and what it is allowed to do.
 *
 * The broadest read in the API and the one to call before anything conditional. It carries the
 * money box, prepaid and invoice balances, which regulations have been accepted, whether the
 * account is verified, and which PIN locks are engaged — the flags that decide whether a later
 * call will need a PIN or a two-factor token.
 */
declare(strict_types=1);

use Polkurier\Webservice\Exception\Exception;


require __DIR__ . '/bootstrap.php';

try {
    $account = polkurier()->account()->getAccountInfo();

    \printf("%s <%s>\n", $account->getDisplayName(), $account->getEmail());
    \printf("  money box  %8.2f PLN (available %.2f)\n",
        $account->getMoneyBoxBalance(), $account->getMoneyBoxPayoutAvailableBalance());
    \printf("  prepaid    %8.2f PLN\n", $account->getPrepaidBalance());
    \printf("  invoices   %8.2f PLN\n", $account->getInvoiceBalance());
    \printf("  verified   %s\n", $account->isVerified() ? 'yes' : 'no');
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

