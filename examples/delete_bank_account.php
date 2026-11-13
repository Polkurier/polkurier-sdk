<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
/**
 * delete_bank_account — remove a bank account.
 *
 * Subject to the same PIN lock as registering one: pass the account's security PIN when
 * get_account_info reports the bank-account lock as engaged. An unknown id also succeeds.
 */
declare(strict_types=1);

use Polkurier\Webservice\Dto\Request\BankAccount\DeleteBankAccountMessage;
use Polkurier\Webservice\Exception\Exception;


require __DIR__ . '/bootstrap.php';

if (!hasFlag('--confirm')) {
    echo "This example deletes a real bank account. Re-run with --confirm to do it for real.\n";
    exit(0);
}

$id = arguments()[0] ?? 'id-from-get_bank_accounts';

try {
    $deleted = polkurier()->bankAccounts()->deleteBankAccount(
        new DeleteBankAccountMessage(id: $id)
    );

    echo $deleted ? "Deleted {$id}.\n" : "Nothing was deleted.\n";
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

