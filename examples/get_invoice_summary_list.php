<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
/**
 * get_invoice_summary_list — download the itemised breakdown behind an invoice.
 *
 * The shipments an invoice covers — useful for reconciliation when a single invoice bundles many
 * orders. Add --csv to get it as a CSV file instead of a PDF.
 */
declare(strict_types=1);

use Polkurier\Webservice\Dto\Request\Invoice\GetInvoiceSummaryListMessage;
use Polkurier\Webservice\Enum\Invoice\InvoiceSummaryFormatEnum;
use Polkurier\Webservice\Exception\Exception;


require __DIR__ . '/bootstrap.php';

$number = arguments()[0] ?? 'FV/2026/01/0001';

try {
    $document = polkurier()->invoices()->getInvoiceSummaryList(
        new GetInvoiceSummaryListMessage(
            number: $number,
            format: hasFlag('--csv') ? InvoiceSummaryFormatEnum::Csv : InvoiceSummaryFormatEnum::Pdf,
        )
    );

    $path = \sys_get_temp_dir() . '/' . $document->filename;
    \file_put_contents($path, $document->file);

    \printf("Saved %s (%s)\n", $path, $document->fileType);
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

