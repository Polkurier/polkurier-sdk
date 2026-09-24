<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
/**
 * two_factor_token_change_transport_method — send the confirmation code a different way.
 *
 * For a customer who cannot receive the code the way it was first sent. The token stays the same;
 * only the delivery changes.
 */
declare(strict_types=1);

use Polkurier\Webservice\Exception\Exception;
use Polkurier\Webservice\Request\TwoFactorToken\ChangeTransportMethodMessage;


require __DIR__ . '/bootstrap.php';

if (!hasFlag('--confirm')) {
    echo "This example sends a real confirmation code. Re-run with --confirm to do it for real.\n";
    exit(0);
}

$token = arguments()[0] ?? 'token-from-two_factor_token_register';
$method = arguments()[1] ?? 'sms';

try {
    $changed = polkurier()->twoFactorToken()->twoFactorTokenChangeTransportMethod(
        new ChangeTransportMethodMessage(token: $token, method: $method)
    );

    \printf("Code now going to %s\n", $changed->getPhoneNumber());
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

