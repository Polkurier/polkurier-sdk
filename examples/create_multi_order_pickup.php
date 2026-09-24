<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
/**
 * create_multi_order_pickup — book one collection covering several orders.
 *
 * One courier visit for a batch of orders rather than one per order. The result reports each order
 * separately: some can be added to the collection and others refused in the same call, so read
 * both lists rather than treating the call as all-or-nothing.
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
        pickupDate: new DateTimeImmutable('tomorrow'),
        pickupTimeFrom: new DateTimeImmutable('tomorrow 10:00'),
        pickupTimeTo: new DateTimeImmutable('tomorrow 16:00'),
        orders: $orderNumbers ?: ['12345-678'],
    ));

    \printf("Booked:  %s\n", \implode(', ', $result->getSuccess()) ?: '(none)');
    \printf("Refused: %s\n", \implode(', ', $result->getFailed()) ?: '(none)');
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

