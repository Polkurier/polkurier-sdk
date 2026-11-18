<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
/**
 * change_phone_pin — set the phone PIN.
 *
 * The code given when contacting support: 4 digits. Not the security PIN.
 */
declare(strict_types=1);

use Polkurier\Webservice\Dto\Request\Account\ChangePinMessage;
use Polkurier\Webservice\Exception\Exception;


require __DIR__ . '/bootstrap.php';

if (!hasFlag('--confirm')) {
    echo "This example changes the real phone PIN. Re-run with --confirm to do it for real.\n";
    exit(0);
}

$pin = (string)\getenv('POLKURIER_NEW_PIN');

if ($pin === '') {
    \fwrite(STDERR, "Set POLKURIER_NEW_PIN.\n");
    exit(1);
}

try {
    echo polkurier()->account()->changePhonePin(new ChangePinMessage(pin: $pin))
        ? "PIN changed.\n"
        : "The PIN was not changed.\n";
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

