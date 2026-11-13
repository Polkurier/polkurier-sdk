<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
/**
 * save_bank_account — register a bank account, or update one.
 *
 * While get_account_info reports the bank-account PIN lock engaged, every save needs the security
 * PIN. Otherwise registering an account, or changing its number or default flag, needs a confirmed
 * two-factor token. The API accepts Polish account numbers only; the SDK checks the IBAN check
 * digits before anything is sent.
 */
declare(strict_types=1);

use Polkurier\Webservice\Entity\BankAccount\SaveBankAccountMessage;
use Polkurier\Webservice\Exception\Exception;


require __DIR__ . '/bootstrap.php';

if (!hasFlag('--confirm')) {
    echo "This example registers a real bank account. Re-run with --confirm to do it for real.\n";
    exit(0);
}

try {
    $account = polkurier()->bankAccounts()->saveBankAccount(new SaveBankAccountMessage(
        name: 'Firmowe',
        number: 'PL34102010260000000000000001',
        // Supply twoFactorToken/twoFactorCode, and accessPin, if the account requires them.
    ));

    \printf("Saved as %s\n", $account->getId() ?? '(no id returned)');
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

