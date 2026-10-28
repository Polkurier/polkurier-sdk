<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
/**
 * get_phone_numbers — list the numbers on the account.
 *
 * Removed numbers are not listed. At most one is the default, and two-factor codes sent by text
 * message go to it.
 */
declare(strict_types=1);

use Polkurier\Webservice\Exception\Exception;


require __DIR__ . '/bootstrap.php';

try {
    foreach (polkurier()->account()->getPhoneNumbers() as $number) {
        \printf(
            "%-8s %-16s %-20s %s\n",
            $number->getId(),
            $number->getNumber(),
            $number->getName(),
            $number->isDefault() ? '(default)' : ''
        );
    }
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

