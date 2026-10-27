<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
/**
 * change_password — change the account password.
 *
 * Needs the current password as well as the new one. Credentials that may not change the password
 * get FORBIDDEN.
 */
declare(strict_types=1);

use Polkurier\Webservice\Exception\Exception;
use Polkurier\Webservice\Request\Account\ChangePasswordMessage;


require __DIR__ . '/bootstrap.php';

if (!hasFlag('--confirm')) {
    echo "This example changes the real account password. Re-run with --confirm to do it for real.\n";
    exit(0);
}

$previous = (string)\getenv('POLKURIER_CURRENT_PASSWORD');
$next = (string)\getenv('POLKURIER_NEW_PASSWORD');

if ($previous === '' || $next === '') {
    \fwrite(STDERR, "Set POLKURIER_CURRENT_PASSWORD and POLKURIER_NEW_PASSWORD.\n");
    exit(1);
}

try {
    $changed = polkurier()->account()->changePassword(
        new ChangePasswordMessage(password: $next, previousPassword: $previous)
    );

    echo $changed ? "Password changed.\n" : "The password was not changed.\n";
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

