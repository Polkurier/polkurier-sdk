<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
/**
 * get_invoices — page through issued invoices.
 *
 * Invoices and corrections are separate types and are asked for separately, one page at a time.
 */
declare(strict_types=1);

use Polkurier\Webservice\Dto\Request\Invoice\GetInvoicesMessage;
use Polkurier\Webservice\Enum\Invoice\InvoiceTypeEnum;
use Polkurier\Webservice\Exception\Exception;


require __DIR__ . '/bootstrap.php';

try {
    $list = polkurier()->invoices()->getInvoices(new GetInvoicesMessage(
        type: InvoiceTypeEnum::Invoice,
        pageSize: 20,
        page: 1,
    ));

    foreach ($list->result as $invoice) {
        \printf(
            "%-20s %10.2f PLN  unpaid %8.2f  due %s\n",
            $invoice->number,
            $invoice->value,
            $invoice->unpaidValue,
            $invoice->paymentDeadline?->format('Y-m-d') ?? '-'
        );
    }

    \printf("-- page %d, %d invoices total\n", $list->currentPage, $list->totalRows);
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

