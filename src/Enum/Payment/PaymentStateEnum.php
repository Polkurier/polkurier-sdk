<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Enum\Payment;

/**
 * How far a payment has got.
 *
 * `register_payment` returns the state of the payment it registers, and `get_payment_info` the
 * state of an existing one. Both draw on the same set.
 *
 * @api
 */
enum PaymentStateEnum: string
{
    /**
     * Created, with no payment provider operation behind it yet.
     */
    case New = 'NEW';

    /**
     * Registered with the payment provider, waiting for the customer to pay.
     */
    case Registered = 'REGISTERED';

    /**
     * The provider has answered and the payment is being settled.
     */
    case Pending = 'PENDING';

    /**
     * Paid. `register_payment` answers with this at once when the orders were covered by the
     * prepaid balance.
     */
    case Done = 'DONE';
}
