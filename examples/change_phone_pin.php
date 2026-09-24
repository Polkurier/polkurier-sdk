<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
/**
 * change_phone_pin — set the security PIN.
 *
 * The PIN that guards the operations get_account_info reports as locked — changing a bank account,
 * for instance. Setting it may itself need a two-factor token, depending on the account.
 */
declare(strict_types=1);

use Polkurier\Webservice\Exception\Exception;
use Polkurier\Webservice\Request\Account\ChangePinMessage;


require __DIR__ . '/bootstrap.php';

if (!hasFlag('--confirm')) {
    echo "This example changes the real security PIN. Re-run with --confirm to do it for real.\n";
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

