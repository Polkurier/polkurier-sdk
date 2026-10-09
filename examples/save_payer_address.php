<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
/**
 * save_payer_address — set the billing details invoices are issued to.
 *
 * Which fields are required depends on the address type and on whether the payer is a company or a
 * private person — a company needs its tax number, a private person does not. The SDK checks the
 * combination before sending.
 */
declare(strict_types=1);

use Polkurier\Webservice\Enum\Account\PayerAddressTypeEnum;
use Polkurier\Webservice\Enum\Account\PayerUserTypeEnum;
use Polkurier\Webservice\Exception\Exception;
use Polkurier\Webservice\Request\Account\SavePayerAddressMessage;


require __DIR__ . '/bootstrap.php';

if (!hasFlag('--confirm')) {
    echo "This example changes real billing details. Re-run with --confirm to do it for real.\n";
    exit(0);
}

try {
    $saved = polkurier()->account()->savePayerAddress(new SavePayerAddressMessage(
        addressType: PayerAddressTypeEnum::Payer,
        userType: PayerUserTypeEnum::Company,
        name: 'Acme sp. z o.o.',
        street: 'Długa',
        buildingNumber: '12',
        postCode: '61-001',
        city: 'Poznań',
        country: 'PL',
        nipPrefix: 'PL',
        nip: '7792308650',
        // With a Polish tax number prefix, a company needs a valid REGON as well.
        regon: '631189204',
    ));

    echo $saved ? "Billing details saved.\n" : "They were not saved.\n";
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

