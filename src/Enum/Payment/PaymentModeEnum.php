<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Enum\Payment;

/**
 * What an online payment is settling.
 *
 * The mode decides where the money goes and whether the customer chooses the amount. For modes
 * that pay an existing debt, the amount is what is owed. Send an amount only for PREPAID.
 */
enum PaymentModeEnum: string
{
    /**
     * Settles the account's outstanding balance.
     */
    case Balance = 'BALANCE';

    /**
     * Settles unpaid invoices.
     */
    case Invoices = 'INVOICES';

    /**
     * Pays for specific orders.
     */
    case Orders = 'ORDERS';

    /**
     * Tops up the prepaid balance. The customer chooses the amount.
     */
    case Prepaid = 'PREPAID';

    /**
     * The small transfer used to verify the account's identity. The amount is fixed and returned
     * by `get_system_info` as `verification_transfer_amount_pln`.
     */
    case Verification = 'VERIFICATION';
}
