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
 *
 * @api
 */
enum PaymentModeEnum: string
{
    /**
     * Settles every unpaid invoice on the account.
     */
    case Balance = 'BALANCE';

    /**
     * Settles the unpaid invoices whose numbers the payment lists.
     */
    case Invoices = 'INVOICES';

    /**
     * Pays for the orders whose numbers the payment lists, from the prepaid balance first.
     */
    case Orders = 'ORDERS';

    /**
     * Tops up the prepaid balance. The customer chooses the amount.
     */
    case Prepaid = 'PREPAID';

    /**
     * The small transfer used to verify the account's identity. The amount is fixed and returned
     * by `get_system_info` as `verification_transfer_amount_pln`.
     *
     * @deprecated `register_payment` does not accept this mode: the call fails with UNKNOWN_ERROR.
     *             Account verification starts with
     *             {@see \Polkurier\Webservice\Api\Service\AccountVerificationServiceInterface::register()}.
     */
    case Verification = 'VERIFICATION';
}
