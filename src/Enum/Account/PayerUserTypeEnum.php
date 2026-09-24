<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Enum\Account;

/**
 * Whether the account bills as a business or as an individual.
 *
 * Mandatory on the payer address and meaningless on the other two. A business is expected to carry
 * a tax identification number.
 */
enum PayerUserTypeEnum: string
{
    case Company = 'COMPANY';
    case Person = 'PERSON';
}
