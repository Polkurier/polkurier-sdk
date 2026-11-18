<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Enum\Account;

/**
 * Where an account verification attempt has got to.
 *
 * `register_account_verification` returns it for the attempt it starts, and
 * `get_account_verification_info` for the current one. An account that has never been verified has
 * no attempt, and the response then carries no fields at all.
 *
 * @api
 */
enum AccountVerificationStatusEnum: string
{
    /**
     * Started, with no payment made yet.
     */
    case New = 'NEW';

    /**
     * The verification payment has been ordered but has not arrived.
     */
    case PaymentPlaced = 'PAYMENT_PLACED';

    /**
     * The payment has arrived and is being checked against the account's data.
     */
    case PaymentReceived = 'PAYMENT_RECEIVED';

    /**
     * The payment arrived but its data did not match the account well enough to decide.
     */
    case Inconclusive = 'INCONCLUSIVE';

    /**
     * The account is verified.
     */
    case Verified = 'VERIFIED';

    /**
     * The attempt was abandoned or expired.
     */
    case Cancelled = 'CANCELLED';
}
