<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
/**
 * delete_phone_number — remove a phone number.
 *
 * The id picks the number; the number is checked for format only. The default number can't be
 * removed.
 */
declare(strict_types=1);

use Polkurier\Webservice\Dto\Request\Account\DeletePhoneNumberMessage;
use Polkurier\Webservice\Exception\Exception;


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

