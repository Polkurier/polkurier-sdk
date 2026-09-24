<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
/**
 * get_invoices — page through issued invoices.
 *
 * Invoices and corrections are separate types and are asked for separately. The response is a
 * paging envelope, so a full listing means walking the pages.
 */
declare(strict_types=1);

use Polkurier\Webservice\Enum\Invoice\InvoiceTypeEnum;
use Polkurier\Webservice\Exception\Exception;
use Polkurier\Webservice\Request\Invoice\GetInvoicesMessage;


require __DIR__ . '/bootstrap.php';

try {
    $list = polkurier()->invoices()->getInvoices(new GetInvoicesMessage(
        type: InvoiceTypeEnum::Invoice,
        pageSize: 20,
        page: 1,
    ));

    foreach ($list->getResult() as $invoice) {
        \printf(
            "%-20s %10.2f PLN  unpaid %8.2f  due %s\n",
            $invoice->getNumber(),
            $invoice->getValue(),
            $invoice->getUnpaidValue(),
            $invoice->getPaymentDeadline()?->format('Y-m-d') ?? '-'
        );
    }

    \printf("-- page %d, %d invoices total\n", $list->getCurrentPage(), $list->getTotalRows());
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

