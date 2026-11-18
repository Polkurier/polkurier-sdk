<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
/**
 * get_protocol — download the handover protocol for one or more orders.
 *
 * The document the courier signs on collection. Always a PDF, unlike get_label, which can also
 * render printer languages. Several order numbers can go in one request and come back as one
 * document. One unknown number among several fails the whole call.
 */
declare(strict_types=1);

use Polkurier\Webservice\Dto\Request\Order\GetDocumentMessage;
use Polkurier\Webservice\Exception\Exception;


require __DIR__ . '/bootstrap.php';

$orderNumbers = arguments() ?: ['12345-678'];

try {
    $document = polkurier()->orders()->getProtocol(new GetDocumentMessage(orderNo: $orderNumbers));

    $path = \sys_get_temp_dir() . '/polkurier-protocol.pdf';
    \file_put_contents($path, $document->file);

    \printf("Saved %s\n", $path);
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

