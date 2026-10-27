<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
/**
 * cancel_order — cancel an order.
 *
 * Whether an order can still be cancelled depends on the carrier and on how far the shipment has
 * got. A refusal is an error carrying the carrier's reason.
 */
declare(strict_types=1);

use Polkurier\Webservice\Exception\Exception;
use Polkurier\Webservice\Request\Order\OrderIdMessage;


require __DIR__ . '/bootstrap.php';

if (!hasFlag('--confirm')) {
    echo "This example cancels a real order. Re-run with --confirm to do it for real.\n";
    exit(0);
}

$orderNumber = arguments()[0] ?? '12345-678';

try {
    polkurier()->orders()->cancelOrder(new OrderIdMessage(orderNo: $orderNumber));

    echo "Order {$orderNumber} cancelled.\n";
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

