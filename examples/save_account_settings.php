<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
/**
 * save_account_settings — change the account defaults.
 *
 * The default sender address, the default carrier and the default sender point. Answers with the
 * account as it now stands, so there is no need to re-read it afterwards. Fields left null are
 * left alone.
 */
declare(strict_types=1);

use Polkurier\Webservice\Dto\Request\Account\SaveAccountSettingsMessage;
use Polkurier\Webservice\Exception\Exception;


require __DIR__ . '/bootstrap.php';

if (!hasFlag('--confirm')) {
    echo "This example changes real account settings. Re-run with --confirm to do it for real.\n";
    exit(0);
}

try {
    $account = polkurier()->account()->saveAccountSettings(new SaveAccountSettingsMessage(
        defaultCourier: 'UPS',
    ));

    \printf("Default carrier is now %s\n", $account->getDefaultCourier());
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

