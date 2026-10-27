<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
/**
 * get_addresses — read the account's address book.
 *
 * Entries are filed per role, so the same place kept as both a sender and a recipient is two
 * entries with two ids. Senders have at most one default, and alternative return addresses another.
 */
declare(strict_types=1);

use Polkurier\Webservice\Exception\Exception;


require __DIR__ . '/bootstrap.php';

try {
    foreach (polkurier()->addresses()->getAddresses() as $address) {
        \printf(
            "%-8s %-20s %-24s %-10s %s\n",
            $address->getId() ?? '',
            $address->getName(),
            $address->getPerson(),
            $address->getPostcode(),
            $address->isDefault() ? '(default)' : ''
        );
    }
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

