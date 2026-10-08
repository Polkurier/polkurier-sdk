<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
/**
 * save_bank_account — register a bank account, or update one.
 *
 * Redirecting where money is paid is treated as sensitive, so the API may refuse the change
 * without a confirmed two-factor token, and may additionally refuse it while the account's bank-
 * account PIN lock is engaged. get_account_info and get_system_info report which applies. The
 * number is checked as an IBAN before anything is sent.
 */
declare(strict_types=1);

use Polkurier\Webservice\Exception\Exception;
use Polkurier\Webservice\Request\BankAccount\SaveMessage;


require __DIR__ . '/bootstrap.php';

if (!hasFlag('--confirm')) {
    echo "This example registers a real bank account. Re-run with --confirm to do it for real.\n";
    exit(0);
}

try {
    $account = polkurier()->bankAccounts()->saveBankAccount(new SaveMessage(
        name: 'Firmowe',
        number: 'PL34102010260000000000000001',
        // Supply twoFactorToken/twoFactorCode, and accessPin, if the account requires them.
    ));

    \printf("Saved as %s\n", $account->getId() ?? '(no id returned)');
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

