<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
/**
 * change_default_sender_point — set the drop-off point preselected for a carrier.
 *
 * Per carrier rather than global: an account can keep a different default point for each one.
 * Passing an empty point clears the default for that carrier.
 */
declare(strict_types=1);

use Polkurier\Webservice\Exception\Exception;
use Polkurier\Webservice\Request\Account\ChangeDefaultSenderPointMessage;


require __DIR__ . '/bootstrap.php';

if (!hasFlag('--confirm')) {
    echo "This example changes a real default point. Re-run with --confirm to do it for real.\n";
    exit(0);
}

try {
    $changed = polkurier()->account()->changeDefaultSenderPoint(
        new ChangeDefaultSenderPointMessage(courier: 'INPOST_PACZKOMAT', point: 'POZ01A')
    );

    echo $changed ? "Default point changed.\n" : "The change was not applied.\n";
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

