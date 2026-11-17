<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
/**
 * get_invoice_pdf — download one invoice or correction as a PDF.
 *
 * The number comes from get_invoices. The payload carries the file base64-encoded along with the
 * name to save it under.
 */
declare(strict_types=1);

use Polkurier\Webservice\Dto\Request\Invoice\GetInvoiceDocumentMessage;
use Polkurier\Webservice\Exception\Exception;


require __DIR__ . '/bootstrap.php';

$number = arguments()[0] ?? 'FV/2026/01/0001';

try {
    $document = polkurier()->invoices()->getInvoicePdf(
        new GetInvoiceDocumentMessage(number: $number)
    );

    $path = \sys_get_temp_dir() . '/' . \basename($document->filename);
    \file_put_contents($path, $document->file);

    \printf("Saved %s\n", $path);
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

