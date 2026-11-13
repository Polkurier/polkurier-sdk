<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Enum\Account;

/**
 * Which of the account's 3 addresses is being written.
 *
 * The payer address appears on invoices and is the only one that needs a legal form. The other 2
 * can be marked as the same as the payer address instead of being filled in.
 *
 * @api
 */
enum PayerAddressTypeEnum: string
{
    /**
     * The billing address, as printed on invoices.
     */
    case Payer = 'PAYER';

    /**
     * Correspondence address for posted documents.
     */
    case Recipient = 'RECIPIENT';

    /**
     * Contact address.
     */
    case Contact = 'CONTACT';
}
