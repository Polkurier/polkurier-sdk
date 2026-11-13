<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
/**
 * get_order_file — download a file attached to an order.
 *
 * Documents attached to the order besides its label and protocol, such as customs paperwork or a
 * proof of delivery. Get the file names from get_orders, by order number or with files turned on.
 */
declare(strict_types=1);

use Polkurier\Webservice\Dto\Request\Order\GetOrderFileMessage;
use Polkurier\Webservice\Exception\Exception;


require __DIR__ . '/bootstrap.php';

$orderNumber = arguments()[0] ?? '12345-678';
$filename = arguments()[1] ?? 'pod.pdf';

try {
    $file = polkurier()->orders()->getOrderFile(
        new GetOrderFileMessage(orderNo: $orderNumber, filename: $filename)
    );

    $path = \sys_get_temp_dir() . '/' . $file->getName();
    \file_put_contents($path, $file->getDecodedFile());

    \printf("Saved %s (%s, %d bytes)\n", $path, $file->getType(), $file->getSize());
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

