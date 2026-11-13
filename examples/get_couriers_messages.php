<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
/**
 * get_couriers_messages — read the carriers’ current service notices.
 *
 * Delays, suspensions and general information. Each notice carries a severity and the carrier it
 * concerns, so a shop can warn a customer before they choose a carrier. The message is HTML in
 * Polish.
 */
declare(strict_types=1);

use Polkurier\Webservice\Exception\Exception;


require __DIR__ . '/bootstrap.php';

try {
    foreach (polkurier()->system()->getCouriersMessages() as $message) {
        \printf(
            "[%s] %s: %s\n",
            $message->severity->value ?? 'unknown',
            $message->courier,
            $message->message
        );
    }
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

