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
 * Required on the payer address only, and not used on the other 2. A business has a tax
 * identification number.
 */
enum PayerUserTypeEnum: string
{
    case Company = 'COMPANY';
    case Person = 'PERSON';
}
