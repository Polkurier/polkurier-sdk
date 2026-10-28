<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Enum\Invoice;

/**
 * Which kind of accounting document to list.
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
