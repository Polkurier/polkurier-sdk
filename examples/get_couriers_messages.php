<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
/**
 * get_couriers_messages — read the carriers’ current service notices.
 *
 * Delays, suspended regions, holiday cut-offs. Each notice carries a severity and the carrier it
 * concerns, so a shop can warn a customer before they choose a carrier that is not collecting this
 * week rather than after the order is placed.
 */
declare(strict_types=1);

use Polkurier\Webservice\Exception\Exception;


require __DIR__ . '/bootstrap.php';

try {
    foreach (polkurier()->system()->getCouriersMessages() as $message) {
        \printf(
            "[%s] %s: %s\n",
            $message->getSeverity()->value ?? 'unknown',
            $message->getCourier(),
            $message->getMessage()
        );
    }
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

