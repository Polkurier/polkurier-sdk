<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
/**
 * two_factor_token_resend — send the confirmation code again.
 *
 * For when the first one did not arrive. It reuses the token rather than issuing a new one, so the
 * code from either message will do.
 */
declare(strict_types=1);

use Polkurier\Webservice\Exception\Exception;
use Polkurier\Webservice\Request\Common\TokenMessage;


require __DIR__ . '/bootstrap.php';

if (!hasFlag('--confirm')) {
    echo "This example sends a real confirmation code. Re-run with --confirm to do it for real.\n";
    exit(0);
}

$token = arguments()[0] ?? 'token-from-two_factor_token_register';

try {
    $resent = polkurier()->twoFactorToken()->twoFactorTokenResend(new TokenMessage(token: $token));

    \printf("Code re-sent to %s\n", $resent->getTransportMethod()?->getName() ?? '(unknown)');
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

