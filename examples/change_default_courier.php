<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
/**
 * change_default_courier — set the carrier preselected on new orders.
 *
 * A narrower form of save_account_settings, for when the carrier is the only thing changing. The
 * code must be one available_carriers reports for this account.
 */
declare(strict_types=1);

use Polkurier\Webservice\Dto\Request\Account\ChangeDefaultCourierMessage;
use Polkurier\Webservice\Exception\Exception;


require __DIR__ . '/bootstrap.php';

if (!hasFlag('--confirm')) {
    echo "This example changes the real default carrier. Re-run with --confirm to do it for real.\n";
    exit(0);
}

try {
    $changed = polkurier()->account()->changeDefaultCourier(
        new ChangeDefaultCourierMessage(courier: 'UPS')
    );

    echo $changed ? "Default carrier changed.\n" : "The change was not applied.\n";
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

