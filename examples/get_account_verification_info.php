<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
/**
 * get_account_verification_info — check how identity verification is going.
 *
 * Poll this after register_account_verification to see whether the provider has finished.
 */
declare(strict_types=1);

use Polkurier\Webservice\Exception\Exception;


require __DIR__ . '/bootstrap.php';

try {
    $verification = polkurier()->accountVerification()->getInfo();

    if ($verification === null) {
        echo "No verification has been started.\n";
        exit(0);
    }

    \printf("%s: %s\n", $verification->verificationId, $verification->status);
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

