<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
/**
 * save_address — add an address book entry, or update one.
 *
 * An id updates that entry; without one a new entry is filed. House and flat numbers go in their
 * own fields. `default` is for senders only.
 */
declare(strict_types=1);

use Polkurier\Webservice\Entity\Address;
use Polkurier\Webservice\Enum\Address\AddressTypeEnum;
use Polkurier\Webservice\Exception\Exception;


require __DIR__ . '/bootstrap.php';

if (!hasFlag('--confirm')) {
    echo "This example writes to the address book. Re-run with --confirm to do it for real.\n";
    exit(0);
}

try {
    $address = polkurier()->addresses()->saveAddress(new Address(
        name: 'Magazyn',
        person: 'Jan Kowalski',
        street: 'Długa',
        houseNumber: '12',
        postcode: '61-001',
        city: 'Poznań',
        email: 'jan.kowalski@example.com',
        phone: '500600700',
        type: AddressTypeEnum::Sender,
        company: 'Acme sp. z o.o.',
    ));

    \printf("Saved as %s\n", $address->getId() ?? '(no id returned)');
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

