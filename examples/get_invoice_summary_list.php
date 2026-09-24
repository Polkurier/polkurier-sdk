<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
/**
 * get_invoice_summary_list — download the itemised breakdown behind an invoice.
 *
 * The consignments an invoice covers, as a document rather than as data — useful for
 * reconciliation when a single invoice bundles many orders.
 */
declare(strict_types=1);

use Polkurier\Webservice\Exception\Exception;
use Polkurier\Webservice\Request\Invoice\GetInvoiceDocumentMessage;


require __DIR__ . '/bootstrap.php';

$number = arguments()[0] ?? 'FV/2026/01/0001';

try {
    $document = polkurier()->invoices()->getInvoiceSummaryList(
        new GetInvoiceDocumentMessage(number: $number)
    );

    $path = \sys_get_temp_dir() . '/' . $document->getFilename();
    \file_put_contents($path, $document->getDecodedFile());

    \printf("Saved %s (%s)\n", $path, $document->getFiletype());
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

