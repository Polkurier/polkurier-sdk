<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
/**
 * validate_user_pin — check a PIN without spending it.
 *
 * Answers with a flag rather than an error, which makes it the right way to check a PIN a customer
 * has just typed before attempting the operation it guards.
 */
declare(strict_types=1);

use Polkurier\Webservice\Exception\Exception;
use Polkurier\Webservice\Request\Account\ValidatePinMessage;


require __DIR__ . '/bootstrap.php';

$pin = arguments()[0] ?? (string)\getenv('POLKURIER_PIN');

if ($pin === '') {
    \fwrite(STDERR, "Pass a PIN as the first argument, or set POLKURIER_PIN.\n");
    exit(1);
}

try {
    $validity = polkurier()->account()->validateUserPin(new ValidatePinMessage(pin: $pin));

    echo $validity->isValid() ? "That PIN is correct.\n" : "That PIN is wrong.\n";
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

