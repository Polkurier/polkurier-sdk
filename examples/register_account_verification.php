<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
/**
 * register_account_verification — start identity verification, or get the one in progress.
 *
 * Answers with the verification's id and an access token for the provider's flow. An order number
 * can be given when the verification was prompted by a particular consignment.
 */
declare(strict_types=1);

use Polkurier\Webservice\Dto\Request\Account\RegisterAccountVerificationMessage;
use Polkurier\Webservice\Exception\Exception;


require __DIR__ . '/bootstrap.php';

if (!hasFlag('--confirm')) {
    echo "This example starts a real verification. Re-run with --confirm to do it for real.\n";
    exit(0);
}

try {
    $verification = polkurier()->accountVerification()->register(
        new RegisterAccountVerificationMessage()
    );

    \printf("Verification %s: %s\n", $verification->verificationId, $verification->status->value ?? '');
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

