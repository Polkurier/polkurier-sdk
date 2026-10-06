<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Enum\Invoice;

/**
 * The file format to download an invoice's itemised summary in.
 */
enum InvoiceSummaryFormatEnum: string
{
    /**
     * A PDF document, for reading or printing.
     */
    case Pdf = 'PDF';

    /**
     * A CSV file, for importing into a spreadsheet or accounting system.
     */
    case Csv = 'CSV';
}
