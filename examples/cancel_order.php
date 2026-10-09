<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
/**
 * cancel_order — withdraw an order that has not yet been collected.
 *
 * Whether an order can still be cancelled depends on the carrier and on how far the shipment has
 * got. The answer is a flag rather than an error, so a refusal reads as `false` and not as an
 * exception.
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
    $cancellation = polkurier()->orders()->cancelOrder(new OrderIdMessage(orderNo: $orderNumber));

    echo $cancellation->isCancelled()
        ? "Order {$orderNumber} cancelled.\n"
        : "Order {$orderNumber} could not be cancelled at this stage.\n";
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

