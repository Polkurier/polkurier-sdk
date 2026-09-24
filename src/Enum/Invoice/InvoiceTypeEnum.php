<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Enum\Invoice;

/**
 * Which kind of accounting document to list.
 *
 * The two are listed separately, so a full picture of what an account owes needs both calls.
 */
enum InvoiceTypeEnum: string
{
    /**
     * An ordinary invoice.
     */
    case Invoice = 'INVOICE';

    /**
     * A correction to a previously issued invoice.
     */
    case InvoiceCorrection = 'INVOICE_CORRECTION';
}
