<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
/**
 * create_multi_order_pickup — book one collection covering several orders.
 *
 * One courier visit for a batch of orders rather than one per order. Takes order numbers, of orders
 * placed for a collective pickup ({@see \Polkurier\Webservice\Request\Order\PickupMessage}'s
 * multiPickup). The result reports each order separately: some can be added to the collection and
 * others refused in the same call, so the call isn't all-or-nothing.
 */
declare(strict_types=1);

use Polkurier\Webservice\Exception\Exception;
use Polkurier\Webservice\Request\Order\MultiPickupRequestMessage;


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

