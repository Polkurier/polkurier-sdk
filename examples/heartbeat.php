<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
/**
 * heartbeat — check that the API is reachable.
 *
 * The only method that needs no credentials, which makes it the right probe for connectivity and
 * for confirming the configured API URL is correct.
 */
declare(strict_types=1);

use Polkurier\Webservice\Exception\ConnectionException;

require __DIR__ . '/bootstrap.php';

try {
    dumpResponse(polkurier()->utils()->heartbeat());
} catch (ConnectionException $e) {
    \fwrite(STDERR, 'The API could not be reached: ' . $e->getMessage() . "\n");
    exit(1);
}
