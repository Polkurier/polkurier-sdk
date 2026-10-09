<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
/**
 * delete_phone_number — remove a phone number.
 *
 * Needs both the id and the number itself, which is the API's guard against deleting the wrong
 * one. The default number cannot be removed while it is the default.
 */
declare(strict_types=1);

use Polkurier\Webservice\Exception\Exception;
use Polkurier\Webservice\Request\Account\DeletePhoneNumberMessage;


require __DIR__ . '/bootstrap.php';

if (!hasFlag('--confirm')) {
    echo "This example deletes a real phone number. Re-run with --confirm to do it for real.\n";
    exit(0);
}

$id = arguments()[0] ?? 'id-from-get_phone_numbers';
$number = arguments()[1] ?? '500600700';

try {
    $deleted = polkurier()->account()->deletePhoneNumber(
        new DeletePhoneNumberMessage(id: $id, number: $number)
    );

    echo $deleted ? "Deleted {$number}.\n" : "Nothing was deleted.\n";
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

