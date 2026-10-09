<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
/**
 * delete_address — remove an address book entry.
 *
 * Addresses are filed per role, so deleting one needs the role as well as the id — the same place
 * kept as both a sender and a recipient is two separate entries.
 */
declare(strict_types=1);

use Polkurier\Webservice\Enum\Address\AddressTypeEnum;
use Polkurier\Webservice\Exception\Exception;
use Polkurier\Webservice\Request\Address\DeleteMessage;


require __DIR__ . '/bootstrap.php';

if (!hasFlag('--confirm')) {
    echo "This example deletes a real address. Re-run with --confirm to do it for real.\n";
    exit(0);
}

$id = arguments()[0] ?? 'id-from-get_addresses';

try {
    $deleted = polkurier()->addresses()->deleteAddress(
        new DeleteMessage(id: $id, type: AddressTypeEnum::Sender)
    );

    echo $deleted ? "Deleted {$id}.\n" : "Nothing was deleted.\n";
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

