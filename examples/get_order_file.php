<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
/**
 * get_order_file — download a file attached to an order.
 *
 * Customs paperwork, proofs of delivery, and anything else the carrier has filed against the
 * consignment. The file names come from the order itself, so read the order first rather than
 * guessing them.
 */
declare(strict_types=1);

use Polkurier\Webservice\Exception\Exception;
use Polkurier\Webservice\Request\Order\GetOrderFileMessage;


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

