<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
/**
 * get_phone_numbers — list the numbers on the account.
 *
 * Archived numbers are filtered out, so everything listed is live. Exactly one is the default, and
 * it is the one two-factor codes and carrier notifications go to.
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

