<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
/**
 * save_phone_number — add a phone number, or make one the default.
 *
 * Making a number the default is what two-factor codes will be sent to, so the API may require a
 * confirmed two-factor token for that specific change while allowing a plain addition without one.
 */
declare(strict_types=1);

use Polkurier\Webservice\Exception\Exception;
use Polkurier\Webservice\Request\Account\SavePhoneNumberMessage;


require __DIR__ . '/bootstrap.php';

if (!hasFlag('--confirm')) {
    echo "This example adds a real phone number. Re-run with --confirm to do it for real.\n";
    exit(0);
}

try {
    $number = polkurier()->account()->savePhoneNumber(new SavePhoneNumberMessage(
        number: '500600700',
        name: 'Magazyn',
        // default: true would also need twoFactorToken and twoFactorCode.
    ));

    \printf("Saved %s as %s\n", $number->getNumber(), $number->getId());
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

