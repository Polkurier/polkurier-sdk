<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Enum\Order;

/**
 * Why a cash-on-delivery amount was used for something other than a normal payout.
 */
enum CodReturnReasonEnum: string
{
    /**
     * Offset against an amount the customer owed.
     */
    case Compensation = 'COMPENSATION';

    /**
     * Applied to one of the customer's outstanding invoices.
     */
    case CustomerInvoicePayment = 'CUSTOMER_INVOICE_PAYMENT';

    /**
     * Added to the customer's prepaid balance.
     */
    case CustomerPrepaidPayment = 'CUSTOMER_PREPAID_PAYMENT';

    /**
     * Taken as the money box handling fee.
     */
    case CustomerMoneyBoxFee = 'CUSTOMER_MONEY_BOX_FEE';

    /**
     * Paid out to the customer as usual.
     */
    case CustomerPaid = 'CUSTOMER_PAID';
}
