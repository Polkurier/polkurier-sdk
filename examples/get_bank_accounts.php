<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
/**
 * get_bank_accounts — list the accounts cash-on-delivery money is paid into.
 *
 * One of them is the default, and it is the one used when an order asks for collected money to go
 * to a bank account without naming one.
 */
declare(strict_types=1);

use Polkurier\Webservice\Exception\Exception;


require __DIR__ . '/bootstrap.php';

try {
    foreach (polkurier()->bankAccounts()->getBankAccounts() as $account) {
        \printf(
            "%-8s %-20s %-30s %s\n",
            $account->getId() ?? '',
            $account->getName(),
            $account->getNumber(),
            $account->isDefault() ? '(default)' : ''
        );
    }
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

