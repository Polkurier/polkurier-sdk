<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
/**
 * save_address — add an address book entry, or update one.
 *
 * Passing an id updates that entry; leaving it out files a new one. The house and flat numbers go
 * in their own fields rather than on the end of the street, which is what lets the carriers that
 * check them do so. Setting `default` un-defaults whichever entry held that before.
 */
declare(strict_types=1);

use Polkurier\Webservice\Enum\Address\AddressTypeEnum;
use Polkurier\Webservice\Exception\Exception;
use Polkurier\Webservice\Request\Address\SaveMessage;


require __DIR__ . '/bootstrap.php';

if (!hasFlag('--confirm')) {
    echo "This example writes to the address book. Re-run with --confirm to do it for real.\n";
    exit(0);
}

try {
    $address = polkurier()->addresses()->saveAddress(new SaveMessage(
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

