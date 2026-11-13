<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
/**
 * get_label — download shipping labels as a PDF.
 *
 * Several order numbers can be requested at once; the API returns one PDF containing all of their
 * labels. get_protocol takes the same request for the handover protocol.
 */
declare(strict_types=1);

use Polkurier\Webservice\Dto\Request\Order\GetDocumentMessage;
use Polkurier\Webservice\Exception\Exception;

require __DIR__ . '/bootstrap.php';

$orderNumbers = arguments();
if ($orderNumbers === []) {
    \fwrite(STDERR, "Usage: php examples/get_label.php <order-number> [order-number …]\n");
    exit(1);
}

try {
    $document = polkurier()->orders()
        ->getLabel(new GetDocumentMessage(orderNo: $orderNumbers));

    $path = \sys_get_temp_dir() . '/polkurier-labels.pdf';
    \file_put_contents($path, $document->getDecodedFile());

    \printf("Saved the labels for up to %d order(s) to %s\n", \count($orderNumbers), $path);
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}
