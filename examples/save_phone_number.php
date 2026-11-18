<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
/**
 * save_phone_number — add a phone number, or make one the default.
 *
 * Making the number the default needs a confirmed two-factor token registered under
 * CHANGE_DEFAULT_PHONE_NUMBER, and a Polish mobile number; adding it needs neither.
 */
declare(strict_types=1);

use Polkurier\Webservice\Dto\Request\Account\SavePhoneNumberMessage;
use Polkurier\Webservice\Entity\PhoneNumber;
use Polkurier\Webservice\Exception\Exception;


require __DIR__ . '/bootstrap.php';

if (!hasFlag('--confirm')) {
    echo "This example adds a real phone number. Re-run with --confirm to do it for real.\n";
    exit(0);
}

try {
    $number = polkurier()->account()->savePhoneNumber(new SavePhoneNumberMessage(
        phoneNumber: new PhoneNumber(number: '500600700', name: 'Magazyn'),
        // A number with default: true would also need twoFactorToken and twoFactorCode.
    ));

    \printf("Saved %s as %s\n", $number->getNumber(), $number->getId());
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

