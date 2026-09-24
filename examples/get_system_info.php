<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
/**
 * get_system_info — read the limits and switches the API is currently applying.
 *
 * Worth calling once at start-up and caching. It reports the caps the account is subject to and
 * which security features are turned on, which is what tells you whether an operation will need a
 * PIN or a two-factor token before you attempt it.
 */
declare(strict_types=1);

use Polkurier\Webservice\Exception\Exception;


require __DIR__ . '/bootstrap.php';

try {
    $info = polkurier()->system()->getSystemInfo();

    dumpResponse($info);
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

