<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
/**
 * get_bank_accounts — list the accounts cash-on-delivery money is paid into.
 *
 * A money box payout to a bank account must name one of these. At most one is the default.
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

