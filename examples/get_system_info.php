<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
/**
 * get_system_info — read the limits the API is currently applying.
 *
 * Security PIN lengths, the two-factor code length and resend window, ticket attachment limits,
 * the minimum coupon length, the verification transfer amount and the terms acceptance period. It
 * holds no account data, so call it once at start-up and cache it.
 */
declare(strict_types=1);

use Polkurier\Webservice\Exception\Exception;


require __DIR__ . '/bootstrap.php';

try {
    $info = polkurier()->system()->getSystemInfo();

    dumpResponse($info);
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

