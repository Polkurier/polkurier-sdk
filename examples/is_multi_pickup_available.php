<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
/**
 * is_multi_pickup_available — check whether several orders can share one collection.
 *
 * A precondition for create_multi_order_pickup, and worth asking before offering the option:
 * whether it is available depends on the account and on what the carriers currently allow.
 */
declare(strict_types=1);

use Polkurier\Webservice\Exception\Exception;


require __DIR__ . '/bootstrap.php';

try {
    $availability = polkurier()->orders()->isMultiPickupAvailable();

    echo $availability->isAvailable()
        ? "A shared collection can be booked.\n"
        : "A shared collection is not available for this account.\n";
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

