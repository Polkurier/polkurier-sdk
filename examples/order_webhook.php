<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
/**
 * order_webhook — check and read an order event Polkurier posted to your webhook address.
 *
 * Takes the path of a file holding the request body as it came, or reads it from standard input,
 * and the webhook's secret from POLKURIER_WEBHOOK_SECRET. In your endpoint, pass
 * file_get_contents('php://input') instead, and answer with HTTP 200 and "OK".
 *
 * This makes no API call.
 */
declare(strict_types=1);

use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Webhook\OrderReader;


require __DIR__ . '/bootstrap.php';

$path = arguments()[0] ?? 'php://stdin';
$body = (string)\file_get_contents($path);
$secret = (string)\getenv('POLKURIER_WEBHOOK_SECRET');

$reader = new OrderReader();

if (!$reader->isAuthentic($body, $secret)) {
    \fwrite(STDERR, "The event isn't signed with the webhook's secret. Set POLKURIER_WEBHOOK_SECRET.\n");
    exit(1);
}

try {
    $event = $reader->read($body)->event;
} catch (InvalidArgumentException $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

\printf(
    "%s: order %s, status %s (%s)\n",
    $event->type->value ?? 'unknown event',
    $event->order->getNumber(),
    $event->order->getStatus(),
    $event->order->getStatusCode(),
);

