<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
/**
 * create_multi_order_pickup — book one collection covering several orders.
 *
 * Takes the numbers of orders placed for a collective pickup
 * ({@see \Polkurier\Webservice\Entity\Order\PickupMessage}'s multiPickup). The result reports each
 * order separately, and the call succeeds even when none goes through.
 */
declare(strict_types=1);

use Polkurier\Webservice\Dto\Request\Order\MultiPickupRequestMessage;
use Polkurier\Webservice\Exception\Exception;


require __DIR__ . '/bootstrap.php';

if (!hasFlag('--confirm')) {
    echo "This example books a real collection. Re-run with --confirm to do it for real.\n";
    exit(0);
}

$orderNumbers = arguments();

try {
    $result = polkurier()->orders()->createMultiOrderPickup(new MultiPickupRequestMessage(
        pickupDate: new DateTimeImmutable('+1 weekday'),
        pickupTimeFrom: new DateTimeImmutable('10:00'),
        pickupTimeTo: new DateTimeImmutable('16:00'),
        orders: $orderNumbers ?: ['12345-678'],
    ));

    \printf("Booked:  %s\n", \implode(', ', $result->getSuccess()) ?: '(none)');
    foreach ($result->getFailed() as $orderNumber => $reason) {
        \printf("Refused: %s: %s\n", $orderNumber, $reason);
    }
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

