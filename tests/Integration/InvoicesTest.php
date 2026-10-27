<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Integration;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;
use Polkurier\Webservice\Enum\Invoice\InvoiceTypeEnum;
use Polkurier\Webservice\Request\Invoice\GetInvoiceDocumentMessage;
use Polkurier\Webservice\Request\Invoice\GetInvoiceSummaryListMessage;
use Polkurier\Webservice\Request\Invoice\GetInvoicesMessage;

#[Group('integration')]
#[CoversNothing]
final class InvoicesTest extends IntegrationTestCase
{
    public function testInvoicesRead(): void
    {
        $this->assertReads(
            fn() => $this->sdk->invoices()->getInvoices(new GetInvoicesMessage(type: InvoiceTypeEnum::Invoice)),
        );
    }

    public function testAnInvoicePdfNeedsANumber(): void
    {
        self::assertApiError(
            fn() => self::client(validatePayloads: false)->invoices()->getInvoicePdf(
                new GetInvoiceDocumentMessage(number: ''),
            ),
            'PARAMS_IS_EMPTY',
        );
    }

    public function testASummaryOfAnUnknownInvoiceIsNotFound(): void
    {
        self::assertApiError(
            fn() => $this->sdk->invoices()->getInvoiceSummaryList(
                new GetInvoiceSummaryListMessage(number: '0000/00/0000/POL'),
            ),
            'NOT_FOUND',
        );
    }
}
