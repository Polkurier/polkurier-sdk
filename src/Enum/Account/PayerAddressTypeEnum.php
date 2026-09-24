<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Enum\Account;

/**
 * Which of the account's three addresses is being written.
 *
 * The payer address is the one that appears on invoices and is the only one whose legal form must
 * be stated. The other two can be marked as identical to it rather than filled in.
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
